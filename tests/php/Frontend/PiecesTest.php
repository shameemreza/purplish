<?php
/**
 * Tests for pages made of other products.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\PageData;
use Purplish\Frontend\Pieces;
use Purplish\Options;
use Purplish\Tests\Support\Containers;
use Purplish\Tests\Support\FakeBundledItem;
use Purplish\Tests\Support\FakeChildItem;
use Purplish\Tests\Support\FakeComponent;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * A bundle, composite, mix and match or grouped page starts on its own
 * Page color and lists each item's colors for the script.
 */
class PiecesTest extends WP_UnitTestCase {

	/**
	 * A variable sweater, orange and blue.
	 *
	 * @return \WC_Product_Variable
	 */
	private function sweater() {
		return Containers::color_sweater(
			Products::sweater(),
			array(
				'Orange' => '#d94421',
				'Blue'   => '#2a519e',
			)
		);
	}

	/**
	 * A bundle starts on its own color and lists every item by bundled item ID.
	 */
	public function test_bundle_lists_items() {
		$sweater = $this->sweater();
		$scarf   = Containers::simple( '#7b2cbf' );
		$bundle  = Containers::product( 'bundle', '#3a8f5c' );

		$bundle->bundled = array(
			new FakeBundledItem( 4, $sweater ),
			new FakeBundledItem( 5, $scarf ),
		);

		$data = PageData::for_product( $bundle, Options::defaults() );

		$this->assertSame( '#3a8f5c', $data['default']['color'] );
		$this->assertSame( array(), $data['variations'] );
		$this->assertSame( Pieces::KIND_BUNDLE, $data['pieces']['kind'] );
		$this->assertSame(
			array(
				4 => $sweater->get_id(),
				5 => $scarf->get_id(),
			),
			$data['pieces']['items']
		);
		$this->assertCount( 4, $data['pieces']['products'][ $sweater->get_id() ]['variations'] );
		$this->assertSame( '#7b2cbf', $data['pieces']['products'][ $scarf->get_id() ]['set']['color'] );
	}

	/**
	 * Without its own color, a bundle starts on its first item that
	 * already has one: here the sweater's starting picks.
	 */
	public function test_bundle_without_color_starts_on_first_item() {
		$sweater = $this->sweater();
		$bundle  = Containers::product( 'bundle' );

		$bundle->bundled = array(
			new FakeBundledItem( 1, Containers::simple( '' ) ),
			new FakeBundledItem( 2, $sweater, array( 'color' => 'Blue' ) ),
			new FakeBundledItem( 3, Containers::simple( '#7b2cbf' ) ),
		);

		$data = PageData::for_product( $bundle, Options::defaults() );

		$this->assertSame( '#2a519e', $data['default']['color'] );
	}

	/**
	 * Optional and hidden items never give the starting color, and a
	 * hidden item isn't listed, since it has no markup.
	 */
	public function test_optional_and_hidden_items_skip_the_start() {
		$hidden = Containers::simple( '#111111' );
		$bundle = Containers::product( 'bundle' );

		$bundle->bundled = array(
			new FakeBundledItem( 1, Containers::simple( '#d94421' ), array(), true ),
			new FakeBundledItem( 2, $hidden, array(), false, false ),
			new FakeBundledItem( 3, Containers::simple( '#2a519e' ) ),
		);

		$data = PageData::for_product( $bundle, Options::defaults() );

		$this->assertSame( '#2a519e', $data['default']['color'] );
		$this->assertArrayNotHasKey( 2, $data['pieces']['items'] );
	}

	/**
	 * A composite lists every option of every component, and starts on the
	 * first component's default when it has no color of its own.
	 */
	public function test_composite_lists_options() {
		$sweater = $this->sweater();
		$scarf   = Containers::simple( '#7b2cbf' );
		$coat    = Containers::simple( '#3a8f5c' );
		$look    = Containers::product( 'composite' );

		$look->components = array(
			1 => new FakeComponent( 1, array( $sweater->get_id(), $coat->get_id() ) ),
			2 => new FakeComponent( 2, array( $scarf->get_id() ), $scarf->get_id() ),
		);

		$data = PageData::for_product( $look, Options::defaults() );

		$this->assertSame( Pieces::KIND_COMPOSITE, $data['pieces']['kind'] );
		$this->assertSame( array( $sweater->get_id(), $coat->get_id(), $scarf->get_id() ), array_keys( $data['pieces']['products'] ) );
		$this->assertSame( '#7b2cbf', $data['default']['color'] );
	}

	/**
	 * Mix and match rows are single products or variations, each with one color.
	 */
	public function test_mix_and_match_rows() {
		$variations = Products::sweater();
		Containers::color_sweater(
			$variations,
			array(
				'Orange' => '#d94421',
				'Blue'   => '#2a519e',
			)
		);
		$blue = wc_get_product( $variations['Blue/S']->get_id() );
		$box  = Containers::product( 'mix-and-match', '#3a8f5c' );

		$box->child = array( new FakeChildItem( $blue ) );

		$data = PageData::for_product( $box, Options::defaults() );

		$this->assertSame( Pieces::KIND_ROWS, $data['pieces']['kind'] );
		$this->assertSame( '#2a519e', $data['pieces']['products'][ $blue->get_id() ]['set']['color'] );
		$this->assertSame( '#3a8f5c', $data['default']['color'] );
	}

	/**
	 * A grouped page lists its simple children; a variable child only links
	 * to its own page there, so it isn't listed. With no color of its own,
	 * nothing is chosen yet, so the page keeps the theme colors.
	 */
	public function test_grouped_lists_simple_children() {
		$sweater = $this->sweater();
		$scarf   = Containers::simple( '#7b2cbf' );
		$group   = new \WC_Product_Grouped();
		$group->set_children( array( $sweater->get_id(), $scarf->get_id() ) );
		$group->update_meta_data( \Purplish\Meta::MODE, \Purplish\Meta::MODE_OFF );
		$group->save();

		$data = PageData::for_product( $group, Options::defaults() );

		$this->assertSame( array( $scarf->get_id() ), array_keys( $data['pieces']['products'] ) );
		$this->assertNull( $data['default'] );
	}

	/**
	 * A container with no colors anywhere gets no data, like a product
	 * without colors.
	 */
	public function test_nothing_colored_gives_null() {
		$group = new \WC_Product_Grouped();
		$group->set_children( array( Containers::simple( '' )->get_id() ) );
		$group->update_meta_data( \Purplish\Meta::MODE, \Purplish\Meta::MODE_OFF );
		$group->save();

		$this->assertNull( PageData::for_product( $group, Options::defaults() ) );
	}

	/**
	 * Only its own color, with no items: data without pieces.
	 */
	public function test_own_color_only() {
		$group = new \WC_Product_Grouped();
		$group->update_meta_data( \Purplish\Meta::MODE, \Purplish\Meta::MODE_CUSTOM );
		$group->update_meta_data( \Purplish\Meta::CUSTOM_COLOR, '#3a8f5c' );
		$group->save();

		$data = PageData::for_product( $group, Options::defaults() );

		$this->assertSame( '#3a8f5c', $data['default']['color'] );
		$this->assertArrayNotHasKey( 'pieces', $data );
	}

	/**
	 * A type added with the filter gets its own color.
	 */
	public function test_filter_adds_a_type() {
		$box = Containers::product( 'gift-box', '#3a8f5c' );
		$this->assertNull( PageData::for_product( $box, Options::defaults() ) );

		$add = static function ( $types ) {
			$types[] = 'gift-box';
			return $types;
		};
		add_filter( 'purplish_container_types', $add );
		$data = PageData::for_product( $box, Options::defaults() );
		remove_filter( 'purplish_container_types', $add );

		$this->assertSame( '#3a8f5c', $data['default']['color'] );
	}

	/**
	 * The list stops at the cap, so a component built from a large
	 * category can't fill the page with data.
	 */
	public function test_products_are_capped() {
		$ids = array();
		for ( $i = 0; $i < Pieces::MAX_PRODUCTS + 2; $i++ ) {
			$ids[] = Containers::simple( '#7b2cbf' )->get_id();
		}
		$look = Containers::product( 'composite', '#3a8f5c' );

		$look->components = array( 1 => new FakeComponent( 1, $ids ) );

		$data = PageData::for_product( $look, Options::defaults() );

		$this->assertCount( Pieces::MAX_PRODUCTS, $data['pieces']['products'] );
	}

	/**
	 * A variable product's data is the same as before.
	 */
	public function test_variable_product_unchanged() {
		$data = PageData::for_product( $this->sweater(), Options::defaults() );

		$this->assertArrayNotHasKey( 'pieces', $data );
		$this->assertCount( 4, $data['variations'] );
	}
}
