<?php

/**
 * @file
 * Contains \Drupal\hybridauth\Controller\HybridAuthController.
 */

namespace Drupal\hybridauth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\hybridauth\Provider\HybridauthProvider;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for the hybridauth module.
 */
class HybridAuthController extends ControllerBase {

  /**
   * The url generator to generate the form action.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current Request object.
   *
   * @var \Drupal\hybridauth\Provider\HybridauthProvider
   */
  protected $providerService;

  /**
   * Constructs HybridAuthController.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\hybridauth\Provider\HybridauthProvider $provider_service
   *   The handler of module.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(UrlGeneratorInterface $url_generator, UserStorageInterface $user_storage, Request $request, HybridauthProvider $provider_service, ModuleHandlerInterface $module_handler) {
    $this->urlGenerator = $url_generator;
    $this->userStorage = $user_storage;
    $this->request = $request;
    $this->providerService = $provider_service;

    // Connect to the Hybridauth library.
    $module_path = $module_handler->getModule('hybridauth')->getPath();
    require $module_path . '/vendor/autoload.php';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('hybridauth.provider'),
      $container->get('module_handler')
    );
  }

  /**
   * First authentication step.
   *
   * @param string $provider_id
   *   Authentication provider.
   */
  public function authenticate($provider_id) {
    // Make sure the session is started, HybridAuth library needs it.
    // @todo find the way how to avoid session_start().
    // https://symfony.com/doc/current/components/http_foundation/sessions.html ?
    session_start();

    try {
      // Configure function name and path for it.
      $provider_function = 'Hybridauth\\Provider\\' . $provider_id;
      // Get provider from variable function.
      if (class_exists($provider_function)) {
        $provider_config =
          $this->providerService->getConfiguration($provider_id);
        $provider = new $provider_function($provider_config);
      }
      else {
        return false;
      }

      $session = $this->request->getSession();

      // Saving provider id in session for further usage.
      $session->set('hybridauth_provider', $provider_id);

      $provider->authenticate();
    }
    catch (\Exception $e) {
      echo 'Oops, we ran into an issue! ' . $e->getMessage();
    }
  }

  /**
   * HybridAuth endpoint.
   */
  public function endpoint() {
    try {
      $session = $this->request->getSession();

      // Get provider id from session.
      $provider_id = $session->get('hybridauth_provider');
      // Configure function name and path for it.
      $provider_function = 'Hybridauth\\Provider\\' . $provider_id;
      // Get provider by variable function.
      if (class_exists($provider_function)) {
        $provider_config =
          $this->providerService->getConfiguration($provider_id);
        $provider = new $provider_function($provider_config);
      }
      else {
        return false;
      }

      $provider->authenticate();
      $account = $this->authenticateUser($provider->getUserProfile());
      return $this->redirect(
        'entity.user.canonical', ['user' => $account->id()]
      );
    }
    catch (\Exception $e) {
      echo 'Oops, we ran into an issue! ' . $e->getMessage();
    }
  }

  /**
   * Authenticates user.
   *
   * @param array $account_data
   *   Array with user data from authentication provider.
   *
   * @return \Drupal\user\UserInterface $account
   *   User entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function authenticateUser($account_data) {
    $email = $account_data->email;

    // Checking if user exists.
    $uids = $this->userStorage->getQuery()->condition('mail', $email)->execute();

    if ($uids) {
      $account = $this->userStorage->load(reset($uids));
    }
    else {
      // Creating new user.
      $account = $this->userStorage->create();
      $account->enforceIsNew();
      $account->activate();
      $account->setEmail($email);
      $account->setUsername($account_data->displayName);
      $account->save();
    }

    user_login_finalize($account);

    return $account;
  }

}
