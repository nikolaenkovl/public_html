<?php

namespace Drupal\yandex_maps_examples\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class YandexMapsExampleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yandex_maps_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['objects'] = [
      '#type' => 'yandex_map',
      '#title' => 'Form',
      '#map_center' => [37.61, 55.75],
      '#map_zoom' => 13,
      '#map_object_types' => ['point', 'line', 'polygon'],
      '#map_multiple' => TRUE,
      '#description' => $this->t('Click on map - add object, double click on object - remove object from map.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    debug($form_state->getValues());
    $form_state->setRebuild();
  }

}
