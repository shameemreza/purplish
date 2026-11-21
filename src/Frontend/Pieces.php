<?php
/**
 * Colors for pages made of other products: bundles, composites, mix and
 * match boxes and grouped products.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

/**
 * The page starts on the container's own Page color, then follows the
 * item the shopper changed last (assets/src/frontend/pieces.js). This
 * works out the starting color and every item's colors for the script.
 */
final class Pieces {

	/**
	 * Most products listed for one page, so a component built from a large
	 * category can't fill the page with data.
	 */
	const MAX_PRODUCTS = 60;

	/**
	 * Product Bundles: items keyed by bundled item ID, with option pickers.
	 */
	const KIND_BUNDLE = 'bundle';

	/**
	 * Composite Products: a product picked per component, with option pickers.
	 */
	const KIND_COMPOSITE = 'composite';

	/**
	 * Mix and Match and grouped: rows with a quantity each.
	 */
	const KIND_ROWS = 'rows';

	/**
	 * The product types that hold other products and get their own Page
	 * color.
	 *
	 * @return string[]
	 */
	public static function types() {
		/**
		 * Filters the product types that hold other products. Each one gets
		 * the Page color field and starts the page on its own color.
		 * Bundles, composites, mix and match boxes and grouped products also
		 * follow the item the shopper changed last.
		 *
		 * @since 1.4.0
		 *
		 * @param string[] $types Product types.
		 */
		$types = apply_filters( 'purplish_container_types', array( 'bundle', 'composite', 'mix-and-match', 'grouped' ) );
		return array_values( array_filter( array_map( 'strval', (array) $types ) ) );
	}

	/**
	 * Whether a product holds other products.
	 *
	 * @param mixed $product Product.
	 * @return bool
	 */
	public static function is_container( $product ) {
		return $product instanceof \WC_Product && in_array( $product->get_type(), self::types(), true );
	}

	/**
	 * The color data for a container: its own color to start with, and its
	 * items' colors under `pieces`.
	 *
	 * @param \WC_Product                                $product Container product.
	 * @param array{parts: string[], transition: string} $options Options.
	 * @return array|null Null when neither the container nor any item has a color.
	 */
	public static function for_product( $product, $options ) {
		$own    = PageData::product_set( $product );
		$pieces = self::pieces( $product );

		if ( null === $own && empty( $pieces['products'] ) ) {
			return null;
		}

		$data = array(
			'variations' => array(),
			'default'    => null !== $own ? $own : $pieces['first'],
			'options'    => $options,
		);
		if ( ! empty( $pieces['products'] ) ) {
			$data['pieces'] = array(
				'kind'     => $pieces['kind'],
				'products' => $pieces['products'],
				'items'    => $pieces['items'],
			);
		}
		return $data;
	}

	/**
	 * The items of a container.
	 *
	 * @param \WC_Product $product Container product.
	 * @return array{kind: string, products: array, items: array, first: array|null}
	 */
	private static function pieces( $product ) {
		$found = new PieceList();

		switch ( $product->get_type() ) {
			case 'bundle':
				$kind = self::KIND_BUNDLE;
				self::bundle( $product, $found );
				break;
			case 'composite':
				$kind = self::KIND_COMPOSITE;
				self::composite( $product, $found );
				break;
			case 'mix-and-match':
				$kind = self::KIND_ROWS;
				self::mix_and_match( $product, $found );
				break;
			case 'grouped':
				$kind = self::KIND_ROWS;
				foreach ( $product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );
					// A variable child only links to its own page here.
					if ( $child instanceof \WC_Product && ! $child instanceof WC_Product_Variable ) {
						$found->add( $child );
					}
				}
				break;
			default:
				$kind = '';
		}

		return array(
			'kind'     => $kind,
			'products' => $found->products,
			'items'    => $found->items,
			'first'    => $found->first,
		);
	}

	/**
	 * Product Bundles: every visible item, keyed by its bundled item ID.
	 * Optional items start unticked, so they never give the starting color.
	 *
	 * @param \WC_Product $product Bundle.
	 * @param PieceList   $found   List to fill.
	 * @return void
	 */
	private static function bundle( $product, $found ) {
		if ( ! method_exists( $product, 'get_bundled_items' ) ) {
			return;
		}

		foreach ( (array) $product->get_bundled_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_product' ) ) {
				continue;
			}
			if ( method_exists( $item, 'is_visible' ) && ! $item->is_visible() ) {
				continue;
			}

			$child = $item->get_product();
			if ( ! $found->add( $child ) ) {
				continue;
			}
			$found->items[ (int) $item->get_id() ] = (int) $child->get_id();

			if ( method_exists( $item, 'is_optional' ) && $item->is_optional() ) {
				continue;
			}
			$picked = method_exists( $item, 'get_selected_product_variation_attributes' ) ? $item->get_selected_product_variation_attributes() : array();
			$found->offer_start( $child, is_array( $picked ) ? $picked : array() );
		}
	}

	/**
	 * Composite Products: every option of every component. The first
	 * component's default option can give the starting color.
	 *
	 * @param \WC_Product $product Composite.
	 * @param PieceList   $found   List to fill.
	 * @return void
	 */
	private static function composite( $product, $found ) {
		if ( ! method_exists( $product, 'get_components' ) ) {
			return;
		}

		foreach ( (array) $product->get_components() as $component ) {
			if ( ! is_object( $component ) || ! method_exists( $component, 'get_options' ) ) {
				continue;
			}

			foreach ( (array) $component->get_options() as $option_id ) {
				$found->add( wc_get_product( $option_id ) );
			}

			$default = method_exists( $component, 'get_default_option' ) ? (int) $component->get_default_option() : 0;
			if ( $default ) {
				$option = wc_get_product( $default );
				if ( $option instanceof \WC_Product ) {
					$found->offer_start( $option, $option->get_default_attributes() );
				}
			}
		}
	}

	/**
	 * Mix and Match: each allowed product or variation is a row.
	 *
	 * @param \WC_Product $product Mix and match box.
	 * @param PieceList   $found   List to fill.
	 * @return void
	 */
	private static function mix_and_match( $product, $found ) {
		if ( ! method_exists( $product, 'get_child_items' ) ) {
			return;
		}

		foreach ( (array) $product->get_child_items() as $item ) {
			if ( is_object( $item ) && method_exists( $item, 'get_product' ) ) {
				$child = $item->get_product();
				if ( ! $child instanceof WC_Product_Variable ) {
					$found->add( $child );
				}
			}
		}
	}
}
