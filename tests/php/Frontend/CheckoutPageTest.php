<?php
/**
 * Tests for the Checkout page looks: field style, focus ring, soft summary,
 * bigger photos, choices, side panel and raised card.
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
	 * Choices take cards or list; the side panel and raised card are switches.
	 */
	public function test_layout_options() {
		foreach ( array( 'cards', 'list' ) as $choices ) {
			$this->assertSame(
				array( 'purplish-checkoutpage', 'purplish-checkoutpage-choices-' . $choices, 'purplish-checkoutpage-side', 'purplish-checkoutpage-cards' ),
				BlockEffects::classes_for(
					'woocommerce/checkout',
					array(
						'choices' => $choices,
						'side'    => true,
						'cards'   => true,
					)
				),
				$choices
			);
		}
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'choices' => 'tiles' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'choices' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'side' => 'tint' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'cards' => 'white' ) ) );
	}

	/**
	 * Step numbers take badges or timeline, and nothing else.
	 */
	public function test_steps_options() {
		foreach ( array( 'badges', 'timeline' ) as $steps ) {
			$this->assertSame(
				array( 'purplish-checkoutpage', 'purplish-checkoutpage-steps-' . $steps ),
				BlockEffects::classes_for( 'woocommerce/checkout', array( 'steps' => $steps ) ),
				$steps
			);
		}
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'steps' => 'numbers' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/checkout', array( 'steps' => true ) ) );
	}

	/**
	 * Step numbers sit on the same wrapper as the other looks.
	 */
	public function test_steps_markup() {
		$out = $this->render(
			array(
				'choices' => 'cards',
				'steps'   => 'timeline',
				'color'   => '#8a4b2d',
			)
		);

		$this->assertStringStartsWith( '<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading purplish-checkoutpage purplish-checkoutpage-choices-cards purplish-checkoutpage-steps-timeline purplish-color-8a4b2d">', $out );
	}

	/**
	 * Round 1 and round 2 looks sit together on the one wrapper.
	 */
	public function test_layout_markup() {
		$out = $this->render(
			array(
				'summary' => true,
				'choices' => 'list',
				'side'    => true,
				'color'   => '#2f6f4f',
			)
		);

		$this->assertStringStartsWith( '<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading purplish-checkoutpage purplish-checkoutpage-summary purplish-checkoutpage-choices-list purplish-checkoutpage-side purplish-color-2f6f4f">', $out );
		$this->assertStringContainsString( '<div class="wp-block-woocommerce-checkout-fields-block"></div>', $out );
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
		$this->assertSame( array( 'cards', 'list' ), $map['woocommerce/checkout']['options']['choices'] );
		$this->assertTrue( $map['woocommerce/checkout']['options']['side'] );
		$this->assertTrue( $map['woocommerce/checkout']['options']['cards'] );
		$this->assertSame( array( 'badges', 'timeline' ), $map['woocommerce/checkout']['options']['steps'] );
	}
}
