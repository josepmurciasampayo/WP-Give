<?php
/**
 * @var array $shortcodeArgs Array of form shortcode arguments.
 */
global $post; ?>
<!DOCTYPE html>
<html lang="<?php bloginfo( 'language' ); ?>" style="margin-top: 0 !important;">
	<head>
		<meta charset="utf-8">
		<title><?php echo esc_html( $post->post_title ); ?></title>
		<?php
		/**
		 * Fire the action hook in header
		 */
		do_action( 'give_embed_head' );
		?>
	</head>
	<body class="give-form-templates">
		<?php
		// Fetch the Give Form.
		give_get_donation_form( $shortcodeArgs );

		/**
		 * Fire the action hook in footer
		 */
		do_action( 'give_embed_footer' );
		?>
	</body>
</html>
