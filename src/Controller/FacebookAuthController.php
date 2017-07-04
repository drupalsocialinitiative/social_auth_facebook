<?php

namespace Drupal\social_auth_facebook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_facebook\FacebookAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The Facebook Persistent Data Handler.
   *
   * @var \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler
   */
  private $persistentDataHandler;

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
   * @param \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler $persistent_data_handler
   *   FacebookAuthPersistentDataHandler object.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $user_manager, FacebookAuthManager $facebook_manager, RequestStack $request, FacebookAuthPersistentDataHandler $persistent_data_handler) {
    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->facebookManager = $facebook_manager;
    $this->request = $request;
    $this->persistentDataHandler = $persistent_data_handler;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_facebook');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify([
      $this->persistentDataHandler->getSessionPrefix() . 'access_token',
    ]);
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
      $container->get('social_auth_facebook.persistent_data_handler')
    );
  }

  /**
   * Response for path 'user/simple-fb-connect'.
   *
   * Redirects the user to FB for authentication.
   */
  public function redirectToFb() {
    /* @var \Facebook\Facebook|false $facebook */
    $facebook = $this->networkManager->createInstance('social_auth_facebook')->getSdk();

    // If facebook client could not be obtained.
    if (!$facebook) {
      drupal_set_message($this->t('Social Auth Facebook not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Facebook service was returned, inject it to $fbManager.
    $this->facebookManager->setClient($facebook);

    // Generates the URL where the user will be redirected for FB login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $fb_login_url = $this->facebookManager->getFbLoginUrl();
    if ($this->persistentDataHandler->get('reprompt')) {
      $fb_login_url = $this->facebookManager->getFbReRequestUrl();
    }

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

    /* @var \Facebook\Facebook|false $facebook */
    $facebook = $this->networkManager->createInstance('social_auth_facebook')->getSdk();

    // If facebook client could not be obtained.
    if (!$facebook) {
      drupal_set_message($this->t('Social Auth Facebook not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $this->facebookManager->setClient($facebook)->authenticate();

    // Checks that user authorized our app to access user's email address.
    if (!$this->facebookManager->checkPermission('email')) {
      drupal_set_message($this->t('Facebook login failed. This site requires permission to get your email address from Facebook. Please try again.'), 'error');
      $this->persistentDataHandler->set('reprompt', TRUE);
      return $this->redirect('user.login');
    }

    // Gets user's FB profile from Facebook API.
    if (!$fb_profile = $this->facebookManager->getUserInfo()) {
      drupal_set_message($this->t('Facebook login failed, could not load Facebook profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Gets user's email from the FB profile.
    if (!$email = $this->facebookManager->getEmail($fb_profile)) {
      drupal_set_message($this->t('Facebook login failed. This site requires permission to get your email address.'), 'error');
      return $this->redirect('user.login');
    }

    // Saves access token to session so that event subscribers can call FB API.
    $this->persistentDataHandler->set('access_token', $this->facebookManager->getAccessToken());

    // If user information could be retrieved.
    return $this->userManager->authenticateUser($email, $fb_profile->getField('name'), $fb_profile->getField('id'), $this->facebookManager->getFbProfilePicUrl());
  }

}
