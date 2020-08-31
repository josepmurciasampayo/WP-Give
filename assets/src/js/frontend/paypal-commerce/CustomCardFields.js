import PaymentMethod from './PaymentMethod';
import DonationForm from "./DonationForm";

class CustomCardFields extends PaymentMethod{
	/**
	 * @inheritDoc
	 */
	constructor( form ) {
		super(form);

		this.payPalSupportedCountriesForCardSubscription = [ 'US', 'AU' ];
		this.cardFields = this.getCardFields();
		this.recurringChoiceField = this.form.querySelector( 'input[name="give-recurring-period"]' )

		if( this.recurringChoiceField ) {
			this.recurringChoiceField.addEventListener( 'change', this.renderPaymentMethodOption.bind( this ) );
		}
	}

	/**
	 * @inheritDoc
	 */
	renderPaymentMethodOption() {
		// Show custom card field only if donor opted for recurring donation.
		// And PayPal account is from supported country.
		if(
			! DonationForm.isRecurringDonation( this.form ) ||
			! this.payPalSupportedCountriesForCardSubscription.includes( window.givePayPalCommerce.accountCountry )
		) {
			this.toggleFields(false);
			return;
		}

		// We can not process recurring donation with advanced card fields, so let hide and use card field to process recurring donation with PayPal subscription api.
		this.toggleFields(true);
	}

	/**
	 * Get list of credit card fields.
	 *
	 * @since 2.9.0
	 *
	 * @return {object} object of card field selectors.
	 */
	getCardFields() {
		// Return property if set..
		if ( Array.from( this.cardFields ).length ) {
			return this.cardFields;
		}

		return {
			number: {
				el: this.form.querySelector( 'input[name="card_number"]' ),
			},
			cvv: {
				el: this.form.querySelector( 'input[name="card_cvc"]' ),
			},
			expirationDate: {
				el: this.form.querySelector( 'input[name="card_expiry"]' ),
			},
		};
	}

	/**
	 * Toggle fields.
	 *
	 * @since 2.9.0
	 *
	 * @param {boolean} show Flag to show/hide custom card fields.
	 */
	toggleFields( show){
		this.cardFields.forEach( card => {
			card.el.style.display = show ? 'block' : 'none';
			card.el.disabled = ! show;
		});
	}
}

export default CustomCardFields;
