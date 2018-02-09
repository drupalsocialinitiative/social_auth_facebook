<?php

namespace Drupal\social_auth_facebook;

use Drupal\social_auth\AuthManager\OAuth2Manager;
use Drupal\Core\Config\ConfigFactory;

/**
 * Contains all Simple FB Connect logic that is related to Facebook interaction.
 */
class FacebookAuthManager extends OAuth2Manager {

  /**
   * The Facebook client.
   *
   * @var \League\OAuth2\Client\Provider\Facebook
   */
  protected $client;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   */
  public function __construct(ConfigFactory $configFactory) {
    parent::__construct($configFactory->get('social_auth_facebook.settings'));
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    $this->setAccessToken($this->client->getLongLivedAccessToken($this->client->getAccessToken('authorization_code',
      ['code' => $_GET['code']])));
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    $this->user = $this->client->getResourceOwner($this->getAccessToken());
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl() {
    $scopes = ['email', 'public_profile'];

    $facebook_scopes = $this->getScopes();
    if ($facebook_scopes) {
      if (strpos($facebook_scopes, ',')) {
        $scopes = array_merge($scopes, explode(',', $facebook_scopes));
      }
      else {
        $scopes[] = $facebook_scopes;
      }
    }

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraDetails() {
    $endpoints = $this->getEndPoints();

    // Store the data mapped with endpoints define in settings.
    $data = [];

    if ($endpoints) {
      // Iterate through api calls define in settings and retrieve them.
      foreach (explode(PHP_EOL, $endpoints) as $endpoint) {
        // Endpoint is set as path/to/endpoint|name.
        $parts = explode('|', $endpoint);
        $call[$parts[1]] = $this->requestEndPoint($parts[0]);
        array_push($data, $call);
      }

      return json_encode($data);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint($path) {
    $url = 'https://graph.facebook.com/' . 'v' . $this->settings->get('graph_version') . $path;

    $url .= '&access_token=' . $this->getAccessToken();

    $request = $this->client->getAuthenticatedRequest('GET', $url, $this->getAccessToken());

    $response = $this->client->getResponse($request);

    return $response->getBody()->getContents();
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->client->getState();
  }

}
