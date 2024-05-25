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
			if( empty( $this->private_key )  ) {
				return;
			}
		
			// do not work with card detailes without SSL unless your website is in a test mode
			if( ! $this->testmode && ! is_ssl() ) {
				return;
			}
		
			// let's suppose it is our payment processor JavaScript that allows to obtain a token
		
			// and this is our custom JS in your plugin directory that works with token.js
		

		
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
				Cardholder Name <span class="card-required">*</span>
				</label>
					<input id="card_holder_name" name="card_holder_name" type="text" autocomplete="off">
				</div>
				<div class="form-row form-row-wide">
				<label>
				Card Number <span class="card-required">*</span>
				</label>
					<input id="card_holder_no" name="card_holder_no" type="text" autocomplete="off">
				</div>
					<div class="form-row form-row-first">
						<label>Expiry Date <span class="card-required">*</span></label>
						<input id="card_expdate_month" name="card_expdate_month" type="text" autocomplete="off" style="width: 40%;" placeholder="MM">/
						<input id="card_expdate_year" name="card_expdate_year" type="text" autocomplete="off" style="width: 40%;" placeholder="YY">
					</div>
					<div class="form-row form-row-last">
						<label>Card Code (CVC) <span class="card-required">*</span></label>
						<input id="card_cvv" name="card_cvv" type="password" autocomplete="off" placeholder="CVC">
					</div>
					<div class="clear"></div>
			
				';

			do_action( 'woocommerce_credit_card_form_end', $this->id );
		 
			echo '<div class="clear"></div></fieldset>';
		 
		}

		public function validate_fields() {
			if (empty($_POST['card_holder_name'])) {
				wc_add_notice('Card Holder Name is required!', 'error');
				return false;
			}
			if (empty($_POST['card_holder_no'])) {
				wc_add_notice('Card Number is required!', 'error');
				return false;
			}
			if (empty($_POST['card_expdate_month']) || empty($_POST['card_expdate_year'])) {
				wc_add_notice('Card Expiry Date is required!', 'error');
				return false;
			}
			if (empty($_POST['card_cvv'])) {
				wc_add_notice('Card CVC is required!', 'error');
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
			$card_holder_name = sanitize_text_field($_POST['card_holder_name']);
			$card_number = sanitize_text_field($_POST['card_holder_no']);
			$card_expiry_month = sanitize_text_field($_POST['card_expdate_month']);
			$card_expiry_year = sanitize_text_field($_POST['card_expdate_year']);
			$card_expiry = $card_expiry_year . $card_expiry_month;
			$card_cvc = sanitize_text_field($_POST['card_cvv']);
			$card_country = $order->get_billing_country();
			$card_billing_address_1 = $order->get_billing_address_1();
			$card_billing_address_2 = $order->get_billing_address_2();
			$card_billing_city = $order->get_billing_city();
			$card_billing_state = $order->get_billing_state();
			$card_billing_postcode = $order->get_billing_postcode();
			$card_billing_phone = $order->get_billing_phone();
			$card_billing_email = $order->get_billing_email();
			$amount = $order->get_total();
			$currency = get_woocommerce_currency();
			$transactionTimestamp = date('YmdHis');
			$new_key_pair = openssl_pkey_new(array(
				"private_key_bits" => 2048,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
			));
			openssl_pkey_export($new_key_pair, $private_key);
			$details = openssl_pkey_get_details($new_key_pair);
			$public_key = $details['key'];
					$args = array(
                'body' => json_encode(array(
                    'merchantId' => $this->merchant_id,
                    'pubKey' => $public_key,
                    'purchaseId' => (string) $order_id,
                )),
                'headers' => array(
                    'Content-Type' => 'application/json'
                )
            );
			$response = wp_remote_post( 'https://3dsecure.bob.bt/3dss/mkReq', $args );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$this->logger->info( 'Response body: ' . wp_remote_retrieve_body( $response ) . ' Response code: ' . wp_remote_retrieve_response_code( $response ));

			if( 200 === wp_remote_retrieve_response_code( $response ) && "000" === $body['responseCode'] ) {
 				// Example field values
				$fields = [
					'MPI_TRANS_TYPE' => 'SALES',
					'MPI_MERC_ID' => $this->merchant_id,
					'MPI_PAN' => $card_number,
					'MPI_CARD_HOLDER_NAME' => $card_holder_name,
					'MPI_PAN_EXP' => $card_expiry,
					'MPI_CVV2' => $card_cvc,
					'MPI_TRXN_ID' => (string) $order_id,
					'MPI_PURCH_DATE' => $transactionTimestamp,
					'MPI_PURCH_CURR' => 'USD',
					'MPI_PURCH_AMT' => $amount,
					'MPI_BILL_ADDR_CITY' => $card_billing_city,
					'MPI_BILL_ADDR_CNTRY' => $card_country,
					'MPI_BILL_ADDR_POSTCODE' => $card_billing_postcode,
					'MPI_BILL_ADDR_LINE1' => $card_billing_address_1,
					'MPI_BILL_ADDR_LINE2' => $card_billing_address_2,
					'MPI_EMAIL' => $card_billing_email,
				];

				$this->logger->info( '$fields: ' . print_r( $fields, true ) );

				$dataString = implode('', $fields);
				
				
				openssl_sign($dataString, $signature, $private_key, OPENSSL_ALGO_SHA256);
				$this->logger->info( 'Signature: ' . $signature );

				$base64UrlSignature = str_replace(
					['+', '/', '='],
					['-', '_', ''],
					base64_encode($signature)
				);

				$MPI_MAC = $base64UrlSignature;
				$this->logger->info( 'MPI_MAC: ' . $MPI_MAC );

				$payment_args = array(
					'body' => json_encode(array(
						'MPI_TRANS_TYPE' => 'SALES',
						'MPI_MERC_ID' => $this->merchant_id,
						'MPI_PAN' => $card_number,
						'MPI_CARD_HOLDER_NAME' => $card_holder_name,
						'MPI_PAN_EXP' => $card_expiry,
						'MPI_CVV2' => $card_cvc,
						'MPI_TRXN_ID' => (string) $placeholder_order,
						'MPI_PURCH_DATE' => $transactionTimestamp,
						'MPI_PURCH_CURR' => 'USD',
						'MPI_PURCH_AMT' => $amount,
						'MPI_BILL_ADDR_CITY' => $card_billing_city,
						'MPI_BILL_ADDR_CNTRY' => $card_country,
						'MPI_BILL_ADDR_POSTCODE' => $card_billing_postcode,
						'MPI_BILL_ADDR_LINE1' => $card_billing_address_1,
						'MPI_BILL_ADDR_LINE2' => $card_billing_address_2,
						'MPI_EMAIL' => $card_billing_email,
						'MPI_MAC' => $MPI_MAC,
					)),
					'headers' => array(
						'Content-Type' => 'application/json'
					)
					);
					$this->logger->info( 'payment_args: ' . print_r($payment_args, true ) );
				$transaction_response = wp_remote_post( 'https://3dsecure.bob.bt/3dss/mercReq', $payment_args );
				$transaction_body = json_decode( wp_remote_retrieve_body( $transaction_response ), true );

				$this->logger->info( 'Response body: ' . wp_remote_retrieve_body( $transaction_response ) . ' Response code: ' . wp_remote_retrieve_response_code( $transaction_response ));

				// it could be different depending on your payment processor
				if( 000 === $transaction_body[ 'MPI_ERROR_CODE' ] ) {
		
				   // we received the payment
				   $order->payment_complete();
				   $order->reduce_order_stock();
		
				   // some notes to customer (replace true with false to make it private)
				   $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
		
				   // Empty cart
				   WC()->cart->empty_cart();
		
				   // Redirect to the thank you page
				   return array(
					   'result' => 'success',
					   'redirect' => $this->get_return_url( $order ),
				   );
		
				} else {
				   wc_add_notice( 'Please try again.', 'error' );
				   return;
			   }
		
		   } else {
			   wc_add_notice( 'Connection error.', 'error' );
			   return;
		   }
	
			
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

