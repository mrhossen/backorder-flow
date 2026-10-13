=== Backorder Flow ===
Contributors: mrhossen
Tags: woocommerce, backorder, stock, inventory, shipping
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced custom backorder status management with intelligent quantity-based display, grouped cart shipping, and Elementor support.

== Description ==

**Take control of your backorder messaging with smart, quantity-aware displays and automatic cart grouping.**

Backorder Flow transforms how you communicate backorder information to customers. Unlike the default WooCommerce backorder message, this plugin provides intelligent, context-aware displays that only show when relevant - either when products are out of stock or when customers order more than available inventory.

The plugin's standout feature is automatic cart grouping that clearly separates in-stock items from backordered items, with support for multiple backorder statuses (e.g., "Ships in 7 days", "Ships in 14 days", "Pre-order"). Customers always know exactly what will ship immediately and what they're waiting for.

Perfect for stores with dropshipping, made-to-order products, pre-orders, or seasonal inventory fluctuations.

Backorder Flow is built and maintained by [WPMagician](https://wpmagician.com/).

= Key Features =

* **Intelligent Quantity-Based Display**: Backorder messages only appear when stock is 0 OR when customer's selected quantity exceeds available stock
* **Automatic Cart Grouping**: Cart and checkout pages automatically group items into "Ships Now" and backorder groups
* **Smart Stock Splitting**: If a customer orders 10 units but only 5 are in stock, automatically shows 5 in "Ships Now" and 5 in backorder group
* **Multiple Custom Statuses**: Create unlimited backorder statuses with custom names, descriptions, colors, and shipping priorities
* **Real-Time Updates**: JavaScript-powered quantity monitoring updates backorder displays instantly as customers change quantities or variations
* **Variable Product Support**: Assign different backorder statuses to individual product variations
* **Elementor Integration**: 4 custom Elementor widgets for complete design control
* **Shortcode Support**: Display backorder information anywhere using flexible shortcodes
* **Email Integration**: Backorder information automatically included in order confirmation emails
* **Order Details**: Shows backorder groups in admin order details and customer order pages
* **REST API**: Full REST API support for custom integrations and headless setups
* **Bulk Edit Support**: Update backorder status for multiple products at once
* **Import/Export**: Export and import backorder statuses as JSON for easy site migration
* **Gutenberg Compatible**: Works seamlessly with WooCommerce Blocks (cart and checkout)
* **HPOS Ready**: Fully compatible with WooCommerce High-Performance Order Storage

= Cart & Checkout Grouping (Core Feature) =

The plugin's most powerful feature is automatic cart grouping. When customers have items with different shipping statuses:

**Group 1: Ships Now**

* Product A x 5 (has 5 in stock, user ordered 10)
* Product B x 10 (has 20 in stock, user ordered 10)

**Group 2: Ships in 10 days**

* Product A x 5 (backorder portion)

**Group 3: Ships in 15 days**

* Product C x 7 (backorder portion)

= Elementor Widgets =

1. **Product Backorder Status**: Display backorder status on single product pages
2. **Cart Backorder Groups**: Show grouped cart items by shipping status
3. **Backorder Products**: Display products filtered by backorder status
4. **Backorder Badge**: Customizable badge showing product backorder status

= Shortcodes =

* `[wmbf_backorder_status]` - Display current product's backorder status
* `[wmbf_backorder_groups]` - Display cart grouped by backorder status
* `[wmbf_backorder_products status="status-id"]` - Display products with specific backorder status
* `[wmbf_backorder_badge product_id="123"]` - Display backorder badge for specific product

= Developer Friendly =

* Well-documented code following WordPress coding standards
* Hooks and filters for customization
* Template override support
* REST API endpoints
* HPOS (High-Performance Order Storage) compatible

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/backorder-flow/` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WooCommerce > Settings > Backorder Status to configure
4. Create your custom backorder statuses
5. Assign backorder statuses to products when editing products
6. Enable backorders for products in the Inventory tab

== Frequently Asked Questions ==

= When does the backorder message display? =

The backorder message intelligently displays in two scenarios:

1. When a product's stock is 0 or less (out of stock)
2. When a customer selects a quantity that exceeds available stock

For example, if Product A has 5 in stock and backorders allowed, the message won't show until the customer enters a quantity of 6 or higher, or until stock reaches 0.

= Does this work with variable products? =

Yes! You can assign different backorder statuses to individual variations. The backorder message updates in real-time as customers select different variations.

= How does the real-time quantity monitoring work? =

The plugin uses JavaScript to monitor quantity input changes. When a customer changes the quantity field, the backorder message instantly appears or disappears based on available stock. No page reload required.

= Can I use this with Elementor? =

Absolutely! The plugin includes 4 custom Elementor widgets specifically designed for displaying backorder information with full design control.

= How does the cart grouping work? =

The plugin automatically calculates available stock vs. ordered quantity for each product. It then groups items into "Ships Now" for in-stock portions and separate groups for each backorder status. If a customer orders 10 units but only 5 are available, 5 will show in "Ships Now" and 5 in the backorder group.

= Does this work with WooCommerce Blocks (Gutenberg)? =

Yes! The plugin is fully compatible with both traditional WooCommerce pages and the new WooCommerce Blocks for cart and checkout.

= Can I customize the appearance? =

Yes! Each backorder status has a custom color. You can also override the plugin's templates by copying them to your theme, and use CSS to further customize the appearance.

= Does this work with HPOS (High-Performance Order Storage)? =

Yes, the plugin is fully compatible with WooCommerce High-Performance Order Storage (HPOS).

= Can I export my backorder statuses? =

Yes! You can export all your backorder statuses as JSON and import them on another site, making it easy to migrate settings between staging and production environments.

= Will this slow down my site? =

No. The plugin uses efficient JavaScript that only runs on product pages when needed. Cart grouping calculations are done server-side with optimized queries.

== Screenshots ==

1. Admin settings page - Create and manage backorder statuses
2. Product edit page - Assign backorder status to products
3. Cart page - Items grouped by shipping status
4. Elementor widget - Product Backorder Status
5. Order details - Backorder shipping groups
6. Email notification - Backorder information

== Changelog ==

= 1.0.0 - 2024-12-09 =
Initial Release

* Intelligent quantity-based backorder message display
* Automatic cart grouping by shipping status ("Ships Now" vs backorder groups)
* Smart stock splitting for partial backorders
* Real-time JavaScript quantity monitoring - updates instantly without page reload
* Full variable product support with per-variation backorder statuses
* Create unlimited custom backorder statuses with names, descriptions, colors, and priorities
* 4 custom Elementor widgets for complete design control
* Flexible shortcode system for displaying backorder information anywhere
* Automatic email integration - backorder info in order confirmation emails
* Order details integration - shows backorder groups in admin and customer views
* Complete REST API support for custom integrations
* Bulk edit support for updating multiple products
* Import/Export backorder statuses as JSON
* Full WooCommerce Blocks (Gutenberg) compatibility
* HPOS (High-Performance Order Storage) compatible
* Template override system for custom designs
* WordPress.org security standards compliance
* Responsive design for all devices
* Translation ready with .pot file included

== Upgrade Notice ==

= 1.0.0 =
Initial release of Backorder Flow.

== Template Override ==

To override templates, copy the template files from:
`wp-content/plugins/backorder-flow/templates/`

To:
`wp-content/themes/your-theme/backorder-flow/`

Available templates:

* cart-groups.php
* single-product-status.php
* email-backorder-info.php
* order-details-backorder.php

== Hooks & Filters ==

**Filters:**

* `wmbf_ships_now_label` - Customize "Ships Now" label
* `wmbf_ships_now_description` - Customize "Ships Now" description
* `wmbf_ships_now_color` - Customize "Ships Now" color
* `wmbf_default_backorder_label` - Customize the "On Backorder" group label (items without a custom status)
* `wmbf_default_backorder_description` - Customize the "On Backorder" group description
* `wmbf_default_backorder_color` - Customize the "On Backorder" group color
* `wmbf_cart_groups` - Modify cart groups data
* `wmbf_order_groups` - Modify order groups data (order pages and emails)
* `wmbf_locate_template` - Change the template file used

**Actions:**

* `wmbf_loaded` - Fires when plugin is fully loaded
