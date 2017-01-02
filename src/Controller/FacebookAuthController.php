<?php

namespace Drupal\social_auth_facebook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\social_auth_facebook\FacebookAuthFbManager;
use Drupal\social_auth_facebook\FacebookAuthUserManager;
use Drupal\social_auth_facebook\FacebookAuthPostLoginManager;
use Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler;
use Drupal\social_auth_facebook\FacebookAuthFbFactory;

/**
 * Returns responses for Simple FB Connect module routes.
 */
class FacebookAuthController extends ControllerBase {

  protected $fbManager;
  protected $userManager;
  protected $postLoginManager;
  protected $persistentDataHandler;
  protected $fbFactory;

  /**
   * Constructor.
   *
   * The constructor parameters are passed from the create() method.
   *
   * @param \Drupal\social_auth_facebook\FacebookAuthFbManager $fb_manager
   *   FacebookAuthFbManager object.
   * @param \Drupal\social_auth_facebook\FacebookAuthUserManager $user_manager
   *   FacebookAuthUserManager object.
   * @param \Drupal\social_auth_facebook\FacebookAuthPostLoginManager $post_login_manager
   *   FacebookAuthPostLoginManager object.
   * @param \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler $persistent_data_handler
   *   FacebookAuthPersistentDataHandler object.
   * @param \Drupal\social_auth_facebook\FacebookAuthFbFactory $fb_factory
   *   FacebookAuthFbFactory object.
   */
  public function __construct(FacebookAuthFbManager $fb_manager, FacebookAuthUserManager $user_manager, FacebookAuthPostLoginManager $post_login_manager, FacebookAuthPersistentDataHandler $persistent_data_handler, FacebookAuthFbFactory $fb_factory) {
    $this->fbManager = $fb_manager;
    $this->userManager = $user_manager;
    $this->postLoginManager = $post_login_manager;
    $this->persistentDataHandler = $persistent_data_handler;
    $this->fbFactory = $fb_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('social_auth_facebook.fb_manager'),
      $container->get('social_auth_facebook.user_manager'),
      $container->get('social_auth_facebook.post_login_manager'),
      $container->get('social_auth_facebook.persistent_data_handler'),
      $container->get('social_auth_facebook.fb_factory')
    );
  }

  /**
   * Response for path 'user/simple-fb-connect'.
   *
   * Redirects the user to FB for authentication.
   */
  public function redirectToFb() {
    // Try to get an instance of Facebook service.
    if (!$facebook = $this->fbFactory->getFbService()) {
      drupal_set_message(t('Simple FB Connect not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Facebook service was returned, inject it to $fbManager.
    $this->fbManager->setFacebookService($facebook);

    // Save post login path to session if it was set as a query parameter.
    if ($post_login_path = $this->postLoginManager->getPostLoginPathFromRequest()) {
      $this->postLoginManager->savePostLoginPath($post_login_path);
    }

    // Redirect the user to FB for authentication.
    $fb_login_url = $this->fbManager->getFbLoginUrl();

    return new TrustedRedirectResponse($fb_login_url);
  }

  /**
   * Response for path 'user/simple-fb-connect/return'.
   *
   * Facebook returns the user here after user has authenticated in FB.
   */
  public function returnFromFb() {
    // Try to get an instance of Facebook service.
    if (!$facebook = $this->fbFactory->getFbService()) {
      drupal_set_message(t('Simple FB Connect not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Facebook service was returned, inject it to $fbManager.
    $this->fbManager->setFacebookService($facebook);

    // Reads user's access token from Facebook.
    if (!$access_token = $this->fbManager->getAccessTokenFromFb()) {
      drupal_set_message(t("Facebook login failed."), 'error');
      return $this->redirect('user.login');
    }

    // Get user's FB profile from Facebook API.
    if (!$fb_profile = $this->fbManager->getFbProfile()) {
      drupal_set_message(t("Facebook login failed, could not load Facebook profile. Contact site administrator."), 'error');
      return $this->redirect('user.login');
    }

    // Get user's email from the FB profile.
    if (!$email = $this->fbManager->getEmail($fb_profile)) {
      drupal_set_message(t('Facebook login failed. This site requires permission to get your email address.'), 'error');
      return $this->redirect('user.login');
    }

    // If we have an existing user with the same email address, try to log in.
    if ($drupal_user = $this->userManager->loadUserByProperty('mail', $email)) {
      if ($this->userManager->loginUser($drupal_user)) {
        // Saves user's access token to session for other modules.
        $this->persistentDataHandler->set('access_token', $access_token);
        // Redirects the user to post login path.
        return new RedirectResponse($this->postLoginManager->getPostLoginPath());
      }
      else {
        return $this->redirect('user.login');
      }
    }

    // If there was no existing user, try to create a new user.
    if ($drupal_user = $this->userManager->createUser($fb_profile->getField('name'), $email)) {

      // Download profile picture for the newly created user.
      if ($picture_url = $this->fbManager->getFbProfilePicUrl()) {
        $this->userManager->setProfilePic($drupal_user, $picture_url, $fb_profile->getField('id'));
      }

      // Log the newly created user in.
      if ($this->userManager->loginUser($drupal_user)) {

        // Saves user's access token to session for other modules.
        $this->persistentDataHandler->set('access_token', $access_token);
        // Check if new users should be redirected to Drupal user form.
        if ($this->postLoginManager->getRedirectNewUsersToUserFormSetting()) {
          drupal_set_message(t("Please check your account details. Since you logged in with Facebook, you don't need to update your password."));
          return new RedirectResponse($this->postLoginManager->getPathToUserForm($drupal_user));
        }

        // Use normal post login path if user wasn't redirected to user form.
        return new RedirectResponse($this->postLoginManager->getPostLoginPath());
      }

      else {
        // New user was created but the account is pending approval.
        drupal_set_message(t('You will receive an email when site administrator activates your account.'), 'warning');
        return $this->redirect('user.login');
      }
    }

    else {
      // User could not be created.
      return $this->redirect('user.login');
    }

    // This should never be reached, user should have been redirected already.
    throw new AccessDeniedHttpException();
  }

}
