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
class HybridauthAdminSettings extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new XmlSitemapCustomAddForm object.
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
    return 'hybridauth_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hybridauth.settings');
    $config->set('hybridauth_settings', $form_state->getValues());
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hybridauth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hybridauth.settings');
    $values = $config->get('hybridauth_settings');

    $form['vtabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => t('Hybrid Auth Settings'),
    ];
    $form['fset_fields'] = [
      '#type' => 'details',
      '#title' => t('Required information'),
      '#group' => 'vtabs',
    ];
    $form['fset_fields']['hybridauth_required_fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Required information'),
      '#options' => [
        'email' => t('Email address'),
        'firstName' => t('First name'),
        'lastName' => t('Last name'),
        'gender' => t('Gender'),
      ],
      '#description' => t("If authentication provider doesn't return it, visitor will need to fill additional form before registration."),
      '#default_value' => $values['hybridauth_required_fields'],
    ];

    // Account settings.
    $form['fset_account'] = [
      '#type' => 'details',
      '#title' => t('Account settings'),
      '#group' => 'vtabs',
    ];
    $form['fset_account']['hybridauth_register'] = [
      '#type' => 'radios',
      '#title' => t('Who can register accounts?'),
      '#options' => [
        1 => t('Visitors'),
        2 => t('Visitors, but administrator approval is required'),
        3 => t('Nobody, only login for existing accounts is possible'),
      ],
      '#default_value' => $values['hybridauth_register'],
    ];
    $form['fset_account']['hybridauth_email_verification'] = [
      '#type' => 'radios',
      '#title' => t('E-mail verification'),
      '#options' => [
        1 => t('Require e-mail verification'),
        2 => t("Don't require e-mail verification"),
      ],
      '#default_value' => $values['hybridauth_email_verification'],
    ];

    // E-mail address verification template.
    $form['fset_account']['fset_email_verification_template'] = [
      '#type' => 'fieldset',
      '#title' => t('E-mail verification template'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="hybridauth_email_verification"]' => [
            'value' => '2',
          ],
        ],
      ],
    ];
    $form['fset_account']['fset_email_verification_template']['hybridauth_email_verification_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $values['hybridauth_email_verification_subject'],
      '#maxlength' => 180,
    ];
    $form['fset_account']['fset_email_verification_template']['hybridauth_email_verification_body'] = [
      '#type' => 'textarea',
      '#title' => t('Body'),
      '#default_value' => $values['hybridauth_email_verification_body'],
      '#rows' => 12,
    ];
    $module_exist_token = $this->moduleHandler->moduleExists('token');
    if ($module_exist_token) {
      $form['fset_account']['fset_email_verification_template']['hybridauth_token_tree'] = [
        '#theme' => 'token_tree',
        '#token_types' => [
          'user',
        ],
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }
    $form['fset_account']['hybridauth_pictures'] = [
      '#type' => 'checkbox',
      '#title' => t('Save HybridAuth provided picture as user picture'),
      '#description' => t('Save pictures provided by HybridAuth as user pictures. Check the "Enable user pictures" option at <a href="@link">Account settings</a> to make this option available.',
        ['@link' => '/admin/config/people/accounts']),
      '#default_value' => $values['hybridauth_pictures'],
    ];
    $form['fset_account']['hybridauth_username'] = [
      '#type' => 'textfield',
      '#title' => t('Username pattern'),
      '#default_value' => $values['hybridauth_username'],
      '#required' => TRUE,
    ];
    $form['fset_account']['hybridauth_display_name'] = [
      '#type' => 'textfield',
      '#title' => t('Display name pattern'),
      '#default_value' => $values['hybridauth_display_name'],
    ];
    if ($module_exist_token) {
      $form['fset_account']['fset_token'] = [
        '#type' => 'fieldset',
        '#title' => t('Tokens'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form['fset_account']['fset_token']['hybridauth_token_tree'] = [
        '#theme' => 'token_tree',
        '#token_types' => [
          'user',
        ],
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      ];
    }
    $form['fset_account']['hybridauth_registration_username_change'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow username change when registering'),
      '#description' => t('Allow users to change their username when registering through HybridAuth.'),
      '#default_value' => $values['hybridauth_registration_username_change'],
    ];
    $form['fset_account']['hybridauth_registration_password'] = [
      '#type' => 'checkbox',
      '#title' => t('Ask user for a password when registering'),
      '#description' => t('Ask users to set password for account when registering through HybridAuth.'),
      '#default_value' => $values['hybridauth_registration_password'],
    ];
    $form['fset_account']['hybridauth_override_realname'] = [
      '#type' => 'checkbox',
      '#title' => t('Override Real name'),
      '#default_value' => $values['hybridauth_override_realname'],
      '#disabled' => !$this->moduleHandler->moduleExists('realname'),
    ];
    $form['fset_account']['hybridauth_disable_username_change'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable username change'),
      '#description' => t('Remove username field from user account edit form for users created by HybridAuth. If this is unchecked then users should also have "Change own username" permission to actually be able to change the username.'),
      '#default_value' => $values['hybridauth_disable_username_change'],
    ];
    $form['fset_account']['hybridauth_remove_password_fields'] = [
      '#type' => 'checkbox',
      '#title' => t('Remove password fields'),
      '#description' => t('Remove password fields from user account edit form for users created by HybridAuth.'),
      '#default_value' => $values['hybridauth_disable_username_change'],
    ];

    // Other settings.
    $form['fset_other'] = [
      '#type' => 'details',
      '#title' => t('Other settings'),
      '#group' => 'vtabs',
    ];
    $form['fset_other']['hybridauth_destination'] = [
      '#type' => 'textfield',
      '#title' => t('Redirect after login'),
      '#default_value' => $values['hybridauth_destination'],
    ];
    $form['fset_other']['hybridauth_destination_error'] = [
      '#type' => 'textfield',
      '#title' => t('Redirect after error on login'),
      '#default_value' => $values['hybridauth_destination_error'],
    ];
    if ($module_exist_token) {
      $form['fset_other']['fset_token'] = [
        '#type' => 'fieldset',
        '#title' => t('Tokens'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form['fset_other']['fset_token']['hybridauth_token_tree'] = [
        '#theme' => 'token_tree',
        '#token_types' => [
          'user',
        ],
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }
    $options = [
      0 => t('Allow duplicate email addresses, create new user account and login'),
      1 => t("Don't allow duplicate email addresses, block registration and advise to login using the existing account"),
      2 => t("Don't allow duplicate email addresses, add new identity to the existing account and login"),
    ];
    $form['fset_other']['hybridauth_duplicate_emails'] = [
      '#type' => 'radios',
      '#title' => t('Duplicate emails'),
      '#options' => $options,
      '#default_value' => $values['hybridauth_duplicate_emails'],
    ];
    $form['fset_other']['hybridauth_proxy'] = [
      '#type' => 'textfield',
      '#title' => t('Proxy'),
      '#default_value' => $values['hybridauth_proxy'],
    ];
    $form['fset_other']['hybridauth_debug'] = [
      '#type' => 'checkbox',
      '#title' => t('Debug mode'),
      '#default_value' => $values['hybridauth_debug'],
    ];

    return parent::buildForm($form, $form_state);
  }

}
