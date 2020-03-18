<?php

	$goal_stats = give_goal_progress_stats( $form_id );
	echo '<h1>' . $goal_stats['format'] . '</h1>';

	// Setup default raised value
	$raised = give_currency_filter(
		give_format_amount(
			$form->get_earnings(),
			array(
				'sanitize' => false,
				'decimal'  => false,
			)
		)
	);

	// Setup default count value
	$count = $form->get_sales();

	// Setup default count label
	$countLabel = __( 'donations', 'give' );

	// Setup default goal value
	$goal = give_currency_filter(
		give_format_amount(
			$form->get_goal(),
			array(
				'sanitize' => false,
				'decimal'  => false,
			)
		)
	);

	// Change values and labels based on goal format
	switch ( $goal_stats['format'] ) {
		case 'percentage': {
			$raised = "{$goal_stats['progress']}%";
			break;
		}
		case 'donation': {
			$count = $goal_stats['actual'];
			$goal  = $goal_stats['goal'];
			break;
		}
		case 'donors': {
			$count      = $goal_stats['actual'];
			$countLabel = __( 'donors', 'give' );
			$goal       = $goal_stats['goal'];
			break;
		}
	}
	?>
<div class="give-section form-stats">
	<div class="raised">
		<div class="number">
			<?php echo $raised; ?>
		</div>
		<div class="text"><?php _e( 'raised', 'give' ); ?></div>
	</div>
	<div class="count">
		<div class="number">
			<?php echo $count; ?>
		</div>
		<div class="text"><?php echo $countLabel; ?></div>
	</div>
	<?php if ( $form->has_goal() ) : ?>
		<div class="goal">
			<div class="number">
			<?php echo $goal; ?>
			</div>
			<div class="text">goal</div>
		</div>
	<?php endif; ?>
</div>
