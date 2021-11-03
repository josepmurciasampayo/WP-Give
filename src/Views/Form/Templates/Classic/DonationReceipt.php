<?php

namespace Give\Views\Form\Templates\Classic;

use Give\Framework\Exceptions\Primitives\InvalidArgumentException;
use Give\Receipt\DonationReceipt as GiveReceipt;
use Give\Session\SessionDonation\DonationAccessor;
use Give_Payment as Donation;

class DonationReceipt extends GiveReceipt {
	/**
	 * @var Donation
	 */
	private $donation;

	/**
	 * @var int
	 */
	public $donationId;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->donation   = new Donation( ( new DonationAccessor() )->getDonationId() );
		$this->donationId = $this->donation->ID;

		// Register sections
		$this->registerDonorSection();
		$this->registerDonationSection();
		$this->registerAdditionalInformationSection();

		/**
		 * Fire the action for receipt object.
		 */
		do_action( 'give_new_receipt', $this );
	}

	/**
	 * Replace template tags in given string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function replaceTags( $string ) {
		$tags = [
			'{name}'        => sprintf( '%s %s', $this->donation->first_name, $this->donation->last_name ),
			'{donor_email}' => sprintf( '<br /><strong>%s</strong>', $this->donation->email )
		];

		return str_replace(
			array_keys( $tags ),
			array_values( $tags ),
			$string
		);
	}

	/**
	 *  Register Donor Section
	 */
	private function registerDonorSection() {
		$donorSection = $this->addSection( [
			'id'    => static::DONORSECTIONID,
			'label' => esc_html__( 'Donor Details', 'give' ),
		] );

		$donorSection->addLineItem( [
			'id'    => 'fullName',
			'label' => esc_html__( 'Donor Name', 'give' ),
			'value' => trim( "{$this->donation->first_name} {$this->donation->last_name}" ),
		] );

		$donorSection->addLineItem( [
			'id'    => 'emailAddress',
			'label' => esc_html__( 'Email Address', 'give' ),
			'value' => $this->donation->email,
		] );
	}

	/**
	 *  Register Donation Section
	 */
	private function registerDonationSection() {
		$donationSection = $this->addSection( [
			'id'    => static::DONATIONSECTIONID,
			'label' => esc_html__( 'Donation Details', 'give' ),
		] );

		$donationSection->addLineItem( [
			'id'    => 'paymentMethod',
			'label' => esc_html__( 'Payment Method', 'give' ),
			'value' => give_get_gateway_checkout_label( $this->donation->gateway ),
		] );

		$donationSection->addLineItem( [
			'id'    => 'paymentStatus',
			'label' => esc_html__( 'Payment Status', 'give' ),
			'value' => give_get_payment_statuses()[ $this->donation->post_status ],
		] );

		$donationSection->addLineItem( [
			'id'    => 'amount',
			'label' => esc_html__( 'Donation Amount', 'give' ),
			'value' => give_currency_filter(
				give_format_amount( $this->donation->total, [ 'donation_id' => $this->donation->ID ] ),
				[
					'currency_code'   => $this->donation->currency,
					'form_id'         => $this->donation->form_id,
					'decode_currency' => true,
				]
			),
		] );
	}

	/**
	 *  Register Additional Information Section
	 */
	private function registerAdditionalInformationSection() {
		$this->addSection( [
			'id'    => static::ADDITIONALINFORMATIONSECTIONID,
			'label' => esc_html__( 'Additional Information', 'give' ),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Validate section.
	 *
	 * @param  array  $array
	 */
	protected function validateSection( $array ) {
		$array = array_filter( $array ); // Remove empty values.

		if ( array_diff( [ 'id' ], array_keys( $array ) ) ) {
			throw new InvalidArgumentException(
				esc_html__( 'Invalid receipt section. Please provide valid section id', 'give' )
			);
		}
	}
}
