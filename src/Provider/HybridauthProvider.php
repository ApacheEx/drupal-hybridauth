<?php

namespace Drupal\hybridauth\Provider;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get the Hybridauth Provider key and secret.
 */
class HybridauthProvider extends ControllerBase {
  /**
   * The url generator to generate the form action.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs HybridAuthController.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   */
  public function __construct(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator')
    );
  }

  /**
   * Callback path for HybridAuth.
   */
  protected function getEndpointPath() {
    return $this->urlGenerator->generateFromRoute(
      'hybridauth.endpoint', [], ['absolute' => TRUE]
    );
  }

  /**
   * Gets providers configuration.
   *
   * @param string $provider_id
   *
   * @return array
   */
  public function getConfiguration($provider_id = '') {
    // Get callback.
    $callback = $this->getEndpointPath();

    // Get parameters from configuration storage.
    $config = $this->config('hybridauth.provider.settings');
    $key = $config->get(
      'hybridauth_providers_settings_' . $provider_id . '_key'
    );
    $secret = $config->get(
      'hybridauth_providers_settings_' . $provider_id . '_secret'
    );

    return [
      'callback' => $callback,
      'keys' => [
        'key' => $key,
        'secret' => $secret,
      ]
    ];
  }

  /**
   * Gets Instance of the provider.
   *
   * @param string $provider_id
   *
   * @return bool
   */
  public function getInstance($provider_id = '') {
    // Configure function name and path for it.
    $provider_function = 'Hybridauth\\Provider\\' . $provider_id;

    // Get provider from variable function.
    if (class_exists($provider_function)) {
      $provider_config =
        $this->getConfiguration($provider_id);
      return new $provider_function($provider_config);
    }
    else {
      return false;
    }
  }

}
