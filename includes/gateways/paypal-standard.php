<?php
/**
 * PayPal Standard Gateway
 *
 * @package     Give
 * @subpackage  Gateways
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayPal Remove CC Form.
 *
 * PayPal Standard does not need a CC form, so remove it.
 *
 * @access private
 * @since  1.0
 */
add_action( 'give_paypal_cc_form', '__return_false' );

/**
 * Process PayPal Purchase.
 *
 * @since 1.0
 *
 * @param array $purchase_data Purchase Data
 *
 * @return void
 */
function give_process_paypal_purchase( $purchase_data ) {

	if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'give-gateway' ) ) {
		wp_die( esc_html__( 'Nonce verification has failed.', 'give' ), esc_html__( 'Error', 'give' ), array( 'response' => 403 ) );
	}

	$form_id  = intval( $purchase_data['post_data']['give-form-id'] );
	$price_id = isset( $purchase_data['post_data']['give-price-id'] ) ? $purchase_data['post_data']['give-price-id'] : '';

	// Collect payment data.
	$payment_data = array(
		'price'           => $purchase_data['price'],
		'give_form_title' => $purchase_data['post_data']['give-form-title'],
		'give_form_id'    => $form_id,
		'give_price_id'   => $price_id,
		'date'            => $purchase_data['date'],
		'user_email'      => $purchase_data['user_email'],
		'purchase_key'    => $purchase_data['purchase_key'],
		'currency'        => give_get_currency(),
		'user_info'       => $purchase_data['user_info'],
		'status'          => 'pending',
		'gateway'         => 'paypal'
	);

	// Record the pending payment.
	$payment_id = give_insert_payment( $payment_data );

	// Check payment.
	if ( ! $payment_id ) {
		// Record the error.
		give_record_gateway_error(
			esc_html__( 'Payment Error', 'give' ),
			sprintf(
			/* translators: %s: payment data */
				esc_html__( 'Payment creation failed before sending donor to PayPal. Payment data: %s', 'give' ),
				json_encode( $payment_data )
			),
			$payment_id
		);
		// Problems? Send back.
		give_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['give-gateway'] );

	} else {

		// Only send to PayPal if the pending payment is created successfully.
		$listener_url = add_query_arg( 'give-listener', 'IPN', home_url( 'index.php' ) );

		// Get the success url.
		$return_url = add_query_arg( array(
			'payment-confirmation' => 'paypal',
			'payment-id'           => $payment_id

		), get_permalink( give_get_option( 'success_page' ) ) );

		// Get the PayPal redirect uri.
		$paypal_redirect = trailingslashit( give_get_paypal_redirect() ) . '?';

		//Item name - pass level name if variable priced.
		$item_name = $purchase_data['post_data']['give-form-title'];

		//Verify has variable prices.
		if ( give_has_variable_prices( $form_id ) && isset( $purchase_data['post_data']['give-price-id'] ) ) {

			$item_price_level_text = give_get_price_option_name( $form_id, $purchase_data['post_data']['give-price-id'] );

			$price_level_amount = give_get_price_option_amount( $form_id, $purchase_data['post_data']['give-price-id'] );

			//Donation given doesn't match selected level (must be a custom amount).
			if ( $price_level_amount != give_sanitize_amount( $purchase_data['price'] ) ) {
				$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
				//user custom amount text if any, fallback to default if not.
				$item_name .= ' - ' . ( ! empty( $custom_amount_text ) ? $custom_amount_text : esc_html__( 'Custom Amount', 'give' ) );

			} //Is there any donation level text?
			elseif ( ! empty( $item_price_level_text ) ) {
				$item_name .= ' - ' . $item_price_level_text;
			}

		} //Single donation: Custom Amount.
		elseif ( give_get_form_price( $form_id ) !== give_sanitize_amount( $purchase_data['price'] ) ) {
			$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
			//user custom amount text if any, fallback to default if not.
			$item_name .= ' - ' . ( ! empty( $custom_amount_text ) ? $custom_amount_text : esc_html__( 'Custom Amount', 'give' ) );
		}

		// Setup PayPal API params.
		$paypal_args = array(
			'business'      => give_get_option( 'paypal_email', false ),
			'first_name'    => $purchase_data['user_info']['first_name'],
			'last_name'     => $purchase_data['user_info']['last_name'],
			'email'         => $purchase_data['user_email'],
			'invoice'       => $purchase_data['purchase_key'],
			'amount'        => $purchase_data['price'],
			'item_name'     => stripslashes( $item_name ),
			'no_shipping'   => '1',
			'shipping'      => '0',
			'no_note'       => '1',
			'currency_code' => give_get_currency(),
			'charset'       => get_bloginfo( 'charset' ),
			'custom'        => $payment_id,
			'rm'            => '2',
			'return'        => $return_url,
			'cancel_return' => give_get_failed_transaction_uri( '?payment-id=' . $payment_id ),
			'notify_url'    => $listener_url,
			'page_style'    => give_get_paypal_page_style(),
			'cbt'           => get_bloginfo( 'name' ),
			'bn'            => 'givewp_SP'
		);

		//Add user address if present.
		if ( ! empty( $purchase_data['user_info']['address'] ) ) {
			$paypal_args['address1'] = isset( $purchase_data['user_info']['address']['line1'] ) ? $purchase_data['user_info']['address']['line1'] : '';
			$paypal_args['address2'] = isset( $purchase_data['user_info']['address']['line2'] ) ? $purchase_data['user_info']['address']['line2'] : '';
			$paypal_args['city']     = isset( $purchase_data['user_info']['address']['city'] ) ? $purchase_data['user_info']['address']['city'] : '';
			$paypal_args['state']    = isset( $purchase_data['user_info']['address']['state'] ) ? $purchase_data['user_info']['address']['state'] : '';
			$paypal_args['country']  = isset( $purchase_data['user_info']['address']['country'] ) ? $purchase_data['user_info']['address']['country'] : '';
		}

		//Donations or regular transactions?
		if ( give_get_option( 'paypal_button_type' ) === 'standard' ) {
			$paypal_extra_args = array(
				'cmd' => '_xclick',
			);
		} else {
			$paypal_extra_args = array(
				'cmd' => '_donations',
			);
		}

		$paypal_args = array_merge( $paypal_extra_args, $paypal_args );
		$paypal_args = apply_filters( 'give_paypal_redirect_args', $paypal_args, $purchase_data );

		// Build query.
		$paypal_redirect .= http_build_query( $paypal_args );

		// Fix for some sites that encode the entities.
		$paypal_redirect = str_replace( '&amp;', '&', $paypal_redirect );

		// Redirect to PayPal.
		wp_redirect( $paypal_redirect );
		exit;
	}

}

add_action( 'give_gateway_paypal', 'give_process_paypal_purchase' );

/**
 * Listens for a PayPal IPN requests and then sends to the processing function
 *
 * @since 1.0
 * @return void
 */
function give_listen_for_paypal_ipn() {
	// Regular PayPal IPN
	if ( isset( $_GET['give-listener'] ) && $_GET['give-listener'] == 'IPN' ) {
		do_action( 'give_verify_paypal_ipn' );
	}
}

add_action( 'init', 'give_listen_for_paypal_ipn' );

/**
 * Process PayPal IPN
 *
 * @since 1.0
 * @return void
 */
function give_process_paypal_ipn() {

	// Check the request method is POST
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] != 'POST' ) {
		return;
	}

	// Set initial post data to empty string
	$post_data = '';

	// Fallback just in case post_max_size is lower than needed
	if ( ini_get( 'allow_url_fopen' ) ) {
		$post_data = file_get_contents( 'php://input' );
	} else {
		// If allow_url_fopen is not enabled, then make sure that post_max_size is large enough
		ini_set( 'post_max_size', '12M' );
	}
	// Start the encoded data collection with notification command
	$encoded_data = 'cmd=_notify-validate';

	// Get current arg separator
	$arg_separator = give_get_php_arg_separator_output();

	// Verify there is a post_data
	if ( $post_data || strlen( $post_data ) > 0 ) {
		// Append the data
		$encoded_data .= $arg_separator . $post_data;
	} else {
		// Check if POST is empty
		if ( empty( $_POST ) ) {
			// Nothing to do
			return;
		} else {
			// Loop through each POST
			foreach ( $_POST as $key => $value ) {
				// Encode the value and append the data.
				$encoded_data .= $arg_separator . "$key=" . urlencode( $value );
			}
		}
	}

	// Convert collected post data to an array.
	parse_str( $encoded_data, $encoded_data_array );

	foreach ( $encoded_data_array as $key => $value ) {

		if ( false !== strpos( $key, 'amp;' ) ) {
			$new_key = str_replace( '&amp;', '&', $key );
			$new_key = str_replace( 'amp;', '&', $new_key );

			unset( $encoded_data_array[ $key ] );
			$encoded_data_array[ $new_key ] = $value;
		}

	}

	//Validate IPN request w/ PayPal if user hasn't disabled this security measure.
	if ( ! give_get_option( 'disable_paypal_verification' ) ) {

		$remote_post_vars = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'headers'     => array(
				'host'         => 'www.paypal.com',
				'connection'   => 'close',
				'content-type' => 'application/x-www-form-urlencoded',
				'post'         => '/cgi-bin/webscr HTTP/1.1',

			),
			'sslverify'   => false,
			'body'        => $encoded_data_array
		);

		// Validate the IPN.
		$api_response = wp_remote_post( give_get_paypal_redirect(), $remote_post_vars );

		if ( is_wp_error( $api_response ) ) {
			give_record_gateway_error(
				esc_html__( 'IPN Error', 'give' ),
				sprintf(
				/* translators: %s: Paypal IPN response */
					esc_html__( 'Invalid IPN verification response. IPN data: %s', 'give' ),
					json_encode( $api_response )
				)
			);

			return; // Something went wrong
		}

		if ( $api_response['body'] !== 'VERIFIED' && give_get_option( 'disable_paypal_verification', false ) ) {
			give_record_gateway_error(
				esc_html__( 'IPN Error', 'give' ),
				sprintf(
				/* translators: %s: Paypal IPN response */
					esc_html__( 'Invalid IPN verification response. IPN data: %s', 'give' ),
					json_encode( $api_response )
				)
			);

			return; // Response not okay
		}

	}

	// Check if $post_data_array has been populated
	if ( ! is_array( $encoded_data_array ) && ! empty( $encoded_data_array ) ) {
		return;
	}

	$defaults = array(
		'txn_type'       => '',
		'payment_status' => ''
	);

	$encoded_data_array = wp_parse_args( $encoded_data_array, $defaults );

	$payment_id = isset( $encoded_data_array['custom'] ) ? absint( $encoded_data_array['custom'] ) : 0;

	if ( has_action( 'give_paypal_' . $encoded_data_array['txn_type'] ) ) {
		// Allow PayPal IPN types to be processed separately.
		do_action( 'give_paypal_' . $encoded_data_array['txn_type'], $encoded_data_array, $payment_id );
	} else {
		// Fallback to web accept just in case the txn_type isn't present.
		do_action( 'give_paypal_web_accept', $encoded_data_array, $payment_id );
	}
	exit;
}

add_action( 'give_verify_paypal_ipn', 'give_process_paypal_ipn' );

/**
 * Process web accept (one time) payment IPNs.
 *
 * @since 1.0
 *
 * @param array $data       IPN Data
 * @param int   $payment_id The payment ID from Give.
 *
 * @return void
 */
function give_process_paypal_web_accept_and_cart( $data, $payment_id ) {

	//Only allow through these transaction types.
	if ( $data['txn_type'] != 'web_accept' && $data['txn_type'] != 'cart' && strtolower( $data['payment_status'] ) != 'refunded' ) {
		return;
	}

	//Need $payment_id to continue.
	if ( empty( $payment_id ) ) {
		return;
	}

	// Collect donation payment details.
	$paypal_amount  = $data['mc_gross'];
	$payment_status = strtolower( $data['payment_status'] );
	$currency_code  = strtolower( $data['mc_currency'] );
	$business_email = isset( $data['business'] ) && is_email( $data['business'] ) ? trim( $data['business'] ) : trim( $data['receiver_email'] );
	$payment_meta   = give_get_payment_meta( $payment_id );

	// Must be a PayPal standard IPN.
	if ( give_get_payment_gateway( $payment_id ) != 'paypal' ) {
		return;
	}

	// Verify payment recipient
	if ( strcasecmp( $business_email, trim( give_get_option( 'paypal_email' ) ) ) != 0 ) {

		give_record_gateway_error(
			esc_html__( 'IPN Error', 'give' ),
			sprintf(
			/* translators: %s: Paypal IPN response */
				esc_html__( 'Invalid business email in IPN response. IPN data: %s', 'give' ),
				json_encode( $data )
			),
			$payment_id
		);
		give_update_payment_status( $payment_id, 'failed' );
		give_insert_payment_note( $payment_id, esc_html__( 'Payment failed due to invalid PayPal business email.', 'give' ) );

		return;
	}

	// Verify payment currency.
	if ( $currency_code != strtolower( $payment_meta['currency'] ) ) {

		give_record_gateway_error(
			esc_html__( 'IPN Error', 'give' ),
			sprintf(
			/* translators: %s: Paypal IPN response */
				esc_html__( 'Invalid currency in IPN response. IPN data: %s', 'give' ),
				json_encode( $data )
			),
			$payment_id
		);
		give_update_payment_status( $payment_id, 'failed' );
		give_insert_payment_note( $payment_id, esc_html__( 'Payment failed due to invalid currency in PayPal IPN.', 'give' ) );

		return;
	}

	//Process refunds & reversed.
	if ( $payment_status == 'refunded' || $payment_status == 'reversed' ) {
		give_process_paypal_refund( $data, $payment_id );
		return;
	}

	// Only complete payments once.
	if ( get_post_status( $payment_id ) == 'publish' ) {
		return;
	}

	// Retrieve the total donation amount (before PayPal).
	$payment_amount = give_get_payment_amount( $payment_id );

	//Check that the donation PP and local db amounts match.
	if ( number_format( (float) $paypal_amount, 2 ) < number_format( (float) $payment_amount, 2 ) ) {
		// The prices don't match
		give_record_gateway_error(
			esc_html__( 'IPN Error', 'give' ),
			sprintf(
			/* translators: %s: Paypal IPN response */
				esc_html__( 'Invalid payment amount in IPN response. IPN data: %s', 'give' ),
				json_encode( $data )
			),
			$payment_id
		);
		give_update_payment_status( $payment_id, 'failed' );
		give_insert_payment_note( $payment_id, esc_html__( 'Payment failed due to invalid amount in PayPal IPN.', 'give' ) );

		return;
	}

	//Process completed donations.
	if ( $payment_status == 'completed' || give_is_test_mode() ) {

		give_insert_payment_note(
			$payment_id,
			sprintf(
			/* translators: %s: Paypal transaction ID */
				esc_html__( 'PayPal Transaction ID: %s', 'give' ),
				$data['txn_id']
			)
		);
		give_set_payment_transaction_id( $payment_id, $data['txn_id'] );
		give_update_payment_status( $payment_id, 'publish' );

	} elseif ( 'pending' == $payment_status && isset( $data['pending_reason'] ) ) {

		// Look for possible pending reasons, such as an echeck.
		$note = give_paypal_get_pending_donation_note( strtolower( $data['pending_reason'] ) );

		if ( ! empty( $note ) ) {

			give_insert_payment_note( $payment_id, $note );

		}
	}

}

add_action( 'give_paypal_web_accept', 'give_process_paypal_web_accept_and_cart', 10, 2 );

/**
 * Process PayPal IPN Refunds
 *
 * @since 1.0
 *
 * @param array $data       IPN Data
 * @param int   $payment_id The payment ID.
 *
 * @return void
 */
function give_process_paypal_refund( $data, $payment_id = 0 ) {

	// Collect payment details

	if ( empty( $payment_id ) ) {
		return;
	}

	if ( get_post_status( $payment_id ) == 'refunded' ) {
		return; // Only refund payments once
	}

	$payment_amount = give_get_payment_amount( $payment_id );
	$refund_amount  = $data['payment_gross'] * - 1;

	if ( number_format( (float) $refund_amount, 2 ) < number_format( (float) $payment_amount, 2 ) ) {

		give_insert_payment_note(
			$payment_id,
			sprintf(
			/* translators: %s: Paypal parent transaction ID */
				esc_html__( 'Partial PayPal refund processed: %s', 'give' ),
				$data['parent_txn_id']
			)
		);

		return; // This is a partial refund

	}

	give_insert_payment_note(
		$payment_id,
		sprintf(
		/* translators: %s: Paypal parent transaction ID */
			esc_html__( 'PayPal Payment #%s Refunded for reason: %s', 'give' ),
			$data['parent_txn_id'], $data['reason_code']
		)
	);
	give_insert_payment_note(
		$payment_id,
		sprintf(
		/* translators: %s: Paypal transaction ID */
			esc_html__( 'PayPal Refund Transaction ID: %s', 'give' ),
			$data['txn_id']
		)
	);
	give_update_payment_status( $payment_id, 'refunded' );
}

/**
 * Get PayPal Redirect
 *
 * @since 1.0
 *
 * @param bool $ssl_check Is SSL?
 *
 * @return string
 */
function give_get_paypal_redirect( $ssl_check = false ) {

	if ( is_ssl() || ! $ssl_check ) {
		$protocal = 'https://';
	} else {
		$protocal = 'http://';
	}

	// Check the current payment mode
	if ( give_is_test_mode() ) {
		// Test mode
		$paypal_uri = $protocal . 'www.sandbox.paypal.com/cgi-bin/webscr';
	} else {
		// Live mode
		$paypal_uri = $protocal . 'www.paypal.com/cgi-bin/webscr';
	}

	return apply_filters( 'give_paypal_uri', $paypal_uri );
}

/**
 * Set the Page Style for PayPal Purchase page
 *
 * @since 1.0
 * @return string
 */
function give_get_paypal_page_style() {
	$page_style = trim( give_get_option( 'paypal_page_style', 'PayPal' ) );

	return apply_filters( 'give_paypal_page_style', $page_style );
}

/**
 * PayPal Success Page
 *
 * Shows "Donation Processing" message for PayPal payments that are still pending on site return
 *
 * @since      1.0
 *
 * @param $content
 *
 * @return string
 *
 */
function give_paypal_success_page_content( $content ) {

	if ( ! isset( $_GET['payment-id'] ) && ! give_get_purchase_session() ) {
		return $content;
	}

	$payment_id = isset( $_GET['payment-id'] ) ? absint( $_GET['payment-id'] ) : false;

	if ( ! $payment_id ) {
		$session    = give_get_purchase_session();
		$payment_id = give_get_purchase_id_by_key( $session['purchase_key'] );
	}

	$payment = get_post( $payment_id );
	if ( $payment && 'pending' == $payment->post_status ) {

		// Payment is still pending so show processing indicator to fix the race condition.
		ob_start();

		give_get_template_part( 'payment', 'processing' );

		$content = ob_get_clean();

	}

	return $content;

}

add_filter( 'give_payment_confirm_paypal', 'give_paypal_success_page_content' );

/**
 * Given a Payment ID, extract the transaction ID
 *
 * @since  1.0
 *
 * @param  string $payment_id Payment ID
 *
 * @return string                   Transaction ID
 */
function give_paypal_get_payment_transaction_id( $payment_id ) {

	$transaction_id = '';
	$notes          = give_get_payment_notes( $payment_id );

	foreach ( $notes as $note ) {
		if ( preg_match( '/^PayPal Transaction ID: ([^\s]+)/', $note->comment_content, $match ) ) {
			$transaction_id = $match[1];
			continue;
		}
	}

	return apply_filters( 'give_paypal_set_payment_transaction_id', $transaction_id, $payment_id );
}

add_filter( 'give_get_payment_transaction_id-paypal', 'give_paypal_get_payment_transaction_id', 10, 1 );

/**
 * Given a transaction ID, generate a link to the PayPal transaction ID details
 *
 * @since  1.0
 *
 * @param  string $transaction_id The Transaction ID
 * @param  int    $payment_id     The payment ID for this transaction
 *
 * @return string                 A link to the PayPal transaction details
 */
function give_paypal_link_transaction_id( $transaction_id, $payment_id ) {

	$paypal_base_url = 'https://history.paypal.com/cgi-bin/webscr?cmd=_history-details-from-hub&id=';
	$transaction_url = '<a href="' . esc_url( $paypal_base_url . $transaction_id ) . '" target="_blank">' . $transaction_id . '</a>';

	return apply_filters( 'give_paypal_link_payment_details_transaction_id', $transaction_url );

}

add_filter( 'give_payment_details_transaction_id-paypal', 'give_paypal_link_transaction_id', 10, 2 );


/**
 * Get pending donation note.
 *
 * @since 1.6.3
 *
 * @param $pending_reason
 *
 * @return string
 */
function give_paypal_get_pending_donation_note( $pending_reason ) {

	$note = '';

	switch ( $pending_reason ) {

		case 'echeck' :

			$note = esc_html__( 'Payment made via eCheck and will clear automatically in 5-8 days.', 'give' );

			break;

		case 'address' :

			$note = esc_html__( 'Payment requires a confirmed donor address and must be accepted manually through PayPal.', 'give' );

			break;

		case 'intl' :

			$note = esc_html__( 'Payment must be accepted manually through PayPal due to international account regulations.', 'give' );

			break;

		case 'multi-currency' :

			$note = esc_html__( 'Payment received in non-shop currency and must be accepted manually through PayPal.', 'give' );

			break;

		case 'paymentreview' :
		case 'regulatory_review' :

			$note = esc_html__( 'Payment is being reviewed by PayPal staff as high-risk or in possible violation of government regulations.', 'give' );

			break;

		case 'unilateral' :

			$note = esc_html__( 'Payment was sent to non-confirmed or non-registered email address.', 'give' );

			break;

		case 'upgrade' :

			$note = esc_html__( 'PayPal account must be upgraded before this payment can be accepted.', 'give' );

			break;

		case 'verify' :

			$note = esc_html__( 'PayPal account is not verified. Verify account in order to accept this donation.', 'give' );

			break;

		case 'other' :

			$note = esc_html__( 'Payment is pending for unknown reasons. Contact PayPal support for assistance.', 'give' );

			break;

	}

	return $note;

}