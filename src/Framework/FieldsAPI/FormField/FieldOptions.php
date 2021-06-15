<?php

namespace Give\Framework\FieldsAPI\FormField;

trait FieldOptions {

	/** @var FieldOption[] */
	protected $options = [];

	/**
	 * Does the field type support options?
	 *
	 * @return bool
	 */
	public function supportsOptions() {
		return in_array(
			$this->getType(),
			[
				FieldTypes::TYPE_SELECT,
				FieldTypes::TYPE_RADIO,
			],
			true
		);
	}

	/**
	 * Set the options
	 *
	 * Note that the keys of associative arrays are not supported for setting values or labels.
	 * For setting labels either use `new FieldOption($value, $label)` or `[$value, $label]`.
	 * In either case, the label is optional.
	 *
	 * @param FieldOption[]|array[]|array $options
	 *
	 * @return $this
	 */
	public function options( array $options ) {
		// Reset options, since they are meant to be set immutably
		$this->options = [];

		// Loop through the options and transform them to the proper format.
		foreach ( $options as $value ) {
			if ( $value instanceof FieldOption ) {
				// In this case, what is provided matches the proper format, so we can just append it.
				$this->options[] = $value;
			} elseif ( is_array( $value ) ) {
				// In this case, what has been provided in the value is an array with a value then a label.
				// This matches the constructor of `FieldOption`, so we can unpack it as arguments for a new instance.
				$this->options[] = new FieldOption( ...$value );
			} else {
				// In this case, we just have a value which is the bare minimum required for a `FieldOption`.
				$this->options[] = new FieldOption( $value );
			}
		}

		return $this;
	}

	/**
	 * Access the options
	 *
	 * @return FieldOption[]
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Walk through the options
	 *
	 * @unreleased
	 *
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function walkOptions( callable $callback ) {
		foreach ( $this->options as $option ) {
			// Call the callback for each option.
			if ( $callback( $option ) === false ) {
				// Returning false breaks the loop.
				break;
			}
		}
	}
}
