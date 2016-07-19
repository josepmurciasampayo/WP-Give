<?php

/**
 * Class Tests_Payment_Class
 */
class Tests_Payment_Class extends WP_UnitTestCase {

	protected $_payment_id = null;
	protected $_key = null;
	protected $_post = null;
	protected $_payment_key = null;

	/**
	 * Set it Up
	 */
	public function setUp() {

		parent::setUp();

		$payment_id         = Give_Helper_Payment::create_simple_payment();
		$this->_payment_key = give_get_payment_key( $payment_id );
		$this->_payment_id  = $payment_id;
		$this->_key         = $this->_payment_key;

		$this->_transaction_id = 'FIR3SID3';
		give_set_payment_transaction_id( $payment_id, $this->_transaction_id );
		give_insert_payment_note(
			$payment_id,
			sprintf(
				/* translators: %s: Paypal transaction id */
				__( 'PayPal Transaction ID: %s', 'give' ),
				$this->_transaction_id
			)
		);

		// Make sure we're working off a clean object caching in WP Core.
		// Prevents some payment_meta from not being present.
		clean_post_cache( $payment_id );
		update_postmeta_cache( array( $payment_id ) );
	}

	/**
	 * Tear Down
	 */
	public function tearDown() {

		parent::tearDown();

		Give_Helper_Payment::delete_payment( $this->_payment_id );

	}

	/**
	 * Test IDs
	 */
	public function test_IDs() {

		$payment = new Give_Payment( $this->_payment_id );
		$this->assertEquals( $this->_payment_id, $payment->ID );
		$this->assertEquals( $payment->_ID, $payment->ID );

	}

	/**
	 * Test ID Save
	 */
	public function test_ID_save_block() {

		$payment = new Give_Payment( $this->_payment_id );
		$this->assertEquals( $this->_payment_id, $payment->ID );
		$payment->ID = 12121222;
		$payment->save();
		$this->assertEquals( $this->_payment_id, $payment->ID );

	}

	/**
	 * Test Get Existing Payment
	 */
	public function test_get_existing_payment() {
		$payment = new Give_Payment( $this->_payment_id );

		$this->assertEquals( $this->_payment_id, $payment->ID );
		$this->assertEquals( 20.00, $payment->total );
	}

	/**
	 * Test Get Bogus Payment
	 */
	public function test_getting_no_payment() {
		$payment = new Give_Payment();
		$this->assertEquals( null, $payment->ID );

		$payment = new Give_Payment( 99999999999 );
		$this->assertEquals( null, $payment->ID );
	}

	/**
	 * Test Payment Status Update
	 */
	public function test_payment_status_update() {

		$payment = new Give_Payment( $this->_payment_id );

		$payment->update_status( 'pending' );
		$this->assertEquals( 'pending', $payment->status );
		$this->assertEquals( 'Pending', $payment->status_nicename );

		// Test backwards compatibility
		give_update_payment_status( $this->_payment_id, 'publish' );

		// Need to get the payment again since it's been updated
		$payment = new Give_Payment( $this->_payment_id );

		$this->assertEquals( 'publish', $payment->status );
		$this->assertEquals( 'Complete', $payment->status_nicename );
	}

	/**
	 * Test Add Donation
	 */
	public function test_add_donation() {

		$payment = new Give_Payment( $this->_payment_id );

		$this->assertEquals( 20.00, $payment->total );

		$new_form = Give_Helper_Form::create_simple_form();

		$payment->add_donation( $new_form->ID );
		$payment->save();

		$this->assertEquals( 40.00, $payment->total );
	}

	/**
	 * Test Remove Donation
	 */
	public function test_remove_donation() {

		$payment = new Give_Payment( $this->_payment_id );
		$form_id = $payment->form_id;
		$amount  = $payment->total;

		$this->assertEquals( 20, $payment->total );

		$remove_args = array( 'amount' => $amount );
		$payment->remove_donation( $form_id, $remove_args );
		$payment->save();

		$this->assertEquals( 0, $payment->total );
	}

	/**
	 * Test Delete Transaction
	 */
	public function test_delete_transaction() {

		$this->markTestIncomplete( 'This test is incomplete' );

	}

	/**
	 * Test User Info
	 */
	public function test_user_info() {
		$payment = new Give_Payment( $this->_payment_id );

		$this->assertEquals( 'Admin', $payment->first_name );
		$this->assertEquals( 'User', $payment->last_name );
	}

	/**
	 * Test for Serialized User Info
	 */
	public function test_for_serialized_user_info() {
		$payment            = new Give_Payment( $this->_payment_id );
		$payment->user_info = serialize( array( 'first_name' => 'John', 'last_name' => 'Doe' ) );
		$payment->save();

		$this->assertInternalType( 'array', $payment->user_info );
		foreach ( $payment->user_info as $key => $value ) {
			$this->assertFalse( is_serialized( $value ), $key . ' returned a serialized value' );
		}
	}

	/**
	 * Test Update Date to Future Date
	 */
	public function test_update_date_future() {
		$payment      = new Give_Payment( $this->_payment_id );
		$current_date = $payment->date;

		$new_date      = strtotime( $payment->date ) + DAY_IN_SECONDS;
		$payment->date = date( 'Y-m-d H:i:s', $new_date );
		$payment->save();

		$date2 = strtotime( $payment->date );
		$this->assertEquals( $new_date, $date2 );
	}

	/**
	 * Test Update Date to Past Date
	 */
	public function test_update_date_past() {
		$payment      = new Give_Payment( $this->_payment_id );
		$current_date = $payment->date;

		$new_date      = strtotime( $payment->date ) - DAY_IN_SECONDS;
		$payment->date = date( 'Y-m-d H:i:s', $new_date );
		$payment->save();

		$date2 = strtotime( $payment->date );
		$this->assertEquals( $new_date, $date2 );
	}

	/**
	 * Test Refund Payment
	 */
	public function test_refund_payment() {

		$payment         = new Give_Payment( $this->_payment_id );
		$payment->status = 'complete';
		$payment->save();

		$form     = new Give_Donate_Form( $payment->form_id );
		$earnings = $form->earnings;
		$sales    = $form->sales;

		$site_earnings = give_get_total_earnings();
		$site_sales    = give_get_total_sales();
		$payment->refund();

		wp_cache_flush();

		$status = get_post_status( $payment->ID );
		$this->assertEquals( 'refunded', $status );
		$this->assertEquals( 'refunded', $payment->status );

		$form2 = new Give_Donate_Form( $form->ID );

		$this->assertEquals( $earnings - $form2->price, $form2->earnings );
		$this->assertEquals( $sales - 1, $form2->sales );

		$this->assertEquals( $site_earnings - $payment->total, give_get_total_earnings() );
		$this->assertEquals( $site_sales - 1, give_get_total_sales() );
	}

	/**
	 * Test Refund Legacy Payment
	 */
	public function test_refund_payment_legacy() {

		$payment         = new Give_Payment( $this->_payment_id );
		$payment->status = 'complete';
		$payment->save();

		$form     = new Give_Donate_Form( $payment->form_id );
		$earnings = $form->earnings;
		$sales    = $form->sales;

		give_undo_donation_on_refund( $payment->ID, 'refunded', 'publish' );

		wp_cache_flush();

		$payment = new Give_Payment( $this->_payment_id );
		$status  = get_post_status( $payment->ID );
		$this->assertEquals( 'refunded', $status );
		$this->assertEquals( 'refunded', $payment->status );

		$form2 = new Give_Donate_Form( $form->ID );

		$this->assertEquals( $earnings - $form->price, $form2->earnings );
		$this->assertEquals( $sales - 1, $form2->sales );

	}


	/**
	 * Test Remove Donation Payment by Price ID
	 */
	public function test_remove_with_multi_price_points_by_price_id() {

		Give_Helper_Payment::delete_payment( $this->_payment_id );

		$form    = Give_Helper_Form::create_multilevel_form();
		$payment = new Give_Payment();

		//Add a multi-level donation amount
		$payment->add_donation( $form->ID, array( 'price_id' => 2 ) );
		$this->assertEquals( 25, $payment->total );
		$payment->status = 'complete';
		$payment->save();

		//Now remove it
		$payment->remove_donation( $form->ID, array( 'price_id' => 2 ) );
		$payment->save();

		$this->assertEmpty( $payment->price_id );
		$this->assertEquals( 0, $payment->total );


	}

	/**
	 * Test Refund Affecting Stats
	 */
	public function test_refund_affecting_stats() {

		$payment         = new Give_Payment( $this->_payment_id );
		$payment->status = 'complete';
		$payment->save();

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$customer_sales    = $customer->purchase_count;
		$customer_earnings = $customer->purchase_value;

		$form_sales    = $form->sales;
		$form_earnings = $form->earnings;

		$site_earnings = give_get_total_earnings();
		$site_sales    = give_get_total_sales();

		$payment->refund();
		wp_cache_flush();

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$this->assertEquals( $customer_earnings - $payment->total, $customer->purchase_value );
		$this->assertEquals( $customer_sales - 1, $customer->purchase_count );

		$this->assertEquals( $form_earnings - $payment->total, $form->earnings );
		$this->assertEquals( $form_sales - 1, $form->sales );

		$this->assertEquals( $site_earnings - $payment->total, give_get_total_earnings() );
		$this->assertEquals( $site_sales - 1, give_get_total_sales() );
	}

	/**
	 * Test Refund Without Affecting Stats
	 */
	public function test_refund_without_affecting_stats() {

		add_filter( 'give_decrease_earnings_on_undo', '__return_false' );
		add_filter( 'give_decrease_sales_on_undo', '__return_false' );
		add_filter( 'give_decrease_customer_value_on_refund', '__return_false' );
		add_filter( 'give_decrease_customer_purchase_count_on_refund', '__return_false' );
		add_filter( 'give_decrease_store_earnings_on_refund', '__return_false' );

		$payment         = new Give_Payment( $this->_payment_id );
		$payment->status = 'complete';
		$payment->save();

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$customer_sales    = $customer->purchase_count;
		$customer_earnings = $customer->purchase_value;

		$form_sales    = $form->sales;
		$form_earnings = $form->earnings;

		$site_earnings = give_get_total_earnings();
		$site_sales    = give_get_total_sales();

		$payment->refund();
		wp_cache_flush();

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$this->assertEquals( $customer_earnings, $customer->purchase_value );
		$this->assertEquals( $customer_sales, $customer->purchase_count );

		$this->assertEquals( $form_earnings, $form->earnings );
		$this->assertEquals( $form_sales, $form->sales );

		$this->assertEquals( $site_earnings, give_get_total_earnings() );
		// Site sales are based off 'publish' & 'revoked' status. So it reduces this count
		$this->assertEquals( $site_sales - 1, give_get_total_sales() );

		remove_filter( 'give_decrease_earnings_on_undo', '__return_false' );
		remove_filter( 'give_decrease_sales_on_undo', '__return_false' );
		remove_filter( 'give_decrease_customer_value_on_refund', '__return_false' );
		remove_filter( 'give_decrease_customer_purchase_count_on_refund', '__return_false' );
		remove_filter( 'give_decrease_store_earnings_on_refund', '__return_false ' );
	}

	/**
	 * Test Pending Affecting Stats
	 */
	public function test_pending_affecting_stats() {
		$payment         = new Give_Payment( $this->_payment_id );
		$payment->status = 'complete';
		$payment->save();

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$customer_sales    = $customer->purchase_count;
		$customer_earnings = $customer->purchase_value;

		$form_sales    = $form->sales;
		$form_earnings = $form->earnings;

		$site_earnings = give_get_total_earnings();
		$site_sales    = give_get_total_sales();

		$payment->status = 'pending';
		$payment->save();
		wp_cache_flush();

		$payment = new Give_Payment( $this->_payment_id );
		$this->assertEmpty( $payment->completed_date );

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$this->assertEquals( $customer_earnings - $payment->total, $customer->purchase_value );
		$this->assertEquals( $customer_sales - 1, $customer->purchase_count );

		$this->assertEquals( $form_earnings - $payment->total, $form->earnings );
		$this->assertEquals( $form_sales - 1, $form->sales );

		$this->assertEquals( $site_earnings - $payment->total, give_get_total_earnings() );
		$this->assertEquals( $site_sales - 1, give_get_total_sales() );
	}

	/**
	 * Test Pending without Affecting Stats
	 */
	public function test_pending_without_affecting_stats() {
		add_filter( 'give_decrease_earnings_on_undo', '__return_false' );
		add_filter( 'give_decrease_sales_on_undo', '__return_false' );
		add_filter( 'give_decrease_customer_value_on_pending', '__return_false' );
		add_filter( 'give_decrease_customer_purchase_count_on_pending', '__return_false' );
		add_filter( 'give_decrease_store_earnings_on_pending', '__return_false' );

		$payment         = new Give_Payment( $this->_payment_id );
		$payment->status = 'complete';
		$payment->save();

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$customer_sales    = $customer->purchase_count;
		$customer_earnings = $customer->purchase_value;

		$form_sales    = $form->sales;
		$form_earnings = $form->earnings;

		$site_earnings = give_get_total_earnings();
		$site_sales    = give_get_total_sales();

		$payment->status = 'pending';
		$payment->save();
		wp_cache_flush();

		$payment = new Give_Payment( $this->_payment_id );
		$this->assertEmpty( $payment->completed_date );

		$customer = new Give_Customer( $payment->customer_id );
		$form     = new Give_Donate_Form( $payment->form_id );

		$this->assertEquals( $customer_earnings, $customer->purchase_value );
		$this->assertEquals( $customer_sales, $customer->purchase_count );

		$this->assertEquals( $form_earnings, $form->earnings );
		$this->assertEquals( $form_sales, $form->sales );

		$this->assertEquals( $site_earnings, give_get_total_earnings() );
		// Store sales are based off 'publish' & 'revoked' status. So it reduces this count
		$this->assertEquals( $site_sales - 1, give_get_total_sales() );

		remove_filter( 'give_decrease_earnings_on_undo', '__return_false' );
		remove_filter( 'give_decrease_sales_on_undo', '__return_false' );
		remove_filter( 'give_decrease_customer_value_on_pending', '__return_false' );
		remove_filter( 'give_decrease_customer_purchase_count_on_pending', '__return_false' );
		remove_filter( 'give_decrease_store_earnings_on_pending', '__return_false ' );
	}

}
