<?php
/**
 * Tests for the orderings on Catalog Sorting.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\CatalogSorting;
use WP_UnitTestCase;

/**
 * A Catalog Sorting block lists only the orderings it keeps, and average
 * rating only once a product has a rating when it asks for that.
 */
class CatalogSortingTest extends WP_UnitTestCase {

	/**
	 * WooCommerce's list without a "Sort by" label.
	 */
	const LIST = array(
		'menu_order' => 'Default sorting',
		'popularity' => 'Sort by popularity',
		'rating'     => 'Sort by average rating',
		'date'       => 'Sort by latest',
		'price'      => 'Sort by price: low to high',
		'price-desc' => 'Sort by price: high to low',
	);

	/**
	 * Clears the rating lookup and the query string.
	 */
	public function tear_down() {
		CatalogSorting::forget();
		unset( $_GET['orderby'] );
		parent::tear_down();
	}

	/**
	 * The list WooCommerce gets while a block with these options renders.
	 *
	 * @param array $saved The block's saved Purplish options.
	 * @return array
	 */
	private function list_for( $saved ) {
		$sorting = new CatalogSorting();
		$sorting->start(
			array(
				'blockName' => CatalogSorting::BLOCK,
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
		$list = $sorting->trim( self::LIST );
		$sorting->stop( '' );
		$this->assertSame( self::LIST, $sorting->trim( self::LIST ), 'the list is whole again after the block' );
		return $list;
	}

	/**
	 * Each option hides its ordering, and default sorting always stays.
	 */
	public function test_hidden_orderings() {
		$this->assertSame(
			array( 'menu_order', 'popularity', 'date', 'price' ),
			array_keys(
				$this->list_for(
					array(
						'hiderating'    => true,
						'hidepricedesc' => true,
					)
				)
			)
		);
		$this->assertSame( array( 'menu_order' ), array_keys( $this->list_for( array_fill_keys( array_keys( CatalogSorting::ORDERINGS ), true ) ) ) );
		$this->assertSame( self::LIST, $this->list_for( array() ) );
		$this->assertSame( self::LIST, $this->list_for( array( 'hideprice' => 'yes' ) ), 'only true hides' );
	}

	/**
	 * The ordering a shopper sorted by stays, so the select names it.
	 */
	public function test_current_ordering_stays() {
		$_GET['orderby'] = 'price';

		$this->assertArrayHasKey( 'price', $this->list_for( array( 'hideprice' => true ) ) );
		$this->assertArrayNotHasKey( 'date', $this->list_for( array( 'hidedate' => true ) ) );
	}

	/**
	 * Other blocks don't trim the list.
	 */
	public function test_other_blocks() {
		$sorting = new CatalogSorting();
		$sorting->start(
			array(
				'blockName' => 'woocommerce/product-results-count',
				'attrs'     => array( 'purplish' => array( 'hidedate' => true ) ),
			)
		);

		$this->assertSame( self::LIST, $sorting->trim( self::LIST ) );
	}

	/**
	 * "Only once a product has one" hides average rating until a product
	 * has a rating.
	 */
	public function test_rated() {
		$this->assertSame( array( 'rating' ), CatalogSorting::hidden( array( 'rated' => true ), false ) );
		$this->assertSame( array(), CatalogSorting::hidden( array( 'rated' => true ), true ) );
		$this->assertSame(
			array( 'rating' ),
			CatalogSorting::hidden(
				array(
					'rated'      => true,
					'hiderating' => true,
				),
				true
			),
			'hidden either way'
		);
	}

	/**
	 * The lookup finds a published product with a rating.
	 */
	public function test_any_rated() {
		$unrated = new \WC_Product_Simple();
		$unrated->save();
		$this->assertFalse( CatalogSorting::any_rated() );
		$this->assertArrayNotHasKey( 'rating', $this->list_for( array( 'rated' => true ) ) );

		$draft = new \WC_Product_Simple();
		$draft->set_status( 'draft' );
		$draft->set_average_rating( 4 );
		$draft->save();
		CatalogSorting::forget();
		$this->assertFalse( CatalogSorting::any_rated(), 'a draft does not count' );

		$rated = new \WC_Product_Simple();
		$rated->set_average_rating( 3.5 );
		$rated->save();
		CatalogSorting::forget();
		$this->assertTrue( CatalogSorting::any_rated() );
		$this->assertArrayHasKey( 'rating', $this->list_for( array( 'rated' => true ) ) );
	}

	/**
	 * Large puts its class on the block, for the 44px select.
	 */
	public function test_size_class() {
		$this->assertSame(
			array( 'purplish-sorting', 'purplish-sorting-size-large' ),
			BlockEffects::classes_for( CatalogSorting::BLOCK, array( 'size' => 'large' ) )
		);
		$this->assertSame( array(), BlockEffects::classes_for( CatalogSorting::BLOCK, array( 'size' => 'huge' ) ) );
	}
}
