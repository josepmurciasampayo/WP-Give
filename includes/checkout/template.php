<?php
/**
 * Checkout Form Template
 *
 * @package     Give
 * @subpackage  Checkout
 * @copyright   Copyright (c) 2014, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get Donation Form
 *
 * @since 1.0
 *
 * @param array $args Arguments for display
 *
 * @return string $purchase_form
 */

add_action( 'give_single_form_summary', 'give_get_donation_form', 10 );

function give_get_donation_form( $args = array() ) {

	global $give_options, $post, $give_displayed_form_ids;

	$post_id = is_object( $post ) ? $post->ID : 0;

	$defaults = apply_filters( 'give_purchase_link_defaults', array(
		'form_id' => $post_id,
	) );

	$args = wp_parse_args( $args, $defaults );

	$form = new Give_Donate_Form( $args['form_id'] );

	//bail if no form ID
	if ( empty( $form->ID ) ) {
		return false;
	}

	$payment_mode = give_get_chosen_gateway();


	$form_action = add_query_arg( apply_filters( 'give_form_action_args', array(
			'payment-mode' => $payment_mode,
		) ),
		get_permalink( $form->ID )
	);

	if ( 'publish' !== $form->post_status && ! current_user_can( 'edit_product', $form->ID ) ) {
		return false; // Product not published or user doesn't have permission to view drafts
	}

	ob_start();

	?>
	<form id="give_donation_form_<?php echo $post_id; ?>" class="give_donate_form give_donate_form_<?php echo absint( $form->ID ); ?>" action="<?php echo $form_action; ?>" method="post">

		<?php

		do_action( 'give_checkout_form_top', $form->ID, $args );

		do_action( 'give_payment_mode_select' );

		do_action( 'give_checkout_form_bottom', $form->ID, $args );

		?>


	</form><!--end #<?php echo absint( $form->ID ); ?>-->
	<?php

	$final_output = ob_get_clean();

	echo apply_filters( 'give_donate_form', $final_output, $args );

}


/**
 * Renders the Purchase Form, hooks are provided to add to the purchase form.
 * The default Purchase Form rendered displays a list of the enabled payment
 * gateways, a user registration form (if enable) and a credit card info form
 * if credit cards are enabled
 *
 * @since 1.4
 * @global $give_options Array of all the EDD options
 * @return string
 */
function give_show_purchase_form() {
	global $give_options;

	$payment_mode = give_get_chosen_gateway();

	do_action( 'give_purchase_form_top' );

	if ( give_can_checkout() ) {

		do_action( 'give_purchase_form_before_register_login' );

		$show_register_form = give_get_option( 'show_register_form', 'none' );
		if ( ( $show_register_form === 'registration' || ( $show_register_form === 'both' && ! isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
			<div id="give_checkout_login_register">
				<?php do_action( 'give_purchase_form_register_fields' ); ?>
			</div>
		<?php elseif ( ( $show_register_form === 'login' || ( $show_register_form === 'both' && isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
			<div id="give_checkout_login_register">
				<?php do_action( 'give_purchase_form_login_fields' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ( ! isset( $_GET['login'] ) && is_user_logged_in() ) || ! isset( $give_options['show_register_form'] ) || 'none' === $show_register_form ) {
			do_action( 'give_purchase_form_after_user_info' );
		}

		do_action( 'give_purchase_form_before_cc_form' );

		// Load the credit card form and allow gateways to load their own if they wish
		if ( has_action( 'give_' . $payment_mode . '_cc_form' ) ) {
			do_action( 'give_' . $payment_mode . '_cc_form' );
		} else {
			do_action( 'give_cc_form' );
		}

		do_action( 'give_purchase_form_after_cc_form' );

	} else {
		// Can't checkout
		do_action( 'give_purchase_form_no_access' );

	}

	do_action( 'give_purchase_form_bottom' );
}

add_action( 'give_purchase_form', 'give_show_purchase_form' );

/**
 * Donation Levels Output
 *
 * Outputs donation levels based on the specific form ID.
 * The output generated can be overridden by the filters provided or by removing
 * the action and adding your own custom action.
 *
 * @since 1.0
 *
 * @param int $form_id Give Form ID
 *
 * @return void
 */
function give_output_donation_levels( $form_id = 0, $args = array() ) {

	global $give_options;

	do_action( 'give_before_donation_levels', $form_id );

	$variable_pricing    = give_has_variable_prices( $form_id );
	$set_pricing_option  = get_post_meta( $form_id, '_give_price_option', true );
	$set_price           = get_post_meta( $form_id, '_give_set_price', true );
	$allow_custom_amount = get_post_meta( $form_id, '_give_custom_amount', true );

	//Output Variable Pricing Levels
	if ( $variable_pricing ) {
		give_output_levels( $form_id );
	} ?>

	<div class="give-total-wrap">

		<div class="give-donation-amount" for="give-amount">

			<?php
			//Set Price, No Custom Amount Allowed means hidden price field
			if ( $set_pricing_option === 'set' && $allow_custom_amount == 'no' ) {
				?>

				<input class="give-text-input" id="give-amount" type="hidden" name="give-amount" value="<?php echo $set_price; ?>" required>
				<p class="set-price">
					<span class="amount"><?php echo give_currency_filter( give_format_amount( $set_price ) ) ?></span>
				</p>

			<?php } else { ?>
				<label class="give-dollar-sign" for="give-amount">$</label>
				<input class="give-text-input" id="give-amount" type="text" placeholder="" value="<?php echo $set_price; ?>" required>
			<?php } ?>


		</div>

	</div>

	<?php

	do_action( 'give_after_donation_levels', $form_id );
}

add_action( 'give_checkout_form_top', 'give_output_donation_levels' );


/**
 * Outputs the Donation Levels in various formats such as dropdown, radios, and buttons
 *
 * @since 1.0
 *
 * @param int $form_id Give Form ID
 *
 * @return string
 */
function give_output_levels( $form_id ) {

	//Do variable pricing
	$prices        = apply_filters( 'give_form_variable_prices', give_get_variable_prices( $form_id ), $form_id );
	$display_style = get_post_meta( $form_id, '_give_display_style', true );

	$output  = '';
	$counter = 0;

	switch ( $display_style ) {
		case 'buttons':

			$output .= '<ul id="give-donation-level-button-wrap" class="give-donation-levels-wrap give-list-inline">';

			foreach ( $prices as $price ) {
				$counter ++;

				$output .= '<li>';
				$output .= '<button class="give-donation-level-btn give-btn give-btn-level-' . $counter . ' ' . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? 'give-btn-selected' : '' ) . '" value="' . $price['_give_amount'] . '">';
				$output .= ( ! empty( $price['_give_text'] ) ? $price['_give_text'] : $price['_give_price'] );
				$output .= '</button>';
				$output .= '</li>';

			}
			$output .= '</ul>';

			break;

		case 'radios':

			$output .= '<ul id="give-donation-level-radio-wrap" class="give-donation-levels-wrap">';

			foreach ( $prices as $price ) {
				$counter ++;

				$output .= '<li>';

				$output .= '<input type="radio" class="give-radio-input give-radio-level-' . $counter . '" name="give-radio-donation-level" id="give-radio-level-' . $counter . '" ' . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? 'checked="checked"' : '' ) . ' value="' . $price['_give_amount'] . '">';

				$output .= '<label for="give-radio-level-' . $counter . '">' . ( ! empty( $price['_give_text'] ) ? $price['_give_text'] : $price['_give_price'] ) . '</label>';

				$output .= '</li>';

			}
			$output .= '</ul>';

			break;

		case 'dropdown':

			$output .= '<select id="give-donation-level-' . $form_id . '" class="give-select">';

			//first loop through prices
			foreach ( $prices as $price ) {

				$output .= '<option id="give-donation-level-' . $form_id . '" ' . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? 'selected="selected"' : '' ) . ' value="' . $price['_give_amount'] . '">';
				$output .= ( ! empty( $price['_give_text'] ) ? $price['_give_text'] : $price['_give_price'] );
				$output .= '</option>';

			}

			$output .= '</select>';

			break;
	}

	echo apply_filters( 'give_form_level_style', $output, $form_id );

}


/**
 * Shows the User Info fields in the Personal Info box, more fields can be added
 * via the hooks provided.
 *
 * @since 1.0
 * @return void
 */
function give_user_info_fields() {
	if ( is_user_logged_in() ) :
		$user_data = get_userdata( get_current_user_id() );
	endif;
	?>
	<fieldset id="give_checkout_user_info">
		<span><legend><?php echo apply_filters( 'give_checkout_personal_info_text', __( 'Personal Info', 'give' ) ); ?></legend></span>
		<?php do_action( 'give_purchase_form_before_email' ); ?>
		<p id="give-email-wrap">
			<label class="give-label" for="give-email">
				<?php _e( 'Email Address', 'give' ); ?>
				<?php if ( give_field_is_required( 'give_email' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'We will send the purchase receipt to this address.', 'give' ); ?></span>
			<input class="give-input required" type="email" name="give_email" placeholder="<?php _e( 'Email address', 'give' ); ?>" id="give-email" value="<?php echo is_user_logged_in() ? $user_data->user_email : ''; ?>" />
		</p>
		<?php do_action( 'give_purchase_form_after_email' ); ?>
		<p id="give-first-name-wrap">
			<label class="give-label" for="give-first">
				<?php _e( 'First Name', 'give' ); ?>
				<?php if ( give_field_is_required( 'give_first' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'We will use this to personalize your account experience.', 'give' ); ?></span>
			<input class="give-input required" type="text" name="give_first" placeholder="<?php _e( 'First name', 'give' ); ?>" id="give-first" value="<?php echo is_user_logged_in() ? $user_data->first_name : ''; ?>" />
		</p>

		<p id="give-last-name-wrap">
			<label class="give-label" for="give-last">
				<?php _e( 'Last Name', 'give' ); ?>
				<?php if ( give_field_is_required( 'give_last' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'We will use this as well to personalize your account experience.', 'give' ); ?></span>
			<input class="give-input<?php if ( give_field_is_required( 'give_last' ) ) {
				echo ' required';
			} ?>" type="text" name="give_last" id="give-last" placeholder="<?php _e( 'Last name', 'give' ); ?>" value="<?php echo is_user_logged_in() ? $user_data->last_name : ''; ?>" />
		</p>
		<?php do_action( 'give_purchase_form_user_info' ); ?>
	</fieldset>
<?php
}

add_action( 'give_purchase_form_after_user_info', 'give_user_info_fields' );
add_action( 'give_register_fields_before', 'give_user_info_fields' );

/**
 * Renders the credit card info form.
 *
 * @since 1.0
 * @return void
 */
function give_get_cc_form() {
	ob_start(); ?>

	<?php do_action( 'give_before_cc_fields' ); ?>

	<fieldset id="give_cc_fields" class="give-do-validate">
		<span><legend><?php _e( 'Credit Card Info', 'give' ); ?></legend></span>
		<?php if ( is_ssl() ) : ?>
			<div id="give_secure_site_wrapper">
				<span class="padlock"></span>
				<span><?php _e( 'This is a secure SSL encrypted payment.', 'give' ); ?></span>
			</div>
		<?php endif; ?>
		<p id="give-card-number-wrap">
			<label for="card_number" class="give-label">
				<?php _e( 'Card Number', 'give' ); ?>
				<span class="give-required-indicator">*</span>
				<span class="card-type"></span>
			</label>
			<span class="give-description"><?php _e( 'The (typically) 16 digits on the front of your credit card.', 'give' ); ?></span>
			<input type="text" autocomplete="off" name="card_number" id="card_number" class="card-number give-input required" placeholder="<?php _e( 'Card number', 'give' ); ?>" />
		</p>

		<p id="give-card-cvc-wrap">
			<label for="card_cvc" class="give-label">
				<?php _e( 'CVC', 'give' ); ?>
				<span class="give-required-indicator">*</span>
			</label>
			<span class="give-description"><?php _e( 'The 3 digit (back) or 4 digit (front) value on your card.', 'give' ); ?></span>
			<input type="text" size="4" autocomplete="off" name="card_cvc" id="card_cvc" class="card-cvc give-input required" placeholder="<?php _e( 'Security code', 'give' ); ?>" />
		</p>

		<p id="give-card-name-wrap">
			<label for="card_name" class="give-label">
				<?php _e( 'Name on the Card', 'give' ); ?>
				<span class="give-required-indicator">*</span>
			</label>
			<span class="give-description"><?php _e( 'The name printed on the front of your credit card.', 'give' ); ?></span>
			<input type="text" autocomplete="off" name="card_name" id="card_name" class="card-name give-input required" placeholder="<?php _e( 'Card name', 'give' ); ?>" />
		</p>
		<?php do_action( 'give_before_cc_expiration' ); ?>
		<p class="card-expiration">
			<label for="card_exp_month" class="give-label">
				<?php _e( 'Expiration (MM/YY)', 'give' ); ?>
				<span class="give-required-indicator">*</span>
			</label>
			<span class="give-description"><?php _e( 'The date your credit card expires, typically on the front of the card.', 'give' ); ?></span>
			<select id="card_exp_month" name="card_exp_month" class="card-expiry-month give-select give-select-small required">
				<?php for ( $i = 1; $i <= 12; $i ++ ) {
					echo '<option value="' . $i . '">' . sprintf( '%02d', $i ) . '</option>';
				} ?>
			</select>
			<span class="exp-divider"> / </span>
			<select id="card_exp_year" name="card_exp_year" class="card-expiry-year give-select give-select-small required">
				<?php for ( $i = date( 'Y' ); $i <= date( 'Y' ) + 10; $i ++ ) {
					echo '<option value="' . $i . '">' . substr( $i, 2 ) . '</option>';
				} ?>
			</select>
		</p>
		<?php do_action( 'give_after_cc_expiration' ); ?>

	</fieldset>
	<?php
	do_action( 'give_after_cc_fields' );

	echo ob_get_clean();
}

add_action( 'give_cc_form', 'give_get_cc_form' );

/**
 * Outputs the default credit card address fields
 *
 * @since 1.0
 * @return void
 */
function give_default_cc_address_fields() {

	$logged_in = is_user_logged_in();

	if ( $logged_in ) {
		$user_address = get_user_meta( get_current_user_id(), '_give_user_address', true );
	}
	$line1 = $logged_in && ! empty( $user_address['line1'] ) ? $user_address['line1'] : '';
	$line2 = $logged_in && ! empty( $user_address['line2'] ) ? $user_address['line2'] : '';
	$city  = $logged_in && ! empty( $user_address['city'] ) ? $user_address['city'] : '';
	$zip   = $logged_in && ! empty( $user_address['zip'] ) ? $user_address['zip'] : '';
	ob_start(); ?>
	<fieldset id="give_cc_address" class="cc-address">
		<span><legend><?php _e( 'Billing Details', 'give' ); ?></legend></span>
		<?php do_action( 'give_cc_billing_top' ); ?>
		<p id="give-card-address-wrap">
			<label for="card_address" class="give-label">
				<?php _e( 'Billing Address', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_address' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'The primary billing address for your credit card.', 'give' ); ?></span>
			<input type="text" id="card_address" name="card_address" class="card-address give-input<?php if ( give_field_is_required( 'card_address' ) ) {
				echo ' required';
			} ?>" placeholder="<?php _e( 'Address line 1', 'give' ); ?>" value="<?php echo $line1; ?>" />
		</p>

		<p id="give-card-address-2-wrap">
			<label for="card_address_2" class="give-label">
				<?php _e( 'Billing Address Line 2 (optional)', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_address_2' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'The suite, apt no, PO box, etc, associated with your billing address.', 'give' ); ?></span>
			<input type="text" id="card_address_2" name="card_address_2" class="card-address-2 give-input<?php if ( give_field_is_required( 'card_address_2' ) ) {
				echo ' required';
			} ?>" placeholder="<?php _e( 'Address line 2', 'give' ); ?>" value="<?php echo $line2; ?>" />
		</p>

		<p id="give-card-city-wrap">
			<label for="card_city" class="give-label">
				<?php _e( 'Billing City', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_city' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'The city for your billing address.', 'give' ); ?></span>
			<input type="text" id="card_city" name="card_city" class="card-city give-input<?php if ( give_field_is_required( 'card_city' ) ) {
				echo ' required';
			} ?>" placeholder="<?php _e( 'City', 'give' ); ?>" value="<?php echo $city; ?>" />
		</p>

		<p id="give-card-zip-wrap">
			<label for="card_zip" class="give-label">
				<?php _e( 'Billing Zip / Postal Code', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_zip' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'The zip or postal code for your billing address.', 'give' ); ?></span>
			<input type="text" size="4" name="card_zip" class="card-zip give-input<?php if ( give_field_is_required( 'card_zip' ) ) {
				echo ' required';
			} ?>" placeholder="<?php _e( 'Zip / Postal code', 'give' ); ?>" value="<?php echo $zip; ?>" />
		</p>

		<p id="give-card-country-wrap">
			<label for="billing_country" class="give-label">
				<?php _e( 'Billing Country', 'give' ); ?>
				<?php if ( give_field_is_required( 'billing_country' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'The country for your billing address.', 'give' ); ?></span>
			<select name="billing_country" id="billing_country" class="billing_country give-select<?php if ( give_field_is_required( 'billing_country' ) ) {
				echo ' required';
			} ?>">
				<?php

				$selected_country = give_get_shop_country();

				if ( $logged_in && ! empty( $user_address['country'] ) && '*' !== $user_address['country'] ) {
					$selected_country = $user_address['country'];
				}

				$countries = give_get_country_list();
				foreach ( $countries as $country_code => $country ) {
					echo '<option value="' . esc_attr( $country_code ) . '"' . selected( $country_code, $selected_country, false ) . '>' . $country . '</option>';
				}
				?>
			</select>
		</p>

		<p id="give-card-state-wrap">
			<label for="card_state" class="give-label">
				<?php _e( 'Billing State / Province', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_state' ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="give-description"><?php _e( 'The state or province for your billing address.', 'give' ); ?></span>
			<?php
			$selected_state = give_get_shop_state();
			$states         = give_get_shop_states( $selected_country );

			if ( $logged_in && ! empty( $user_address['state'] ) ) {
				$selected_state = $user_address['state'];
			}

			if ( ! empty( $states ) ) : ?>
				<select name="card_state" id="card_state" class="card_state give-select<?php if ( give_field_is_required( 'card_state' ) ) {
					echo ' required';
				} ?>">
					<?php
					foreach ( $states as $state_code => $state ) {
						echo '<option value="' . $state_code . '"' . selected( $state_code, $selected_state, false ) . '>' . $state . '</option>';
					}
					?>
				</select>
			<?php else : ?>
				<input type="text" size="6" name="card_state" id="card_state" class="card_state give-input" placeholder="<?php _e( 'State / Province', 'give' ); ?>" />
			<?php endif; ?>
		</p>
		<?php do_action( 'give_cc_billing_bottom' ); ?>
	</fieldset>
	<?php
	echo ob_get_clean();
}

add_action( 'give_after_cc_fields', 'give_default_cc_address_fields' );


/**
 * Renders the user registration fields. If the user is logged in, a login
 * form is displayed other a registration form is provided for the user to
 * create an account.
 *
 * @since 1.0
 * @return string
 */
function give_get_register_fields() {
	global $give_options;
	global $user_ID;

	if ( is_user_logged_in() ) {
		$user_data = get_userdata( $user_ID );
	}

	$show_register_form = give_get_option( 'show_register_form', 'none' );

	ob_start(); ?>
	<fieldset id="give_register_fields">

		<?php if ( $show_register_form == 'both' ) { ?>
			<p id="give-login-account-wrap"><?php _e( 'Already have an account?', 'give' ); ?>
				<a href="<?php echo add_query_arg( 'login', 1 ); ?>" class="give_checkout_register_login" data-action="checkout_login"><?php _e( 'Login', 'give' ); ?></a>
			</p>
		<?php } ?>

		<?php do_action( 'give_register_fields_before' ); ?>

		<fieldset id="give_register_account_fields">
			<span><legend><?php _e( 'Create an account', 'give' );
					if ( ! give_no_guest_checkout() ) {
						echo ' ' . __( '(optional)', 'give' );
					} ?></legend></span>
			<?php do_action( 'give_register_account_fields_before' ); ?>
			<p id="give-user-login-wrap">
				<label for="give_user_login">
					<?php _e( 'Username', 'give' ); ?>
					<?php if ( give_no_guest_checkout() ) { ?>
						<span class="give-required-indicator">*</span>
					<?php } ?>
				</label>
				<span class="give-description"><?php _e( 'The username you will use to log into your account.', 'give' ); ?></span>
				<input name="give_user_login" id="give_user_login" class="<?php if ( give_no_guest_checkout() ) {
					echo 'required ';
				} ?>give-input" type="text" placeholder="<?php _e( 'Username', 'give' ); ?>" title="<?php _e( 'Username', 'give' ); ?>" />
			</p>

			<p id="give-user-pass-wrap">
				<label for="password">
					<?php _e( 'Password', 'give' ); ?>
					<?php if ( give_no_guest_checkout() ) { ?>
						<span class="give-required-indicator">*</span>
					<?php } ?>
				</label>
				<span class="give-description"><?php _e( 'The password used to access your account.', 'give' ); ?></span>
				<input name="give_user_pass" id="give_user_pass" class="<?php if ( give_no_guest_checkout() ) {
					echo 'required ';
				} ?>give-input" placeholder="<?php _e( 'Password', 'give' ); ?>" type="password" />
			</p>

			<p id="give-user-pass-confirm-wrap" class="give_register_password">
				<label for="password_again">
					<?php _e( 'Password Again', 'give' ); ?>
					<?php if ( give_no_guest_checkout() ) { ?>
						<span class="give-required-indicator">*</span>
					<?php } ?>
				</label>
				<span class="give-description"><?php _e( 'Confirm your password.', 'give' ); ?></span>
				<input name="give_user_pass_confirm" id="give_user_pass_confirm" class="<?php if ( give_no_guest_checkout() ) {
					echo 'required ';
				} ?>give-input" placeholder="<?php _e( 'Confirm password', 'give' ); ?>" type="password" />
			</p>
			<?php do_action( 'give_register_account_fields_after' ); ?>
		</fieldset>

		<?php do_action( 'give_register_fields_after' ); ?>

		<input type="hidden" name="give-purchase-var" value="needs-to-register" />

		<?php do_action( 'give_purchase_form_user_info' ); ?>

	</fieldset>
	<?php
	echo ob_get_clean();
}

add_action( 'give_purchase_form_register_fields', 'give_get_register_fields' );

/**
 * Gets the login fields for the login form on the checkout. This function hooks
 * on the give_purchase_form_login_fields to display the login form if a user already
 * had an account.
 *
 * @since 1.0
 * @return string
 */
function give_get_login_fields() {
	global $give_options;

	$color = isset( $give_options['checkout_color'] ) ? $give_options['checkout_color'] : 'gray';
	$color = ( $color == 'inherit' ) ? '' : $color;
	$style = isset( $give_options['button_style'] ) ? $give_options['button_style'] : 'button';

	$show_register_form = give_get_option( 'show_register_form', 'none' );

	ob_start(); ?>
	<fieldset id="give_login_fields">
		<?php if ( $show_register_form == 'both' ) { ?>
			<p id="give-new-account-wrap">
				<?php _e( 'Need to create an account?', 'give' ); ?>
				<a href="<?php echo remove_query_arg( 'login' ); ?>" class="give_checkout_register_login" data-action="checkout_register">
					<?php _e( 'Register', 'give' );
					if ( ! give_no_guest_checkout() ) {
						echo ' ' . __( 'or checkout as a guest.', 'give' );
					} ?>
				</a>
			</p>
		<?php } ?>
		<?php do_action( 'give_checkout_login_fields_before' ); ?>
		<p id="give-user-login-wrap">
			<label class="give-label" for="give-username"><?php _e( 'Username', 'give' ); ?></label>
			<input class="<?php if ( give_no_guest_checkout() ) {
				echo 'required ';
			} ?>give-input" type="text" name="give_user_login" id="give_user_login" value="" placeholder="<?php _e( 'Your username', 'give' ); ?>" />
		</p>

		<p id="give-user-pass-wrap" class="give_login_password">
			<label class="give-label" for="give-password"><?php _e( 'Password', 'give' ); ?></label>
			<input class="<?php if ( give_no_guest_checkout() ) {
				echo 'required ';
			} ?>give-input" type="password" name="give_user_pass" id="give_user_pass" placeholder="<?php _e( 'Your password', 'give' ); ?>" />
			<input type="hidden" name="give-purchase-var" value="needs-to-login" />
		</p>

		<p id="give-user-login-submit">
			<input type="submit" class="give-submit button <?php echo $color; ?>" name="give_login_submit" value="<?php _e( 'Login', 'give' ); ?>" />
		</p>
		<?php do_action( 'give_checkout_login_fields_after' ); ?>
	</fieldset><!--end #give_login_fields-->
	<?php
	echo ob_get_clean();
}

add_action( 'give_purchase_form_login_fields', 'give_get_login_fields' );

/**
 * Renders the payment mode form by getting all the enabled payment gateways and
 * outputting them as radio buttons for the user to choose the payment gateway. If
 * a default payment gateway has been chosen from the Give Settings, it will be
 * automatically selected.
 *
 * @since 1.0
 * @return void
 */
function give_payment_mode_select() {

	$gateways = give_get_enabled_payment_gateways();

	do_action( 'give_payment_mode_top' ); ?>

	<fieldset id="give-payment-mode-select">
		<?php do_action( 'give_payment_mode_before_gateways_wrap' ); ?>
		<div id="give-payment-mode-wrap">
			<span class="give-payment-mode-label"><?php _e( 'Select Payment Method', 'give' ); ?></span><br />
			<?php

			do_action( 'give_payment_mode_before_gateways' );

			foreach ( $gateways as $gateway_id => $gateway ) :
				$checked       = checked( $gateway_id, give_get_default_gateway(), false );
				$checked_class = $checked ? ' give-gateway-option-selected' : '';
				echo '<label for="give-gateway-' . esc_attr( $gateway_id ) . '" class="give-gateway-option' . $checked_class . '" id="give-gateway-option-' . esc_attr( $gateway_id ) . '">';
				echo '<input type="radio" name="payment-mode" class="give-gateway" id="give-gateway-' . esc_attr( $gateway_id ) . '" value="' . esc_attr( $gateway_id ) . '"' . $checked . '>' . esc_html( $gateway['checkout_label'] );
				echo '</label>';
			endforeach;

			do_action( 'give_payment_mode_after_gateways' );

			?>
		</div>
		<?php do_action( 'give_payment_mode_after_gateways_wrap' ); ?>
	</fieldset>

	<div id="give_purchase_form_wrap"></div><!-- the checkout fields are loaded into this-->

	<?php do_action( 'give_payment_mode_bottom' );

}

add_action( 'give_payment_mode_select', 'give_payment_mode_select' );


/**
 * Renders the Checkout Agree to Terms, this displays a checkbox for users to
 * agree the T&Cs set in the EDD Settings. This is only displayed if T&Cs are
 * set in the EDD Settings.
 *
 * @since 1.0
 * @global $give_options Array of all the Give Options
 * @return void
 */
function give_terms_agreement() {
	global $give_options;
	if ( isset( $give_options['show_agree_to_terms'] ) ) {
		?>
		<fieldset id="give_terms_agreement">
			<div id="give_terms" style="display:none;">
				<?php
				do_action( 'give_before_terms' );
				echo wpautop( stripslashes( $give_options['agree_text'] ) );
				do_action( 'give_after_terms' );
				?>
			</div>
			<div id="give_show_terms">
				<a href="#" class="give_terms_links"><?php _e( 'Show Terms', 'give' ); ?></a>
				<a href="#" class="give_terms_links" style="display:none;"><?php _e( 'Hide Terms', 'give' ); ?></a>
			</div>
			<label for="give_agree_to_terms"><?php echo isset( $give_options['agree_label'] ) ? stripslashes( $give_options['agree_label'] ) : __( 'Agree to Terms?', 'give' ); ?></label>
			<input name="give_agree_to_terms" class="required" type="checkbox" id="give_agree_to_terms" value="1" />
		</fieldset>
	<?php
	}
}

add_action( 'give_purchase_form_before_submit', 'give_terms_agreement' );

/**
 * Shows the final purchase total at the bottom of the checkout page
 *
 * @since 1.0
 * @return void
 */
function give_checkout_final_total() {

	//Only proceed if give_total available
	if ( ! isset( $_POST['give_total'] ) ) {
		return;
	}

	$total = apply_filters( 'give_cart_total', give_currency_filter( $_POST['give_total'] ) );

	?>
	<p id="give-final-total-wrap">
		<strong><?php echo apply_filters( 'give_donation_total_label', __( 'Donation Total:', 'give' ) ); ?></strong>
		<span class="give-final-total-amount" data-total="<?php echo $_POST['give_total']; ?>"><?php echo $total; ?></span>
	</p>
<?php
}

add_action( 'give_purchase_form_before_submit', 'give_checkout_final_total', 999 );


/**
 * Renders the Checkout Submit section
 *
 * @since 1.3.3
 * @return void
 */
function give_checkout_submit() {
	?>
	<fieldset id="give_purchase_submit">
		<?php do_action( 'give_purchase_form_before_submit' ); ?>

		<?php give_checkout_hidden_fields(); ?>

		<?php echo give_checkout_button_purchase(); ?>

		<?php do_action( 'give_purchase_form_after_submit' ); ?>

	</fieldset>
<?php
}

add_action( 'give_purchase_form_after_cc_form', 'give_checkout_submit', 9999 );


/**
 * Renders the Purchase button on the Checkout
 *
 * @since 1.0
 * @global $give_options Array of all the EDD Options
 * @return string
 */
function give_checkout_button_purchase() {
	global $give_options;

	$complete_purchase = ! empty( $give_options['checkout_label'] ) ? $give_options['checkout_label'] : __( 'Donate Now', 'give' );

	ob_start();

	?>
	<input type="submit" class="give-submit" id="give-purchase-button" name="give-purchase" value="<?php echo $complete_purchase; ?>" />
	<?php
	return apply_filters( 'give_checkout_button_purchase', ob_get_clean() );
}

/**
 * Outputs the JavaScript code for the Agree to Terms section to toggle
 * the T&Cs text
 *
 * @since 1.0
 * @global $give_options Array of all the EDD Options
 * @return void
 */
function give_agree_to_terms_js() {
	global $give_options;

	if ( isset( $give_options['show_agree_to_terms'] ) ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( 'body' ).on( 'click', '.give_terms_links', function ( e ) {
					//e.preventDefault();
					$( '#give_terms' ).slideToggle();
					$( '.give_terms_links' ).toggle();
					return false;
				} );
			} );
		</script>
	<?php
	}
}

add_action( 'give_checkout_form_top', 'give_agree_to_terms_js' );

/**
 * Renders the hidden Checkout fields
 *
 * @since 1.0
 * @return void
 */
function give_checkout_hidden_fields() {
	?>
	<?php if ( is_user_logged_in() ) { ?>
		<input type="hidden" name="give-user-id" value="<?php echo get_current_user_id(); ?>" />
	<?php } ?>
	<input type="hidden" name="give_action" value="purchase" />
	<input type="hidden" name="give-gateway" value="<?php echo give_get_chosen_gateway(); ?>" />
<?php
}

/**
 * Filter Success Page Content
 *
 * Applies filters to the success page content.
 *
 * @since 1.0
 *
 * @param string $content Content before filters
 *
 * @return string $content Filtered content
 */
function give_filter_success_page_content( $content ) {
	global $give_options;

	if ( isset( $give_options['success_page'] ) && isset( $_GET['payment-confirmation'] ) && is_page( $give_options['success_page'] ) ) {
		if ( has_filter( 'give_payment_confirm_' . $_GET['payment-confirmation'] ) ) {
			$content = apply_filters( 'give_payment_confirm_' . $_GET['payment-confirmation'], $content );
		}
	}

	return $content;
}

add_filter( 'the_content', 'give_filter_success_page_content' );

/**
 * Show a download's files in the purchase receipt
 *
 * @since 1.8.6
 * @return boolean
 */
function give_receipt_show_download_files( $item_id, $receipt_args ) {
	return apply_filters( 'give_receipt_show_download_files', true, $item_id, $receipt_args );
}
