<?php

namespace Give\Framework\FieldsAPI\FieldCollection;

use Give\Framework\FieldsAPI\FieldCollection\Contract\Node;
use Give\Framework\FieldsAPI\FieldCollection\Contract\GroupNode;
use Give\Framework\FieldsAPI\FieldCollection\Exception\ReferenceNodeNotFoundException;

/**
 * @unreleased
 */
trait InsertNode {

	/**
	 * @unreleased
	 */
	public function insertAfter( $siblingName, Node $node ) {
		$this->checkNameCollisionDeep( $node );
		$this->_insertAfter( $siblingName, $node );
		return $this;
	}

	/**
	 * @unreleased
	 */
	protected function _insertAfter( $siblingName, Node $node ) {
		$siblingIndex = $this->getNodeIndexByName( $siblingName );
		if ( false !== $siblingIndex ) {
			return $this->insertAtIndex(
				$siblingIndex + 1,
				$node
			);
		} elseif ( $this->nodes ) {
			foreach ( $this->nodes as $childNode ) {
				if ( $childNode instanceof GroupNode ) {
					$childNode->_insertAfter( $siblingName, $node );
				}
			}
			return;
		}
		throw new ReferenceNodeNotFoundException( $siblingName );
	}

	/**
	 * @unreleased
	 */
	public function insertBefore( $siblingName, Node $node ) {
		$this->checkNameCollisionDeep( $node );
		$this->_insertBefore( $siblingName, $node );
		return $this;
	}

	/**
	 * @unreleased
	 */
	protected function _insertBefore( $siblingName, Node $node ) {
		$siblingIndex = $this->getNodeIndexByName( $siblingName );
		if ( false !== $siblingIndex ) {
			return $this->insertAtIndex(
				$siblingIndex - 1,
				$node
			);
		} elseif ( $this->nodes ) {
			foreach ( $this->nodes as $childNode ) {
				if ( $childNode instanceof GroupNode ) {
					$childNode->_insertBefore( $siblingName, $node );
				}
			}
			return;
		}
		throw new ReferenceNodeNotFoundException( $siblingName );
	}

	/**
	 * @unreleased
	 */
	protected function insertAtIndex( $index, $node ) {
		array_splice( $this->nodes, $index, 0, [ $node ] );
	}
}
