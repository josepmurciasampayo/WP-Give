/**
 * Accessible Block Links
 *
 * Problem: Hyperlink a component while maintaining screen-reader accessibility and the ability to select text.
 * Solution: Use progressive enhancement to conditionally trigger the target anchor element.
 *
 * @link https://css-tricks.com/block-links-the-search-for-a-perfect-solution/
 */

Array.from( document.querySelectorAll( '.setup-item' ) ).forEach( ( setupItem ) => {
	const actionAnchor = setupItem.querySelector( '.js-action-link' );

	if ( actionAnchor ) {
		actionAnchor.addEventListener( 'click', ( e ) => e.stopPropagation() );
		setupItem.style.cursor = 'pointer';
		setupItem.addEventListener( 'click', ( event ) => { // eslint-disable-line no-unused-vars
			if ( ! window.getSelection().toString() ) {
				actionAnchor.click();
			}
		} );
	}
} );

document.getElementById( 'stripeWebhooksCopyHandler' ).addEventListener( 'click', function() {
	const webhooksURL = document.getElementById( 'stripeWebhooksCopy' );
	webhooksURL.disabled = false; // Copying requires the input to not be disabled.
	webhooksURL.select();
	document.execCommand( 'copy' );
	webhooksURL.disabled = true;

	const icon = document.getElementById( 'stripeWebhooksCopyIcon' );
	icon.classList.remove( 'fa-clipboard' );
	icon.classList.add( 'fa-clipboard-check' );
	setTimeout( function() {
		icon.classList.remove( 'fa-clipboard-check' );
		icon.classList.add( 'fa-clipboard' );
	}, 3000 );
} );

// function pollStripeWebhookRecieved() {
// 	fetch( wpApiSettings.root + 'give-api/v2/onboarding/stripe-webhook-recieved' )
// 		.then( response => response.json() )
// 		.then( data => {
// 			console.log( data );
// 			setTimeout( pollStripeWebhookRecieved, 5000 );
// 		} );
// }
// pollStripeWebhookRecieved();

