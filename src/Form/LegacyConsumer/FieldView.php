<?php

namespace Give\Form\LegacyConsumer;

use Give\Framework\FieldsAPI\FormField;

class FieldView {
	public static function render( FormField $field ) {
		echo $field->getType();
		echo '<div class="form-row form-row-wide">';
			ob_start();
			include plugin_dir_path( __FILE__ ) . '/templates/label.html.php';
			include plugin_dir_path( __FILE__ ) . '/templates/' . $field->getType() . '.html.php';
			echo self::mergeAttributes( ob_get_clean(), $field );
		echo '</div>';
	}

	protected static function mergeAttributes( $html, $field ) {
		$attributes = array_map(
			function( $key, $value ) {
				return sprintf( '%s="%s"', $key, $value );
			},
			array_keys( $field->getAttributes() ),
			array_values( $field->getAttributes() )
		);
		return str_replace( '@attributes', implode( ' ', $attributes ), $html );
	}
}
