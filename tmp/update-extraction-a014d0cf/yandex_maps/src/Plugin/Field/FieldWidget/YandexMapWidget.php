<?php

namespace Drupal\yandex_maps\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use geoPHP;

/**
 * @FieldWidget(
 *   id = "geofield_yandex_map",
 *   label = @Translation("Yandex Map"),
 *   field_types = {
 *     "geofield"
 *   },
 *   multiple_values = true
 * )
 */
class YandexMapWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'map_type'             => 'yandex#map',
      'map_center'           => '37.61,55.75',
      'map_auto_centering'   => TRUE,
      'map_auto_zooming'     => TRUE,
      'map_zoom'             => 10,
      'map_controls'         => 'default',
      'map_object_types'     => ['point'],
      'map_selected_control' => 'point',
      'map_object_preset'    => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = yandex_maps_map_settings_form($form, $form_state, $this->getSettings(), TRUE);

    unset($form['map_behaviors']);

    return $form;
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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_storage_definition = $this->fieldDefinition->getFieldStorageDefinition();
    $widget_settings = $this->getSettings();

    $element += [
      '#type'                 => 'yandex_map',
      '#map_type'             => $widget_settings['map_type'],
      '#map_center'           => $widget_settings['map_center'],
      '#map_zoom'             => $widget_settings['map_zoom'],
      '#map_auto_centering'   => $widget_settings['map_auto_centering'],
      '#map_auto_zooming'     => $widget_settings['map_auto_zooming'],
      '#map_controls'         => $widget_settings['map_controls'],
      '#map_object_types'     => array_filter($widget_settings['map_object_types']),
      '#map_selected_control' => $widget_settings['map_selected_control'],
      '#map_object_preset'    => $widget_settings['map_object_preset'],
      '#map_multiple'         => $field_storage_definition->isMultiple(),
      '#map_objects'          => yandex_maps_convert_geofield_items_to_geojson($items), /** @TODO Replace to #default_value */
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    /**
     * @TODO Use geofield.geophp service and DI without geoPHP static methods.
     */
    if ($values['objects'] && $geometry = geoPHP::load($values['objects'], 'geojson')) {
      $geometry = geoPHP::geometryReduce($geometry);

      $values = [];
      foreach (yandex_maps_split_objects($geometry) as $geometry_objects) {
        $values[] = [
          'value' => $geometry_objects->out('wkt'),
        ];
      }

      return $values;
    }

    return [];
  }

}
