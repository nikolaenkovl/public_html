<?php

/**
 * Preprocess function for yandex-map.html.twig.
 */
function yandex_maps_preprocess_yandex_map(&$variables) {
  $element = $variables['element'];

  // Add class and id attributes.
  $variables['attributes']['class'][] = 'yandex-map';
  if (!empty($element['#id']) && empty($variables['#attributes']['id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }

  // Formatting #map_objects as string.
  if (!empty($element['#map_objects'])) {
    if (is_array($element['#map_objects'])) {
      $element['#map_objects'] = yandex_maps_encode_geojson($element['#map_objects']);
    }
    else {
      $element['#map_objects'] = trim($element['#map_objects']);
    }
  }
  else {
    $variables['#attributes']['class'][] = 'yandex-map-empty';
  }

  $default_settings = [
    'map_type'             => 'yandex#map',
    'map_center'           => '0,0',
    'map_zoom'             => 0,
    'map_auto_centering'   => FALSE,
    'map_auto_zooming'     => FALSE,
    'map_editable'         => FALSE,
    'map_multiple'         => FALSE,
    'map_clusterize'       => FALSE,
    'map_save_state'       => FALSE,
    'map_controls'         => 'default',
    'map_behaviors'        => 'default',
    'map_selected_control' => '',
    'map_object_types'     => '',
    'map_object_preset'    => '',
    'map_without_objects'  => FALSE,
    'map_objects'          => '',
    'map_options'          => '',
  ];
  foreach ($default_settings as $setting_name => $setting_value) {
    if (isset($element['#' . $setting_name])) {
      $attribute_name = 'data-' . str_replace('_', '-', $setting_name);
      $attribute_value = $element['#' . $setting_name];
      if (is_array($attribute_value)) {
        $attribute_value = implode(',', $attribute_value);
      }
      if ($attribute_value != $setting_value && $attribute_value !== '') {
        $variables['attributes'][$attribute_name] = $attribute_value;
      }
    }
  }

  if (!empty($variables['attributes']['data-save-state'])) {
    $variables['#attached']['library'][] = 'core/jquery.cookie';
  }

  $variables['#attached']['library'][] = 'yandex_maps/main';
}
