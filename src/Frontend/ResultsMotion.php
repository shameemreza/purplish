<?php
/**
 * Results change on Product Collection.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the script that moves a collection's cards when its results change.
 *
 * Filters, sorting and page links swap a collection's products in place
 * through WooCommerce's client-side navigation. The class comes from
 * BlockEffects; this only loads the script, and only for a collection that
 * swaps in place, since Force page reload loads a new page instead.
 */
final class ResultsMotion {

	const COLLECTION = 'woocommerce/product-collection';
	const HANDLE     = 'purplish-results';

	/**
	 * Hooks in.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_' . self::COLLECTION, array( $this, 'load' ), 10, 2 );
	}

	/**
	 * Loads the script for a rendered collection with Results change on.
	 *
	 * @param string $html  Rendered block.
	 * @param array  $block Parsed block.
	 * @return string The markup, unchanged.
	 */
	public function load( $html, $block ) {
		if ( self::wants( $html, $block ) ) {
			$asset = include PURPLISH_DIR . 'build/results.asset.php';
			wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/results.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		}
		return $html;
	}

	/**
	 * Whether a rendered collection needs the script.
	 *
	 * @param string $html  Rendered block.
	 * @param array  $block Parsed block.
	 * @return bool
	 */
	public static function wants( $html, $block ) {
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		if ( '' === trim( (string) $html ) || ! empty( $attrs['forcePageReload'] ) ) {
			return false;
		}
		$saved = isset( $attrs['purplish'] ) ? $attrs['purplish'] : array();
		foreach ( BlockEffects::classes_for( self::COLLECTION, $saved ) as $class ) {
			if ( 0 === strpos( $class, 'purplish-cards-results-' ) ) {
				return true;
			}
		}
		return false;
	}
}
