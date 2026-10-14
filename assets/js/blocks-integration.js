/**
 * WooCommerce Blocks Integration for Backorder Flow
 *
 * @package Backorder_Flow
 */

(function() {
	'use strict';

	// Check if data is available.
	if ( typeof window.wmbfBlocksData === 'undefined' ) {
		return;
	}

	var wmbfBlocksData = window.wmbfBlocksData;
	var groupsInserted = {
		cart: false,
		checkout: false
	};
	var isRendering = false;

	/**
	 * Get current page context.
	 *
	 * @return {string|null} Page context ('cart', 'checkout', or null).
	 */
	function getPageContext() {
		if ( document.querySelector( '.wp-block-woocommerce-cart' ) ||
			 document.querySelector( '.wc-block-cart' ) ||
			 document.body.classList.contains( 'woocommerce-cart' ) ) {
			return 'cart';
		}
		if ( document.querySelector( '.wp-block-woocommerce-checkout' ) ||
			 document.querySelector( '.wc-block-checkout' ) ||
			 document.body.classList.contains( 'woocommerce-checkout' ) ) {
			return 'checkout';
		}
		return null;
	}

	/**
	 * Check if our groups element exists and is visible.
	 *
	 * @param {string} context Page context.
	 * @return {boolean}
	 */
	function groupsElementExists( context ) {
		var element = document.getElementById( 'wmbf-shipping-groups-' + context );
		return element && element.offsetParent !== null;
	}

	/**
	 * Create the shipping groups element from the server-rendered markup.
	 *
	 * The markup comes from the plugin's cart-groups.php template, so the blocks show
	 * exactly what the classic cart and checkout show.
	 *
	 * @param {Object} data Groups data.
	 * @param {string} context Page context.
	 * @return {HTMLElement} Groups container element.
	 */
	function createGroupsElement( data, context ) {
		var container = document.createElement( 'div' );
		container.className = 'wc-cbs-blocks-groups';
		container.id = 'wmbf-shipping-groups-' + context;
		container.innerHTML = data.html && data.html[ context ] ? data.html[ context ] : '';
		return container;
	}

	/**
	 * Find the best insertion point for groups.
	 *
	 * @param {string} context Page context.
	 * @return {Object|null} Object with element and position, or null.
	 */
	function findInsertionPoint( context ) {
		var selectors, element;

		if ( 'cart' === context ) {
			// Cart block selectors (try multiple for compatibility with different WooCommerce versions).
			selectors = [
				{ sel: '.wc-block-cart__main .wc-block-cart-items', pos: 'after' },
				{ sel: '.wp-block-woocommerce-cart-line-items-block', pos: 'after' },
				{ sel: '.wc-block-cart__main', pos: 'append' },
				{ sel: '.wp-block-woocommerce-cart .wc-block-cart', pos: 'append' },
				{ sel: '.wc-block-cart', pos: 'append' }
			];
		} else {
			// Checkout block selectors.
			selectors = [
				{ sel: '.wc-block-checkout__sidebar .wc-block-components-sidebar', pos: 'append' },
				{ sel: '.wp-block-woocommerce-checkout-order-summary-block', pos: 'append' },
				{ sel: '.wc-block-components-order-summary', pos: 'after' },
				{ sel: '.wp-block-woocommerce-checkout-order-summary-cart-items-block', pos: 'after' },
				{ sel: '.wc-block-checkout__sidebar', pos: 'append' },
				{ sel: '.wc-block-checkout', pos: 'append' }
			];
		}

		for ( var i = 0; i < selectors.length; i++ ) {
			element = document.querySelector( selectors[i].sel );
			if ( element ) {
				return {
					element: element,
					position: selectors[i].pos
				};
			}
		}

		return null;
	}

	/**
	 * Insert groups element into the page.
	 *
	 * @param {HTMLElement} groupsElement Groups element to insert.
	 * @param {Object} insertionPoint Insertion point object.
	 */
	function insertGroupsElement( groupsElement, insertionPoint ) {
		var element = insertionPoint.element;
		var position = insertionPoint.position;

		try {
			switch ( position ) {
				case 'before':
					element.parentNode.insertBefore( groupsElement, element );
					break;
				case 'after':
					if ( element.nextSibling ) {
						element.parentNode.insertBefore( groupsElement, element.nextSibling );
					} else {
						element.parentNode.appendChild( groupsElement );
					}
					break;
				case 'prepend':
					element.insertBefore( groupsElement, element.firstChild );
					break;
				case 'append':
					element.appendChild( groupsElement );
					break;
			}
		} catch ( e ) {
			// DOM manipulation failed, will retry later.
		}
	}

	/**
	 * Remove existing groups element.
	 *
	 * @param {string} context Page context.
	 */
	function removeExistingGroups( context ) {
		var existing = document.getElementById( 'wmbf-shipping-groups-' + context );
		if ( existing && existing.parentNode ) {
			existing.parentNode.removeChild( existing );
		}
		// Also remove fallback if exists.
		var fallback = document.getElementById( 'wmbf-shipping-groups-fallback-' + context );
		if ( fallback && fallback.parentNode ) {
			fallback.parentNode.removeChild( fallback );
		}
	}

	/**
	 * Check if groups should be displayed.
	 *
	 * @param {Object} data Groups data.
	 * @return {boolean}
	 */
	function shouldDisplayGroups( data ) {
		if ( ! data || ! data.groups || data.groups.length === 0 || ! data.html ) {
			return false;
		}

		// Only when the cart has backordered items, like the classic cart.
		return !! data.has_backorder;
	}

	/**
	 * Render shipping groups on the page using provided data.
	 *
	 * @param {string} context Page context.
	 * @param {Object} data Groups data.
	 */
	function renderGroupsWithData( context, data ) {
		// Prevent concurrent rendering.
		if ( isRendering ) {
			return;
		}

		// Check if should display.
		if ( ! shouldDisplayGroups( data ) ) {
			removeExistingGroups( context );
			groupsInserted[ context ] = false;
			return;
		}

		// Find insertion point.
		var insertionPoint = findInsertionPoint( context );
		if ( ! insertionPoint ) {
			return;
		}

		isRendering = true;

		// Remove any existing groups first.
		removeExistingGroups( context );

		// Create and insert groups element.
		var groupsElement = createGroupsElement( data, context );
		insertGroupsElement( groupsElement, insertionPoint );
		groupsInserted[ context ] = true;

		isRendering = false;
	}

	/**
	 * Fetch groups data from REST API.
	 *
	 * @param {Function} callback Callback function.
	 */
	function fetchGroupsData( callback ) {
		// Use wp.apiFetch if available.
		if ( window.wp && window.wp.apiFetch ) {
			window.wp.apiFetch( {
				path: 'wmbf/v1/cart-groups',
				method: 'GET'
			} ).then( function( data ) {
				callback( null, data );
			} ).catch( function( error ) {
				callback( error, null );
			} );
			return;
		}

		// Fallback to XMLHttpRequest.
		var xhr = new XMLHttpRequest();
		xhr.open( 'GET', wmbfBlocksData.restUrl + 'cart-groups', true );
		xhr.setRequestHeader( 'X-WP-Nonce', wmbfBlocksData.nonce );
		xhr.onreadystatechange = function() {
			if ( xhr.readyState === 4 ) {
				if ( xhr.status === 200 ) {
					try {
						var data = JSON.parse( xhr.responseText );
						callback( null, data );
					} catch ( e ) {
						callback( e, null );
					}
				} else {
					callback( new Error( 'Request failed: ' + xhr.status ), null );
				}
			}
		};
		xhr.send();
	}

	/**
	 * Render shipping groups on the page.
	 *
	 * @param {string} context Page context.
	 * @param {boolean} forceRefresh Force refresh from API.
	 */
	function renderGroups( context, forceRefresh ) {
		// If element exists and visible, don't re-render unless forcing.
		if ( ! forceRefresh && groupsElementExists( context ) ) {
			groupsInserted[ context ] = true;
			return;
		}

		// Use inline data on initial load if not forcing refresh.
		if ( ! forceRefresh && wmbfBlocksData.inlineData && wmbfBlocksData.inlineData.groups ) {
			renderGroupsWithData( context, wmbfBlocksData.inlineData );
			return;
		}

		// Find insertion point first to avoid unnecessary API call.
		var insertionPoint = findInsertionPoint( context );
		if ( ! insertionPoint ) {
			return;
		}

		// Fetch fresh data from API.
		fetchGroupsData( function( error, data ) {
			if ( error ) {
				return;
			}
			renderGroupsWithData( context, data );
		} );
	}

	/**
	 * Ensure groups are displayed (re-check and render if needed).
	 *
	 * @param {string} context Page context.
	 */
	function ensureGroupsDisplayed( context ) {
		// If groups exist and visible, we're good.
		if ( groupsElementExists( context ) ) {
			return;
		}

		// Otherwise try to render.
		groupsInserted[ context ] = false;
		renderGroups( context, false );
	}

	/**
	 * Initialize blocks integration.
	 */
	function init() {
		var context = getPageContext();
		if ( ! context ) {
			return;
		}

		// Initial render attempt.
		renderGroups( context, false );

		// Set up a periodic check to ensure groups remain visible.
		// This handles React re-renders that might remove our element.
		var checkInterval = setInterval( function() {
			ensureGroupsDisplayed( context );
		}, 1000 );

		// Stop checking after 30 seconds.
		setTimeout( function() {
			clearInterval( checkInterval );
		}, 30000 );

		// Use MutationObserver only for cart updates (quantity changes, item removal).
		var lastCartCount = document.querySelectorAll( '.wc-block-cart-items__row, .wc-block-components-order-summary-item' ).length;

		var observer = new MutationObserver( function() {
			// Check if cart item count changed (actual cart update, not just React re-render).
			var currentCartCount = document.querySelectorAll( '.wc-block-cart-items__row, .wc-block-components-order-summary-item' ).length;

			if ( currentCartCount !== lastCartCount ) {
				lastCartCount = currentCartCount;

				// Debounce API refresh.
				clearTimeout( window.wmbfCartUpdateTimeout );
				window.wmbfCartUpdateTimeout = setTimeout( function() {
					renderGroups( context, true );
				}, 800 );
			} else {
				// Just ensure groups are displayed (handles React re-renders).
				ensureGroupsDisplayed( context );
			}
		});

		// Start observing.
		var targetNode = document.querySelector( '.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout, .wc-block-cart, .wc-block-checkout' );
		if ( targetNode ) {
			observer.observe( targetNode, {
				childList: true,
				subtree: true
			});
		}

		// Listen for WooCommerce store cart changes if wp.data is available.
		if ( window.wp && window.wp.data ) {
			try {
				var previousCartTotal = null;
				window.wp.data.subscribe( function() {
					try {
						var cartStore = window.wp.data.select( 'wc/store/cart' );
						if ( cartStore ) {
							var cartData = cartStore.getCartData();
							var cartTotal = cartData ? cartData.itemsCount : null;

							// Only refresh if cart item count changed.
							if ( cartTotal !== null && cartTotal !== previousCartTotal ) {
								previousCartTotal = cartTotal;

								// Debounce re-render.
								clearTimeout( window.wmbfStoreUpdateTimeout );
								window.wmbfStoreUpdateTimeout = setTimeout( function() {
									renderGroups( context, true );
								}, 800 );
							}
						}
					} catch ( e ) {
						// Silently fail.
					}
				});
			} catch ( e ) {
				// Silently fail.
			}
		}

		// Retry rendering periodically for initial load.
		var retryCount = 0;
		var maxRetries = 20;
		var retryInterval = setInterval( function() {
			retryCount++;
			if ( retryCount >= maxRetries || groupsElementExists( context ) ) {
				clearInterval( retryInterval );
				return;
			}
			renderGroups( context, false );
		}, 300 );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	// Also initialize after window load.
	window.addEventListener( 'load', function() {
		setTimeout( function() {
			var context = getPageContext();
			if ( context ) {
				ensureGroupsDisplayed( context );
			}
		}, 500 );
	});

})();
