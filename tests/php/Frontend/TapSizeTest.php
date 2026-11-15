<?php
/**
 * Tests for Tap size on Product Filters and the large buy row on Add to
 * Cart with Options.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each switch adds its class when it's on, and nothing for any other value.
 */
class TapSizeTest extends WP_UnitTestCase {

	/**
	 * Tap size adds its class to Product Filters, beside other options.
	 */
	public function test_tap_size_class() {
		$this->assertSame( array( 'purplish-filters', 'purplish-filters-tap' ), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'tap' => true ) ) );
		$this->assertSame(
			array( 'purplish-filters', 'purplish-filters-chips', 'purplish-filters-tap' ),
			BlockEffects::classes_for(
				'woocommerce/product-filters',
				array(
					'tap'   => true,
					'chips' => true,
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'tap' => 'yes' ) ) );
	}

	/**
	 * The large buy row adds its class to Add to Cart with Options.
	 */
	public function test_large_buy_row_class() {
		$this->assertSame( array( 'purplish-cart', 'purplish-cart-large' ), BlockEffects::classes_for( 'woocommerce/add-to-cart-with-options', array( 'large' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/add-to-cart-with-options', array( 'large' => 1 ) ) );
	}

	/**
	 * The editor preview gets both switches, so the editor shows them.
	 */
	public function test_preview_map_lists_both() {
		$map = BlockEffects::preview_map();

		$this->assertTrue( $map['woocommerce/product-filters']['options']['tap'] );
		$this->assertTrue( $map['woocommerce/add-to-cart-with-options']['options']['large'] );
	}
}
