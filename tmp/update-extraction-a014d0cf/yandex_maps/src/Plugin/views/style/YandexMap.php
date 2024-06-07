<?php

namespace Drupal\yandex_maps\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\views\Plugin\views\style\StylePluginBase;
use geoPHP;

/**
 * @ViewsStyle(
 *   id = "yandex_map",
 *   title = @Translation("Yandex Map"),
 *   display_types = {"normal"},
 * )
 */
class YandexMap extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['map_type']              = ['default' => 'yandex#map'];
    $options['map_center']            = ['default' => '37.61,55.75']; // Moscow
    $options['map_zoom']              = ['default' => 10];
    $options['map_auto_centering']    = ['default' => FALSE];
    $options['map_auto_zooming']      = ['default' => FALSE];
    $options['map_clusterize']        = ['default' => FALSE];
    $options['map_hide_empty']        = ['default' => FALSE];
    $options['map_save_state']        = ['default' => FALSE];
    $options['map_object_preset']     = ['default' => ''];
    $options['map_controls']          = ['default' => 'default'];
    $options['map_behaviors']         = ['default' => 'default'];
    $options['geofield_field']        = ['default' => ''];
    $options['id_field']              = ['default' => ''];
    $options['hint_content_field']    = ['default' => ''];
    $options['icon_content_field']    = ['default' => ''];
    $options['cluster_caption_field'] = ['default' => ''];
    $options['preset_field']          = ['default' => ''];
    $options['show_balloon']          = ['default' => FALSE];
    $options['additional_settings']   = [
      'contains' => [
        'object_options' =>             ['default' => ''],
        'object_options_use_tokens' =>  ['default' => FALSE],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['map_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Map type'),
      '#options' => yandex_maps_get_map_types(),
      '#default_value' => $this->options['map_type'],
    ];

    $form['map_center'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map center'),
      '#default_value' => $this->options['map_center'],
      '#size' => 40,
    ];

    $form['map_zoom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map zoom'),
      '#default_value' => $this->options['map_zoom'],
      '#size' => 5,
    ];

    $form['map_auto_centering'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Map auto centering'),
      '#default_value' => $this->options['map_auto_centering'],
    ];

    $form['map_auto_zooming'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Map auto zooming'),
      '#default_value' => $this->options['map_auto_zooming'],
    ];

    $form['map_clusterize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clusterize placemarks'),
      '#default_value' => $this->options['map_clusterize'],
    ];

    $form['show_balloon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show balloon'),
      '#default_value' => $this->options['show_balloon'],
    ];

    $form['map_hide_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide empty map'),
      '#default_value' => $this->options['map_hide_empty'],
    ];

    $form['map_save_state'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save user zoom and center'),
      '#default_value' => $this->options['map_save_state'],
    ];

    $field_names = $this->displayHandler->getFieldLabels();

    $form['geofield_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Geofield'),
      '#description' => $this->t('This field will be excluded from display.'),
      '#options' => $field_names,
      '#empty_option' => $this->t('< none >'),
      '#default_value' => $this->options['geofield_field'],
      '#required' => TRUE,
    ];

    $form['id_field'] = [
      '#type' => 'select',
      '#title' => $this->t('ID field'),
      '#options' => $field_names,
      '#empty_option' => $this->t('< none >'),
      '#default_value' => $this->options['id_field'],
    ];

    $form['hint_content_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Hint content field'),
      '#options' => $field_names,
      '#empty_option' => $this->t('< none >'),
      '#default_value' => $this->options['hint_content_field'],
    ];

    $form['icon_content_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon content field'),
      '#options' => $field_names,
      '#empty_option' => $this->t('< none >'),
      '#default_value' => $this->options['icon_content_field'],
    ];

    $form['cluster_caption_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Cluster caption field'),
      '#options' => $field_names,
      '#empty_option' => $this->t('< none >'),
      '#default_value' => $this->options['cluster_caption_field'],
    ];

    $form['preset_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Preset field'),
      '#description' => $this->t('Use next modules for replace field value to preset name: !modules', [
        '!modules' => '
          <a href="https://drupal.org/sandbox/xandeadx/2205151" target="_blank">Views field replace value</a>,
          <a href="https://drupal.org/project/views_regex_rewrite" target="_blank">Views Regex Rewrite</a>,
          <a href="https://drupal.org/project/views_fieldrewrite" target="_blank">Views Field Rewrite</a>
        ',
      ]),
      '#options' => $field_names,
      '#empty_option' => $this->t('< none >'),
      '#default_value' => $this->options['preset_field'],
    ];

    $form['map_object_preset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Objects default preset'),
      '#description' => $this->t('Default <a href="@url" target="_blank">preset name</a>. Example: <code>islands#blueCircleDotIcon</code>', [
        '@url' => 'http://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/option.presetStorage.xml',
      ]),
      '#default_value' => $this->options['map_object_preset'],
      '#size' => 40,
    ];

    $form['map_controls'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Controls'),
      '#description' => $this->t('<a href="@url" target="_blank">Controls</a> through a comma, or controls set name. Use <code>&lt;none&gt;</code> to hide all controls. Example: <code>fullscreenControl,searchControl</code>. Default set name: <code>default</code>', [
        '@url' => 'http://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/control.Manager.xml#add',
      ]),
      '#default_value' => $this->options['map_controls'],
      '#size' => 40,
    ];

    $form['map_behaviors'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Behaviors'),
      '#description' => $this->t('<a href="@url" target="_blank">Map behaviors</a> through a comma. Use <code>&lt;none&gt;</code> to disable all behaviors. Default value: <code>default</code>', [
        '@url' => 'http://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/map.behavior.Manager.xml#param-behaviors',
      ]),
      '#default_value' => $this->options['map_behaviors'],
      '#size' => 40,
    ];

    $form['additional_settings'] = [
      '#title' => $this->t('Additional settings'),
      '#type' => 'details',
    ];

    $form['additional_settings']['object_options'] = [
      '#title' => $this->t('Object options'),
      '#description' => $this->t('Additional object options in JSON format. <a href="@url" target="_blank">Options list</a>. Example: @example', [
        '@url' => 'http://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/GeoObject.xml#constructor-summary',
        '@example' => Markup::create('
          <code><br />
            {<br />
            &nbsp;&nbsp;"iconLayout": "default#image",<br />
            &nbsp;&nbsp;"iconImageHref": "http://api.yandex.ru/maps/doc/jsapi/2.x/examples/images/myIcon.gif",<br />
            &nbsp;&nbsp;"iconImageSize": [30, 42],<br />
            &nbsp;&nbsp;"iconImageOffset": [-3, -42]<br />
            }
          </code>
        '),
      ]),
      '#type' => 'textarea',
      '#default_value' => $this->options['additional_settings']['object_options'],
    ];

    $form['additional_settings']['object_options_use_tokens'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use replacement tokens (reduce performance)'),
      '#default_value' => $this->options['additional_settings']['object_options_use_tokens'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (!$this->options['geofield_field']) {
      trigger_error('Missing geofield', E_WARNING);
      return [];
    }

    $renderer = \Drupal::service('renderer');
    $features = [];

    // Additional object options without tokens
    $object_options = [];
    if ($this->options['additional_settings']['object_options'] && !$this->options['additional_settings']['object_options_use_tokens']) {
      $object_options = json_decode($this->options['additional_settings']['object_options'], TRUE);
    }

    // Cycle by Views results
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;

      $geofield_values = $this->view->field[$this->options['geofield_field']]->getValue($row);
      if (!$geofield_values) {
        continue;
      }
      if (is_string($geofield_values)) {
        $geofield_values = [$geofield_values];
      }

      $this->view->field[$this->options['geofield_field']]->options['exclude'] = TRUE;

      if ($this->options['show_balloon']) {
        $balloon_content = $this->view->rowPlugin->render($row);
        if (is_array($balloon_content)) {
          $balloon_content = $renderer->renderRoot($balloon_content);
        }
      }

      $object_id       = $this->options['id_field']              ? trim($this->getField($row_index, $this->options['id_field'])) : '';
      $hint_content    = $this->options['hint_content_field']    ? trim($this->getField($row_index, $this->options['hint_content_field'])) : '';
      $icon_content    = $this->options['icon_content_field']    ? trim($this->getField($row_index, $this->options['icon_content_field'])) : '';
      $cluster_caption = $this->options['cluster_caption_field'] ? trim($this->getField($row_index, $this->options['cluster_caption_field'])) : '';
      $preset          = $this->options['preset_field']          ? trim($this->getField($row_index, $this->options['preset_field'])) : '';

      // Additional object options with tokens.
      // @TODO
      /*if ($this->options['additional_settings']['object_options'] && $this->options['additional_settings']['object_options_use_tokens']) {
        $object_options = $this->safe_tokenize_value($this->options['additional_settings']['object_options'], $row_index);
        $object_options = json_decode($object_options, TRUE);
      }*/

      // Cycle by geofield values
      foreach ($geofield_values as $geofield_value) {
        $geometry = geoPHP::load($geofield_value, 'wkt');

        $feature = [
          'type' => 'Feature',
          'geometry' => $geometry->out('json', TRUE),
        ];
        if ($this->options['show_balloon'] && $balloon_content) {
          $feature['properties']['balloonContent'] = $balloon_content;
        }
        if ($object_id) {
          $feature['properties']['id'] = $object_id;
        }
        if ($hint_content) {
          $feature['properties']['hintContent'] = $hint_content;
        }
        if ($icon_content) {
          $feature['properties']['iconContent'] = $icon_content;
          $feature['options']['preset'] = 'islands#blueStretchyIcon';
        }
        if ($cluster_caption) {
          $feature['properties']['clusterCaption'] = $cluster_caption;
        }
        if ($preset) {
          $feature['options']['preset'] = $preset;
        }
        if ($object_options) {
          $feature_options = $feature['options'] ?? [];
          $feature['options'] = $object_options + $feature_options;
        }
        $features[] = $feature;
      }
    }

    if ($this->options['map_hide_empty'] && !$features) {
      return [];
    }

    unset($this->view->row_index);

    return [
      '#theme'              => 'yandex_map',
      '#map_objects'        => $features ? ['type' => 'FeatureCollection', 'features' => $features] : [],
      '#map_type'           => $this->options['map_type'],
      '#map_center'         => $this->options['map_center'],
      '#map_zoom'           => $this->options['map_zoom'],
      '#map_auto_centering' => $this->options['map_auto_centering'],
      '#map_auto_zooming'   => $this->options['map_auto_zooming'],
      '#map_clusterize'     => $this->options['map_clusterize'],
      '#map_object_preset'  => $this->options['map_object_preset'],
      '#map_controls'       => $this->options['map_controls'],
      '#map_behaviors'      => $this->options['map_behaviors'],
      '#map_save_state'     => $this->options['map_save_state'],
      '#id'                 => Html::getId('yandex-map-' . $this->view->id() . '-' . $this->view->current_display),
    ];
  }

}
