<?php
/**
 * Tests for Hide short descriptions on the Mini-Cart drawer and the Cart
 * page, added in 1.3.2.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The switch is a class on the drawer contents and on the Cart page's
 * inner cart element.
 */
class HideDescriptionsTest extends WP_UnitTestCase {

	const CART = '<div class="wp-block-woocommerce-cart alignwide is-loading"><div class="wp-block-woocommerce-filled-cart-block"></div><div class="wp-block-woocommerce-empty-cart-block"></div></div>';

	/**
	 * The switch becomes its class on both blocks.
	 */
	public function test_classes() {
		$this->assertSame(
			array( 'purplish-drawer', 'purplish-drawer-hidedesc' ),
			BlockEffects::classes_for( 'woocommerce/mini-cart-contents', array( 'hidedesc' => true ) )
		);
		$this->assertSame(
			array( 'purplish-cartpage', 'purplish-cartpage-hidedesc' ),
			BlockEffects::classes_for( 'woocommerce/cart', array( 'hidedesc' => true ) )
		);
	}

	/**
	 * Only true turns it on.
	 */
	public function test_bad_values() {
		foreach ( array( 'yes', 1, 'true', array( true ) ) as $bad ) {
			$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/cart', array( 'hidedesc' => $bad ) ) );
		}
	}

	/**
	 * On the Cart page the class goes on the filled cart, not the wrapper.
	 */
	public function test_cart_class_on_the_filled_cart() {
		$html = ( new BlockEffects() )->add_classes(
			self::CART,
			array(
				'blockName' => 'woocommerce/cart',
				'attrs'     => array( 'purplish' => array( 'hidedesc' => true ) ),
			)
		);

		$this->assertStringContainsString( 'class="wp-block-woocommerce-cart alignwide is-loading"', $html );
		$this->assertStringContainsString( 'wp-block-woocommerce-filled-cart-block purplish-cartpage purplish-cartpage-hidedesc', $html );
	}

	/**
	 * The editor gets the switch for its preview.
	 */
	public function test_preview() {
		$map = BlockEffects::preview_map();

		$this->assertTrue( $map['woocommerce/mini-cart-contents']['options']['hidedesc'] );
		$this->assertTrue( $map['woocommerce/cart']['options']['hidedesc'] );
	}
}
