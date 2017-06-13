<?php

namespace Drupal\social_auth_facebook;

use Drupal\social_auth\AuthManager\OAuth2Manager;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\GraphNodes\GraphNode;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Contains all Simple FB Connect logic that is related to Facebook interaction.
 */
class FacebookAuthManager extends OAuth2Manager {

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The Facebook persistent data handler.
   *
   * @var \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler
   */
  protected $persistentDataHandler;

  /**
   * The Facebook client object.
   *
   * @var \Facebook\Facebook
   */
  protected $client;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Used for dispatching events to other modules.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Used for accessing Drupal user picture preferences.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   Used for generating absoulute URLs.
   * @param \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler $persistent_data_handler
   *   Used for reading data from and writing data to session.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, EventDispatcherInterface $event_dispatcher, EntityFieldManagerInterface $entity_field_manager, UrlGeneratorInterface $url_generator, FacebookAuthPersistentDataHandler $persistent_data_handler) {
    $this->loggerFactory         = $logger_factory;
    $this->eventDispatcher       = $event_dispatcher;
    $this->entityFieldManager    = $entity_field_manager;
    $this->urlGenerator          = $url_generator;
    $this->persistentDataHandler = $persistent_data_handler;
  }

  /**
   * Authenticates the users by using the access token.
   *
   * @return $this
   *   The current object.
   */
  public function authenticate() {
    $this->client->setDefaultAccessToken($this->getAccessToken());
    return $this;
  }

  /**
   * Gets the user's access token from Facebook.
   *
   * This method can only be called from route
   * social_auth_facebook.return_from_fb since RedirectLoginHelper will use the
   * URL parameters set by Facebook.
   *
   * @return \Facebook\Authentication\AccessToken|null
   *   User's Facebook access token, if it could be read from Facebook.
   *   Null, otherwise.
   */
  public function getAccessToken() {
    if (!$this->accessToken) {
      $helper = $this->client->getRedirectLoginHelper();

      // URL where Facebook returned the user.
      $return_url = $this->urlGenerator->generateFromRoute(
        'social_auth_facebook.return_from_fb', [], ['absolute' => TRUE]);

      try {
        $access_token = $helper->getAccessToken($return_url);
      }

      catch (FacebookResponseException $ex) {
        // Graph API returned an error.
        $this->loggerFactory
          ->get('social_auth_facebook')
          ->error('Could not get Facebook access token. FacebookResponseException: @message', ['@message' => json_encode($ex->getMessage())]);
        return NULL;
      }

      catch (FacebookSDKException $ex) {
        // Validation failed or other local issues.
        $this->loggerFactory
          ->get('social_auth_facebook')
          ->error('Could not get Facebook access token. Exception: @message', ['@message' => ($ex->getMessage())]);
        return NULL;
      }

      // If login was OK on Facebook, we now have user's access token.
      if (isset($access_token)) {
        $this->accessToken = $access_token;
      }
      else {
        // If we're still here, user denied the login request on Facebook.
        $this->loggerFactory
          ->get('social_auth_facebook')
          ->error('Could not get Facebook access token. User cancelled the dialog in Facebook or return URL was not valid.');
        return NULL;
      }
    }

    return $this->accessToken;
  }

  /**
   * Makes an API call to get user's Facebook profile.
   *
   * @param string $fields
   *   The fields to retrieve.
   *
   * @return \Facebook\GraphNodes\GraphNode|false
   *   GraphNode representing the user
   *   False if exception was thrown
   */
  public function getUserInfo($fields = 'id,name,email') {
    try {
      return $this->getClient()
        ->get('/me?fields=' . $fields)
        ->getGraphNode();
    }
    catch (FacebookResponseException $ex) {
      $this->loggerFactory
        ->get('simple_fb_connect')
        ->error('Could not load Facebook user profile: FacebookResponseException: @message', ['@message' => json_encode($ex->getMessage())]);
    }
    catch (FacebookSDKException $ex) {
      $this->loggerFactory
        ->get('simple_fb_connect')
        ->error('Could not load Facebook user profile: FacebookSDKException: @message', ['@message' => ($ex->getMessage())]);
    }

    // Something went wrong.
    return FALSE;
  }

  /**
   * Returns the Facebook login URL where user will be redirected.
   *
   * @return string
   *   Absolute Facebook login URL where user will be redirected
   */
  public function getFbLoginUrl() {
    $login_helper = $this->client->getRedirectLoginHelper();

    // Define the URL where Facebook should return the user.
    $return_url = $this->urlGenerator->generateFromRoute(
    'social_auth_facebook.return_from_fb', [], ['absolute' => TRUE]);

    // Define the initial array of Facebook permissions.
    $scope = ['public_profile', 'email'];

    // Dispatch an event so that other modules can modify the permission scope.
    // Set the scope twice on the event: as the main subject but also in the
    // list of arguments.
    $e = new GenericEvent($scope, ['scope' => $scope]);
    $event = $this->eventDispatcher->dispatch('social_auth_facebook.scope', $e);
    $final_scope = $event->getArgument('scope');

    // Generate and return the URL where we should redirect the user.
    return $login_helper->getLoginUrl($return_url, $final_scope);
  }

  /**
   * Returns the Facebook login URL for re-requesting email permission.
   *
   * @return string
   *   Absolute Facebook login URL where user will be redirected
   */
  public function getFbReRequestUrl() {
    $login_helper = $this->client->getRedirectLoginHelper();

    // Define the URL where Facebook should return the user.
    $return_url = $this->urlGenerator->generateFromRoute(
    'social_auth_facebook.return_from_fb', [], ['absolute' => TRUE]);

    // Define the array of Facebook permissions to re-request.
    $scope = ['public_profile', 'email'];

    // Generate and return the URL where we should redirect the user.
    return $login_helper->getReRequestUrl($return_url, $scope);
  }

  /**
   * Makes an API call to check if user has granted given permission.
   *
   * @param string $permission_to_check
   *   Permission to check.
   *
   * @return bool
   *   True if user has granted given permission.
   *   False otherwise.
   */
  public function checkPermission($permission_to_check) {
    try {
      $permissions = $this->client
        ->get('/me/permissions')
        ->getGraphEdge()
        ->asArray();
      foreach ($permissions as $permission) {
        if ($permission['permission'] == $permission_to_check && $permission['status'] == 'granted') {
          return TRUE;
        }
      }
    }
    catch (FacebookResponseException $ex) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not check Facebook permissions: FacebookResponseException: @message', ['@message' => json_encode($ex->getMessage())]);
    }
    catch (FacebookSDKException $ex) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not check Facebook permissions: FacebookSDKException: @message', ['@message' => ($ex->getMessage())]);
    }

    // We don't have permission or we got an exception during the API call.
    return FALSE;
  }

  /**
   * Makes an API call to get the URL of user's Facebook profile picture.
   *
   * @return string|false
   *   Absolute URL of the profile picture
   *   False if user did not have a profile picture on FB or an error occured.
   */
  public function getFbProfilePicUrl() {
    // Determine preferred resolution for the profile picture.
    $resolution = $this->getPreferredResolution();

    // Generate FB API query.
    $query = '/me/picture?redirect=false';
    if (is_array($resolution)) {
      $query .= '&width=' . $resolution['width'] . '&height=' . $resolution['height'];
    }

    // Call Graph API to request profile picture.
    try {
      $graph_node = $this->client->get($query)->getGraphNode();

      // We don't download the FB default silhouettes, only real pictures.
      $is_silhouette = (bool) $graph_node->getField('is_silhouette');
      if ($is_silhouette) {
        return FALSE;
      }

      // We have a real picture, return URL for it.
      return $graph_node->getField('url');
    }
    catch (FacebookResponseException $ex) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not load Facebook profile picture URL. FacebookResponseException: @message', ['@message' => json_encode($ex->getMessage())]);
    }
    catch (FacebookSDKException $ex) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not load Facebook profile picture URL. FacebookSDKException: @message', ['@message' => ($ex->getMessage())]);
    }

    // Something went wrong and the picture could not be loaded.
    return FALSE;
  }

  /**
   * Returns user's email address from Facebook profile.
   *
   * @param \Facebook\GraphNodes\GraphNode $fb_profile
   *   GraphNode object representing user's Facebook profile.
   *
   * @return string|false
   *   User's email address if found
   *   False otherwise
   */
  public function getEmail(GraphNode $fb_profile) {
    if ($email = $fb_profile->getField('email')) {
      return $email;
    }

    // Email address was not found. Log error and return FALSE.
    $this->loggerFactory
      ->get('social_auth_facebook')
      ->error('No email address in Facebook user profile');
    return FALSE;
  }

  /**
   * Determines preferred profile pic resolution from account settings.
   *
   * Return order: max resolution, min resolution, FALSE.
   *
   * @return array|false
   *   Array of resolution, if defined in Drupal account settings
   *   False otherwise
   */
  protected function getPreferredResolution() {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    if (!isset($field_definitions['user_picture'])) {
      return FALSE;
    }

    $max_resolution = $field_definitions['user_picture']->getSetting('max_resolution');
    $min_resolution = $field_definitions['user_picture']->getSetting('min_resolution');

    // Return order: max resolution, min resolution, FALSE.
    if ($max_resolution) {
      $resolution = $max_resolution;
    }
    elseif ($min_resolution) {
      $resolution = $min_resolution;
    }
    else {
      return FALSE;
    }
    $dimensions = explode('x', $resolution);
    return ['width' => $dimensions[0], 'height' => $dimensions[1]];
  }

}
