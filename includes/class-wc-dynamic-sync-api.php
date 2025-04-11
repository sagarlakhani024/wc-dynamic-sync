<?php
/**
 * WC Dynamic Sync API Handler
 *
 * @package WC Dynamic Sync
 * @author  Sagar Lakhani <sagarlakhani024@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * WC Dynamic Sync API Handler
 *
 * Handles the REST API requests for syncing products and orders.
 *
 * @since 1.0.0
 * @package WC_Dynamic_Sync
 */
class WC_Dynamic_Sync_API {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register custom REST API route
	 */
	public function register_routes() {
		register_rest_route(
			'wc-dynamic/v1',
			'/sync',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_sync_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle the sync request
	 *
	 * @param WP_REST_Request $request WP rest api request.
	 * @return WP_REST_Response
	 */
	public function handle_sync_request( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['products'] ) || empty( $data['order'] ) ) {
			return $this->error_response( 'Missing products or order data.' );
		}

		$created_user_id = null;
		$total_weight    = 0;
		$line_items      = array();

		foreach ( $data['products'] as $product_data ) {
			$product_id = $this->create_or_update_product( $product_data );
			if ( is_wp_error( $product_id ) ) {
				return $this->error_response( $product_id->get_error_message() );
			}

			$line_items[] = array(
				'product_id' => $product_id,
				'quantity'   => 1,
			);

			$total_weight += isset( $product_data['weight'] ) ? floatval( $product_data['weight'] ) : 0;
		}

		$user_email = sanitize_email( $data['order']['user']['email'] );
		$user       = get_user_by( 'email', $user_email );

		if ( ! $user ) {
			$created_user_id = $this->create_new_user( $data['order']['user'] );
			if ( is_wp_error( $created_user_id ) ) {
				return $this->error_response( $created_user_id->get_error_message() );
			}
			$user = get_user_by( 'id', $created_user_id );
		}

		$order_id = $this->create_order( $line_items, $user->ID, $data['order'], $total_weight );
		if ( is_wp_error( $order_id ) ) {
			return $this->error_response( $order_id->get_error_message() );
		}

		return rest_ensure_response(
			array(
				'status'   => 'success',
				'order_id' => $order_id,
				'user_id'  => $created_user_id ?? 0,
			)
		);
	}

	/**
	 * Create or update WooCommerce product
	 *
	 * @param array $data Product data.
	 * @return int|WP_Error
	 */
	private function create_or_update_product( $data ) {
		$sku              = sanitize_text_field( $data['sku'] );
		$regular_price    = floatval( $data['price'] );
		$discounted_price = round( $regular_price * 0.90, 2 ); // 10% off

		$product_id = wc_get_product_id_by_sku( $sku );

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
		} else {
			$product = new WC_Product_Simple();
			$product->set_sku( $sku );
		}

		$product->set_name( sanitize_text_field( $data['title'] ) );
		$product->set_description( sanitize_textarea_field( $data['description'] ) );
		$product->set_regular_price( $regular_price );
		$product->set_sale_price( $discounted_price );
		$product->set_stock_quantity( intval( $data['stock_quantity'] ) );
		$product->set_manage_stock( true );
		$product->set_weight( isset( $data['weight'] ) ? floatval( $data['weight'] ) : 0 );

		return $product->save();
	}

	/**
	 * Create a new user if not exists
	 *
	 * @param array $user_data User data.
	 * @return int|WP_Error
	 */
	private function create_new_user( $user_data ) {
		$password = wp_generate_password();
		$email    = sanitize_email( $user_data['email'] );

		$user_id = wp_insert_user(
			array(
				'user_login' => $email,
				'user_email' => $email,
				'user_pass'  => $password,
				'first_name' => sanitize_text_field( $user_data['first_name'] ),
				'last_name'  => sanitize_text_field( $user_data['last_name'] ),
				'role'       => 'customer',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		if ( isset( $user_data['billing'] ) ) {
			update_user_meta( $user_id, 'billing_address_1', sanitize_text_field( $user_data['billing']['address_1'] ) );
			update_user_meta( $user_id, 'billing_city', sanitize_text_field( $user_data['billing']['city'] ) );
			update_user_meta( $user_id, 'billing_country', sanitize_text_field( $user_data['billing']['country'] ) );
		}

		do_action( 'wc_dynamic_send_customer_credentials_notification', $user_id, $password );

		return $user_id;
	}

	/**
	 * Create WooCommerce order
	 *
	 * @param array $line_items Line items.
	 * @param int   $user_id User ID.
	 * @param array $order_data Order data.
	 * @param float $total_weight Total weight.
	 * @return int|WP_Error
	 */
	private function create_order( $line_items, $user_id, $order_data, $total_weight ) {
		$order = wc_create_order( array( 'customer_id' => $user_id ) );

		foreach ( $line_items as $item ) {
			$order->add_product( wc_get_product( $item['product_id'] ), $item['quantity'] );
		}

		$order->set_address( array_map( 'sanitize_text_field', $order_data['shipping'] ), 'shipping' );
		$order->set_address( array_map( 'sanitize_text_field', $order_data['user']['billing'] ), 'billing' );

		$shipping_cost = $total_weight <= 10 ? 10 : 20;

		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_method_title( 'Flat Rate Shipping' );
		$shipping_item->set_total( $shipping_cost );

		$order->add_item( $shipping_item );
		$order->calculate_totals();

		return $order->get_id();
	}

	/**
	 * Return error response
	 *
	 * @param string $message Error message.
	 * @return WP_REST_Response
	 */
	private function error_response( $message ) {
		return new WP_REST_Response(
			array(
				'status'  => 'error',
				'message' => esc_html( $message ),
			),
			400
		);
	}
}

new WC_Dynamic_Sync_API();
