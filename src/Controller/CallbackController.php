<?php

/**
 * @file
 * Contains \Drupal\hybridauth\Controller\CallbackController.
 */

namespace Drupal\hybridauth\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the hybridauth module.
 */
class CallbackController extends ControllerBase {

  public function callback() {
    return ['#markup' => 'todo'];
  }

}
