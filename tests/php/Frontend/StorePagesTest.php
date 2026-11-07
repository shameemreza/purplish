<?php
/**
 * Tests for the first 1.2 store page options: the thank-you moment and
 * receipt card on Order confirmation, and button looks on Proceed to
 * Checkout and Place Order.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each option becomes a wrapper class. The checkout buttons carry their
 * effect color as a class, because React redraws them without the style.
 */
class StorePagesTest extends WP_UnitTestCase {

	const STATUS = '<div class="wp-block-woocommerce-order-confirmation-status wc-block-order-confirmation-status has-large-font-size"><h1>Order received</h1><p>Thank you. Your order has been received.</p></div>';

	const PROCEED = '<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>';

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
	 * The server accepts the attribute on all four blocks.
	 */
	public function test_attribute_registered() {
		$names = array(
			'woocommerce/order-confirmation-status',
			'woocommerce/order-confirmation-summary',
			'woocommerce/proceed-to-checkout-block',
			'woocommerce/checkout-actions-block',
		);
		foreach ( $names as $name ) {
			$args = ( new BlockEffects() )->add_attribute( array(), $name );

			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * The thank-you moment and the receipt card are single switches.
	 */
	public function test_order_confirmation_options() {
		$this->assertSame( array( 'purplish-thanks', 'purplish-thanks-moment' ), BlockEffects::classes_for( 'woocommerce/order-confirmation-status', array( 'moment' => true ) ) );
		$this->assertSame( array( 'purplish-summary', 'purplish-summary-receipt' ), BlockEffects::classes_for( 'woocommerce/order-confirmation-summary', array( 'receipt' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/order-confirmation-status', array( 'moment' => 'yes' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/order-confirmation-summary', array( 'moment' => true ) ) );
	}

	/**
	 * Both checkout buttons take the four looks, which combine, and nothing
	 * else.
	 */
	public function test_buy_button_looks() {
		$all = array(
			'glow'     => true,
			'sweep'    => true,
			'glass'    => true,
			'spectrum' => true,
		);
		foreach ( array( 'woocommerce/proceed-to-checkout-block', 'woocommerce/checkout-actions-block' ) as $name ) {
			$this->assertSame( array( 'purplish-buybutton', 'purplish-buybutton-glow', 'purplish-buybutton-sweep', 'purplish-buybutton-glass', 'purplish-buybutton-spectrum' ), BlockEffects::classes_for( $name, $all ), $name );
			$this->assertSame( array(), BlockEffects::classes_for( $name, array( 'eyes' => 'two' ) ), $name );
		}
	}

	/**
	 * The status block keeps its markup; the classes and the effect color
	 * go on its wrapper.
	 */
	public function test_status_markup() {
		$out = $this->render(
			'woocommerce/order-confirmation-status',
			self::STATUS,
			array(
				'moment' => true,
				'color'  => '#7b2cbf',
			)
		);

		$this->assertSame( '<div style="--purplish-effect:#7b2cbf;--purplish-effect-on:#ffffff;" class="wp-block-woocommerce-order-confirmation-status wc-block-order-confirmation-status has-large-font-size purplish-thanks purplish-thanks-moment"><h1>Order received</h1><p>Thank you. Your order has been received.</p></div>', $out );
	}

	/**
	 * A checkout button gets its color as a class with a matching rule, and
	 * no style attribute, since React would drop it.
	 */
	public function test_buy_button_color_class() {
		$out = $this->render(
			'woocommerce/proceed-to-checkout-block',
			self::PROCEED,
			array(
				'glass' => true,
				'color' => '#7B2CBF',
			)
		);

		$this->assertSame( '<div class="wp-block-woocommerce-proceed-to-checkout-block purplish-buybutton purplish-buybutton-glass purplish-color-7b2cbf"></div>', $out );
		$this->assertStringContainsString( '.purplish-color-7b2cbf{--purplish-effect:#7B2CBF;--purplish-effect-on:#ffffff;}', implode( '', (array) wp_styles()->get_data( 'purplish-effects', 'after' ) ) );
	}

	/**
	 * Without a look, a saved color alone prints nothing.
	 */
	public function test_buy_button_color_alone() {
		$out = $this->render( 'woocommerce/checkout-actions-block', self::PROCEED, array( 'color' => '#7b2cbf' ) );

		$this->assertSame( self::PROCEED, $out );
	}

	/**
	 * The editor gets the options of the four store blocks, and of no block
	 * from 1.0 or 1.1.
	 */
	public function test_preview_map() {
		$map = BlockEffects::preview_map();

		$this->assertSame(
			array(
				'woocommerce/order-confirmation-status',
				'woocommerce/order-confirmation-summary',
				'woocommerce/proceed-to-checkout-block',
				'woocommerce/checkout-actions-block',
			),
			array_keys( $map )
		);
		$this->assertSame(
			array(
				'prefix'  => 'buybutton',
				'options' => BlockEffects::BUY_BUTTON_LOOKS,
				'legacy'  => array(),
			),
			$map['woocommerce/checkout-actions-block']
		);
	}

	/**
	 * The effect styles load into the editor, and not on the front end
	 * through this hook.
	 */
	public function test_editor_styles() {
		wp_dequeue_style( 'purplish-effects' );
		wp_dequeue_script( 'purplish-effects' );
		( new BlockEffects() )->enqueue_editor_styles();
		$this->assertFalse( wp_style_is( 'purplish-effects' ), 'front end' );

		set_current_screen( 'post' );
		( new BlockEffects() )->enqueue_editor_styles();
		$this->assertTrue( wp_style_is( 'purplish-effects' ), 'editor' );
		$this->assertFalse( wp_script_is( 'purplish-effects' ), 'no front script' );
		set_current_screen( 'front' );
	}
}
