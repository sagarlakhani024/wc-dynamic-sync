<?php
/**
 * Plugin Name: WC Dynamic Sync
 * Description: Sync WooCommerce products and orders via custom API.
 * Version: 1.0.0
 * Author: sagarlakhani
 * License: GPL2+
 *
 *  @package WC_Dynamic_Sync
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activation Hook: Check WooCommerce dependency at activation time
 */
function wc_dynamic_sync_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			'WC Dynamic Sync requires WooCommerce to be installed and activated.',
			'Plugin dependency check',
			array( 'back_link' => true )
		);
	}
}
register_activation_hook( __FILE__, 'wc_dynamic_sync_activate' );

/**
 * Runtime check on every admin load
 */
function wc_dynamic_sync_check_woocommerce_dependency() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>WC Dynamic Sync</strong> requires <strong>WooCommerce</strong> to be installed and activated.</p></div>';
			}
		);
	}
}
add_action( 'admin_init', 'wc_dynamic_sync_check_woocommerce_dependency' );

/**
 * Proceed only if WooCommerce is active.
 */
function wc_dynamic_sync_init() {
	if ( class_exists( 'WooCommerce' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-dynamic-sync-api.php';
	}
}
add_action( 'plugins_loaded', 'wc_dynamic_sync_init', 20 );

/**
 * Register custom WooCommerce email class.
 *
 * @param array $emails WooCommerce emails.
 * @return array
 */
function wc_dynamic_register_custom_email_class( $emails ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/emails/class-wc-email-customer-credentials.php';
	$emails['WC_Email_Customer_Credentials'] = new WC_Email_Customer_Credentials();
	return $emails;
}
add_filter( 'woocommerce_email_classes', 'wc_dynamic_register_custom_email_class' );
