/**
 * Elementor Widgets JavaScript
 *
 * @package Backorder_Flow
 */

(function($) {
    'use strict';

    $(window).on('elementor/frontend/init', function() {
        // Product Backorder Status Widget
        elementorFrontend.hooks.addAction('frontend/element_ready/wc-cbs-product-backorder-status.default', function($scope) {
            initProductBackorderStatus($scope);
        });

        // Cart Backorder Groups Widget
        elementorFrontend.hooks.addAction('frontend/element_ready/wc-cbs-cart-backorder-groups.default', function($scope) {
            initCartBackorderGroups($scope);
        });

        // Backorder Products Widget
        elementorFrontend.hooks.addAction('frontend/element_ready/wc-cbs-backorder-products.default', function($scope) {
            initBackorderProducts($scope);
        });

        // Backorder Badge Widget
        elementorFrontend.hooks.addAction('frontend/element_ready/wc-cbs-backorder-badge.default', function($scope) {
            initBackorderBadge($scope);
        });
    });

    /**
     * Initialize Product Backorder Status Widget
     */
    function initProductBackorderStatus($scope) {
        var $widget = $scope.find('.wc-cbs-backorder-badge');
        if ($widget.length) {
            $widget.fadeIn(300);
        }
    }

    /**
     * Initialize Cart Backorder Groups Widget
     */
    function initCartBackorderGroups($scope) {
        var $groups = $scope.find('.wc-cbs-cart-group');

        $groups.each(function(index) {
            var $group = $(this);
            setTimeout(function() {
                $group.css('opacity', '0').animate({opacity: 1}, 300);
            }, index * 100);
        });
    }

    /**
     * Initialize Backorder Products Widget
     */
    function initBackorderProducts($scope) {
        var $products = $scope.find('.wc-cbs-product-item');

        // Add hover effects
        $products.hover(
            function() {
                $(this).find('.wc-cbs-product-badge').fadeIn(200);
            },
            function() {
                // Keep badge visible
            }
        );

        // Carousel functionality (if layout is carousel)
        var $carousel = $scope.find('.wc-cbs-layout-carousel .wc-cbs-products-grid');
        if ($carousel.length && typeof $.fn.slick !== 'undefined') {
            $carousel.slick({
                dots: true,
                arrows: true,
                infinite: true,
                speed: 300,
                slidesToShow: parseInt($scope.find('[data-columns]').data('columns')) || 4,
                slidesToScroll: 1,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2
                        }
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 1
                        }
                    }
                ]
            });
        }
    }

    /**
     * Initialize Backorder Badge Widget
     */
    function initBackorderBadge($scope) {
        var $badge = $scope.find('.wc-cbs-badge');

        if ($badge.length) {
            // Add entrance animation
            $badge.css('opacity', '0').animate({opacity: 1}, 400);

            // Add pulse animation on hover if animated
            if ($badge.hasClass('wc-cbs-badge-animated')) {
                $badge.hover(
                    function() {
                        $(this).addClass('wc-cbs-badge-pulse');
                    },
                    function() {
                        $(this).removeClass('wc-cbs-badge-pulse');
                    }
                );
            }
        }
    }

})(jQuery);
