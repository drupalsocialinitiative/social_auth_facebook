<?php

/**
 * @file
 * Contains \Drupal\social_auth_facebook\SimpleFbConnectPostLoginManager.
 */

namespace Drupal\social_auth_facebook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\user\Entity\User;

/**
 * Contains all logic that is related to post login redirects.
 *
 * Class FacebookAuthPostLoginManager
 * @package Drupal\social_auth_facebook
 */
class FacebookAuthPostLoginManager {
  protected $configFactory;
  protected $requestContext;
  protected $pathValidator;
  protected $persistentDataHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used for accessing Drupal configuration.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Used for reading the query string.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Used for validating user provided paths.
   * @param \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler $persistent_data_handler
   *   Used for reading data from and writing data to session.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestContext $request_context, PathValidatorInterface $path_validator, FacebookAuthPersistentDataHandler $persistent_data_handler) {
    $this->configFactory         = $config_factory;
    $this->requestContext        = $request_context;
    $this->pathValidator         = $path_validator;
    $this->persistentDataHandler = $persistent_data_handler;
  }

  /**
   * Returns the value of postLoginPath query parameter if set.
   *
   * @returns string|bool
   *   Raw query parameter value if set.
   *   False otherwise.
   */
  public function getPostLoginPathFromRequest() {
    if ($query_string = $this->requestContext->getQueryString()) {
      parse_str($query_string, $query_params);
      if (isset($query_params['postLoginPath'])) {
        return $query_params['postLoginPath'];
      }
    }
    return FALSE;
  }

  /**
   * Saves given path to session.
   *
   * @param string $post_login_path
   *   Path to save to session.
   */
  public function savePostLoginPath($post_login_path) {
    if (!is_null($post_login_path)) {
      $this->persistentDataHandler->set('post_login_path', $post_login_path);
    }
  }

  /**
   * Returns the path the user should be redirected after a successful login.
   *
   * Return order:
   * 1. Path from query string, if set, valid and not external.
   * 2. Path from module settings, if valid and not external.
   * 3. User page.
   *
   * @return string
   *   URL where the user should be redirected after FB login.
   */
  public function getPostLoginPath() {
    // 1. Read the post login path from session.
    $post_login_path = $this->persistentDataHandler->get('post_login_path');
    if ($post_login_path) {
      if ($valid_path = $this->validateInternalPath($post_login_path)) {
        return $valid_path;
      }
    }

    // 2. Use post login path from module settings.
    $post_login_path = $this->getPostLoginPathSetting();
    if ($valid_path = $this->validateInternalPath($post_login_path)) {
      return $valid_path;
    }

    // 3. Use user page.
    $post_login_path = 'user';
    return $this->validateInternalPath($post_login_path);
  }

  /**
   * Checks if new users should be redirected to Drupal user form.
   *
   * @return bool
   *   True if new users should be redirected to user form.
   *   False otherwise.
   */
  public function getRedirectNewUsersToUserFormSetting() {
    return $this->configFactory
      ->get('social_auth_facebook.settings')
      ->get('redirect_user_form');
  }

  /**
   * Returns the path to user's user form.
   *
   * @param \Drupal\user\Entity\User $drupal_user
   *   User object.
   *
   * @return string
   *   Path to user edit form.
   */
  public function getPathToUserForm(User $drupal_user) {
    $uid = $drupal_user->id();
    $post_login_path = 'user/' . $uid . '/edit';
    $url = $this->pathValidator->getUrlIfValid($post_login_path);
    return $url->toString();
  }

  /**
   * Returns the post login path defined in module settings.
   *
   * @return string
   *   Path defined in module settings.
   */
  protected function getPostLoginPathSetting() {
    return $this->configFactory
      ->get('social_auth_facebook.settings')
      ->get('post_login_path');
  }

  /**
   * Checks that given path is valid internal Drupal path.
   *
   * Returned path includes subfolder as path prefix if Drupal is installed to a
   * subfolder.
   *
   * @param string $path
   *   Path to validate.
   *
   * @return string|bool
   *   Path to redirect the user if $path was valid.
   *   False otherwise
   */
  protected function validateInternalPath($path) {
    $url = $this->pathValidator->getUrlIfValid($path);
    if ($url !== FALSE && $url->isExternal() === FALSE) {
      return $url->toString();
    }
    return FALSE;
  }

}
