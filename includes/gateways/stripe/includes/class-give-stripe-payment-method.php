<?php
/**
 * Give - Stripe Core | Payment Method API
 *
 * @since 2.5.0
 *
 * @package    Give
 * @subpackage Stripe Core
 * @copyright  Copyright (c) 2019, GiveWP
 * @license    https://opensource.org/licenses/gpl-license GNU Public License
 */

// Exit, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check for class Give_Stripe_Payment_Method exists.
 *
 * @since 2.5.0
 */
if ( ! class_exists( 'Give_Stripe_Payment_Method' ) ) {

	class Give_Stripe_Payment_Method {

		/**
		 * Retrieves the payment method.
		 *
		 * @param string $id Payment Intent ID.
		 *
		 * @return \Stripe\PaymentMethod
		 */
		public function retrieve( $id ) {

			try {

				$payment_method_details = \Stripe\PaymentMethod::retrieve( $id );

			} catch( Exception $e ) {
				give_record_gateway_error(
					__( 'Stripe Payment Method Error', 'give' ),
					sprintf(
						/* translators: %s Exception Message Body */
						__( 'The Stripe Gateway returned an error while retrieving the payment method of the customer. Details: %s', 'give' ),
						$e->getMessage()
					)
				);
				give_set_error( 'stripe_error', __( 'An occurred while retrieving the payment method of the customer. Please try again.', 'give' ) );
				give_send_back_to_checkout( '?payment-mode=' . give_clean( $_POST['payment-mode']) );
				return false;
			}

			return $payment_method_details;
		}

		/**
		 * Fetch all payment methods of the customer.
		 *
		 * @param string $customer_id Stripe Customer ID.
		 * @param string $type        Stripe Payment Type.
		 *
		 * @since 2.5.0
		 *
		 * @return \Stripe\PaymentMethod
		 */
		public function list_all( $customer_id, $type = 'card' ) {

			try {

				$all_payment_methods = \Stripe\PaymentMethod::all(
					array(
						'customer' => $customer_id,
						'type'     => $type,
						'limit'    => 100,
					)
				);

			} catch( Exception $e ) {
				give_record_gateway_error(
					__( 'Stripe Payment Method Error', 'give' ),
					sprintf(
						/* translators: %s Exception Message Body */
						__( 'The Stripe Gateway returned an error while fetching the list of payment methods of the customer. Details: %s', 'give' ),
						$e->getMessage()
					)
				);
				give_set_error( 'stripe_error', __( 'An occurred while fetching the list of payment methods of the customer. Please try again.', 'give' ) );
				give_send_back_to_checkout( '?payment-mode=' . give_clean( $_POST['payment-mode']) );
				return false;
			}

			return $all_payment_methods;
		}
	}
}
