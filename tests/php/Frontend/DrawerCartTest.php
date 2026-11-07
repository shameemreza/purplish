<?php
/**
 * Tests for the second 1.2 store page options: fly to cart on the
 * Mini-Cart, the drawer looks, the button looks on the drawer's Go to
 * checkout, and the Cart page looks.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each option becomes a class. The Cart page's classes go on its inner
 * cart element, which React keeps, and carry the effect color as a class.
 */
class DrawerCartTest extends WP_UnitTestCase {

	const DRAWER = '<div class="wp-block-woocommerce-mini-cart-contents"><div class="wp-block-woocommerce-filled-mini-cart-contents-block"></div></div>';

	const CART = '<div class="wp-block-woocommerce-cart alignwide is-loading"><div class="wp-block-woocommerce-filled-cart-block"></div><div class="wp-block-woocommerce-empty-cart-block"></div></div>';

	/**
	 * Renders a block through the class filter.
	 *
	 * @param string $name  Block name.
	 * @param string $html  Rendered block.
	 * @param array  $saved Saved options.
	 * @return string
	 */
	private function render( $name, $html, $saved ) {
		return ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => $name,
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * The server accepts the attribute on the three new blocks.
	 */
	public function test_attribute_registered() {
		foreach ( array( 'woocommerce/mini-cart-contents', 'woocommerce/mini-cart-checkout-button-block', 'woocommerce/cart' ) as $name ) {
			$args = ( new BlockEffects() )->add_attribute( array(), $name );

			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * Fly to cart is a switch next to the count pop.
	 */
	public function test_fly_to_cart() {
		$this->assertSame(
			array( 'purplish-minicart', 'purplish-minicart-pop', 'purplish-minicart-fly' ),
			BlockEffects::classes_for(
				'woocommerce/mini-cart',
				array(
					'pop' => true,
					'fly' => true,
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/mini-cart', array( 'fly' => 'yes' ) ) );
	}

	/**
	 * The drawer and the Cart page take their switches, and nothing else.
	 */
	public function test_options() {
		$this->assertSame(
			array( 'purplish-drawer', 'purplish-drawer-spring', 'purplish-drawer-rows', 'purplish-drawer-photos' ),
			BlockEffects::classes_for(
				'woocommerce/mini-cart-contents',
				array(
					'spring' => true,
					'rows'   => true,
					'photos' => true,
				)
			)
		);
		$this->assertSame(
			array( 'purplish-cartpage', 'purplish-cartpage-rows', 'purplish-cartpage-photos', 'purplish-cartpage-sticky' ),
			BlockEffects::classes_for(
				'woocommerce/cart',
				array(
					'rows'   => true,
					'photos' => true,
					'sticky' => true,
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/mini-cart-contents', array( 'sticky' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/cart', array( 'spring' => true ) ) );
	}

	/**
	 * Go to checkout takes the same four looks as Proceed to Checkout.
	 */
	public function test_drawer_button_looks() {
		$this->assertSame(
			array( 'purplish-buybutton', 'purplish-buybutton-glow', 'purplish-buybutton-glass' ),
			BlockEffects::classes_for(
				'woocommerce/mini-cart-checkout-button-block',
				array(
					'glow'  => true,
					'glass' => true,
				)
			)
		);
	}

	/**
	 * The drawer is printed by the server, so its color stays inline on
	 * its own wrapper.
	 */
	public function test_drawer_markup() {
		$out = $this->render(
			'woocommerce/mini-cart-contents',
			self::DRAWER,
			array(
				'rows'  => true,
				'color' => '#7b2cbf',
			)
		);

		$this->assertSame( '<div style="--purplish-effect:#7b2cbf;--purplish-effect-on:#ffffff;" class="wp-block-woocommerce-mini-cart-contents purplish-drawer purplish-drawer-rows"><div class="wp-block-woocommerce-filled-mini-cart-contents-block"></div></div>', $out );
	}

	/**
	 * The Cart page's classes and color class go on the filled cart, not on
	 * the outer wrapper React draws again, and not on the empty cart.
	 */
	public function test_cart_markup() {
		$out = $this->render(
			'woocommerce/cart',
			self::CART,
			array(
				'rows'  => true,
				'color' => '#7B2CBF',
			)
		);

		$this->assertSame( '<div class="wp-block-woocommerce-cart alignwide is-loading"><div class="wp-block-woocommerce-filled-cart-block purplish-cartpage purplish-cartpage-rows purplish-color-7b2cbf"></div><div class="wp-block-woocommerce-empty-cart-block"></div></div>', $out );
		$this->assertStringContainsString( '.purplish-color-7b2cbf{--purplish-effect:#7B2CBF;--purplish-effect-on:#ffffff;}', implode( '', (array) wp_styles()->get_data( 'purplish-effects', 'after' ) ) );
	}

	/**
	 * A cart without a filled cart inside is left as it is.
	 */
	public function test_cart_without_target() {
		$html = '<div class="wp-block-woocommerce-cart"></div>';

		$this->assertSame( $html, $this->render( 'woocommerce/cart', $html, array( 'rows' => true ) ) );
	}

	/**
	 * The editor gets the options of the drawer, its button and the Cart
	 * page, and not the Mini-Cart, whose looks only play on the front end.
	 */
	public function test_preview_map() {
		$map = BlockEffects::preview_map();

		$this->assertArrayHasKey( 'woocommerce/mini-cart-contents', $map );
		$this->assertArrayHasKey( 'woocommerce/cart', $map );
		$this->assertArrayNotHasKey( 'woocommerce/mini-cart', $map );
		$this->assertSame(
			array(
				'prefix'  => 'buybutton',
				'options' => BlockEffects::BUY_BUTTON_LOOKS,
				'legacy'  => array(),
			),
			$map['woocommerce/mini-cart-checkout-button-block']
		);
	}
}
