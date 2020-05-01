<?php
namespace Give\Helpers\Form\Template\Utils\Admin;

use Give\Form\Template;
use Give\FormAPI\Form\Field;
use Give\FormAPI\Group;
use WP_Post;
use Give\Helpers\Form\Template as FormTemplateUtils;


/**
 * Render template setting in form metabox.
 *
 * @since 2.7.0
 *
 * @global WP_Post $post
 * @param Template $template
 * @return string
 */
function renderMetaboxSettings( $template ) {
	global $post;

	ob_start();

	$saveOptions = FormTemplateUtils::getOptions( $post->ID, $template->getID() );

	/* @var Group $option */
	foreach ( $template->getOptions()->groups as $group ) {
		printf(
			'<div class="give-row %1$s">',
			$group->id
		);

		printf(
			'<div class="give-row-head">
							<button type="button" class="handlediv" aria-expanded="true">
								<span class="toggle-indicator"/>
							</button>
							<h2 class="hndle"><span>%1$s</span></h2>
						</div>',
			$group->name
		);

		echo '<div class="give-row-body">';

		/* @var Field $field */
		foreach ( $group->fields as $field ) {
			$field = $field->toArray();
			if ( isset( $saveOptions[ $group->id ][ $field['id'] ] ) ) {
				$field['attributes']['value'] = $saveOptions[ $group->id ][ $field['id'] ];
			}

			$field['id'] = "{$template->getID()}[{$group->id}][{$field['id']}]";

			give_render_field( $field );
		}

		echo '</div></div>';
	}

	return ob_get_clean();
}


