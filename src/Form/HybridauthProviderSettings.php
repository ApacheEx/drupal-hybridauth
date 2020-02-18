<?php

namespace Drupal\hybridauth\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hybridauth admin settings form.
 */
class HybridauthProviderSettings extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new HybridauthAdminSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hybridauth_provider_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get id of provider from the form.
    $provider_id = $form['#attributes']['provider_id'];

    // Get values from form.
    $values = $form_state->getUserInput();

    $config = $this->config('hybridauth.provider.settings');

    $config->set(
      'hybridauth_providers_settings_' . $provider_id . '_key',
      $values['hybridauth_provider_' . $provider_id . '_key']
    );
    $config->set(
      'hybridauth_providers_settings_' . $provider_id . '_secret',
      $values['hybridauth_provider_' . $provider_id . '_secret']
    );

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hybridauth.provider.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $provider_id = '') {
    // Set the tab.
    $form['vtabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t($provider_id),
    ];

    // Get provider configuration.
    $config = $this->config('hybridauth.provider.settings');

    // Set the provider_id to the attributes of a form.
    $form['#attributes']['provider_id'] = $provider_id;

    // Add a tab.
    $form['application'] = [
      '#type' => 'details',
      '#title' => $this->t('Application settings'),
      '#description' => t('Enter the Client ID and Client Secret.'),
      '#group' => 'vtabs',
    ];
    // Add key field.
    $form['application']['hybridauth_provider_' . $provider_id . '_key'] = [
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#default_value' =>
        $config->get('hybridauth_providers_settings_' . $provider_id . '_key'),
    ];

    // Add secret field.
    $form['application']['hybridauth_provider_' . $provider_id . '_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Client Secret.'),
      '#default_value' =>
        $config->get(
          'hybridauth_providers_settings_' . $provider_id . '_secret'
        ),
    ];
    return parent::buildForm($form, $form_state);
  }

}
