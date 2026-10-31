<?php
/**
 * Builds variable products for the tests.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Support;

use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Variable products with a color and a size attribute, like the sweater on
 * the test store.
 */
final class Products {

	/**
	 * A variable product with a custom color attribute and a custom size
	 * attribute, and one variation per color and size.
	 *
	 * @param string[] $colors Color options.
	 * @param string[] $sizes  Size options.
	 * @return WC_Product_Variation[] Variations, keyed "Color/Size".
	 */
	public static function sweater( $colors = array( 'Orange', 'Blue' ), $sizes = array( 'S', 'M' ) ) {
		$product = new WC_Product_Variable();
		$product->set_name( 'Everyday Crew Sweater' );
		$product->set_attributes(
			array(
				self::custom_attribute( 'Color', $colors ),
				self::custom_attribute( 'Size', $sizes ),
			)
		);
		$product->save();

		$variations = array();
		foreach ( $colors as $color ) {
			foreach ( $sizes as $size ) {
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $product->get_id() );
				$variation->set_attributes(
					array(
						'color' => $color,
						'size'  => $size,
					)
				);
				$variation->set_regular_price( '50' );
				$variation->save();
				$variations[ "{$color}/{$size}" ] = $variation;
			}
		}

		return $variations;
	}

	/**
	 * A variable product with a global `pa_color` attribute, and one
	 * variation per term.
	 *
	 * @param array<string, string> $swatches Term name => swatch color, or '' for none.
	 * @return WC_Product_Variation[] Variations, keyed by term slug.
	 */
	public static function with_global_color( $swatches ) {
		$taxonomy = self::color_taxonomy();

		$term_ids = array();
		foreach ( $swatches as $name => $color ) {
			$term = term_exists( $name, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $name, $taxonomy );
			}
			$term_id = (int) $term['term_id'];
			if ( '' !== $color ) {
				update_term_meta( $term_id, 'color', $color );
			}
			$term_ids[] = $term_id;
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $term_ids );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Swatch Tee' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variations = array();
		foreach ( $term_ids as $term_id ) {
			$slug      = get_term( $term_id, $taxonomy )->slug;
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
			$variation->set_attributes( array( $taxonomy => $slug ) );
			$variation->set_regular_price( '20' );
			$variation->save();
			$variations[ $slug ] = $variation;
		}

		return $variations;
	}

	/**
	 * Registers the global color attribute once.
	 *
	 * @return string The taxonomy, `pa_color`.
	 */
	private static function color_taxonomy() {
		$taxonomy = 'pa_color';
		if ( ! wc_attribute_taxonomy_id_by_name( $taxonomy ) ) {
			wc_create_attribute(
				array(
					'name' => 'Color',
					'slug' => 'color',
				)
			);
		}
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, array( 'product' ) );
		}
		// WooCommerce fills this list on init, before the test made the attribute.
		$GLOBALS['wc_product_attributes'][ $taxonomy ] = wc_get_attribute( wc_attribute_taxonomy_id_by_name( $taxonomy ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

		return $taxonomy;
	}

	/**
	 * A custom (not global) attribute used for variations.
	 *
	 * @param string   $name    Attribute name.
	 * @param string[] $options Options.
	 * @return WC_Product_Attribute
	 */
	private static function custom_attribute( $name, $options ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( $name );
		$attribute->set_options( $options );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		return $attribute;
	}
}
