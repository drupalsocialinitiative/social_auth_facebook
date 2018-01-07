<?php

namespace Drupal\social_auth_facebook;

use Drupal\social_auth\AuthManager\OAuth2Manager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Config\ConfigFactory;

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
   * @var \League\OAuth2\Client\Provider\Facebook
   */
  protected $client;
  /**
   * The Facebook access token.
   *
   * @var \League\OAuth2\Client\Token\AccessToken
   */
  protected $token;

  /**
   * The Facebook user object.
   *
   * @var \League\OAuth2\Client\Provider\FacebookUser
   */
  protected $user;

  /**
   * The data point to be collected.
   *
   * @var string
   */
  protected $scopes;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Social Auth Facebook Settings.
   *
   * @var array
   */
  protected $settings;

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
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, EventDispatcherInterface $event_dispatcher, EntityFieldManagerInterface $entity_field_manager, UrlGeneratorInterface $url_generator, ConfigFactory $configFactory) {
    $this->loggerFactory      = $logger_factory;
    $this->eventDispatcher    = $event_dispatcher;
    $this->entityFieldManager = $entity_field_manager;
    $this->urlGenerator       = $url_generator;
    $this->config             = $configFactory->getEditable('social_auth_facebook.settings');
  }

  /**
   * Authenticates the user.
   */
  public function authenticate() {
    $this->token = $this->client->getLongLivedAccessToken($this->client->getAccessToken('authorization_code',
      ['code' => $_GET['code']]));
  }

  /**
   * Gets the data by using the access token returned.
   *
   * @return \League\OAuth2\Client\Provider\FacebookUser
   *   User Info returned by the facebook.
   */
  public function getUserInfo() {
    $this->user = $this->client->getResourceOwner($this->token);
    return $this->user;
  }

  /**
   * Returns the Facebook login URL where user will be redirected.
   *
   * @return string
   *   Absolute Facebook login URL where user will be redirected.
   */
  public function getFbLoginUrl() {
    $scopes = ['email', 'public_profile'];

    $facebook_scopes = explode(PHP_EOL, $this->getScopes());
    foreach ($facebook_scopes as $scope) {
      array_push($scopes, $scope);
    }

    $login_url = $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
    // Generate and return the URL where we should redirect the user.
    return $login_url;
  }

  /**
   * Returns the Facebook login URL where user will be redirected.
   *
   * @return string
   *   Absolute Facebook login URL where user will be redirected
   */
  public function getState() {
    $state = $this->client->getState();

    // Generate and return the URL where we should redirect the user.
    return $state;
  }

  /**
   * Gets the data by using the access token returned.
   *
   * @return string
   *   Data returned by Making API Call.
   */
  public function getExtraDetails($url, $param) {
    if ($url) {
      $baseUrl = 'https://graph.facebook.com/v2.10';
      $params = http_build_query([
        'fields' => $param,
        'limit' => '5',
        'access_token' => $this->token->getToken(),
        'appsecret_proof' => hash_hmac('sha256', $this->token, $this->config->get('app_secret')),
      ]);

      $response = file_get_contents($baseUrl . $url . '?' . $params);
      return json_decode($response, TRUE);
    }
  }

  /**
   * Returns token generated after authorization.
   *
   * @return string
   *   Used for making API calls.
   */
  public function getAccessToken() {
    return $this->token;
  }

  /**
   * Gets the data Point defined the settings form page.
   *
   * @return string
   *   Data points separtated by comma.
   */
  public function getScopes() {
    if (!$this->scopes) {
      $this->scopes = $this->config->get('scopes');
    }
    return $this->scopes;
  }

  /**
   * Gets the API calls to collect data.
   *
   * @return string
   *   API calls separtated by comma.
   */
  public function getApiCalls() {
    return $this->config->get('api_calls');
  }

}
