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
    $config = $this->config('hybridauth.providers.settings');
    $config->set('hybridauth_providers_settings', $form_state->getValues());
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hybridauth.providers.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $provider_id = '') {
    $config = $this->config('hybridauth.providers.settings');
    $values = $config->get('hybridauth_providers_settings');

    $form['vtabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t($provider_id),
    ];

    switch ($provider_id) {
      case 'LinkedIn':
        $form['application'] = [
          '#type' => 'details',
          '#title' => $this->t('Application settings'),
          '#description' => t('Enter the Client ID and Client Secret.'),
          '#group' => 'vtabs',
        ];

        $form['application']['hybridauth_provider_' . $provider_id . '_keys_key'] = array(
          '#type' => 'textfield',
          '#title' => t('Client ID'),
          '#default_value' => $values['hybridauth_provider_' . $provider_id . '_keys_key'],
        );

        $form['application']['hybridauth_provider_' . $provider_id . '_keys_secret'] = array(
          '#type' => 'textfield',
          '#title' => t('Client Secret.'),
          '#default_value' => $values['hybridauth_provider_' . $provider_id . '_keys_secret'],
        );

//        $app_settings['#description'] = t('Enter the Client ID and Client Secret.');
//        $app_settings['hybridauth_provider_' . $provider_id . '_keys_key']['#title'] = t('Client ID');
//        unset($app_settings['hybridauth_provider_' . $provider_id . '_keys_key']['#description']);
//        $app_settings['hybridauth_provider_' . $provider_id . '_keys_secret']['#title'] = t('Client Secret');
//        unset($app_settings['hybridauth_provider_' . $provider_id . '_keys_secret']['#description']);
//        unset($app_settings['hybridauth_provider_' . $provider_id . '_keys_id']);

        break;
    }




//    $form['application']['hybridauth_provider_' . $provider_id . '_keys_id'] = array(
//      '#type' => 'textfield',
//      '#title' => t('Application ID'),
//      '#description' => t('The application ID.'),
//      '#default_value' => \Drupal::state()->get('hybridauth_provider_' . $provider_id . '_keys_id', ''),
//    );

//
//    $form['window_settings'] = [
//      '#type' => 'details',
//      '#title' => $this->t('Authentication window settings'),
//      '#group' => 'vtabs',
//    ];
//    $options = array(
//      'current' => t('Current window'),
//      'popup' => t('New popup window'),
//    );
//    $modal_description = FALSE;
//    if (\Drupal::moduleHandler()->moduleExists('colorbox')) {
//      $options['colorbox'] = t('Colorbox');
//      $modal_description = TRUE;
//    }
//    if (\Drupal::moduleHandler()->moduleExists('shadowbox')) {
//      $options['shadowbox'] = t('Shadowbox');
//      $modal_description = TRUE;
//    }
//    if (\Drupal::moduleHandler()->moduleExists('fancybox')) {
//      $options['fancybox'] = t('fancyBox');
//      $modal_description = TRUE;
//    }
//    if (\Drupal::moduleHandler()->moduleExists('lightbox2')) {
//      $options['lightbox2'] = t('Lightbox2');
//      $modal_description = TRUE;
//    }
//    $form['window_settings']['hybridauth_provider_' . $provider_id . '_window_type'] = array(
//      '#type' => 'radios',
//      '#title' => t('Authentication window type'),
//      '#options' => $options,
//      '#default_value' => \Drupal::state()->get('hybridauth_provider_' . $provider_id . '_window_type', 'current'),
//      '#description' => $modal_description ? t("Be careful with modal windows - some authentication providers (Twitter, LinkedIn) won't work with them.") : '',
//    );
//    $base = array(
//      '#type' => 'textfield',
//      '#element_validate' => array('element_validate_integer_positive'),
//      '#size' => 4,
//      '#maxlength' => 4,
//      '#states' => array(
//        'invisible' => array(
//          ':input[name="hybridauth_provider_' . $provider_id . '_window_type"]' => array('value' => 'current'),
//        ),
//      ),
//    );
//    $form['window_settings']['hybridauth_provider_' . $provider_id . '_window_width'] = array(
//        '#title' => t('Width'),
//        '#description' => t('Authentication window width (pixels).'),
//        '#default_value' => \Drupal::state()->get('hybridauth_provider_' . $provider_id . '_window_width', 800),
//      ) + $base;
//    $form['window_settings']['hybridauth_provider_' . $provider_id . '_window_height'] = array(
//        '#title' => t('Height'),
//        '#description' => t('Authentication window height (pixels).'),
//        '#default_value' => \Drupal::state()->get('hybridauth_provider_' . $provider_id . '_window_height', 500),
//      ) + $base;

//    if ($provider = hybridauth_get_provider($provider_id)) {
//      if ($function = ctools_plugin_get_function($provider, 'configuration_form_callback')) {
//        $function($form, $provider_id);
//      }
//    }

    return parent::buildForm($form, $form_state);
  }

}
