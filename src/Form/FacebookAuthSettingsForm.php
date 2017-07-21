<?php

namespace Drupal\social_auth_facebook\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Auth Facebook.
 */
class FacebookAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   Used to check if route exists.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Used to check if path is valid and exists.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Holds information about the current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteProviderInterface $route_provider, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory, $route_provider, $path_validator);
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
    // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('router.route_provider'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_facebook_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_facebook.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_facebook.settings');

    $form['fb_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Facebook App at <a href="@facebook-dev">@facebook-dev</a>', ['@facebook-dev' => 'https://developers.facebook.com/apps']),
    ];

    $form['fb_settings']['app_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('app_id'),
      '#description' => $this->t('Copy the App ID of your Facebook App here. This value can be found from your App Dashboard.'),
    ];

    $form['fb_settings']['app_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('App Secret'),
      '#default_value' => $config->get('app_secret'),
      '#description' => $this->t('Copy the App Secret of your Facebook App here. This value can be found from your App Dashboard.'),
    ];

    $form['fb_settings']['graph_version'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Facebook Graph API version'),
      '#default_value' => $config->get('graph_version'),
      '#description' => $this->t('Copy the API Version of your Facebook App here. This value can be found from your App Dashboard. More information on API versions can be found at <a href="@facebook-changelog">Facebook Platform Changelog</a>', ['@facebook-changelog' => 'https://developers.facebook.com/docs/apps/changelog']),
    ];

    $form['fb_settings']['oauth_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Valid OAuth redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Valid OAuth redirect URIs</em> field of your Facebook App settings.'),
      '#default_value' => $GLOBALS['base_url'] . '/user/login/facebook/callback',
    ];

    $form['fb_settings']['app_domains'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('App Domains'),
      '#description' => $this->t('Copy this value to <em>App Domains</em> field of your Facebook App settings.'),
      '#default_value' => $this->requestContext->getHost(),
    ];

    $form['fb_settings']['site_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('Copy this value to <em>Site URL</em> field of your Facebook App settings.'),
      '#default_value' => $GLOBALS['base_url'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[2-9]\.[0-9]{1,2}$/', $form_state->getValue('graph_version'))) {
      $form_state->setErrorByName('graph_version', $this->t('Invalid API version. The syntax for API version is for example <em>v2.8</em>'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_facebook.settings')
      ->set('app_id', $values['app_id'])
      ->set('app_secret', $values['app_secret'])
      ->set('graph_version', $values['graph_version'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
