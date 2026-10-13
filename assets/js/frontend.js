/**
 * Frontend JavaScript
 *
 * @package Backorder_Flow
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initBackorderGroups();
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

})();
