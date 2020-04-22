<?php
/**
 * Payment receipt view.
 *
 * @since 2.7.0
 */
use Give\Views\IframeContentView;

$iframeView = new IframeContentView();

echo $iframeView->setTitle( __( 'Donation Receipt', 'give' ) )
	->setBody( '<div id="give-receipt"></div>' )->render();
