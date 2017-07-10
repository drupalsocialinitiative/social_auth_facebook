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
   * The Facebook access token.
   *
   * @var \Facebook\Facebook
   */
  protected $token;

  /**
   * The Facebook access token.
   *
   * @var \Facebook\Facebook
   */
  protected $user;
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

   $this->token = $this->client->getAccessToken('authorization_code', [
              'code' => $_GET['code']
   ]);

   return $this->token;
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
  public function getUserInfo() {

    $this->user = $this->client->getResourceOwner($this->token);
    return $this->user;
  }

  /**
   * Returns the Facebook login URL where user will be redirected.
   *
   * @return string
   *   Absolute Facebook login URL where user will be redirected
   */
  public function getFbLoginUrl() {
    $login_helper = $this->client->getAuthorizationUrl([
      'scope' => ['email', 'public_profile'],
    ]);

    // Generate and return the URL where we should redirect the user.
    return $login_helper;
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
