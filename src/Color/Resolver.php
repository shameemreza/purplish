<?php
/**
 * Decides which color a variation gets.
 *
 * @package Purplish
 */

namespace Purplish\Color;

use Purplish\Meta;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Custom color first, then WooCommerce's swatch color for the term, then
 * the color read from the photo. Off means no color.
 */
final class Resolver {

	/**
	 * The term meta WooCommerce's "Color swatches for attributes" feature
	 * saves on global attribute terms. Read as plain meta, never through
	 * WooCommerce's internal class.
	 */
	const TERM_COLOR_META = 'color';

	/**
	 * The color for a variation.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @return string Hex color, or an empty string when the variation keeps the theme colors.
	 */
	public static function color( $variation ) {
		$mode = Meta::mode( $variation );
		if ( Meta::MODE_OFF === $mode ) {
			return '';
		}

		if ( Meta::MODE_CUSTOM === $mode ) {
			$custom = sanitize_hex_color( (string) $variation->get_meta( Meta::CUSTOM_COLOR ) );
			if ( $custom ) {
				return $custom;
			}
		}

		$term_color = self::term_color( $variation );
		if ( '' !== $term_color ) {
			return $term_color;
		}

		return (string) sanitize_hex_color( (string) $variation->get_meta( Meta::AUTO_COLOR ) );
	}

	/**
	 * The swatch color of the first global attribute term on the variation
	 * that has one.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @return string Hex color, or an empty string.
	 */
	public static function term_color( $variation ) {
		foreach ( $variation->get_attributes() as $taxonomy => $slug ) {
			if ( '' === $slug || ! taxonomy_is_product_attribute( $taxonomy ) ) {
				continue;
			}

			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, self::TERM_COLOR_META, true ) );
			if ( $color ) {
				return $color;
			}
		}

		return '';
	}
}
