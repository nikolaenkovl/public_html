<?php

namespace Drupal\yandex_maps\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for editing Yandex Map.
 *
 * Usage Example:
 * @code
 * $form['location'] = array(
 *   '#type' => 'yandex_map',
 *   '#map_center' => [37.61, 55.75],
 *   '#map_zoom' => 12,
 * );
 * @endcode
 *
 * @FormElement("yandex_map")
 */
class YandexMapElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#map_editable' => TRUE,
      '#map_object_types' => ['point'],
      '#map_selected_control' => 'point',
      '#map_objects' => '',
      '#map_center' => '',
      '#map_zoom' => '',
      '#process' => [
        [$class, 'processYandexMap'],
      ],
      '#theme' => 'yandex_map',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Process yandex map element.
   */
  public static function processYandexMap(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    if (isset($element['#value']['center'])) {
      $element['#map_center'] = $element['#value']['center'];
    }
    if (isset($element['#value']['zoom'])) {
      $element['#map_zoom'] = $element['#value']['zoom'];
    }

    $objects = $element['#map_objects'];
    if ($objects && is_array($objects)) {
      $objects = yandex_maps_encode_geojson($objects);
    }
    $element['objects'] = [
      '#type' => 'hidden',
      '#default_value' => $objects,
    ];
    // Removing objects from data-map-objects attribute, because objects will be in hidden input
    unset($element['#map_objects']);

    $element['center'] = [
      '#type' => 'hidden',
      '#default_value' => is_array($element['#map_center']) ? implode(',', $element['#map_center']) : $element['#map_center'],
    ];

    $element['zoom'] = [
      '#type' => 'hidden',
      '#default_value' => $element['#map_zoom'],
    ];

    if ($element['#map_selected_control'] && !in_array($element['#map_selected_control'], $element['#map_object_types'])) {
      $element['#map_selected_control'] = reset($element['#map_object_types']);
    }

    return $element;
  }

}
