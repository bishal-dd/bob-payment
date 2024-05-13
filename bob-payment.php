<?php
/*
	Plugin Name: BOB Payment Gateway
	Description: Extends WooCommerce with a BOB Payment gateway.
	Plugin URI: https://www.lightwebx.com
	Version: 1.0
	Author: Light Webx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

add_action( 'plugins_loaded', 'bob_payment_gateway_init', 0 );

function bob_payment_gateway_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	load_plugin_textdomain('wc-bob-payment-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');


	/**
	 * BOB Payment Gateway class
	 */
	class WC_Bob_Payment_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id                 = 'bob_payment_gateway';
			$this->method_title       = __( 'BOB Payment Gateway', 'woocommerce' );
			$this->method_description = __( 'Pay with BOB Payment Gateway', 'woocommerce' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );

			// Define supported features.
			$this->supports = array( 'products' );

			// Actions.
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'      => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable BOB Payment', 'woocommerce' ),
					'default' => 'yes',
				),
				'title'        => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( 'BOB Payment', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description'  => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'merchant_id'        => array(
					'title'       => __( 'Merchant ID', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( 'BOB Payment', 'woocommerce' ),
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			// Mark as on-hold (we're awaiting the payment).
			$order->update_status( 'on-hold', __( 'Awaiting payment', 'woocommerce' ) );

			// Reduce stock levels.
			$order->reduce_order_stock();

			// Remove cart.
			WC()->cart->empty_cart();

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}

	/**
	 * Add the BOB Payment Gateway to WooCommerce.
	 *
	 * @param array $methods Array of registered payment methods.
	 * @return array
	 */
	function woocommerce_bob_payment_gateway( $methods ) {
		$methods[] = 'WC_Bob_Payment_Gateway';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_bob_payment_gateway' );
}

