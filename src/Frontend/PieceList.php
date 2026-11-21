<?php
/**
 * The items of one container page, as Pieces collects them.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

/**
 * Collects each product's colors once, keyed by product ID, and the first
 * starting color offered.
 */
final class PieceList {

	/**
	 * Colors by product ID: `variations` for a variable product, `set` for
	 * a product or variation with one color.
	 *
	 * @var array<int, array>
	 */
	public $products = array();

	/**
	 * Product ID by bundled item ID (bundles only).
	 *
	 * @var array<int, int>
	 */
	public $items = array();

	/**
	 * The first starting color offered, or null.
	 *
	 * @var array|null
	 */
	public $first = null;

	/**
	 * Products already looked at, colored or not.
	 *
	 * @var array<int, bool>
	 */
	private $seen = array();

	/**
	 * Adds a product's colors, once, while under the cap.
	 *
	 * @param mixed $product Product or variation.
	 * @return bool Whether the product is listed with colors.
	 */
	public function add( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		$id = (int) $product->get_id();
		if ( isset( $this->seen[ $id ] ) ) {
			return isset( $this->products[ $id ] );
		}
		if ( count( $this->seen ) >= Pieces::MAX_PRODUCTS ) {
			return false;
		}
		$this->seen[ $id ] = true;

		if ( $product instanceof WC_Product_Variable ) {
			$variations = PageData::variations_of( $product );
			if ( ! empty( $variations ) ) {
				$this->products[ $id ] = array( 'variations' => $variations );
			}
		} else {
			$set = PageData::product_set( $product );
			if ( null !== $set ) {
				$this->products[ $id ] = array( 'set' => $set );
			}
		}

		return isset( $this->products[ $id ] );
	}

	/**
	 * Offers a product's starting color; the first one that settles wins.
	 *
	 * @param \WC_Product           $product Product.
	 * @param array<string, string> $picked  Starting picks by attribute name.
	 * @return void
	 */
	public function offer_start( $product, $picked ) {
		if ( null !== $this->first ) {
			return;
		}
		$this->add( $product );
		$data = isset( $this->products[ $product->get_id() ] ) ? $this->products[ $product->get_id() ] : null;
		if ( null === $data ) {
			return;
		}
		$this->first = isset( $data['set'] ) ? $data['set'] : PageData::starting_set( $data['variations'], $picked );
	}
}
