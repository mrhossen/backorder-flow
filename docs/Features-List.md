# Backorder Flow – Features List

Backorder Flow (by WPMagician) replaces WooCommerce's generic "Available on backorder" text with custom, quantity-aware backorder statuses, and splits the cart, checkout, orders and emails into shipping groups ("Ships Now" plus one group per backorder status).

Every feature below was tested end to end on **WordPress 7.1.3, WooCommerce 11.2.1, Elementor 4.0.8 and PHP 8.3** (classic theme, classic cart/checkout, block cart/checkout, posts and HPOS order storage). See [Test coverage](#test-coverage) at the end.

---

## 1. Backorder statuses (admin)

Location: **WooCommerce → Settings → Backorder Status**.

| Feature | Details |
| --- | --- |
| Unlimited custom statuses | Each status has a name, customer-facing description, color, priority and an enabled switch. Two statuses are created on activation ("Ships in 10 days", "Ships in 15 days"). |
| Add / edit in a modal | Saved over AJAX. The edit modal shows the status ID (click to select) for use in shortcodes and widgets. |
| Enable / disable | A disabled status is hidden everywhere: product pages, cart groups, shortcodes, widgets and new orders. Products using it fall back to the default backorder message. |
| Delete | With confirmation. |
| Drag-and-drop ordering | Dragging rows rewrites the priorities. Lower priority groups are shown first in the cart. |
| Default backorder message | Shown as the stock text for products that are on backorder but have no custom status, and used as the description of the "On Backorder" cart group. |
| Export / import | Export downloads all statuses as JSON. Import replaces the statuses from a JSON file; each field is sanitized and entries without a name are skipped. |
| Shortcode reference | The settings page lists every shortcode with its usage. |

All admin actions check the `manage_woocommerce` capability and a nonce.

## 2. Assigning statuses to products

| Feature | Details |
| --- | --- |
| Simple products | "Backorder Status" field in **Product data → Inventory**. It appears when stock is managed with backorders allowed, or when stock is not managed and the stock status is "On backorder". |
| Variable products | The parent product's status applies to every variation that has no status of its own. |
| Per-variation status | Each variation has its own "Backorder Status" select (default: "Same as parent product"), saved with WooCommerce's "Save changes" button. |
| Quick Edit | Set or clear the status from the products list. |
| Bulk Edit | Set or clear the status on many products at once ("— No Change —", "Default backorder message", or any enabled status). |
| Products list column | "Backorder Status" column, shown in the status color. |

## 3. Product page display

| Feature | Details |
| --- | --- |
| Status badge | Colored badge with the status name and description, shown above the add-to-cart form. |
| Quantity-aware | The badge appears only when stock is 0 or less, or when the selected quantity is more than the stock. It updates live as the quantity changes (typing, arrows, +/- buttons). |
| Variable products | The badge follows the selected variation (its own status or the parent's) and its stock. |
| Products without stock management | Products set to "On backorder" show their status badge. |
| Replaces WooCommerce's text | When the badge applies, WooCommerce's "Available on backorder" text is hidden. In-stock text such as "10 in stock" is kept while the product still has stock. |
| Default message | Backordered products without a custom status show the default backorder message as their stock text. |
| No duplicates | When a shortcode or Elementor widget already shows the current product's status, the automatic badge is not shown. |

## 4. Cart and checkout shipping groups

| Feature | Details |
| --- | --- |
| Automatic grouping | Cart items are grouped into **Ships Now**, one group per backorder status, and **On Backorder** for backordered items without a custom status. |
| Stock splitting | A line that is partly in stock is split, e.g. 12 ordered with 10 in stock shows 10 under "Ships Now" and 2 under the backorder group, each marked "(10 of 12 total)". |
| Prices per group | Each part shows its share of the line total (including tax when the store displays prices with tax). |
| Variation details | Variation attributes are listed under the product name. |
| Only when relevant | Groups are only shown when the cart has backordered items. |
| Classic cart | Shown below the cart table, inside the cart form, so it refreshes when the cart is updated. |
| Classic checkout | Shown before the payment methods and refreshed with the order review. |
| Block cart and checkout | Groups are inserted into the WooCommerce Cart and Checkout blocks and refresh after quantity changes and item removal (through the plugin's REST endpoint). A server-rendered fallback is included. |
| Group order | Ships Now first, then statuses by priority, then On Backorder. |

## 5. Orders and emails

| Feature | Details |
| --- | --- |
| Saved at checkout | Each order line stores its in-stock quantity, backorder quantity and the status ID and name at the time of purchase (classic and block checkout). |
| Stable history | Order pages and emails use the saved status name, so renaming or deleting a status later does not change past orders. |
| Customer order page | "Shipping Information" section on the order received page and My Account → Orders. |
| Admin order screen | "Backorder Shipping Groups" panel on the order edit screen. |
| Orders list column | "Backorder" column with a clock icon for orders that contain backordered items (posts and HPOS order tables). |
| Order emails | "Shipping Groups" section in order emails (HTML and plain text), plus the status under each backordered item, e.g. "Ships in 10 days – 1 item(s)". |
| HPOS | Declared compatible with High-Performance Order Storage and tested with it on and off. |

## 6. Shortcodes

| Shortcode | Attributes | Output |
| --- | --- | --- |
| `[wmbf_backorder_status]` | `product_id` (default: current post), `style` = `badge` \| `text` \| `full` | The product's backorder status. Nothing is shown for products that cannot be backordered. |
| `[wmbf_backorder_badge]` | `product_id` (default: current post) | The product's status as a badge. |
| `[wmbf_backorder_products]` | `status` (required, status ID), `limit` (10), `columns` (4), `orderby` (date), `order` (DESC) | Published products assigned to that status, using the theme's product loop. |
| `[wmbf_backorder_groups]` | `layout` = `table` \| `list` \| `cards`, `show_thumbnails` (yes), `show_prices` (yes) | The current cart grouped by shipping group. |

## 7. Elementor integration

Requires Elementor. Widgets appear in the **WPMagician** category.

| Widget | Main options |
| --- | --- |
| Product Backorder Status | Display style, icon, show description, show stock quantity; typography, colors, container style. |
| Backorder Badge | Product ID (or current product), badge style (flat, rounded, pill, custom), size, icon, hover animation; colors, typography, border, shadow. |
| Cart Backorder Groups | Layout (table, list, cards), show thumbnails, prices and quantities; header, item and column styling. |
| Backorder Products | Status, products per page, order by, order, layout (grid, list, carousel), columns, show image, title, price, add to cart and badge. |

Also included: a **Product Backorder Status** dynamic tag (text) that outputs the current product's status name.

## 8. REST API

Namespace `wmbf/v1`. All routes are read-only (GET) and public.

| Route | Returns |
| --- | --- |
| `/statuses` | All statuses. |
| `/statuses/{id}` | One status, or 404. |
| `/products/{id}/backorder-status` | The product's status plus `accepts_backorders`, `on_backorder` and `stock_quantity`, or 404 for unknown or unpublished products. |
| `/products/by-status/{status_id}` | Published products with that status. `per_page` (max 100) and `page`. |
| `/cart/groups` | The current visitor's cart groups (simplified). |
| `/cart-groups` | The current visitor's cart groups formatted for the cart/checkout blocks. |

## 9. Developer features

**Template overrides.** Copy any file from `templates/` to `yourtheme/backorder-flow/` to override it:
`cart-groups.php`, `single-product-status.php`, `email-backorder-info.php`, `order-details-backorder.php`.

**Filters**

| Filter | Purpose |
| --- | --- |
| `wmbf_ships_now_label`, `wmbf_ships_now_description`, `wmbf_ships_now_color` | Customize the "Ships Now" group. |
| `wmbf_default_backorder_label`, `wmbf_default_backorder_description`, `wmbf_default_backorder_color` | Customize the "On Backorder" group for items without a custom status. |
| `wmbf_cart_groups` | Modify the cart groups. |
| `wmbf_order_groups` | Modify the order groups (order pages and emails). |
| `wmbf_locate_template` | Change which template file is used. |

**Actions:** `wmbf_loaded` fires when the plugin has loaded.

**Stored data:** options `wmbf_statuses`, `wmbf_default_message`, `wmbf_version`; product/variation meta `_wmbf_backorder_status`; order item meta `_wmbf_instock_quantity`, `_wmbf_backorder_quantity`, `_wmbf_backorder_status_id`, `_wmbf_backorder_status_name`.

**Other:** translation ready (text domain `backorder-flow`); admin scripts load only on the WooCommerce settings screen; frontend output is escaped, including badges built in JavaScript.

---

## Test coverage

Tested in a fresh install with a classic theme (Twenty Twenty-One) and these products: in stock with backorders allowed, out of stock with a status, backorders disabled, out of stock without a status, stock not managed and set to "On backorder", and a variable product with one variation using its own status and one using the parent's.

| Area | Checks | Result |
| --- | --- | --- |
| Product pages (quantity-aware badge, variations, default message, disabled status) | 17 | Pass |
| Classic cart, checkout, order, emails, REST API, shortcodes, theme override | 33 | Pass |
| Admin settings (add, edit, disable, default message, reorder, export, import, delete) | 10 | Pass |
| Product admin (field visibility, simple, variation, quick edit, bulk edit) | 12 | Pass |
| Admin order screen and orders list (posts and HPOS) | 4 | Pass |
| Block cart and checkout (groups, refresh on change, order meta) | 8 | Pass |
| Elementor (registration, 4 widgets, dynamic tag) | 17 | Pass |
