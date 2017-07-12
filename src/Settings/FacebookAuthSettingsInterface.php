<?php

namespace Drupal\social_auth_facebook\Settings;

/**
 * Defines the settings interface.
 */
interface FacebookAuthSettingsInterface {

  /**
   * Gets the application ID.
   *
   * @return mixed
   *   The application ID.
   */
  public function getAppId();

  /**
   * Gets the application secret.
   *
   * @return string
   *   The application secret.
   */
  public function getAppSecret();

  /**
   * Gets the graph version.
   *
   * @return string
   *   The version.
   */
  public function getGraphVersion();

}
