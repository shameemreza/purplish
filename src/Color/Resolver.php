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

	const SOURCE_CUSTOM = 'custom';
	const SOURCE_TERM   = 'term';
	const SOURCE_AUTO   = 'auto';

	/**
	 * The color for a variation, as the merchant or the photo gave it.
	 *
	 * @param \WC_Product $variation Variation or simple product.
	 * @return string Hex color, or an empty string when the variation keeps the theme colors.
	 */
	public static function color( $variation ) {
		return self::resolve( $variation )['color'];
	}

	/**
	 * The color the page uses for a variation. A color read from the photo
	 * is tuned into a usable accent; a custom or swatch color is the
	 * merchant's choice and stays as set.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @return string Hex color, or an empty string when the variation keeps the theme colors.
	 */
	public static function accent( $variation ) {
		$resolved = self::resolve( $variation );
		if ( self::SOURCE_AUTO === $resolved['source'] ) {
			return Tone::accent( $resolved['color'] );
		}
		return $resolved['color'];
	}

	/**
	 * The color for a variation and where it came from.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @return array{color: string, source: string} Empty strings when the variation keeps the theme colors.
	 */
	public static function resolve( $variation ) {
		$none = array(
			'color'  => '',
			'source' => '',
		);

		$mode = Meta::mode( $variation );
		if ( Meta::MODE_OFF === $mode ) {
			return $none;
		}

		if ( Meta::MODE_CUSTOM === $mode ) {
			$custom = sanitize_hex_color( (string) $variation->get_meta( Meta::CUSTOM_COLOR ) );
			if ( $custom ) {
				return array(
					'color'  => $custom,
					'source' => self::SOURCE_CUSTOM,
				);
			}
		}

		$term_color = self::term_color( $variation );
		if ( '' !== $term_color ) {
			return array(
				'color'  => $term_color,
				'source' => self::SOURCE_TERM,
			);
		}

		$auto = sanitize_hex_color( (string) $variation->get_meta( Meta::AUTO_COLOR ) );
		if ( $auto ) {
			return array(
				'color'  => $auto,
				'source' => self::SOURCE_AUTO,
			);
		}

		return $none;
	}

	/**
	 * The swatch color of the first global attribute term on the variation
	 * that has one.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @return string Hex color, or an empty string.
	 */
	public static function term_color( $variation ) {
		// Only a variation has a picked term; a simple product's attributes are all its options.
		if ( ! $variation instanceof WC_Product_Variation ) {
			return '';
		}

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
