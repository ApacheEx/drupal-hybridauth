<?php

namespace Drupal\hybridauth\Element;

use Drupal\Core\Form\FormStateInterface;
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
      '#process' => [
        [$class, 'processHybridAuthWidget'],
      ],
      '#theme' => 'hybridauth_widget',
      '#theme_wrappers' => ['form_element'],
      '#attached' => [],
    ];
  }

  /**
   * Render API callback.
   */
  public static function preRenderHybridAuthWidget($element) {
    return $element;
  }

  /**
   * Processes a checkboxes form element.
   */
  public static function processHybridAuthWidget(&$element, FormStateInterface $form_state, &$complete_form) {
    return $element;
  }

}
