<?php
/**
 * Tests for the editor previews of the page section options.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Group and Product Filters show their resting look in the editor. The
 * scroll and count motion on the other page section blocks stays on the
 * front end.
 */
class PageSectionPreviewsTest extends WP_UnitTestCase {

	/**
	 * Group and Product Filters are in the editor map, with their options.
	 */
	public function test_preview_map() {
		$map    = BlockEffects::preview_map();
		$blocks = BlockEffects::blocks();

		foreach ( array( 'core/group', 'woocommerce/product-filters' ) as $name ) {
			$this->assertArrayHasKey( $name, $map, $name );
			$this->assertSame( $blocks[ $name ]['prefix'], $map[ $name ]['prefix'], $name );
			$this->assertSame( $blocks[ $name ]['options'], $map[ $name ]['options'], $name );
		}
	}

	/**
	 * Scroll entrances, the heading reveal, cover scroll effects and the
	 * count pop only play on the front end.
	 */
	public function test_motion_stays_front_end() {
		$map = BlockEffects::preview_map();

		foreach ( array( 'core/columns', 'core/heading', 'woocommerce/mini-cart' ) as $name ) {
			$this->assertArrayNotHasKey( $name, $map, $name );
		}
		$this->assertArrayNotHasKey( 'scroll', $map['core/cover']['options'], 'cover scroll effects' );
	}

	/**
	 * A cover's hover look shows in the editor, on its own.
	 */
	public function test_cover_hover_previews() {
		$map = BlockEffects::preview_map();

		$this->assertSame( 'cover', $map['core/cover']['prefix'] );
		$this->assertSame( array( 'hover' => array( 'push' ) ), $map['core/cover']['options'] );
	}
}
