<?php

namespace Drupal\social_auth_facebook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Facebook\Facebook;

/**
 * Creates an instance of Facebook\Facebook service with app ID and secret from
 * SimpleFbConnect module setting
 */
class FacebookAuthFbFactory {
  protected $configFactory;
  protected $loggerFactory;

  /**
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * Constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *  Used for working with a Facebook Auth network plugin instance
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used for accessing Drupal configuration.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->networkManager = $network_manager;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Returns an instance of Facebook\Facebook service.
   *
   * Reads Facebook App ID and App Secret from SimpleFbConnect module settings
   * and creates an instance of Facebook service with these as parameters.
   *
   * @return \Facebook\Facebook
   *   Facebook service instance.
   */
  public function getFbService() {
    // Check that App ID and secret have been defined in module settings.
    if ($this->validateConfig()) {
      // Returns a Facebook\Facebook object
      return $this->networkManager->createInstance('social_auth_facebook')->getSdk();
    }

    // Return FALSE if app ID or secret is missing.
    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @return bool
   *   True if module is configured
   *   False otherwise
   */
  protected function validateConfig() {
    $app_id = $this->getAppId();
    $app_secret = $this->getAppSecret();

    if (!$app_id || !$app_secret) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Define App ID and App Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns app_id from module settings.
   *
   * @return string
   *   Application ID defined in module settings.
   */
  protected function getAppId() {
    $app_id = $this->configFactory
      ->get('social_auth_facebook.settings')
      ->get('app_id');
    return $app_id;
  }

  /**
   * Returns app_secret from module settings.
   *
   * @return string
   *   Application secret defined in module settings.
   */
  protected function getAppSecret() {
    $app_secret = $this->configFactory
      ->get('social_auth_facebook.settings')
      ->get('app_secret');
    return $app_secret;
  }

}
