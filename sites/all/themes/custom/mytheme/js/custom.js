(function ($) {
    Drupal.behaviors.customSwiper = {
        attach: function (context, settings) {
            $('.views_slideshow_main').each(function () {
                var swiper = new Swiper($(this), {
                    // Настройки Swiper здесь
                    loop: true,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                });
            });
        }
    };
})(jQuery);
