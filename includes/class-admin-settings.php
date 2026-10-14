<?php
/**
 * Admin Settings
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Admin_Settings class.
 */
class WMBF_Admin_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Admin_Settings
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMBF_Admin_Settings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_backorder_status', array( $this, 'output_settings' ) );
		add_action( 'woocommerce_settings_save_backorder_status', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_wmbf_save_default_message', array( $this, 'ajax_save_default_message' ) );
		add_action( 'wp_ajax_wmbf_add_status', array( $this, 'ajax_add_status' ) );
		add_action( 'wp_ajax_wmbf_delete_status', array( $this, 'ajax_delete_status' ) );
		add_action( 'wp_ajax_wmbf_update_status_order', array( $this, 'ajax_update_status_order' ) );
		add_action( 'wp_ajax_wmbf_export_statuses', array( $this, 'ajax_export_statuses' ) );
		add_action( 'wp_ajax_wmbf_import_statuses', array( $this, 'ajax_import_statuses' ) );
		add_action( 'wp_ajax_wmbf_add_starter_statuses', array( $this, 'ajax_add_starter_statuses' ) );

		// Hide the save button for this tab.
		add_action( 'admin_footer', array( $this, 'hide_save_button' ) );
	}

	/**
	 * Add settings tab to WooCommerce settings.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs.
	 * @return array
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['backorder_status'] = __( 'Backorder Flow', 'backorder-flow' );
		return $settings_tabs;
	}

	/**
	 * URL of the plugin icon.
	 *
	 * @return string
	 */
	public static function icon_url() {
		return WMBF_ASSETS_URL . 'images/icon.svg';
	}

	/**
	 * Documentation URL shown in the admin.
	 *
	 * @return string
	 */
	public static function docs_url() {
		return apply_filters( 'wmbf_docs_url', 'https://wpmagician.com/plugins/backorder-flow/' );
	}

	/**
	 * Support URL shown in the admin.
	 *
	 * @return string
	 */
	public static function support_url() {
		return apply_filters( 'wmbf_support_url', 'https://wpmagician.com/' );
	}

	/**
	 * Print (or return) an inline SVG icon used in the admin screens.
	 *
	 * @param string $name   Icon name.
	 * @param bool   $return Return the markup instead of printing it.
	 * @return string|void
	 */
	public static function icon( $name, $return = false ) {
		$paths = array(
			'tag'    => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.3"/>',
			'msg'    => '<path d="M4 5h16v11H9l-5 4z"/>',
			'blocks' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><path d="M16.5 13v7M13 16.5h7"/>',
			'arrows' => '<path d="M7 4v16M3.5 7.5L7 4l3.5 3.5M17 20V4M13.5 16.5L17 20l3.5-3.5"/>',
			'plus'   => '<path d="M12 5v14M5 12h14"/>',
			'book'   => '<path d="M5 4.5A1.5 1.5 0 0 1 6.5 3H19v15H6.5A1.5 1.5 0 0 0 5 19.5z"/><path d="M5 19.5A1.5 1.5 0 0 0 6.5 21H19"/>',
			'help'   => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5v.4M12 17h.01"/>',
			'store'  => '<path d="M4 9.5V20h16V9.5M3 9.5l1.5-5.5h15L21 9.5a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0"/>',
			'box'    => '<path d="M3.5 7.5L12 3l8.5 4.5v9L12 21l-8.5-4.5z"/><path d="M3.5 7.5L12 12l8.5-4.5M12 12v9"/>',
			'cart'   => '<path d="M3 4h2l2.4 11h10.2L20 8H6.2"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>',
			'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5L12 13l8.5-6.5"/>',
			'receipt'=> '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
			'down'   => '<path d="M12 4v11M8 11l4 4 4-4M5 20h14"/>',
			'up'     => '<path d="M12 16V5M8 9l4-4 4 4M5 20h14"/>',
			'copy'   => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/>',
			'alert'  => '<path d="M12 3l9.5 17h-19z"/><path d="M12 10v4M12 17h.01"/>',
			'info'   => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
			'ext'    => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
			'x'      => '<path d="M6 6l12 12M18 6L6 18"/>',
			'check'  => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
			'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
		);

		if ( ! isset( $paths[ $name ] ) ) {
			return $return ? '' : null;
		}

		// Static markup from the list above.
		$svg = '<svg class="wmbf-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
		if ( $return ) {
			return $svg;
		}
		echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Count the products using each status.
	 *
	 * A product counts once per status, whether the status is set on the product or on one of its variations.
	 *
	 * @return array Status ID => product count.
	 */
	public function get_status_product_counts() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One aggregate query for the settings screen.
		$rows = $wpdb->get_results(
			"SELECT pm.meta_value AS status_id, COUNT( DISTINCT IF( p.post_type = 'product_variation', p.post_parent, p.ID ) ) AS total
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_wmbf_backorder_status'
				AND pm.meta_value <> ''
				AND p.post_type IN ( 'product', 'product_variation' )
				AND p.post_status NOT IN ( 'trash', 'auto-draft' )
			GROUP BY pm.meta_value"
		);

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ $row->status_id ] = (int) $row->total;
		}

		return $counts;
	}

	/**
	 * Statuses prepared for the settings screen script.
	 *
	 * @return array
	 */
	private function get_statuses_for_js() {
		$counts = $this->get_status_product_counts();
		$list   = array();

		foreach ( $this->get_statuses() as $status ) {
			$id     = isset( $status['id'] ) ? (string) $status['id'] : '';
			$list[] = array(
				'id'          => $id,
				'name'        => isset( $status['name'] ) ? $status['name'] : '',
				'description' => isset( $status['description'] ) ? $status['description'] : '',
				'color'       => ! empty( $status['color'] ) ? $status['color'] : '#ff9800',
				'priority'    => isset( $status['priority'] ) ? (int) $status['priority'] : 1,
				'enabled'     => ! empty( $status['enabled'] ),
				'products'    => isset( $counts[ $id ] ) ? $counts[ $id ] : 0,
			);
		}

		return $list;
	}

	/**
	 * Output settings page.
	 */
	public function output_settings() {
		$default_message = WMBF_Frontend_Display::get_default_message();
		$icon            = self::icon_url();
		$statuses        = $this->get_statuses_for_js();

		$data = array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'wmbf_admin_nonce' ),
			'statuses'       => $statuses,
			'defaultMessage' => $default_message,
			'iconUrl'        => $icon,
			'palette'        => array( '#16CF9E', '#702BB2', '#A54CEB', '#E8912D', '#E0475B', '#2F7DE1', '#5B5470' ),
			'i18n'           => array(
				'addStatus'        => __( 'Add status', 'backorder-flow' ),
				'editStatus'       => __( 'Edit status', 'backorder-flow' ),
				'addSub'           => __( 'Create a shipping promise you can assign to products.', 'backorder-flow' ),
				/* translators: %d: number of products (always 1) */
				'usedOnOne'        => __( 'Used on %d product. Changes show on the store right away.', 'backorder-flow' ),
				/* translators: %d: number of products */
				'usedOnMany'       => __( 'Used on %d products. Changes show on the store right away.', 'backorder-flow' ),
				'notUsed'          => __( 'Not assigned to any products yet.', 'backorder-flow' ),
				'saveChanges'      => __( 'Save changes', 'backorder-flow' ),
				'saving'           => __( 'Saving…', 'backorder-flow' ),
				'statusName'       => __( 'Status name', 'backorder-flow' ),
				/* translators: %s: status name */
				'saved'            => __( 'Saved “%s”', 'backorder-flow' ),
				/* translators: %s: status name */
				'added'            => __( 'Added “%s”. Assign it to products from the product editor.', 'backorder-flow' ),
				/* translators: %s: status name */
				'turnedOn'         => __( '“%s” is live on the store', 'backorder-flow' ),
				/* translators: %s: status name */
				'turnedOff'        => __( '“%s” is hidden. Its products show the default message.', 'backorder-flow' ),
				/* translators: %s: status name */
				'deleteTitle'      => __( 'Delete “%s”?', 'backorder-flow' ),
				/* translators: %d: number of products (always 1) */
				'deleteUsedOne'    => __( '%d product uses this status. It will show the default backorder message instead.', 'backorder-flow' ),
				/* translators: %d: number of products */
				'deleteUsedMany'   => __( '%d products use this status. They will show the default backorder message instead.', 'backorder-flow' ),
				'deleteUnused'     => __( 'No products use this status, so nothing changes on the store.', 'backorder-flow' ),
				'deleteBtn'        => __( 'Delete status', 'backorder-flow' ),
				'cancel'           => __( 'Cancel', 'backorder-flow' ),
				/* translators: %s: status name */
				'deleted'          => __( 'Deleted “%s”', 'backorder-flow' ),
				/* translators: %s: status name */
				'restored'         => __( 'Restored “%s”', 'backorder-flow' ),
				'undo'             => __( 'Undo', 'backorder-flow' ),
				'orderSaved'       => __( 'Order saved. The cart groups follow this order.', 'backorder-flow' ),
				/* translators: 1: status name, 2: position number */
				'moved'            => __( 'Moved “%1$s” to position %2$d', 'backorder-flow' ),
				'copied'           => __( 'Copied to clipboard', 'backorder-flow' ),
				'idCopied'         => __( 'Status ID copied', 'backorder-flow' ),
				'shortcodeCopied'  => __( 'Shortcode copied', 'backorder-flow' ),
				'copyFailed'       => __( 'Select the text and press Ctrl+C to copy', 'backorder-flow' ),
				'error'            => __( 'Something went wrong. Reload the page and try again.', 'backorder-flow' ),
				'statuses'         => __( 'statuses', 'backorder-flow' ),
				'live'             => __( 'live on the store', 'backorder-flow' ),
				'assigned'         => __( 'products assigned', 'backorder-flow' ),
				'colStatus'        => __( 'Status', 'backorder-flow' ),
				'colMessage'       => __( 'Message to customers', 'backorder-flow' ),
				'colProducts'      => __( 'Products', 'backorder-flow' ),
				'colLive'          => __( 'Live', 'backorder-flow' ),
				'colActions'       => __( 'Actions', 'backorder-flow' ),
				'product'          => __( 'product', 'backorder-flow' ),
				'products'         => __( 'products', 'backorder-flow' ),
				'noMessage'        => __( 'No message. Only the name is shown.', 'backorder-flow' ),
				'copyId'           => __( 'Copy status ID', 'backorder-flow' ),
				/* translators: 1: status name, 2: position, 3: total */
				'moveLabel'        => __( 'Move %1$s. Position %2$d of %3$d. Use the arrow keys to move.', 'backorder-flow' ),
				/* translators: %s: status name */
				'showLabel'        => __( 'Show %s on the store', 'backorder-flow' ),
				/* translators: %s: status name */
				'editLabel'        => __( 'Edit %s', 'backorder-flow' ),
				/* translators: %s: status name */
				'deleteLabel'      => __( 'Delete %s', 'backorder-flow' ),
				'footNote'         => __( 'Changes save automatically. Products without a status use the', 'backorder-flow' ),
				'defaultMessage'   => __( 'default message', 'backorder-flow' ),
				'emptyTitle'       => __( 'Create your first backorder status', 'backorder-flow' ),
				'emptyText'        => __( 'A status tells customers when a backordered item will ship, like "Ships in 2–3 weeks". Assign it to products and it shows up on product pages, in the cart and in emails.', 'backorder-flow' ),
				'starter'          => __( 'Add starter statuses', 'backorder-flow' ),
				'starterAdded'     => __( 'Added 3 starter statuses. Edit them to match your store.', 'backorder-flow' ),
				'starterNames'     => array( __( 'Ships in 1–2 weeks', 'backorder-flow' ), __( 'Pre-order', 'backorder-flow' ), __( 'Made to order', 'backorder-flow' ) ),
				'nameRequired'     => __( 'Give the status a name. Customers see it as the headline.', 'backorder-flow' ),
				'msgSaved'         => __( 'Default message saved', 'backorder-flow' ),
				'unsaved'          => __( 'You have unsaved changes', 'backorder-flow' ),
				'allSaved'         => __( 'All changes saved', 'backorder-flow' ),
				'hidden'           => __( '(hidden)', 'backorder-flow' ),
				'importNew'        => __( 'New', 'backorder-flow' ),
				'importReplaces'   => __( 'Replaces existing', 'backorder-flow' ),
				'importRemoved'    => __( 'Removed', 'backorder-flow' ),
				/* translators: %d: number of products */
				'importAffected'   => __( '%d products affected', 'backorder-flow' ),
				/* translators: %d: number of statuses */
				'importBtn'        => __( 'Replace with %d statuses', 'backorder-flow' ),
				/* translators: %d: number of statuses */
				'imported'         => __( 'Imported %d statuses', 'backorder-flow' ),
				/* translators: %s: file name */
				'importBadFile'    => __( '%s isn\'t a Backorder Flow export. Choose a .json file exported from this page.', 'backorder-flow' ),
				/* translators: %d: number of statuses */
				'exported'         => __( 'Downloaded %d statuses', 'backorder-flow' ),
				'untitled'         => __( 'Untitled', 'backorder-flow' ),
				'customColor'      => __( 'Custom color', 'backorder-flow' ),
				/* translators: %s: color hex code */
				'colorLabel'       => __( 'Color %s', 'backorder-flow' ),
			),
		);
		?>
		<div class="wmbf-admin wmbf-ui" id="wmbf-settings">
			<script type="application/json" id="wmbf-settings-data"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>

			<header class="wmbf-hero">
				<img src="<?php echo esc_url( $icon ); ?>" alt="" width="52" height="52">
				<div class="wmbf-hero-text">
					<h1><?php esc_html_e( 'Backorder Flow', 'backorder-flow' ); ?> <span class="wmbf-ver">v<?php echo esc_html( WMBF_VERSION ); ?></span></h1>
					<p><?php esc_html_e( 'Tell customers exactly when their backordered items will ship.', 'backorder-flow' ); ?></p>
					<div class="wmbf-by"><?php esc_html_e( 'by WPMagician', 'backorder-flow' ); ?></div>
				</div>
				<div class="wmbf-hero-links">
					<a class="wmbf-btn-glass" href="<?php echo esc_url( self::docs_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php self::icon( 'book' ); ?><?php esc_html_e( 'Documentation', 'backorder-flow' ); ?></a>
					<a class="wmbf-btn-glass" href="<?php echo esc_url( self::support_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php self::icon( 'help' ); ?><?php esc_html_e( 'Get support', 'backorder-flow' ); ?></a>
				</div>
			</header>

			<div class="wmbf-nav" role="tablist" aria-label="<?php esc_attr_e( 'Backorder Flow settings', 'backorder-flow' ); ?>">
				<button type="button" role="tab" id="wmbf-tab-statuses" aria-controls="wmbf-panel-statuses" data-tab="statuses" aria-selected="true"><?php self::icon( 'tag' ); ?><?php esc_html_e( 'Statuses', 'backorder-flow' ); ?> <span class="wmbf-count" id="wmbf-nav-count"><?php echo (int) count( $statuses ); ?></span></button>
				<button type="button" role="tab" id="wmbf-tab-message" aria-controls="wmbf-panel-message" data-tab="message" aria-selected="false"><?php self::icon( 'msg' ); ?><?php esc_html_e( 'Default message', 'backorder-flow' ); ?></button>
				<button type="button" role="tab" id="wmbf-tab-shortcodes" aria-controls="wmbf-panel-shortcodes" data-tab="shortcodes" aria-selected="false"><?php self::icon( 'blocks' ); ?><?php esc_html_e( 'Shortcodes & widgets', 'backorder-flow' ); ?></button>
				<button type="button" role="tab" id="wmbf-tab-import" aria-controls="wmbf-panel-import" data-tab="import" aria-selected="false"><?php self::icon( 'arrows' ); ?><?php esc_html_e( 'Import / Export', 'backorder-flow' ); ?></button>
			</div>

			<?php
			$this->output_statuses_panel();
			$this->output_message_panel( $default_message );
			$this->output_shortcodes_panel();
			$this->output_import_panel();
			$this->output_drawer_and_dialog();
			?>
			<div class="wmbf-toasts" id="wmbf-toasts" aria-live="polite"></div>
		</div>
		<?php
	}

	/**
	 * Output the Statuses panel.
	 */
	private function output_statuses_panel() {
		?>
		<section class="wmbf-panel" id="wmbf-panel-statuses" role="tabpanel" aria-labelledby="wmbf-tab-statuses" data-panel="statuses">
			<div class="wmbf-grid">
				<div class="wmbf-card" id="wmbf-status-card">
					<div class="wmbf-card-h">
						<div>
							<h2><?php esc_html_e( 'Backorder statuses', 'backorder-flow' ); ?></h2>
							<p><?php esc_html_e( 'Each status is a shipping promise you can assign to products on backorder.', 'backorder-flow' ); ?></p>
						</div>
						<button type="button" class="wmbf-btn wmbf-btn-primary" data-act="add"><?php self::icon( 'plus' ); ?><?php esc_html_e( 'Add status', 'backorder-flow' ); ?></button>
					</div>
					<div id="wmbf-status-body"></div>
				</div>
				<aside class="wmbf-rail">
					<div class="wmbf-card wmbf-tip"><div class="wmbf-card-b">
						<h3><?php esc_html_e( 'How the order works', 'backorder-flow' ); ?></h3>
						<p><?php esc_html_e( 'Drag a status to move it. The top status ships first, so it leads the cart\'s shipping groups and order emails.', 'backorder-flow' ); ?></p>
					</div></div>
					<div class="wmbf-card"><div class="wmbf-card-b">
						<h3><?php esc_html_e( 'Where statuses show up', 'backorder-flow' ); ?></h3>
						<ul class="wmbf-where-list">
							<li><?php self::icon( 'store' ); ?><?php esc_html_e( 'Product pages', 'backorder-flow' ); ?></li>
							<li><?php self::icon( 'cart' ); ?><?php esc_html_e( 'Cart and checkout groups', 'backorder-flow' ); ?></li>
							<li><?php self::icon( 'mail' ); ?><?php esc_html_e( 'Order emails', 'backorder-flow' ); ?></li>
							<li><?php self::icon( 'receipt' ); ?><?php esc_html_e( 'My Account order details', 'backorder-flow' ); ?></li>
						</ul>
					</div></div>
					<div class="wmbf-card"><div class="wmbf-card-b">
						<h3><?php esc_html_e( 'Resources', 'backorder-flow' ); ?></h3>
						<ul class="wmbf-links">
							<li><a href="<?php echo esc_url( self::docs_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php self::icon( 'book' ); ?><?php esc_html_e( 'Setting up your first status', 'backorder-flow' ); ?></a></li>
							<li><a href="#shortcodes" data-goto="shortcodes"><?php self::icon( 'blocks' ); ?><?php esc_html_e( 'Shortcodes & widgets', 'backorder-flow' ); ?></a></li>
							<li><a href="<?php echo esc_url( self::support_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php self::icon( 'help' ); ?><?php esc_html_e( 'Contact WPMagician support', 'backorder-flow' ); ?></a></li>
						</ul>
					</div></div>
				</aside>
			</div>
		</section>
		<?php
	}

	/**
	 * Output the Default message panel.
	 *
	 * @param string $default_message Current default message.
	 */
	private function output_message_panel( $default_message ) {
		?>
		<section class="wmbf-panel" id="wmbf-panel-message" role="tabpanel" aria-labelledby="wmbf-tab-message" data-panel="message" hidden>
			<div class="wmbf-grid">
				<div class="wmbf-stack">
					<div class="wmbf-card">
						<div class="wmbf-card-h"><div>
							<h2><?php esc_html_e( 'Default backorder message', 'backorder-flow' ); ?></h2>
							<p><?php esc_html_e( 'Shown on any backordered product that has no status assigned.', 'backorder-flow' ); ?></p>
						</div></div>
						<div class="wmbf-card-b">
							<div class="wmbf-split">
								<div class="wmbf-field">
									<label class="wmbf-lbl" for="wmbf-default-message"><?php esc_html_e( 'Message', 'backorder-flow' ); ?> <span class="wmbf-meta" id="wmbf-default-count"></span></label>
									<textarea class="wmbf-inp" id="wmbf-default-message" rows="4" maxlength="200"><?php echo esc_textarea( $default_message ); ?></textarea>
									<p class="wmbf-help"><?php esc_html_e( 'Keep it short and specific. A ship-by window works better than "soon".', 'backorder-flow' ); ?></p>
									<div class="wmbf-err" id="wmbf-default-err" hidden><?php self::icon( 'alert' ); ?><?php esc_html_e( 'Add a message before saving. Customers see this text on every backordered product without a status.', 'backorder-flow' ); ?></div>
								</div>
								<div class="wmbf-preview">
									<div class="wmbf-preview-cap"><span><?php esc_html_e( 'Customer preview', 'backorder-flow' ); ?></span><span><?php esc_html_e( 'Product page', 'backorder-flow' ); ?></span></div>
									<div class="wmbf-fe-badge" style="--c:#8B8E94">
										<i></i>
										<div><strong><?php echo esc_html( apply_filters( 'wmbf_default_backorder_label', __( 'On Backorder', 'backorder-flow' ) ) ); ?></strong><span id="wmbf-default-preview"></span></div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="wmbf-card">
						<div class="wmbf-card-h"><div>
							<h2><?php esc_html_e( 'Where customers see backorder info', 'backorder-flow' ); ?></h2>
							<p><?php esc_html_e( 'Statuses and the default message appear in all of these places automatically.', 'backorder-flow' ); ?></p>
						</div></div>
						<div class="wmbf-card-b">
							<div class="wmbf-where">
								<div><?php self::icon( 'box' ); ?><strong><?php esc_html_e( 'Product page', 'backorder-flow' ); ?></strong><span><?php esc_html_e( 'Updates as the shopper changes quantity', 'backorder-flow' ); ?></span></div>
								<div><?php self::icon( 'cart' ); ?><strong><?php esc_html_e( 'Cart & checkout', 'backorder-flow' ); ?></strong><span><?php esc_html_e( 'Items grouped by when they ship', 'backorder-flow' ); ?></span></div>
								<div><?php self::icon( 'mail' ); ?><strong><?php esc_html_e( 'Order emails', 'backorder-flow' ); ?></strong><span><?php esc_html_e( 'Shipping groups in the confirmation', 'backorder-flow' ); ?></span></div>
								<div><?php self::icon( 'receipt' ); ?><strong><?php esc_html_e( 'My Account', 'backorder-flow' ); ?></strong><span><?php esc_html_e( 'Order details page', 'backorder-flow' ); ?></span></div>
							</div>
						</div>
					</div>
					<div class="wmbf-savebar is-clean" id="wmbf-savebar">
						<i class="wmbf-dot"></i><span id="wmbf-save-text"><?php esc_html_e( 'All changes saved', 'backorder-flow' ); ?></span>
						<button type="button" class="wmbf-btn wmbf-btn-ghost wmbf-btn-sm" id="wmbf-discard" hidden><?php esc_html_e( 'Discard', 'backorder-flow' ); ?></button>
						<button type="button" class="wmbf-btn wmbf-btn-mint wmbf-btn-sm" id="wmbf-save-message" disabled><?php esc_html_e( 'Save message', 'backorder-flow' ); ?></button>
					</div>
				</div>
				<aside class="wmbf-rail">
					<div class="wmbf-card wmbf-tip"><div class="wmbf-card-b">
						<h3><?php esc_html_e( 'Partial backorders', 'backorder-flow' ); ?></h3>
						<p><?php esc_html_e( 'If a shopper orders 3 and you have 1 in stock, Backorder Flow splits the line: 1 ships now, 2 follow with the product\'s status.', 'backorder-flow' ); ?></p>
					</div></div>
				</aside>
			</div>
		</section>
		<?php
	}

	/**
	 * Print one shortcode reference row.
	 *
	 * @param string $title       Title.
	 * @param string $description Description.
	 * @param string $where       Where to use it.
	 * @param string $code        Shortcode.
	 * @param array  $attrs       Attribute name => description.
	 */
	private function output_shortcode_row( $title, $description, $where, $code, $attrs = array() ) {
		?>
		<div class="wmbf-sc">
			<div class="wmbf-sc-top">
				<div><h3><?php echo esc_html( $title ); ?></h3><p><?php echo esc_html( $description ); ?></p></div>
				<span class="wmbf-tag"><?php echo esc_html( $where ); ?></span>
			</div>
			<div class="wmbf-code"><code><?php echo esc_html( $code ); ?></code><button type="button" data-copy="<?php echo esc_attr( $code ); ?>"><?php self::icon( 'copy' ); ?><?php esc_html_e( 'Copy', 'backorder-flow' ); ?></button></div>
			<?php if ( $attrs ) : ?>
				<div class="wmbf-attrs">
					<?php foreach ( $attrs as $name => $text ) : ?>
						<span><b><?php echo esc_html( $name ); ?></b> <?php echo esc_html( $text ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output the Shortcodes & widgets panel.
	 */
	private function output_shortcodes_panel() {
		?>
		<section class="wmbf-panel" id="wmbf-panel-shortcodes" role="tabpanel" aria-labelledby="wmbf-tab-shortcodes" data-panel="shortcodes" hidden>
			<div class="wmbf-grid">
				<div class="wmbf-stack">
					<div class="wmbf-card">
						<div class="wmbf-card-h"><div>
							<h2><?php esc_html_e( 'Shortcode builder', 'backorder-flow' ); ?></h2>
							<p><?php esc_html_e( 'Pick what to show and copy a ready-made shortcode. No IDs to hunt for.', 'backorder-flow' ); ?></p>
						</div></div>
						<div class="wmbf-card-b">
							<div class="wmbf-builder">
								<div class="wmbf-field">
									<label class="wmbf-lbl" for="wmbf-b-type"><?php esc_html_e( 'Show', 'backorder-flow' ); ?></label>
									<div class="wmbf-select"><select id="wmbf-b-type">
										<option value="products"><?php esc_html_e( 'Products with a status', 'backorder-flow' ); ?></option>
										<option value="badge"><?php esc_html_e( 'Badge for one product', 'backorder-flow' ); ?></option>
										<option value="status"><?php esc_html_e( 'Current product\'s status', 'backorder-flow' ); ?></option>
										<option value="groups"><?php esc_html_e( 'Cart shipping groups', 'backorder-flow' ); ?></option>
									</select></div>
								</div>
								<div class="wmbf-field" id="wmbf-b-status-wrap">
									<label class="wmbf-lbl" for="wmbf-b-status"><?php esc_html_e( 'Status', 'backorder-flow' ); ?></label>
									<div class="wmbf-select has-dot"><i class="wmbf-sel-dot" id="wmbf-b-dot"></i><select id="wmbf-b-status"></select></div>
								</div>
								<div class="wmbf-field" id="wmbf-b-product-wrap" hidden>
									<label class="wmbf-lbl" for="wmbf-b-product"><?php esc_html_e( 'Product ID', 'backorder-flow' ); ?></label>
									<input type="number" min="1" class="wmbf-inp" id="wmbf-b-product" placeholder="123">
								</div>
							</div>
							<div class="wmbf-code wmbf-code-out"><code id="wmbf-b-out"></code><button type="button" id="wmbf-b-copy"><?php self::icon( 'copy' ); ?><?php esc_html_e( 'Copy', 'backorder-flow' ); ?></button></div>
						</div>
					</div>
					<div class="wmbf-card">
						<div class="wmbf-card-h"><div>
							<h2><?php esc_html_e( 'All shortcodes', 'backorder-flow' ); ?></h2>
							<p><?php esc_html_e( 'Paste these into any page, post or widget area.', 'backorder-flow' ); ?></p>
						</div></div>
						<div class="wmbf-card-b wmbf-sc-list">
							<?php
							$this->output_shortcode_row(
								__( 'Product status', 'backorder-flow' ),
								__( 'The current product\'s backorder status. Use it on single product templates.', 'backorder-flow' ),
								__( 'Product page', 'backorder-flow' ),
								'[wmbf_backorder_status]',
								array(
									'product_id' => __( 'optional · defaults to the current product', 'backorder-flow' ),
									'style'      => __( 'badge, text or full', 'backorder-flow' ),
								)
							);
							$this->output_shortcode_row(
								__( 'Cart shipping groups', 'backorder-flow' ),
								__( 'Cart items grouped by when they ship.', 'backorder-flow' ),
								__( 'Cart & checkout', 'backorder-flow' ),
								'[wmbf_backorder_groups]',
								array(
									'layout'          => __( 'table, list or cards', 'backorder-flow' ),
									'show_thumbnails' => __( 'yes or no', 'backorder-flow' ),
									'show_prices'     => __( 'yes or no', 'backorder-flow' ),
								)
							);
							$this->output_shortcode_row(
								__( 'Products with a status', 'backorder-flow' ),
								__( 'A grid of every product using one status. Good for a "Coming soon" page.', 'backorder-flow' ),
								__( 'Any page', 'backorder-flow' ),
								'[wmbf_backorder_products status="status-id"]',
								array(
									'status'  => __( 'required · the status ID', 'backorder-flow' ),
									'limit'   => __( 'default 10', 'backorder-flow' ),
									'columns' => __( 'default 4', 'backorder-flow' ),
								)
							);
							$this->output_shortcode_row(
								__( 'Product badge', 'backorder-flow' ),
								__( 'A compact badge for one specific product.', 'backorder-flow' ),
								__( 'Any page', 'backorder-flow' ),
								'[wmbf_backorder_badge product_id="123"]',
								array(
									'product_id' => __( 'optional · defaults to the current product', 'backorder-flow' ),
								)
							);
							?>
						</div>
					</div>
				</div>
				<aside class="wmbf-rail">
					<div class="wmbf-card"><div class="wmbf-card-b">
						<h3><?php esc_html_e( 'Page builders', 'backorder-flow' ); ?></h3>
						<p><?php esc_html_e( 'Prefer drag and drop? Backorder Flow adds these to your builder.', 'backorder-flow' ); ?></p>
						<div class="wmbf-widget">
							<div><span class="wmbf-tag is-purple">Elementor</span><span class="wmbf-tag"><?php esc_html_e( '4 widgets', 'backorder-flow' ); ?></span></div>
							<span><?php esc_html_e( 'Product Backorder Status · Cart Backorder Groups · Backorder Products · Backorder Badge', 'backorder-flow' ); ?></span>
						</div>
						<div class="wmbf-widget">
							<div><span class="wmbf-tag is-purple">Elementor</span><span class="wmbf-tag"><?php esc_html_e( 'Dynamic tag', 'backorder-flow' ); ?></span></div>
							<span><?php esc_html_e( 'Insert a product\'s status into any text widget.', 'backorder-flow' ); ?></span>
						</div>
						<div class="wmbf-widget">
							<div><span class="wmbf-tag is-mint"><?php esc_html_e( 'Block checkout', 'backorder-flow' ); ?></span></div>
							<span><?php esc_html_e( 'Shipping groups appear in the WooCommerce Cart and Checkout blocks with no setup.', 'backorder-flow' ); ?></span>
						</div>
					</div></div>
				</aside>
			</div>
		</section>
		<?php
	}

	/**
	 * Output the Import / Export panel.
	 */
	private function output_import_panel() {
		?>
		<section class="wmbf-panel" id="wmbf-panel-import" role="tabpanel" aria-labelledby="wmbf-tab-import" data-panel="import" hidden>
			<div class="wmbf-ie">
				<div class="wmbf-card">
					<div class="wmbf-card-h"><div>
						<h2><?php esc_html_e( 'Export statuses', 'backorder-flow' ); ?></h2>
						<p><?php esc_html_e( 'Save your statuses as a JSON file to back them up or copy them to another store.', 'backorder-flow' ); ?></p>
					</div></div>
					<div class="wmbf-card-b wmbf-stack-sm">
						<pre class="wmbf-json" id="wmbf-json-out"></pre>
						<div class="wmbf-row">
							<button type="button" class="wmbf-btn wmbf-btn-primary" id="wmbf-export"><?php self::icon( 'down' ); ?><?php esc_html_e( 'Download JSON', 'backorder-flow' ); ?></button>
							<button type="button" class="wmbf-btn" data-copy-from="wmbf-json-out"><?php self::icon( 'copy' ); ?><?php esc_html_e( 'Copy to clipboard', 'backorder-flow' ); ?></button>
						</div>
					</div>
				</div>
				<div class="wmbf-card">
					<div class="wmbf-card-h"><div>
						<h2><?php esc_html_e( 'Import statuses', 'backorder-flow' ); ?></h2>
						<p><?php esc_html_e( 'Load a JSON file exported from Backorder Flow.', 'backorder-flow' ); ?></p>
					</div></div>
					<div class="wmbf-card-b wmbf-stack-sm">
						<label class="wmbf-drop" id="wmbf-drop" for="wmbf-import-file">
							<span class="wmbf-drop-ico"><?php self::icon( 'up' ); ?></span>
							<strong><?php esc_html_e( 'Drop a .json file here or click to browse', 'backorder-flow' ); ?></strong>
							<span><?php esc_html_e( 'Exported from Backorder Flow or Custom Backorder Pro', 'backorder-flow' ); ?></span>
						</label>
						<input type="file" id="wmbf-import-file" accept=".json,application/json" class="screen-reader-text">
						<div id="wmbf-import-review" hidden>
							<div class="wmbf-warn"><?php self::icon( 'alert' ); ?><div><b><?php esc_html_e( 'Importing replaces all current statuses.', 'backorder-flow' ); ?></b> <?php esc_html_e( 'Products using a status that isn\'t in the file will show the default message.', 'backorder-flow' ); ?></div></div>
							<div class="wmbf-diff" id="wmbf-diff"></div>
							<div class="wmbf-row">
								<button type="button" class="wmbf-btn wmbf-btn-primary" id="wmbf-import-go"></button>
								<button type="button" class="wmbf-btn" id="wmbf-import-cancel"><?php esc_html_e( 'Cancel', 'backorder-flow' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Output the status editor drawer and the confirm dialog.
	 */
	private function output_drawer_and_dialog() {
		?>
		<div class="wmbf-scrim" id="wmbf-scrim" hidden></div>
		<div class="wmbf-drawer" id="wmbf-drawer" role="dialog" aria-modal="true" aria-labelledby="wmbf-d-title" hidden>
			<div class="wmbf-drawer-h">
				<div><h2 id="wmbf-d-title"></h2><p id="wmbf-d-sub"></p></div>
				<button type="button" class="wmbf-icon-btn" data-close="drawer" aria-label="<?php esc_attr_e( 'Close', 'backorder-flow' ); ?>"><?php self::icon( 'x' ); ?></button>
			</div>
			<div class="wmbf-drawer-b">
				<div class="wmbf-field">
					<label class="wmbf-lbl" for="wmbf-f-name"><?php esc_html_e( 'Status name', 'backorder-flow' ); ?> <span class="wmbf-req">*</span></label>
					<input type="text" class="wmbf-inp" id="wmbf-f-name" placeholder="<?php esc_attr_e( 'e.g. Ships in 2–3 weeks', 'backorder-flow' ); ?>" autocomplete="off">
					<div class="wmbf-err" id="wmbf-f-name-err" hidden><?php self::icon( 'alert' ); ?><span></span></div>
				</div>
				<div class="wmbf-field">
					<label class="wmbf-lbl" for="wmbf-f-msg"><?php esc_html_e( 'Message to customers', 'backorder-flow' ); ?> <span class="wmbf-meta" id="wmbf-f-msg-count"></span></label>
					<textarea class="wmbf-inp" id="wmbf-f-msg" rows="3" maxlength="200" placeholder="<?php esc_attr_e( 'e.g. Back in stock soon. Your order ships within 2–3 weeks.', 'backorder-flow' ); ?>"></textarea>
					<p class="wmbf-help"><?php esc_html_e( 'Shown under the status name on product pages, in the cart and in order emails.', 'backorder-flow' ); ?></p>
				</div>
				<div class="wmbf-field">
					<span class="wmbf-lbl"><?php esc_html_e( 'Color', 'backorder-flow' ); ?></span>
					<div class="wmbf-swatches" id="wmbf-swatches"></div>
				</div>
				<div class="wmbf-field">
					<div class="wmbf-row-split">
						<div><strong><?php esc_html_e( 'Show on the store', 'backorder-flow' ); ?></strong><span><?php esc_html_e( 'Turn off to hide this status without deleting it.', 'backorder-flow' ); ?></span></div>
						<label class="wmbf-toggle"><input type="checkbox" id="wmbf-f-on" aria-label="<?php esc_attr_e( 'Show on the store', 'backorder-flow' ); ?>"><span></span></label>
					</div>
				</div>
				<div class="wmbf-field" id="wmbf-f-id-wrap" hidden>
					<label class="wmbf-lbl" for="wmbf-f-id"><?php esc_html_e( 'Status ID', 'backorder-flow' ); ?> <span class="wmbf-meta"><?php esc_html_e( 'for shortcodes', 'backorder-flow' ); ?></span></label>
					<div class="wmbf-copyfield"><input type="text" id="wmbf-f-id" readonly><button type="button" data-copy-from="wmbf-f-id"><?php self::icon( 'copy' ); ?><?php esc_html_e( 'Copy', 'backorder-flow' ); ?></button></div>
				</div>

				<div class="wmbf-sec-lbl"><?php esc_html_e( 'Customer preview', 'backorder-flow' ); ?></div>
				<div class="wmbf-preview" id="wmbf-f-preview">
					<div class="wmbf-preview-cap"><span><?php esc_html_e( 'Product page', 'backorder-flow' ); ?></span></div>
					<div class="wmbf-fe-badge"><i></i><div><strong data-pv="name"></strong><span data-pv="msg"></span></div></div>
					<div class="wmbf-preview-cap"><span><?php esc_html_e( 'Cart shipping group', 'backorder-flow' ); ?></span></div>
					<div class="wmbf-fe-cart"><strong data-pv="name"></strong><span data-pv="msg"></span></div>
				</div>
			</div>
			<div class="wmbf-drawer-f">
				<span class="wmbf-note"><?php esc_html_e( 'Drag statuses in the list to change their order.', 'backorder-flow' ); ?></span>
				<button type="button" class="wmbf-btn" data-close="drawer"><?php esc_html_e( 'Cancel', 'backorder-flow' ); ?></button>
				<button type="button" class="wmbf-btn wmbf-btn-primary" id="wmbf-d-save"></button>
			</div>
		</div>

		<div class="wmbf-scrim wmbf-dlg-scrim" id="wmbf-dlg-scrim" hidden></div>
		<div class="wmbf-dlg" id="wmbf-dlg" role="alertdialog" aria-modal="true" aria-labelledby="wmbf-dlg-title" aria-describedby="wmbf-dlg-text" hidden>
			<div class="wmbf-dlg-ico"><?php self::icon( 'alert' ); ?></div>
			<h3 id="wmbf-dlg-title"></h3>
			<p id="wmbf-dlg-text"></p>
			<div class="wmbf-dlg-acts">
				<button type="button" class="wmbf-btn" id="wmbf-dlg-no"></button>
				<button type="button" class="wmbf-btn wmbf-btn-danger" id="wmbf-dlg-yes"></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings.
	 */
	public function save_settings() {
		// Settings are saved via AJAX - no need for save button.
	}

	/**
	 * Hide the WooCommerce save button on our settings tab.
	 */
	public function hide_save_button() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Check if we're on WooCommerce settings page
		if ( 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		// Check if we're on our tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine active tab, not processing form data.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'backorder_status' !== $current_tab ) {
			return;
		}

		// Register an empty style handle and add inline style to hide the save button.
		wp_register_style( 'wmbf-admin-settings', false, array(), WMBF_VERSION );
		wp_enqueue_style( 'wmbf-admin-settings' );

		$custom_css = "
			.woocommerce-save-button,
			p.submit {
				display: none !important;
			}
		";

		wp_add_inline_style( 'wmbf-admin-settings', $custom_css );
	}

	/**
	 * Get all backorder statuses.
	 *
	 * @return array
	 */
	public function get_statuses() {
		$statuses = get_option( 'wmbf_statuses', array() );

		if ( ! is_array( $statuses ) ) {
			return array();
		}

		// Sort by priority.
		usort( $statuses, function( $a, $b ) {
			$priority_a = isset( $a['priority'] ) ? $a['priority'] : 999;
			$priority_b = isset( $b['priority'] ) ? $b['priority'] : 999;
			return (int) $priority_a - (int) $priority_b;
		});

		return $statuses;
	}

	/**
	 * Get status by ID.
	 *
	 * @param string $status_id Status ID.
	 * @return array|null
	 */
	public function get_status( $status_id ) {
		$statuses = $this->get_statuses();
		foreach ( $statuses as $status ) {
			if ( isset( $status['id'] ) && $status['id'] === $status_id ) {
				return $status;
			}
		}
		return null;
	}

	/**
	 * AJAX add/update status.
	 */
	public function ajax_add_status() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$status_id = isset( $_POST['status_id'] ) ? sanitize_text_field( wp_unslash( $_POST['status_id'] ) ) : '';
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$color = isset( $_POST['color'] ) ? sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '';
		if ( ! $color ) {
			$color = '#ff9800';
		}
		$priority = isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 1;
		$enabled = isset( $_POST['enabled'] ) && 'true' === $_POST['enabled'];

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Status name is required.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		$new_status = array(
			'id'          => $status_id ? $status_id : uniqid(),
			'name'        => $name,
			'description' => $description,
			'color'       => $color,
			'priority'    => $priority,
			'enabled'     => $enabled,
		);

		// Update existing or add new.
		if ( $status_id ) {
			$found = false;
			foreach ( $statuses as $key => $status ) {
				if ( isset( $status['id'] ) && $status['id'] === $status_id ) {
					$statuses[ $key ] = $new_status;
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$statuses[] = $new_status;
			}
		} else {
			$statuses[] = $new_status;
		}

		update_option( 'wmbf_statuses', $statuses );

		wp_send_json_success( array(
			'message' => __( 'Status saved successfully.', 'backorder-flow' ),
			'status'  => $new_status,
		) );
	}

	/**
	 * AJAX delete status.
	 */
	public function ajax_delete_status() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$status_id = isset( $_POST['status_id'] ) ? sanitize_text_field( wp_unslash( $_POST['status_id'] ) ) : '';

		if ( empty( $status_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status ID.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		$statuses = array_filter( $statuses, function( $status ) use ( $status_id ) {
			return ! isset( $status['id'] ) || $status['id'] !== $status_id;
		});

		update_option( 'wmbf_statuses', array_values( $statuses ) );

		wp_send_json_success( array( 'message' => __( 'Status deleted successfully.', 'backorder-flow' ) ) );
	}

	/**
	 * AJAX update status order.
	 */
	public function ajax_update_status_order() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$order = isset( $_POST['order'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['order'] ) ) : array();

		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order data.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		$ordered_statuses = array();

		foreach ( $order as $priority => $status_id ) {
			foreach ( $statuses as $key => $status ) {
				if ( isset( $status['id'] ) && $status['id'] === $status_id ) {
					$status['priority'] = $priority + 1;
					$ordered_statuses[] = $status;
					unset( $statuses[ $key ] );
					break;
				}
			}
		}

		// Keep any statuses that were not part of the submitted order, after the ordered ones.
		foreach ( $statuses as $status ) {
			$status['priority'] = count( $ordered_statuses ) + 1;
			$ordered_statuses[] = $status;
		}

		update_option( 'wmbf_statuses', $ordered_statuses );

		wp_send_json_success( array( 'message' => __( 'Order updated successfully.', 'backorder-flow' ) ) );
	}

	/**
	 * AJAX export statuses.
	 */
	public function ajax_export_statuses() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		wp_send_json_success( array( 'data' => $statuses ) );
	}

	/**
	 * AJAX import statuses.
	 */
	public function ajax_import_statuses() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data validated and each field sanitized individually below.
		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'No data provided.', 'backorder-flow' ) ) );
		}

		$imported_statuses = json_decode( $data, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Invalid JSON data.', 'backorder-flow' ) ) );
		}

		// Validate data structure.
		if ( ! is_array( $imported_statuses ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data format.', 'backorder-flow' ) ) );
		}

		// Sanitize each imported status.
		$sanitized_statuses = array();
		foreach ( $imported_statuses as $status ) {
			if ( ! is_array( $status ) ) {
				continue;
			}

			if ( empty( $status['name'] ) ) {
				continue;
			}

			$sanitized_statuses[] = array(
				'id'          => ! empty( $status['id'] ) ? preg_replace( '/[^a-zA-Z0-9]/', '', $status['id'] ) : uniqid(),
				'name'        => isset( $status['name'] ) ? sanitize_text_field( $status['name'] ) : '',
				'description' => isset( $status['description'] ) ? sanitize_textarea_field( $status['description'] ) : '',
				'color'       => ( isset( $status['color'] ) && sanitize_hex_color( $status['color'] ) ) ? sanitize_hex_color( $status['color'] ) : '#ff9800',
				'priority'    => isset( $status['priority'] ) ? absint( $status['priority'] ) : 1,
				'enabled'     => isset( $status['enabled'] ) ? (bool) $status['enabled'] : true,
			);
		}

		update_option( 'wmbf_statuses', $sanitized_statuses );

		wp_send_json_success(
			array(
				'message'  => __( 'Statuses imported successfully.', 'backorder-flow' ),
				'statuses' => $this->get_statuses_for_js(),
			)
		);
	}

	/**
	 * AJAX add a starter set of statuses (first-run screen).
	 */
	public function ajax_add_starter_statuses() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();

		// Only for stores that have no statuses yet.
		if ( empty( $statuses ) ) {
			$starter = array(
				array( __( 'Ships in 1–2 weeks', 'backorder-flow' ), __( 'Back in stock soon. Your order ships within 1–2 weeks.', 'backorder-flow' ), '#16CF9E' ),
				array( __( 'Pre-order', 'backorder-flow' ), __( 'Coming soon. We ship it as soon as it arrives.', 'backorder-flow' ), '#702BB2' ),
				array( __( 'Made to order', 'backorder-flow' ), __( 'Made after you order. Allow 3–4 weeks.', 'backorder-flow' ), '#2F7DE1' ),
			);

			foreach ( $starter as $index => $item ) {
				$statuses[] = array(
					'id'          => uniqid() . $index,
					'name'        => $item[0],
					'description' => $item[1],
					'color'       => $item[2],
					'priority'    => $index + 1,
					'enabled'     => true,
				);
			}

			update_option( 'wmbf_statuses', $statuses );
		}

		wp_send_json_success( array( 'statuses' => $this->get_statuses_for_js() ) );
	}

	/**
	 * AJAX save default message.
	 */
	public function ajax_save_default_message() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Message cannot be empty.', 'backorder-flow' ) ) );
		}

		update_option( 'wmbf_default_message', $message );

		wp_send_json_success( array( 'message' => __( 'Default message saved successfully.', 'backorder-flow' ) ) );
	}
}
