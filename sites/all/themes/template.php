
function bartik_preprocess_page(&$variables) {
    // Добавляем Swiper CSS
    drupal_add_css(drupal_get_path('theme', 'bartik') . '/sites/all/libraries/swiper/swiper-bundle.min.css');
    // Добавляем Swiper JS
    drupal_add_js(drupal_get_path('theme', 'bartik') . '/sites/all/libraries/swiper/swiper-bundle.min.js');
}
