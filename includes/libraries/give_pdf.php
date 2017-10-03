<?php
/**
 * PDF MultiCell Table Class.
 *
 * @package     Give PDFs
 * @subpackage  TCPDF
 * @since       1.8.14
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if TCPDF lib not load through composer.
if ( ! file_exists( GIVE_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	wp_die( __( 'It requires TCPDF lib. Please install it through composer.', 'give' ) );
	exit;
}

// Load autoloader.
require_once GIVE_PLUGIN_DIR . '/vendor/autoload.php';

// Check if TCPDF class exist.
if ( ! class_exists( 'TCPDF') ) {
	wp_die( __( 'It requires TCPDF lib. Please update composer to load TCPDF lib.', 'give' ) );
	exit;
}

/**
 * Class Give_PDF
 */
class Give_PDF extends TCPDF {

	/**
	 * Width.
	 *
	 * @var int $widths Width.
	 */
	var $widths;

	/**
	 * Alignment.
	 *
	 * @var string $aligns Alignment.
	 */
	var $aligns;

	/**
	 * Set Header.
	 */
	function Header() {
	}

	/**
	 * Set Footer.
	 */
	function Footer() {
		$this->SetY( - 15 );
		$this->SetFont( 'Helvetica', 'I', 8 );
		$this->Cell( 0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C' );
	}

	/**
	 * Set Width.
	 *
	 * @param int $w Cell Width.
	 */
	function SetWidths( $w ) {
		$this->widths = $w;
	}

	/**
	 * Set Alignment.
	 *
	 * @param string $a Cell Alignment.
	 */
	function SetAligns( $a ) {
		$this->aligns = $a;
	}

	/**
	 * Set Table Row.
	 *
	 * @param array $data Set data in a row.
	 */
	function Row( $data ) {
		$nb = 0;

		for ( $i = 0; $i < count( $data ); $i ++ ) {
			$nb = max( $nb, $this->getNumLines( $data[ $i ], $this->widths[ $i ] ) );
			$h  = 5 * $nb;

			for ( $i = 0; $i < count( $data ); $i ++ ) {
				$w = $this->widths[ $i ];
				$a = isset( $this->aligns[ $i ] ) ? $this->aligns[ $i ] : 'L';

				$x = $this->GetX();
				$y = $this->GetY();
				$this->Rect( $x, $y, $w, $h );
				$this->MultiCell( $w, $h, $data[ $i ], 0, $a, false, 1, '', '', true, 0, false, true, 0, 'M', false );
				$this->SetXY( $x + $w, $y );
			}
		}

		$this->Ln( $h );
	}
}