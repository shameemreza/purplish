<?php
/**
 * Tests for the order total on the Cart's pinned checkout bar.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The switch is a class on Proceed to Checkout alone, and it brings the
 * script that keeps the total.
 */
class CheckoutTotalTest extends WP_UnitTestCase {

	const PROCEED = '<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>';

	/**
	 * Drops the script between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( BlockEffects::TOTAL_HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE );
		parent::tear_down();
	}

	/**
	 * Renders Proceed to Checkout with the given Purplish options.
	 *
	 * @param array $saved Purplish options.
	 * @return string
	 */
	private function render( $saved ) {
		return ( new BlockEffects() )->add_classes(
			self::PROCEED,
			array(
				'blockName' => 'woocommerce/proceed-to-checkout-block',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * Proceed to Checkout takes the switch; Place Order and Go to checkout
	 * don't.
	 */
	public function test_option_on_proceed_to_checkout_only() {
		$this->assertSame(
			array( 'purplish-buybutton', 'purplish-buybutton-glow', 'purplish-buybutton-total' ),
			BlockEffects::classes_for(
				'woocommerce/proceed-to-checkout-block',
				array(
					'glow'  => true,
					'total' => true,
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/proceed-to-checkout-block', array( 'total' => 'yes' ) ) );
		foreach ( array( 'woocommerce/checkout-actions-block', 'woocommerce/mini-cart-checkout-button-block' ) as $name ) {
			$this->assertSame( array(), BlockEffects::classes_for( $name, array( 'total' => true ) ), $name );
		}
	}

	/**
	 * With the switch on, the block keeps its markup and the script loads
	 * deferred.
	 */
	public function test_script_with_the_switch() {
		$out = $this->render( array( 'total' => true ) );

		$this->assertSame( '<div class="wp-block-woocommerce-proceed-to-checkout-block purplish-buybutton purplish-buybutton-total"></div>', $out );
		$this->assertTrue( wp_script_is( BlockEffects::TOTAL_HANDLE, 'enqueued' ) );
		$this->assertSame( 'defer', wp_scripts()->get_data( BlockEffects::TOTAL_HANDLE, 'strategy' ) );
		$this->assertStringEndsWith( 'build/total.js', wp_scripts()->registered[ BlockEffects::TOTAL_HANDLE ]->src );
		$this->assertContains( 'wp-data', wp_scripts()->registered[ BlockEffects::TOTAL_HANDLE ]->deps );
	}

	/**
	 * Without the switch there's no script.
	 */
	public function test_no_script_without_the_switch() {
		$this->render( array( 'glow' => true ) );

		$this->assertFalse( wp_script_is( BlockEffects::TOTAL_HANDLE, 'enqueued' ) );
	}
}
