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

		private $logger;


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
			$this->public_key = $this->get_option( 'public_key' );
			$this->private_key = $this->get_option( 'private_key' );

		
			
			$this->logger = wc_get_logger();
			$this->log_context = array('source' => 'bob_payment_gateway');

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
				'public_key'        => array(
					'title'       => __( 'Public Key', 'woocommerce' ),
					'type'        => 'password',
					'description' => __( 'For security reasons, enter your public key', 'woocommerce' ),
					'placeholder' => __( '----Begin Public Key----', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'private_key'        => array(
					'title'       => __( 'Private Key', 'woocommerce' ),
					'type'        => 'password',
					'description' => __( 'For security reasons, enter your private key', 'woocommerce' ),
					'placeholder'     => __( '----Begin Private Key----', 'woocommerce' ),
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


		
		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
				   $order->payment_complete();
				   $order->reduce_order_stock();
		
				   // some notes to customer (replace true with false to make it private)
				   $to = $order->get_billing_email();
					$subject = 'Bank Details for Wire Transfer';
					$message = '
					<html>
					<head>
						<title>Bank Details for Wire Transfer</title>
						<style>
							body {
								font-family: Arial, sans-serif;
								line-height: 1.6;
								color: #333333;
							}
							.container {
								width: 80%;
								margin: 0 auto;
								padding: 20px;
								border: 1px solid #dddddd;
								border-radius: 5px;
								background-color: #f9f9f9;
							}
							.header {
								background-color: #0073aa;
								color: #ffffff;
								padding: 10px;
								border-radius: 5px 5px 0 0;
								text-align: center;
							}
							.content {
								padding: 20px;
							}
							.footer {
								padding: 10px;
								text-align: center;
								font-size: 0.9em;
								color: #777777;
							}
						</style>
					</head>
					<body>
						<div class="container">
							<div class="header">
								<h1>Bank Details for Wire Transfer</h1>
							</div>
							<div class="content">
								<p>Dear Customer,</p>
								<p>Please find the bank details below for the wire transfer payment:</p>
								<p>' . nl2br($this->bank_details) . '</p>
								<p>Thank you for your purchase!</p>
							</div>
							<div class="footer">
								<p>&copy; ' . date('Y') . ' Your Company. All rights reserved.</p>
							</div>
						</div>
					</body>
					</html>';
					$this->logger->info( 'email sent: ' . $to);

					if (is_email($to)) {
						// Send email and check for errors
						if (wp_mail($to, $subject, $message)) {
							$this->logger->info( 'Email sent successfully to: ' . $to);
						} else {
							$this->logger->error( 'Failed to send email to: ' . $to );
							wp_mail('sales@himalayacordyceps.com', 'Failed Payment Email: ', 'Failed to send payment email to: ' . $to);
							wc_add_notice( 'Failed to send payment email. Please try again', 'error' );
							return;
						}
					} else {
						$this->logger->error( 'Invalid email address: ' . $to );
						wc_add_notice( 'Invalid email address provided. Please check and try again.', 'error' );
						return;
					}
		
				   // Empty cart
				   $order->payment_complete();
				   $order->reduce_order_stock();
				   WC()->cart->empty_cart();
		
				   // Redirect to the thank you page
				   return array(
					   'result' => 'success',
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

