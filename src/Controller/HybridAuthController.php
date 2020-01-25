<?php

/**
 * @file
 * Contains \Drupal\hybridauth\Controller\HybridAuthController.
 */

namespace Drupal\hybridauth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\user\UserStorageInterface;
use Hybridauth\Provider\LinkedIn;
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
   * Constructs HybridAuthController.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(UrlGeneratorInterface $url_generator, UserStorageInterface $user_storage, Request $request) {
    $this->urlGenerator = $url_generator;
    $this->userStorage = $user_storage;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('request_stack')->getCurrentRequest()
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
    session_start();

    try {
      $provider = new LinkedIn($this->getConfiguration());

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
      $provider = new LinkedIn($this->getConfiguration());
      $provider->authenticate();
      $account = $this->authenticateUser($provider->getUserProfile());
      return $this->redirect('entity.user.canonical', ['user' => $account->id()]);
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

  /**
   * Callback path for HybridAuth.
   */
  protected function getEndpointPath() {
    return $this->urlGenerator->generateFromRoute('hybridauth.endpoint', [], ['absolute' => TRUE]);
  }

  /**
   * Gets providers configuration.
   */
  protected function getConfiguration() {
    // @todo add admin pages and get key & secret from config.
    // @todo it works with Linkedin only now - need to make more flexible.
    return [
      'callback' => $this->getEndpointPath(),
      'keys' => [
        'key' => 'Your Linked API key here',
        'secret' => 'Your Linkedin secret here',
      ]
    ];
  }

}
