<?php
/**
 * Tests for the Checkout page looks: field style, focus ring, soft summary
 * and bigger photos.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each option becomes a class on the Checkout's own wrapper, which React
 * keeps, and the effect color goes on as a class.
 */
class CheckoutPageTest extends WP_UnitTestCase {

	const CHECKOUT = '<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><div class="wp-block-woocommerce-checkout-fields-block"></div><div class="wp-block-woocommerce-checkout-totals-block"></div></div>';

	/**
	 * Renders the Checkout block through the class filter.
	 *
	 * @param array $saved Saved options.
	 * @return string
	 */
	private function render( $saved ) {
		return ( new BlockEffects() )->add_classes(
			self::CHECKOUT,
			array(
				'blockName' => 'woocommerce/checkout',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * The server accepts the attribute on the Checkout block.
	 */
	public function test_attribute_registered() {
		$args = ( new BlockEffects() )->add_attribute( array(), 'woocommerce/checkout' );

		$this->assertSame( 'object', $args['attributes']['purplish']['type'] );
	}

	/**
	 * The field style takes one of three values; the rest are switches.
	 */
	public function test_options() {
		foreach ( array( 'soft', 'filled', 'underline' ) as $style ) {
			$this->assertSame(
				array( 'purplish-checkoutpage', 'purplish-checkoutpage-fields-' . $style, 'purplish-checkoutpage-focus', 'purplish-checkoutpage-summary', 'purplish-checkoutpage-photos' ),
				BlockEffects::classes_for(
					'woocommerce/checkout',
					array(
						'fields'  => $style,
						'focus'   => true,
						'summary' => true,
						'photos'  => true,
					)
				),
				$style
			);
		}
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'fields' => 'rounded' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'fields' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'sticky' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'focus' => 'yes' ) ) );
	}

	/**
	 * The classes and the color class go on the outer wrapper, never on the
	 * inner blocks, and the color is printed once as a rule.
	 */
	public function test_markup() {
		$out = $this->render(
			array(
				'fields' => 'filled',
				'focus'  => true,
				'color'  => '#7B2CBF',
			)
		);

		$this->assertSame( '<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading purplish-checkoutpage purplish-checkoutpage-fields-filled purplish-checkoutpage-focus purplish-color-7b2cbf"><div class="wp-block-woocommerce-checkout-fields-block"></div><div class="wp-block-woocommerce-checkout-totals-block"></div></div>', $out );
		$this->assertStringContainsString( '.purplish-color-7b2cbf{--purplish-effect:#7B2CBF;--purplish-effect-on:#ffffff;}', implode( '', (array) wp_styles()->get_data( 'purplish-effects', 'after' ) ) );
	}

	/**
	 * Saved options with nothing on leave the checkout as it is.
	 */
	public function test_nothing_on() {
		$this->assertSame( self::CHECKOUT, $this->render( array( 'color' => '#7b2cbf' ) ) );
	}

	/**
	 * The editor gets the Checkout's options.
	 */
	public function test_preview_map() {
		$map = BlockEffects::preview_map();

		$this->assertSame( 'checkoutpage', $map['woocommerce/checkout']['prefix'] );
		$this->assertSame( array( 'soft', 'filled', 'underline' ), $map['woocommerce/checkout']['options']['fields'] );
	}
}
