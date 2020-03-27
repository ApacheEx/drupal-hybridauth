<?php

namespace Drupal\hybridauth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides a 'hybridauth_login_block' block.
 *
 * @Block(
 *   id = "hybridauth_login_block",
 *   admin_label = @Translation("Hybridauth Block for login with Social networks"),
 *   category = @Translation("Custom block")
 * )
 */
class HybridauthLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new Date object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $providers = [];

    // Get settings from configs
    $values = $this->configFactory
      ->get('hybridauth.settings')
      ->get('hybridauth_settings');
    // Get enabled providers.
    $enabled_providers = $values['hybridauth_providers'];
    // Generate elements of forms for enabled providers.
    foreach ($enabled_providers as $name => $value) {
      if (!empty($value)) {
        $providers[$name] = Url::fromRoute(
          'hybridauth.authenticate',
          ['provider_id' => $name]
        );
      }
    }

    return [
      '#providers' => $providers,
      '#theme' => 'hybridauth_block_display',
    ];
  }

}
