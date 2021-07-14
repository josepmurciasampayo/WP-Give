<?php

namespace Give\Framework\FieldsAPI\Factory\Exception;

use Give\Framework\Exceptions\Primitives\Exception;

class TypeNotSupported extends Exception {
	public function __construct( $type, $code = 0, $previous = null ) {
		$message = "Field type $type is not supported";
		parent::__construct( $message, $code, $previous );
	}
}
