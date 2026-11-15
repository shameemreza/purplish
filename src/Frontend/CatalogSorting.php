<?php
/**
 * Orderings on Catalog Sorting.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Leaves out the orderings a Catalog Sorting block hides, and "average
 * rating" until a product has a rating when the block asks for that. The
 * block prints WooCommerce's own list, so the list is trimmed through
 * `woocommerce_catalog_orderby` while that one block renders.
 */
final class CatalogSorting {

	const BLOCK = 'woocommerce/catalog-sorting';

	/**
	 * The orderings a block can hide, by option name.
	 */
	const ORDERINGS = array(
		'hidepopularity' => 'popularity',
		'hiderating'     => 'rating',
		'hidedate'       => 'date',
		'hideprice'      => 'price',
		'hidepricedesc'  => 'price-desc',
	);

	/**
	 * The orderings to leave out of the block rendering now.
	 *
	 * @var string[]
	 */
	private $hidden = array();

	/**
	 * Whether any product has a rating, worked out once.
	 *
	 * @var bool|null
	 */
	private static $rated = null;

	/**
	 * Hooks in.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_data', array( $this, 'start' ) );
		add_filter( 'render_block_' . self::BLOCK, array( $this, 'stop' ) );
		add_filter( 'woocommerce_catalog_orderby', array( $this, 'trim' ), 99 );
	}

	/**
	 * The orderings a block's saved options hide.
	 *
	 * @param mixed     $saved The block's saved Purplish options.
	 * @param bool|null $rated Whether a product has a rating; null looks it up.
	 * @return string[] Ordering keys, like `rating`.
	 */
	public static function hidden( $saved, $rated = null ) {
		if ( ! is_array( $saved ) ) {
			return array();
		}
		$classes = BlockEffects::classes_for( self::BLOCK, $saved );
		$hidden  = array();
		foreach ( self::ORDERINGS as $option => $ordering ) {
			if ( in_array( 'purplish-sorting-' . $option, $classes, true ) ) {
				$hidden[] = $ordering;
			}
		}
		if ( in_array( 'purplish-sorting-rated', $classes, true ) && ! in_array( 'rating', $hidden, true ) && ! ( null === $rated ? self::any_rated() : $rated ) ) {
			$hidden[] = 'rating';
		}
		return $hidden;
	}

	/**
	 * Whether at least one published product has a rating.
	 *
	 * @return bool
	 */
	public static function any_rated() {
		if ( null === self::$rated ) {
			$rated       = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One row, once per page.
					'meta_query'     => array(
						array(
							'key'     => '_wc_average_rating',
							'value'   => 0,
							'compare' => '>',
							'type'    => 'DECIMAL(3,2)',
						),
					),
				)
			);
			self::$rated = ! empty( $rated );
		}
		return self::$rated;
	}

	/**
	 * Forgets the lookup, for tests and after a rating changes in one request.
	 *
	 * @return void
	 */
	public static function forget() {
		self::$rated = null;
	}

	/**
	 * Notes which orderings the block about to render hides.
	 *
	 * @param array $block Parsed block.
	 * @return array
	 */
	public function start( $block ) {
		if ( isset( $block['blockName'] ) && self::BLOCK === $block['blockName'] ) {
			$this->hidden = self::hidden( isset( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array() );
		}
		return $block;
	}

	/**
	 * Stops trimming once the block has rendered.
	 *
	 * @param string $block_content The block HTML.
	 * @return string
	 */
	public function stop( $block_content ) {
		$this->hidden = array();
		return $block_content;
	}

	/**
	 * Leaves the hidden orderings out of WooCommerce's list. The one the
	 * shopper sorted by stays, so the select still names the order the
	 * products are in.
	 *
	 * @param array $options Orderings by key.
	 * @return array
	 */
	public function trim( $options ) {
		if ( empty( $this->hidden ) || ! is_array( $options ) ) {
			return $options;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, to keep the current ordering.
		$current = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : '';
		foreach ( $this->hidden as $ordering ) {
			if ( $ordering !== $current ) {
				unset( $options[ $ordering ] );
			}
		}
		return $options;
	}
}
