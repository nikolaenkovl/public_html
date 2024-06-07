(function ($) {
    Drupal.behaviors.customSwiper = {
        attach: function (context, settings) {
            $('.view-slideshow', context).once('swiper-init').each(function () {
                var swiper = new Swiper($(this).find('.swiper-container'), {
                    loop: true,
                    pagination: {
                        el: $(this).find('.swiper-pagination'),
                        clickable: true,
                    },
                    navigation: {
                        nextEl: $(this).find('.swiper-button-next'),
                        prevEl: $(this).find('.swiper-button-prev'),
                    },
                });
            });
        }
    };
})(jQuery);
