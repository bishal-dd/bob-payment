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
			$this->icon               = "";
			$this->method_title       = __( 'BOB Payment Gateway', 'woocommerce' );
			$this->method_description = __( 'Pay with BOB Payment Gateway', 'woocommerce' );
			$this->has_fields = true;
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->merchant_id = $this->get_option( 'merchant_id' );
			$this->testmode = $this->get_option( 'testmode' );

			// Define supported features.
			$this->supports = array( 'products' );

			// Actions.
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

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
				'testmode' => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
			);
		}
		public function enqueue_styles() {
			wp_enqueue_style('app-css', plugins_url('app.css', __FILE__), array(), '1.0', 'all');
		}
		public function payment_scripts() {

			// we need JavaScript to process a token only on cart/checkout pages, right?
			if( ! is_cart() && ! is_checkout() && ! isset( $_GET[ 'pay_for_order' ] ) ) {
				return;
			}
		
			// if our payment gateway is disabled, we do not have to enqueue JS too
			if( 'no' === $this->enabled ) {
				return;
			}
		
			// no reason to enqueue JavaScript if API keys are not set
			if( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
				return;
			}
		
			// do not work with card detailes without SSL unless your website is in a test mode
			if( ! $this->testmode && ! is_ssl() ) {
				return;
			}
		
			// let's suppose it is our payment processor JavaScript that allows to obtain a token
			wp_enqueue_script( 'misha_js', 'some payment processor site/api/token.js' );
		
			// and this is our custom JS in your plugin directory that works with token.js
			wp_register_script( 'woocommerce_misha', plugins_url( 'misha.js', __FILE__ ), array( 'jquery', 'misha_js' ) );
		
			// in most payment processors you have to use PUBLIC KEY to obtain a token
			wp_localize_script( 'woocommerce_misha', 'misha_params', array(
				'publishableKey' => $this->publishable_key
			) );
		
			wp_enqueue_script( 'woocommerce_misha' );
			wp_enqueue_script( 'modal-js', plugins_url( 'modal.js', __FILE__ ), array( 'jquery' ), '1.0', true );

		
		}

		public function payment_fields() {
 
			// ok, let's display some description before the payment form
			if( $this->description ) {
				// you can instructions for test mode, I mean test card numbers etc.
				if( $this->testmode ) {
					$this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#">documentation</a>.';
					$this->description  = trim( $this->description );
				}
				// display the description with <p> tags etc.
				echo wpautop( wp_kses_post( $this->description ) );
			}
		 
			// I will echo() the form, but you can close PHP tags and print it directly in HTML
			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
		 
			// Add this action hook if you want your custom payment gateway to support it
			do_action( 'woocommerce_credit_card_form_start', $this->id );
		 
			// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
			echo '
				<div class="form-row form-row-wide">
				<label>
				Card Number <span class="required">*</span>
				</label>
					<input id="misha_ccNo" type="text" autocomplete="off">
				</div>
				<div class="form-row form-row-wide">
				<label>
				Cardholder name <span class="required">*</span>
				</label>
					<input id="misha_ccNo" type="text" autocomplete="off">
				</div>
					<div class="form-row form-row-first">
						<label>Expiry Date <span class="required">*</span></label>
						<input id="misha_expdate" type="text" autocomplete="off" placeholder="MM / YY">
					</div>
					<div class="form-row form-row-last">
						<label>Card Code (CVC) <span class="required">*</span></label>
						<input id="misha_cvv" type="password" autocomplete="off" placeholder="CVC">
					</div>
					<div class="clear"></div>
			
				';

			do_action( 'woocommerce_credit_card_form_end', $this->id );
		 
			echo '<div class="clear"></div></fieldset>';
		 
		}

		public function validate_fields(){
 
			if( empty( $_POST[ 'billing_first_name' ] ) ) {
				wc_add_notice( 'First name is required!', 'error' );
				return false;
			}
			return true;
		 
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

