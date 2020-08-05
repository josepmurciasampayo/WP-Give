<?php

// same as default WP from wp-admin/admin-header.php.
$wp_version_class = 'branch-' . str_replace( [ '.', ',' ], '-', floatval( get_bloginfo( 'version' ) ) );

set_current_screen();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php esc_html_e( 'GiveWP &rsaquo; Onboarding Wizard', 'give' ); ?></title>
	<?php wp_print_styles( [ 'give-styles' ] ); ?>

	<style>
		body {
			margin: 0;
			padding: 0;
		}

		.iframe-loader {
			min-height: 776px !important;
		}
	</style>
</head>
	<body class="<?php echo esc_attr( $wp_version_class ); ?>">
			<?php
			echo give_form_shortcode(
				[
					'id' => 18,
				]
			);
			?>
			<?php wp_print_scripts( [ 'give' ] ); ?>
	</body>
</html>
