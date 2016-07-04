<?php

namespace Drupal\social_auth_facebook;

use Drupal\social_api\Plugin\NetworkManager;

/**
 * Creates an instance of Facebook\Facebook service with app ID and secret from
 * SimpleFbConnect module setting
 */
class FacebookAuthFbFactory {
  /**
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * Constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *  Used for working with a Facebook Auth network plugin instance
   */
  public function __construct(NetworkManager $network_manager) {
    $this->networkManager = $network_manager;
  }

  /**
   * Returns an instance of Facebook\Facebook service.
   *
   * Reads Facebook App ID and App Secret from SimpleFbConnect module settings
   * and creates an instance of Facebook service with these as parameters.
   *
   * @return \Facebook\Facebook | bool
   */
  public function getFbService() {
    // Returns a Facebook\Facebook object or false if module was not configured
    return $this->networkManager->createInstance('social_auth_facebook')->getSdk();
  }
}
