<?php

namespace Drupal\social_auth_facebook\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
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
      '#default_value' => Url::fromRoute('social_auth_facebook.callback')->setAbsolute()->toString(),
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

    $form['fb_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['fb_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: user_birthday,user_location).<br>
                                  The scopes \'email\' and \'public_profile\' are added by default and always requested.<br>
                                  You can see the full list of valid scopes and their description <a href="@scopes">here</a>.', ['@scopes' => 'https://developers.facebook.com/docs/facebook-login/permissions/']),
    ];

    $form['fb_settings']['advanced']['endpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API calls to be made to collect data'),
      '#default_value' => $config->get('endpoints'),
      '#description' => $this->t('Define the Endpoints to be requested when user authenticates with Facebook for the first time<br>
                                  Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                                  <b>For instance:</b><br>
                                  /me?fields=birthday|user_birthday<br>
                                  /me?fields=address|user_address'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $graph_version = $form_state->getValue('graph_version');
    if ($graph_version[0] === 'v') {
      $graph_version = substr($graph_version, 1);
      $form_state->setValue('graph_version', $graph_version);
    }
    if (!preg_match('/^[2-9]\.[0-9]{1,2}$/', $graph_version)) {
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
      ->set('scopes', $values['scopes'])
      ->set('endpoints', $values['endpoints'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
