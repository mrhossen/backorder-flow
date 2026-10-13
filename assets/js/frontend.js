/**
 * Frontend JavaScript
 *
 * @package Custom_Backorder_Pro
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initBackorderGroups();
        handleVariationChanges();
    });

    /**
     * Initialize backorder groups
     */
    function initBackorderGroups() {
        var groups = document.querySelectorAll('.wc-cbs-cart-group');

        groups.forEach(function(group) {
            // Add fade-in animation
            group.style.opacity = '0';
            setTimeout(function() {
                group.style.transition = 'opacity 0.3s ease';
                group.style.opacity = '1';
            }, 100);
        });
    }

    /**
     * Handle variation changes to update backorder status
     */
    function handleVariationChanges() {
        if (typeof wc_add_to_cart_variation_params === 'undefined') {
            return;
        }

        // Listen for variation changes
        document.addEventListener('found_variation', function(event) {
            // You can add custom logic here when a variation is selected
            console.log('Variation selected:', event.detail);
        });
    }

    /**
     * Refresh cart groups via AJAX (if needed)
     */
    function refreshCartGroups() {
        // This can be used to dynamically update cart groups
        // Implementation depends on your specific requirements
    }

})();
