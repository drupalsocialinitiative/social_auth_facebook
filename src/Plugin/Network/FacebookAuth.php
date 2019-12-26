<?php

namespace Drupal\social_auth_facebook\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_facebook\Settings\FacebookAuthSettings;
use League\OAuth2\Client\Provider\Facebook;

/**
 * Defines a Network Plugin for Social Auth Facebook.
 *
 * @package Drupal\social_auth_facebook\Plugin\Network
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
   * Sets the underlying SDK library.
   *
   * @return \League\OAuth2\Client\Provider\Facebook|false
   *   The initialized 3rd party library instance.
   *   False if library could not be initialized.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\League\OAuth2\Client\Provider\Facebook';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP League OAuth2 library for Facebook not found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId'          => $settings->getAppId(),
        'clientSecret'      => $settings->getAppSecret(),
        'redirectUri'       => Url::fromRoute('social_auth_facebook.callback')->setAbsolute()->toString(),
        'graphApiVersion'   => 'v' . $settings->getGraphVersion(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Facebook($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings
   *   The Social Auth Facebook settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(FacebookAuthSettings $settings) {
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
