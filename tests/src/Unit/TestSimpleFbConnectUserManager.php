<?php

/**
 * @file
 * Contains Drupal\Tests\simple_fb_connect\Unit\SimpleFbConnectTestUserManager.
 */

namespace Drupal\Tests\simple_fb_connect\Unit;

use Drupal\simple_fb_connect\SimpleFbConnectUserManager;
use Drupal\user\UserInterface;

/**
 * This subclass overrides methods that call procedural Drupal functions.
 *
 * SimpleFbConnectUserManager needs to call procedural Drupal functions,
 * for example user_login_finalize(). These procedural methods are wrapped
 * to methods like userLoginFinalize() in SimpleFbConnectUserManager so that
 * this subclass can override the methods with dummy values for unit testing
 * purposes.
 *
 * This subclass is also used to unit test the protected methods where needed.
 */
class TestSimpleFbConnectUserManager extends SimpleFbConnectUserManager {

  /**
   * Allows us to modify the configuration for some test cases.
   */
  public function setConfigFactory($new_factory) {
    $this->configFactory = $new_factory;
  }

  /**
   * Overrides userLoginFinalize.
   */
  protected function userLoginFinalize(UserInterface $account) {
    return NULL;
  }

  /**
   * Overrides userPassword.
   */
  protected function userPassword($length) {
    return "12345";
  }

  /**
   * Public method to help unit testing generateUniqueUsername().
   */
  public function subGenerateUniqueUsername($fb_name) {
    return $this->generateUniqueUsername($fb_name);
  }

  /**
   * Public method to help unit testing downloadProfilePic().
   */
  public function subDownloadProfilePic($picture_url, $fbid) {
    return $this->downloadProfilePic($picture_url, $fbid);
  }

  /**
   * Overrides filePrepareDirectory.
   */
  public function filePrepareDirectory(&$directory, $options) {
    // Return FALSE for simulating a directory which is not writeable.
    if ($directory == 'not/writeable/directory') {
      return FALSE;
    }

    // Return TRUE for all other directories.
    return TRUE;
  }

  /**
   * Overrides systemRetrieveFile.
   */
  public function systemRetrieveFile($url, $destination, $managed, $replace) {
    // Simulate succesfull download by returning a file object.
    $file = $this->entityTypeManager
      ->getStorage('file')
      ->create();
    return $file;
  }

}
