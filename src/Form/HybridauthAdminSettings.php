<?php

namespace Drupal\hybridauth\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
      '#title' => $this->t('Hybrid Auth Settings'),
    ];

    // Authentication providers.
    // - set tab.
    $form['fset_providers'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication providers'),
      '#group' => 'vtabs',
    ];

    // - set content of tab.
    // Header of table.
    $header = [
      'name' => t('Name'),
      'available' => t('Available'),
      'settings' => t('Settings'),
    ];
    $options = [];
    // Clear the providers cache here to get any new ones.
    $providers = hybridauth_providers_list(TRUE);
    $enabled_providers = $values['hybridauth_providers'];
    $available_providers = hybridauth_providers_files();
    $form['fset_providers']['hybridauth_providers'] = [];

    foreach (array_keys($enabled_providers + $providers) as $provider_id) {
      $available = array_key_exists($provider_id, $available_providers);
      if ($available) {
        $link = Link::fromTextAndUrl(
          $this->t('Settings'),
          Url::fromRoute(
            'hybridauth.provider.settings',
            ['provider_id' => $provider_id],
            ['query' => $this->getDestinationArray()]
          )
        )->toString();
        $options[$provider_id] = [
          'name' => $providers[$provider_id],
          'available' => $available ? t('Yes') : t('No'),
          'settings' => $link,
          '#attributes' => [
            'class' => [
              'draggable'
            ]
          ],
        ];
      }
    }
    $form['fset_providers']['hybridauth_providers'] += [
      '#type' => 'tableselect',
      '#title' => $this->t('Providers'),
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $enabled_providers,
      '#js_select' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'hybridauth-providers-weight',
        ],
      ],
    ];

    // Required information
    $form['fset_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Required information'),
      '#group' => 'vtabs',
    ];
    $form['fset_fields']['hybridauth_required_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Required information'),
      '#options' => [
        'email' => $this->t('Email address'),
        'firstName' => $this->t('First name'),
        'lastName' => $this->t('Last name'),
        'gender' => $this->t('Gender'),
      ],
      '#description' => $this->t("If authentication provider doesn't return it, visitor will need to fill additional form before registration."),
      '#default_value' => $values['hybridauth_required_fields'],
    ];

    // Account settings.
    $form['fset_account'] = [
      '#type' => 'details',
      '#title' => $this->t('Account settings'),
      '#group' => 'vtabs',
    ];
    $form['fset_account']['hybridauth_register'] = [
      '#type' => 'radios',
      '#title' => $this->t('Who can register accounts?'),
      '#options' => [
        1 => $this->t('Visitors'),
        2 => $this->t('Visitors, but administrator approval is required'),
        3 => $this->t('Nobody, only login for existing accounts is possible'),
      ],
      '#default_value' => $values['hybridauth_register'],
    ];
    $form['fset_account']['hybridauth_email_verification'] = [
      '#type' => 'radios',
      '#title' => $this->t('E-mail verification'),
      '#options' => [
        1 => $this->t('Require e-mail verification'),
        2 => $this->t("Don't require e-mail verification"),
      ],
      '#default_value' => $values['hybridauth_email_verification'],
    ];

    // E-mail address verification template.
    $form['fset_account']['fset_email_verification_template'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('E-mail verification template'),
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
      '#title' => $this->t('Subject'),
      '#default_value' => $values['hybridauth_email_verification_subject'],
      '#maxlength' => 180,
    ];
    $form['fset_account']['fset_email_verification_template']['hybridauth_email_verification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $values['hybridauth_email_verification_body'],
      '#rows' => 12,
    ];
    $token_exists = $this->moduleHandler->moduleExists('token');
    if ($token_exists) {
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
      '#title' => $this->t('Save HybridAuth provided picture as user picture'),
      '#description' => $this->t('Save pictures provided by HybridAuth as user pictures. Check the "Enable user pictures" option at <a href="@link">Account settings</a> to make this option available.',
        ['@link' => '/admin/config/people/accounts']),
      '#default_value' => $values['hybridauth_pictures'],
    ];
    $form['fset_account']['hybridauth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username pattern'),
      '#default_value' => $values['hybridauth_username'],
      '#required' => TRUE,
    ];
    $form['fset_account']['hybridauth_display_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display name pattern'),
      '#default_value' => $values['hybridauth_display_name'],
    ];
    if ($token_exists) {
      $form['fset_account']['fset_token'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Tokens'),
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
      '#title' => $this->t('Allow username change when registering'),
      '#description' => $this->t('Allow users to change their username when registering through HybridAuth.'),
      '#default_value' => $values['hybridauth_registration_username_change'],
    ];
    $form['fset_account']['hybridauth_registration_password'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ask user for a password when registering'),
      '#description' => $this->t('Ask users to set password for account when registering through HybridAuth.'),
      '#default_value' => $values['hybridauth_registration_password'],
    ];
    $form['fset_account']['hybridauth_override_realname'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override Real name'),
      '#default_value' => $values['hybridauth_override_realname'],
    ];
    $form['fset_account']['hybridauth_disable_username_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable username change'),
      '#description' => $this->t('Remove username field from user account edit form for users created by HybridAuth. If this is unchecked then users should also have "Change own username" permission to actually be able to change the username.'),
      '#default_value' => $values['hybridauth_disable_username_change'],
    ];
    $form['fset_account']['hybridauth_remove_password_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove password fields'),
      '#description' => $this->t('Remove password fields from user account edit form for users created by HybridAuth.'),
      '#default_value' => $values['hybridauth_disable_username_change'],
    ];

    // Other settings.
    $form['fset_other'] = [
      '#type' => 'details',
      '#title' => $this->t('Other settings'),
      '#group' => 'vtabs',
    ];
    $form['fset_other']['hybridauth_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect after login'),
      '#default_value' => $values['hybridauth_destination'],
    ];
    $form['fset_other']['hybridauth_destination_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect after error on login'),
      '#default_value' => $values['hybridauth_destination_error'],
    ];
    if ($token_exists) {
      $form['fset_other']['fset_token'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Tokens'),
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
    $form['fset_other']['hybridauth_duplicate_emails'] = [
      '#type' => 'radios',
      '#title' => $this->t('Duplicate emails'),
      '#options' => [
        0 => $this->t('Allow duplicate email addresses, create new user account and login'),
        1 => $this->t("Don't allow duplicate email addresses, block registration and advise to login using the existing account"),
        2 => $this->t("Don't allow duplicate email addresses, add new identity to the existing account and login"),
      ],
      '#default_value' => $values['hybridauth_duplicate_emails'],
    ];
    $form['fset_other']['hybridauth_proxy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy'),
      '#default_value' => $values['hybridauth_proxy'],
    ];
    $form['fset_other']['hybridauth_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#default_value' => $values['hybridauth_debug'],
    ];

    return parent::buildForm($form, $form_state);
  }

}
