<?php
/**
 * "Count" on Product Results Count.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Shows the results count as just the number, the number in brackets or
 * "23 products", for a shop hero next to the title. WooCommerce's sentence
 * stays in the status element for screen readers.
 */
final class ResultsCount {

	const BLOCK = 'woocommerce/product-results-count';

	/**
	 * Hooks in.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_' . self::BLOCK, array( $this, 'render' ), 10, 2 );
	}

	/**
	 * The saved Count option, or '' for WooCommerce's sentence.
	 *
	 * @param mixed $saved The block's saved Purplish options.
	 * @return string
	 */
	public static function show( $saved ) {
		$classes = is_array( $saved ) ? BlockEffects::classes_for( self::BLOCK, $saved ) : array();
		foreach ( $classes as $class ) {
			if ( preg_match( '/^purplish-count-show-([a-z]+)$/', $class, $found ) ) {
				return $found[1];
			}
		}
		return '';
	}

	/**
	 * Swaps the sentence for the number.
	 *
	 * @param string $block_content The block HTML.
	 * @param array  $block         The parsed block.
	 * @return string
	 */
	public function render( $block_content, $block ) {
		$show = self::show( isset( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array() );
		if ( '' === $show || ! function_exists( 'wc_get_loop_prop' ) ) {
			return $block_content;
		}

		// The same total WooCommerce just printed in the sentence.
		$total = (int) wc_get_loop_prop( 'total' );
		return $total > 0 ? self::replace( $block_content, $show, $total ) : $block_content;
	}

	/**
	 * The visible text for a total.
	 *
	 * @param string $show  Count option.
	 * @param int    $total Number of products.
	 * @return string
	 */
	public static function text( $show, $total ) {
		$number = number_format_i18n( $total );
		if ( 'brackets' === $show ) {
			/* translators: %s: number of products. */
			return sprintf( _x( '(%s)', 'product count in brackets', 'purplish' ), $number );
		}
		if ( 'products' === $show ) {
			/* translators: %s: number of products. */
			return sprintf( _n( '%s product', '%s products', $total, 'purplish' ), $number );
		}
		return $number;
	}

	/**
	 * Hides the sentence visually and adds the number, with each digit in
	 * its own span for the roll. The number's key changes with the total,
	 * so a filter that changes it plays the motion again.
	 *
	 * @param string $html  The block HTML.
	 * @param string $show  Count option.
	 * @param int    $total Number of products.
	 * @return string
	 */
	public static function replace( $html, $show, $total ) {
		$number = number_format_i18n( $total );
		$digits = '';
		foreach ( (array) preg_split( '//u', $number, -1, PREG_SPLIT_NO_EMPTY ) as $i => $char ) {
			$digits .= '<span class="purplish-count-digit" style="--i:' . (int) $i . '">' . esc_html( $char ) . '</span>';
		}
		$text    = str_replace( esc_html( $number ), $digits, esc_html( self::text( $show, $total ) ) );
		$visible = '<span class="purplish-count-number" aria-hidden="true" data-wp-key="purplish-count-' . (int) $total . '">' . $text . '</span>';

		return (string) preg_replace_callback(
			'#(<p\b[^>]*\bclass="(?:[^"]*\s)?woocommerce-result-count(?=[\s"])[^"]*"[^>]*>)(.*?)(</p>)#s',
			static function ( $found ) use ( $visible ) {
				return $found[1] . '<span class="screen-reader-text">' . trim( $found[2] ) . '</span>' . $visible . $found[3];
			},
			$html,
			1
		);
	}
}
