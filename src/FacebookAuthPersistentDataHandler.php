<?php

namespace Drupal\social_auth_facebook;

use Facebook\PersistentData\PersistentDataInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Variables are written to and read from session via this class.
 *
 * By default, Facebook SDK uses native PHP sessions for storing data. We
 * implement Facebook\PersistentData\PersistentDataInterface using Symfony
 * Sessions so that Facebook SDK will use that instead of native PHP sessions.
 * Also SimpleFbConnect reads data from and writes data to session via this
 * class.
 *
 * @see https://developers.facebook.com/docs/php/PersistentDataInterface/5.0.0
 */
class FacebookAuthPersistentDataHandler implements PersistentDataInterface {
  protected $session;
  protected $sessionPrefix = 'social_auth_facebook_';

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used for reading data from and writing data to session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return $this->session->get($this->getSessionPrefix() . $key);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->session->set($this->getSessionPrefix() . $key, $value);
  }

  /**
   * Gets the session prefix for the data handler.
   *
   * @return string
   *   The session prefix.
   */
  public function getSessionPrefix() {
    return $this->sessionPrefix;
  }

}
