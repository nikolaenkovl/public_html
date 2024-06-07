<?php

namespace Drupal\yandex_maps\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class YandexMapsSettingsForm extends ConfigFormBase {

  /**
   * @var LinkGenerator
   */
  protected $linkGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, LinkGenerator $link_generator) {
    parent::__construct($config_factory);
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yandex_maps_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'yandex_maps.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yandex_maps.settings');

    $api_key_url = Url::fromUri('https://developer.tech.yandex.ru/', ['attributes' => ['target' => '_blank']]);
    $api_key_link = $this->linkGenerator->generate('developer.tech.yandex.ru', $api_key_url);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('API key from @url', ['@url' => $api_key_link]),
      '#default_value' => $config->get('api_key'),
    ];

    $form['presets_file_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to presets'),
      '#description' => $this->t('Path to Yandex Map presets. Example: <code>@example</code>', [
        '@example' => \Drupal::service('extension.list.module')->getPath('yandex_maps') . '/js/yandex-maps-presets.example.js',
      ]),
      '#default_value' => $config->get('presets_file_path'),
    ];

    $form['objects_default_preset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Objects default preset'),
      '#description' => $this->t('Default <a href="@url" target="_blank">preset</a> name. Example: <code>islands#blueDotIcon</code>', [
        '@url' => 'http://api.yandex.ru/maps/doc/jsapi/2.1/ref/reference/option.presetStorage.xml',
      ]),
      '#default_value' => $config->get('objects_default_preset'),
    ];

    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#description' => $this->t('Use unpacked version Yandex.Maps API script. Not recomend on production sites.'),
      '#default_value' => $config->get('debug_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('yandex_maps.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('presets_file_path', $form_state->getValue('presets_file_path'))
      ->set('objects_default_preset', $form_state->getValue('objects_default_preset'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
