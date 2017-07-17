<?php

namespace Drupal\social_auth_facebook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_facebook\FacebookAuthManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\social_auth\SocialAuthDataHandler;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple FB Connect module routes.
 */
class FacebookAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The Facebook authentication manager.
   *
   * @var \Drupal\social_auth_facebook\FacebookAuthManager
   */
  private $facebookManager;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  private $dataHandler;

  /**
   * The data point to be collected.
   *
   * @var string
   */
  private $dataPoints;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * FacebookAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_facebook network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_facebook\FacebookAuthManager $facebook_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $user_manager, FacebookAuthManager $facebook_manager, RequestStack $request, SocialAuthDataHandler $social_auth_data_handler, LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->facebookManager = $facebook_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    // Sets session prefix for data handler.
    $this->dataHandler->getSessionPrefix('social_auth_google');

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_facebook');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token']);

    $this->setting = $this->config('social_auth_facebook.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_facebook.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.social_auth_data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Response for path 'user/simple-fb-connect'.
   *
   * Redirects the user to FB for authentication.
   */
  public function redirectToFb() {
    /* @var \League\OAuth2\Client\Provider\Facebook false $facebook */
    $facebook = $this->networkManager->createInstance('social_auth_facebook')->getSdk();

    // If facebook client could not be obtained.
    if (!$facebook) {
      drupal_set_message($this->t('Social Auth Facebook not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Facebook service was returned, inject it to $fbManager.
    $this->facebookManager->setClient($facebook);

    $data_points = explode(',', $this->getDataPoints());

    // Generates the URL where the user will be redirected for FB login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $fb_login_url = $this->facebookManager->getFbLoginUrl($data_points);

    $state = $this->facebookManager->getState();

    $this->dataHandler->set('oAuth2State', $state);

    return new TrustedRedirectResponse($fb_login_url);
  }

  /**
   * Response for path 'user/login/facebook/callback'.
   *
   * Facebook returns the user here after user has authenticated in FB.
   */
  public function returnFromFb() {
    // Checks if user cancel login via Facebook.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \League\OAuth2\Client\Provider\Facebook false $facebook */
    $facebook = $this->networkManager->createInstance('social_auth_facebook')->getSdk();

    // If facebook client could not be obtained.
    if (!$facebook) {
      drupal_set_message($this->t('Social Auth Facebook not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oAuth2State');

    if (empty($_GET['state']) || ($_GET['state'] !== $state)) {
      $this->userManager->setSessionKeysToNullify(['oauth2state']);
      drupal_set_message($this->t('Facebook login failed. Unvalid oAuth2 State.'), 'error');
      return $this->redirect('user.login');
    }

    $this->facebookManager->setClient($facebook)->authenticate();

    // Gets user's FB profile from Facebook API.
    if (!$fb_profile = $this->facebookManager->getUserInfo()) {
      drupal_set_message($this->t('Facebook login failed, could not load Facebook profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Gets user's email from the FB profile.
    if (!$email = $this->facebookManager->getUserInfo()->getEmail()) {
      drupal_set_message($this->t('Facebook login failed. This site requires permission to get your email address.'), 'error');
      return $this->redirect('user.login');
    }

    $data = [];

    $data_points = explode(',', $this->getDataPoints());

    foreach ($data_points as $data_point) {
      switch ($data_point) {
        case 'email': $data['email'] = $fb_profile->getEmail();
          break;

        case 'name': $data['name'] = $fb_profile->getName();
          break;

        default: $this->loggerFactory->get($this->userManager->getPluginId())->error(
          'Failed to fetch Data Point. Invalid Data Point: @$data_point', ['@$data_point' => $data_point]);
      }
    }

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->facebookManager->getAccessToken());

    // If user information could be retrieved.
    return $this->userManager->authenticateUser($fb_profile->getName(), $email, 'social_auth_facebook', $fb_profile->getId(), $fb_profile->getPictureUrl(), json_encode($data));
  }

  /**
   * Gets the data Point defined the settings form page.
   *
   * @return string
   *   Data points separtated by comma.
   */
  public function getDataPoints() {
    if (!$this->dataPoints) {
      $this->dataPoints = $this->config('social_auth_facebook.settings')->get('data_points');
    }
    return $this->dataPoints;
  }

}
