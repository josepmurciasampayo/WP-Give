/**
 * Disconnect stripe account
 *
 * This will be used to disconnect any Stripe account from the list
 *
 * @unreleased
 */
const { __, sprintf } = wp.i18n;

window.addEventListener( 'DOMContentLoaded', function() {
	const disconnectBtns = Array.from( document.querySelectorAll( '.give-stripe-disconnect-account-btn' ) );

	if ( ! disconnectBtns.length ) {
		return
	}

	disconnectBtns.forEach( ( button ) => {
		button.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			new Give.modal.GiveConfirmModal( {
				type: 'alert',
				classes: {
					modalWrapper: 'give-modal--warning',
				},
				modalContent: {
					title: __( 'Disconnect Stripe Account', 'give' ),
					desc: __( 'Are you sure you want to disconnect this Stripe account?', 'give' ),
				},
				successConfirm: () => {
					fetch( e.target.attr('href') )
						.then( response => response.json() )
						.then( response => {
							window.location.reload();
					})
				},
			} ).render();
		} );
	} );
});
