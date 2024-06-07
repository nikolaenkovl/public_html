<?php

namespace Drupal\yandex_maps_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

class YandexMapsExamplesController extends ControllerBase {

  public function themeExample() {
    $default['#suffix'] = '<br /><br />';

    $elements = [
      [
        '#theme' => 'yandex_map',
        '#map_center' => [37.61, 55.75],
        '#map_zoom' => 12,
      ],
      [
        '#theme' => 'yandex_map',
        '#map_type' => 'yandex#satellite',
        '#map_center' => [37.61, 55.75],
        '#map_zoom' => 12,
      ],
      [
        '#theme' => 'yandex_map',
        '#map_objects' => '{"type":"Point","coordinates":[37.62817382812449,55.75485888286848]}',
        '#map_zoom' => 12,
      ],
    ];

    $elements[] = $this->themeClusterizeExample();

    foreach ($elements as &$element) {
      $element['#prefix'] = '<p><pre>' . print_r($element, TRUE) . '</pre></p>';
      $element['#suffix'] = '<br /><br />';
    }

    return $elements;
  }

  public function themeClusterizeExample() {
    return [
      '#theme' => 'yandex_map',
      '#map_clusterize' => TRUE,
      '#map_objects' => '
          [
            {"type": "Point", "coordinates": [37.52586364746094,55.7765730186677]},
            {"type": "Point", "coordinates": [37.61444091796875,55.754940702479146]},
            {"type": "Point", "coordinates": [37.60963439941406,55.753781489660035]},
            {"type": "Point", "coordinates": [37.613067626953125,55.75532709909638]},
            {"type": "Point", "coordinates": [37.529296875,55.77811772485584]},
            {"type": "Point", "coordinates": [37.73185729980469,55.7642131648377]},
            {"type": "Point", "coordinates": [37.72911071777344,55.7649857705176]},
            {"type": "Point", "coordinates": [37.68104553222656,55.73445620353334]},
            {"type": "Point", "coordinates": [37.629547119140625,55.800895029938275]},
            {"type": "Point", "coordinates": [37.62611389160156,55.800123135977486]},
            {"type": "Point", "coordinates": [37.63092041015624,55.800123135977486]},
            {"type": "Point", "coordinates": [37.628173828125,55.800509084870626]},
            {"type": "Point", "coordinates": [37.628173828125,55.800509084870626]}
          ]
        ',
    ];
  }
}
