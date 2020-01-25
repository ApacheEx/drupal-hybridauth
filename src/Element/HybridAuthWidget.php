<?php

namespace Drupal\hybridauth\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an HybridAuth widget.
 *
 * @FormElement("hybridauth_widget")
 */
class HybridAuthWidget extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => FALSE,
      '#element_validate' => [
        [$class, 'validateHybridAuthWidget'],
      ],
      '#pre_render' => [
        [$class, 'preRenderHybridAuthWidget'],
      ],
      '#theme' => 'hybridauth_widget',
      '#theme_wrappers' => ['form_element'],
      '#attached' => [],
    ];
  }

}
