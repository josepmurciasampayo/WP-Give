<?php
/**
 * Edit Payment Template
 *
 * @package     Give
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2013, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$payment_id   = absint( $_GET['purchase_id'] );
$payment      = get_post( $payment_id );
$payment_data = give_get_payment_meta( $payment_id );
?>
<div class="wrap">
	<h2><?php _e( 'Edit Payment', 'give' ); ?>: <?php echo get_the_title( $payment_id ) . ' - #' . $payment_id; ?> -
		<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=edd-payment-history' ); ?>" class="button-secondary"><?php _e( 'Go Back', 'give' ); ?></a>
	</h2>

	<form id="edd-edit-payment" action="" method="post">
		<table class="form-table">
			<tbody>
			<?php do_action( 'give_edit_payment_top', $payment->ID ); ?>
			<tr>
				<th scope="row" valign="top">
					<span><?php _e( 'Donator\'s Email', 'give' ); ?></span>
				</th>
				<td>
					<input class="regular-text" type="text" name="edd-donator-email" id="edd-donator-email" value="<?php echo give_get_payment_user_email( $payment_id ); ?>" />

					<p class="description"><?php _e( 'If needed, you can update the donator\'s email here.', 'give' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<span><?php _e( 'Donator\'s User ID', 'give' ); ?></span>
				</th>
				<td>
					<input class="small-text" type="number" min="-1" step="1" name="edd-donator-user-id" id="edd-donator-user-id" value="<?php echo give_get_payment_user_id( $payment_id ); ?>" />

					<p class="description"><?php _e( 'If needed, you can update the donator\'s WordPress user ID here.', 'give' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<span><?php printf( __( 'Payment Amount in %s', 'give' ), give_get_currency() ); ?></span>
				</th>
				<td>
					<input class="small-text" type="number" min="0" step="0.01" name="edd-payment-amount" id="edd-payment-amount" value="<?php echo give_get_payment_amount( $payment_id ); ?>" />

					<p class="description"><?php _e( 'If needed, you can update the purchase total here.', 'give' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<span><?php _e( 'Downloads Purchased', 'give' ); ?></span>
				</th>
				<td id="purchased-downloads">
					<?php
					$downloads  = maybe_unserialize( $payment_data['downloads'] );
					$cart_items = isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;
					if ( $downloads ) {
						foreach ( $downloads as $download ) {
							$id = isset( $payment_data['cart_details'] ) ? $download['id'] : $download;

							if ( isset( $download['options']['price_id'] ) ) {
								$variable_prices = '<input type="hidden" name="edd-purchased-downloads[' . $id . '][options][price_id]" value="' . $download['options']['price_id'] . '" />';
								$variable_prices .= '(' . give_get_price_option_name( $id, $download['options']['price_id'], $payment_id ) . ')';
							} else {
								$variable_prices = '';
							}

							echo '<div class="purchased_download_' . $id . '">
											<input type="hidden" name="edd-purchased-downloads[' . $id . ']" value="' . $id . '"/>
											<strong>' . get_the_title( $id ) . ' ' . $variable_prices . '</strong> - <a href="#" class="edd-remove-purchased-download" data-action="remove_purchased_download" data-id="' . $id . '">' . __( 'Remove', 'give' ) . '</a>
										  </div>';
						}
					}
					?>
					<p id="edit-downloads">
						<a href="#TB_inline?width=640&amp;inlineId=available-downloads" class="thickbox" title="<?php printf( __( 'Add %s to purchase', 'give' ), strtolower( give_get_forms_label_plural() ) ); ?>"><?php printf( __( 'Add %s to purchase', 'give' ), strtolower( give_get_forms_label_plural() ) ); ?></a>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<span><?php _e( 'Payment Notes', 'give' ); ?></span>
				</th>
				<td>
					<?php
					$notes = give_get_payment_notes( $payment->ID );
					if ( ! empty( $notes ) ) {
						echo '<ul id="payment-notes">';
						foreach ( $notes as $note ) {
							if ( ! empty( $note->user_id ) ) {
								$user = get_userdata( $note->user_id );
								$user = $user->display_name;
							} else {
								$user = __( 'Give System', 'give' );
							}
							$delete_note_url = wp_nonce_url( add_query_arg( array(
								'edd-action' => 'delete_payment_note',
								'note_id'    => $note->comment_ID
							) ), 'give_delete_payment_note' );
							echo '<li>';
							echo '<strong>' . $user . '</strong>&nbsp;<em>' . $note->comment_date . '</em>&nbsp;&mdash;&nbsp;' . $note->comment_content;
							echo '&nbsp;&ndash;&nbsp;<a href="' . $delete_note_url . '" class="edd-delete-payment-note" title="' . __( 'Delete this payment note', 'give' ) . '">' . __( 'Delete', 'give' ) . '</a>';
							echo '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>' . __( 'No payment notes', 'give' ) . '</p>';
					}
					?>
					<label for="edd-payment-note"><?php _e( 'Add New Note', 'give' ); ?></label><br />
					<textarea name="edd-payment-note" id="edd-payment-note" cols="30" rows="5"></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<span><?php _e( 'Payment Status', 'give' ); ?></span>
				</th>
				<td>
					<select name="edd-payment-status" id="give_payment_status">
						<?php
						$status   = $payment->post_status; // Current status
						$statuses = give_get_payment_statuses();
						foreach ( $statuses as $status_id => $label ) {
							echo '<option value="' . $status_id . '" ' . selected( $status, $status_id, false ) . '>' . $label . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<span><?php _e( 'Unlimited Downloads', 'give' ); ?></span>
				</th>
				<td>
					<input type="checkbox" name="edd-unlimited-downloads" id="give_unlimited_downloads" value="1"<?php checked( true, get_post_meta( $payment_id, '_unlimited_file_downloads', true ) ); ?>/>
					<label class="description" for="give_unlimited_downloads"><?php _e( 'Check this box to enable unlimited file downloads for this purchase.', 'give' ); ?></label>
				</td>
			</tr>
			<tr id="give_payment_notification" style="display:none;">
				<th scope="row" valign="top">
					<span><?php _e( 'Send Purchase Receipt', 'give' ); ?></span>
				</th>
				<td>
					<input type="checkbox" name="edd-payment-send-email" id="give_send_email" value="yes" />
					<label class="description" for="give_send_email"><?php _e( 'Check this box to send the purchase receipt, including all download links.', 'give' ); ?></label>
				</td>
			</tr>
			<?php do_action( 'give_edit_payment_bottom', $payment->ID ); ?>
			</tbody>
		</table>

		<input type="hidden" name="give_action" value="edit_payment" />
		<input type="hidden" name="edd-old-status" value="<?php echo $status; ?>" />
		<input type="hidden" name="payment-id" value="<?php echo $payment_id; ?>" />
		<?php wp_nonce_field( 'give_payment_nonce', 'edd-payment-nonce' ); ?>
		<?php echo submit_button( __( 'Update Payment', 'give' ) ); ?>
	</form>
	<div id="available-downloads" style="display:none;">
		<form id="edd-add-downloads-to-purchase">
			<p>
				<?php echo Give()->html->product_dropdown( 'downloads[0][id]' ); ?>
				&nbsp;<img src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" class="hidden give_add_download_to_purchase_waiting waiting" />
			</p>

			<p>
				<a href="#" class="button-secondary edd-add-another-download"><?php echo sprintf( __( 'Add Another %s', 'give' ), esc_html( give_get_forms_label_singular() ) ); ?></a>
			</p>

			<p>
				<a id="edd-add-download" class="button-primary" title="<?php _e( 'Add Selected Downloads', 'give' ); ?>"><?php _e( 'Add Selected Downloads', 'give' ); ?></a>
				<a id="edd-close-add-download" class="button-secondary" onclick="tb_remove();" title="<?php _e( 'Close', 'give' ); ?>"><?php _e( 'Close', 'give' ); ?></a>
			</p>
			<?php wp_nonce_field( 'give_add_downloads_to_purchase_nonce', 'give_add_downloads_to_purchase_nonce' ); ?>
		</form>
	</div>
</div>