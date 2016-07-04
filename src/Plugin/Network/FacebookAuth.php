<?php

namespace Drupal\social_auth_facebook\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler;
use Drupal\social_api\Plugin\NetworkBase;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_facebook\Settings\FacebookAuthSettings;
use Facebook\Facebook;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Network Plugin for Social Auth Facebook
 *
 * @package Drupal\simple_fb_connect\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_facebook",
 *   social_network = "Facebook",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_facebook\Settings\FacebookAuthSettings",
 *       "config_id": "social_auth_facebook.settings"
 *     }
 *   }
 * )
 */
class FacebookAuth extends NetworkBase implements FacebookAuthInterface {

  /**
   * @var FacebookAuthPersistentDataHandler.
   */
  protected $persistentDataHandler;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('social_auth_facebook.persistent_data_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * FacebookAuth constructor.
   *
   * @param \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler $persistent_data_handler
   * @param array $configuration
   * @param mixed $plugin_id
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   */
  public function __construct(FacebookAuthPersistentDataHandler $persistent_data_handler, array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactory $logger_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->persistentDataHandler = $persistent_data_handler;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \Facebook\Facebook
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk()
  {
    $class_name = '\Facebook\Facebook';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Facebook could not be found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings */
    $settings = $this->settings;

    if($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $facebook_settings = [
        'app_id' => $settings->getAppId(),
        'app_secret' => $settings->getAppSecret(),
        'default_graph_version' => 'v' . $settings->getGraphVersion(),
        'persistent_data_handler'=> $this->persistentDataHandler
      ];

      return new Facebook($facebook_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param FacebookAuthSettings $settings
   *
   * @return bool True if module is configured
   * True if module is configured
   * False otherwise
   */
  protected function validateConfig($settings) {
    $app_id = $settings->getAppId();
    $app_secret = $settings->getAppSecret();
    $graph_version = $settings->getGraphVersion();

    if (!$app_id || !$app_secret || !$graph_version) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Define App ID and App Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }
}
