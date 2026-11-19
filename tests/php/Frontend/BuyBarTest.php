<?php
/**
 * Tests for the sticky Add to Cart bar on phones.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The option becomes a class, and the block gets a hidden bar after it with
 * the product's name and price, plus the script that shows it.
 */
class BuyBarTest extends WP_UnitTestCase {

	const BLOCK = '<form class="wp-block-woocommerce-add-to-cart-with-options"><button type="submit" class="single_add_to_cart_button">Add to cart</button></form>';

	/**
	 * Drops the script and the post between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( BlockEffects::BAR_HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE );
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Renders the block with the given Purplish options.
	 *
	 * @param array $saved Purplish options.
	 * @return string
	 */
	private function render( $saved ) {
		return ( new BlockEffects() )->add_classes(
			self::BLOCK,
			array(
				'blockName' => 'woocommerce/add-to-cart-with-options',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * Makes a product the current post.
	 *
	 * @return \WC_Product_Simple
	 */
	private function on_product() {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Navy <em>Cargo</em> Trousers' );
		$product->set_regular_price( '40' );
		$product->save();
		$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return $product;
	}

	/**
	 * The switch is a class on the block.
	 */
	public function test_option() {
		$this->assertSame( array( 'purplish-cart', 'purplish-cart-sticky' ), BlockEffects::classes_for( 'woocommerce/add-to-cart-with-options', array( 'sticky' => true ) ) );
		$this->assertSame(
			array( 'purplish-cart', 'purplish-cart-large', 'purplish-cart-sticky' ),
			BlockEffects::classes_for(
				'woocommerce/add-to-cart-with-options',
				array(
					'large'  => true,
					'sticky' => true,
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/add-to-cart-with-options', array( 'sticky' => 'yes' ) ) );
	}

	/**
	 * On a product page the bar follows the block, hidden, with the name,
	 * the price and the block's looks but not the switch itself.
	 */
	public function test_bar_after_the_block() {
		$product = $this->on_product();
		$out     = $this->render(
			array(
				'sticky' => true,
				'large'  => true,
			)
		);

		$this->assertStringStartsWith( '<form class="wp-block-woocommerce-add-to-cart-with-options purplish-cart purplish-cart-large purplish-cart-sticky">', $out );
		$this->assertStringContainsString( '</form><div class="purplish-bar purplish-cart purplish-cart-large" data-pick="Select options" hidden>', $out );
		$this->assertStringContainsString( '<p class="purplish-bar-name">Navy &lt;em&gt;Cargo&lt;/em&gt; Trousers</p>', $out );
		$this->assertStringContainsString( '<div class="purplish-bar-price">' . wp_kses_post( $product->get_price_html() ) . '</div>', $out );
		$this->assertStringNotContainsString( 'purplish-bar purplish-cart purplish-cart-large purplish-cart-sticky', $out );

		$this->assertTrue( wp_script_is( BlockEffects::BAR_HANDLE, 'enqueued' ) );
		$this->assertSame( 'defer', wp_scripts()->get_data( BlockEffects::BAR_HANDLE, 'strategy' ) );
		$this->assertStringEndsWith( 'build/bar.js', wp_scripts()->registered[ BlockEffects::BAR_HANDLE ]->src );
	}

	/**
	 * Without the switch, or outside a product, there's no bar and no script.
	 */
	public function test_no_bar() {
		$this->on_product();
		$this->assertStringNotContainsString( 'purplish-bar', $this->render( array( 'large' => true ) ) );
		$this->assertFalse( wp_script_is( BlockEffects::BAR_HANDLE, 'enqueued' ) );

		unset( $GLOBALS['post'] );
		$this->assertStringNotContainsString( 'purplish-bar', $this->render( array( 'sticky' => true ) ) );
		$this->assertFalse( wp_script_is( BlockEffects::BAR_HANDLE, 'enqueued' ) );
	}
}
