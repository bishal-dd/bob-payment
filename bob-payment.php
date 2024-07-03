<?php
/*
	Plugin Name: Direct Wire Transfer
	Description: Extends WooCommerce with a Direct Wire Transfer.
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
			$this->method_title       = __( 'Direct Wire Transfer Email', 'woocommerce' );
			$this->method_description = __( 'Get Email with bank details', 'woocommerce' );
			$this->has_fields = true;
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->BeneficiaryBank  = $this->get_option( 'BeneficiaryBank' );
			$this->BeneficiaryName = $this->get_option( 'BeneficiaryName' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->BeneficiaryAccountNo = $this->get_option( 'BeneficiaryAccountNo' );
			$this->BeneficiaryAddress = $this->get_option( 'BeneficiaryAddress' );
			$this->BeneficiaryBankSWIFTCODE = $this->get_option( 'BeneficiaryBankSWIFTCODE' );

		
			
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
				'BeneficiaryBank'  => array(
					'title'       => __( 'Beneficiary Bank', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Bank account', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'BeneficiaryName'  => array(
					'title'       => __( 'Beneficiary Name', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Beneficiary Name.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'BeneficiaryAccountNo' => array(
					'title'       => __( 'Beneficiary Account No', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Beneficiary Account No', 'woocommerce' ),
					'default'     => "",
					'desc_tip'    => true,
				),
				'BeneficiaryBankSWIFTCODE' => array(
					'title'       => __( 'Beneficiary Bank SWIFT CODE', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Beneficiary Bank SWIFT CODE', 'woocommerce' ),
					'default'     => "",
					'desc_tip'    => true,
				),
				'BeneficiaryAddress'        => array(
					'title'       => __( 'Beneficiary Address', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Beneficiary Address', 'woocommerce' ),
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
				   $order_total = $order->get_total();
				   $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
				   $order->payment_complete();
				   $order->reduce_order_stock();
		
				   // some notes to customer (replace true with false to make it private)
				   $to = $order->get_billing_email();
					$subject = 'Bank Details for Wire Transfer';
					$headers = array('Content-Type: text/html; charset=UTF-8');
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
								width: 40%;
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
								font-size: 1.1em;
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
								<p>Dear ' . $customer_name . ',</p>
								<p>Please find the bank details and amount below for the wire transfer payment:</p>
								<p><b>Order Total:</b> ' . wc_price($order_total) . '</p>
								<p><b>Beneficiary Bank:</b> '. $this->BeneficiaryBank . '</p>
								<p><b>Beneficiary Name:</b> '. $this->BeneficiaryName . '</p>
								<p><b>Beneficiary Account No:</b> '. $this->BeneficiaryAccountNo . '</p>
								<p><b>Beneficiary Bank SWIFT CODE:</b> '. $this->BeneficiaryBankSWIFTCODE . '</p>
								<p><b>Beneficiary Address:</b> '. $this->BeneficiaryAddress . '</p>
								<p>Thank you for your purchase!</p>
							</div>
							<div class="footer">
								<p>&copy; ' . date('Y') . ' Organic Himalayan Cordyceps . All rights reserved.</p>
							</div>
						</div>
					</body>
					</html>';
					$this->logger->info( 'email sent: ' . $to);

					if (is_email($to)) {
						// Send email and check for errors
						if (wp_mail($to, $subject, $message, $headers)) {
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

