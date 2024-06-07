<?php

namespace Drupal\yandex_maps\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldFormatter(
 *   id = "geofield_yandex_map",
 *   label = @Translation("Yandex Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class YandexMapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'map_type' => 'yandex#map',
      'map_center' => '',
      'map_zoom' => 12,
      'map_auto_centering' => TRUE,
      'map_auto_zooming' => TRUE,
      'map_controls' => 'default',
      'map_behaviors' => 'default',
      'map_object_preset' => '',
      'map_object_hint_content' => '',
      'map_object_balloon_content' => '',
      'map_hide_empty' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $formatter_settings = $this->getSettings();
    $element = yandex_maps_map_settings_form([], $form_state, $formatter_settings, FALSE);

    $element['map_object_hint_content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Object hint content'),
      '#default_value' => $formatter_settings['map_object_hint_content'],
    ];

    $element['map_object_balloon_content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Object balloon content'),
      '#default_value' => $formatter_settings['map_object_balloon_content'],
    ];

    $element['map_hide_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide empty map'),
      '#default_value' => $formatter_settings['map_hide_empty'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      t('Map type') . ': ' . $this->getSetting('map_type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $formatter_settings = $this->getSettings();

    // Hide empty map
    if ($formatter_settings['map_hide_empty'] && $items->isEmpty()) {
      return [];
    }

    $objects = yandex_maps_convert_geofield_items_to_geojson($items);

    if ($objects && ($formatter_settings['map_object_hint_content'] || $formatter_settings['map_object_balloon_content'])) {
      $entity = $items->getEntity();
      $token_service = \Drupal::token();
      $token_data = [$entity->getEntityTypeId() => $entity];

      foreach ($objects['features'] as &$feature) {
        // Hint content
        if ($formatter_settings['map_object_hint_content']) {
          $feature['properties']['hintContent'] = $token_service->replace($formatter_settings['map_object_hint_content'], $token_data, ['clear' => TRUE]);
        }
        // Balloon content
        if ($formatter_settings['map_object_balloon_content']) {
          $feature['properties']['balloonContent'] = $token_service->replace($formatter_settings['map_object_balloon_content'], $token_data, ['clear' => TRUE]);
        }
      }
    }

    return [[
      '#theme'                => 'yandex_map',
      '#map_type'             => $formatter_settings['map_type'],
      '#map_center'           => $formatter_settings['map_center'],
      '#map_zoom'             => $formatter_settings['map_zoom'],
      '#map_auto_centering'   => $formatter_settings['map_auto_centering'],
      '#map_auto_zooming'     => $formatter_settings['map_auto_zooming'],
      '#map_controls'         => $formatter_settings['map_controls'],
      '#map_behaviors'        => $formatter_settings['map_behaviors'],
      '#map_object_preset'    => $formatter_settings['map_object_preset'],
      '#map_objects'          => $objects,
    ]];
  }

}
