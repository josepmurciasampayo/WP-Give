<?php

/**
 * @group give_customers
 */
class Tests_Customers_DB extends WP_UnitTestCase {

	protected $_post_id = null;

	protected $_user_id = null;

	protected $_customer_id = null;

	public function setUp() {
		parent::setUp();

		$this->_post_id = $this->factory->post->create( array(
			'post_title'  => 'Test Donation',
			'post_type'   => 'give_forms',
			'post_status' => 'publish'
		) );

		$_variable_pricing = array(
			array(
				'name'   => 'Simple',
				'amount' => 20
			),
			array(
				'name'   => 'Advanced',
				'amount' => 100
			)
		);

		$meta = array(
			'give_price'                      => '0.00',
			'_variable_pricing'               => 1,
			'_give_price_options_mode'        => 'on',
			'give_variable_prices'            => array_values( $_variable_pricing ),
			'_give_download_limit'            => 20,
			'_give_hide_purchase_link'        => 1,
			'give_product_notes'              => 'Donation Notes',
			'_give_product_type'              => 'default',
			'_give_download_earnings'         => 129.43,
			'_give_download_sales'            => 59,
			'_give_download_limit_override_1' => 1
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $this->_post_id, $key, $value );
		}

		/** Generate some donations */
		$this->_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user           = get_userdata( $this->_user_id );

		$user_info = array(
			'id'         => $user->ID,
			'email'      => 'testadmin@domain.com',
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'discount'   => 'none'
		);

		$download_details = array(
			array(
				'id'      => $this->_post_id,
				'options' => array(
					'price_id' => 1
				)
			)
		);

		$price = '100.00';

		$total = 0;

		$prices     = get_post_meta( $download_details[0]['id'], 'give_variable_prices', true );
		$item_price = $prices[1]['amount'];

		$total += $item_price;

		$purchase_data = array(
			'price'        => number_format( (float) $total, 2 ),
			'date'         => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'purchase_key' => strtolower( md5( uniqid() ) ),
			'user_email'   => $user_info['email'],
			'user_info'    => $user_info,
			'currency'     => 'USD',
			'status'       => 'pending',
			'tax'          => '0.00'
		);

		$_SERVER['REMOTE_ADDR'] = '10.0.0.0';
		$_SERVER['SERVER_NAME'] = 'give_virtual';

		$payment_id = give_insert_payment( $purchase_data );

		give_update_payment_status( $payment_id, 'complete' );

	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_get_customer_columns() {
		$columns = array(
			'id'             => '%d',
			'user_id'        => '%d',
			'name'           => '%s',
			'email'          => '%s',
			'payment_ids'    => '%s',
			'purchase_value' => '%f',
			'purchase_count' => '%d',
			'notes'          => '%s',
			'date_created'   => '%s',
		);

		$this->assertEquals( $columns, Give()->customers->get_columns() );
	}

	public function test_get_by() {

		$customer = Give()->customers->get_customer_by( 'email', 'testadmin@domain.com' );

		$this->assertInternalType( 'object', $customer );
		$this->assertObjectHasAttribute( 'email', $customer );

	}

	public function test_get_column_by() {

		$customer_id = Give()->customers->get_column_by( 'id', 'email', 'testadmin@domain.com' );

		$this->assertGreaterThan( 0, $customer_id );

	}

	public function test_exists() {

		$this->assertTrue( Give()->customers->exists( 'testadmin@domain.com' ) );

	}

	public function test_legacy_attach_payment() {

		$customer = new Give_Customer( 'testadmin@domain.com' );
		Give()->customers->attach_payment( $customer->id, 999999 );

		$updated_customer = new Give_Customer( 'testadmin@domain.com' );
		$payment_ids      = array_map( 'absint', explode( ',', $updated_customer->payment_ids ) );

		$this->assertTrue( in_array( 999999, $payment_ids ) );

	}

	public function test_legacy_remove_payment() {

		$customer = new Give_Customer( 'testadmin@domain.com' );
		Give()->customers->attach_payment( $customer->id, 91919191 );

		$updated_customer = new Give_Customer( 'testadmin@domain.com' );
		$payment_ids      = array_map( 'absint', explode( ',', $updated_customer->payment_ids ) );
		$this->assertTrue( in_array( 91919191, $payment_ids ) );

		Give()->customers->remove_payment( $updated_customer->id, 91919191 );
		$updated_customer = new Give_Customer( 'testadmin@domain.com' );
		$payment_ids      = array_map( 'absint', explode( ',', $updated_customer->payment_ids ) );

		$this->assertFalse( in_array( 91919191, $payment_ids ) );

	}

	public function test_legacy_increment_stats() {

		$customer = new Give_Customer( 'testadmin@domain.com' );

		$this->assertEquals( '100', $customer->purchase_value );
		$this->assertEquals( '1', $customer->purchase_count );

		Give()->customers->increment_stats( $customer->id, 10 );

		$updated_customer = new Give_Customer( 'testadmin@domain.com' );

		$this->assertEquals( '110', $updated_customer->purchase_value );
		$this->assertEquals( '2', $updated_customer->purchase_count );
	}

	public function test_legacy_decrement_stats() {

		$customer = new Give_Customer( 'testadmin@domain.com' );

		$this->assertEquals( '100', $customer->purchase_value );
		$this->assertEquals( '1', $customer->purchase_count );

		Give()->customers->decrement_stats( $customer->id, 10 );

		$updated_customer = new Give_Customer( 'testadmin@domain.com' );

		$this->assertEquals( '90', $updated_customer->purchase_value );
		$this->assertEquals( '0', $updated_customer->purchase_count );
	}

	public function test_get_customers() {

		$customers = Give()->customers->get_customers();

		$this->assertEquals( 1, count( $customers ) );

	}

	public function test_count_customers() {

		$this->assertEquals( 1, Give()->customers->count() );

		$args = array(
			'date' => array(
				'start' => 'January 1 ' . date( 'Y' ) + 1,
				'end'   => 'January 1 ' . date( 'Y' ) + 2,
			)
		);

		$this->assertEquals( 0, Give()->customers->count( $args ) );

	}

}
