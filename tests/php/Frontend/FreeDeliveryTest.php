<?php
/**
 * Tests for the free shipping meter on the drawer and the Cart page.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\FreeDelivery;
use WP_UnitTestCase;

/**
 * The meter reads the Free shipping minimum from the shopper's zone and
 * counts the cart as WooCommerce does.
 */
class FreeDeliveryTest extends WP_UnitTestCase {

	const DRAWER = '<div class="wp-block-woocommerce-mini-cart-contents"><div class="wc-block-mini-cart__footer wp-block-woocommerce-mini-cart-footer-block"><div class="wc-block-mini-cart__footer-subtotal"></div></div></div>';

	const CART = '<div class="wp-block-woocommerce-cart alignwide is-loading"></div>';

	/**
	 * The product's ID, priced 30.
	 *
	 * @var int
	 */
	private $product;

	/**
	 * A fresh cart with one 30 product, and no shipping zones.
	 */
	public function set_up() {
		parent::set_up();
		WC()->frontend_includes();
		WC()->initialize_session();
		WC()->initialize_cart();
		WC()->cart->empty_cart();

		$product = new \WC_Product_Simple();
		$product->set_regular_price( '30' );
		$this->product = $product->save();
		WC()->cart->add_to_cart( $this->product, 1 );
		WC()->cart->calculate_totals();
	}

	/**
	 * Empties the cart and takes the zone's methods away.
	 */
	public function tear_down() {
		WC()->cart->empty_cart();
		$zone = new \WC_Shipping_Zone( 0 );
		foreach ( $zone->get_shipping_methods() as $method ) {
			$zone->delete_shipping_method( $method->instance_id );
		}
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		parent::tear_down();
	}

	/**
	 * Adds Free shipping to the zone every address falls into.
	 *
	 * @param string $requires What the method needs.
	 * @param string $min      Minimum order amount.
	 * @param string $title    The method's title.
	 */
	private function free_shipping( $requires = 'min_amount', $min = '50', $title = 'Free shipping' ) {
		$zone = new \WC_Shipping_Zone( 0 );
		$id   = $zone->add_shipping_method( 'free_shipping' );
		update_option(
			'woocommerce_free_shipping_' . $id . '_settings',
			array(
				'title'            => $title,
				'requires'         => $requires,
				'min_amount'       => $min,
				'ignore_discounts' => 'no',
			)
		);
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * The meter is a switch on both blocks.
	 */
	public function test_meter_option() {
		$this->assertSame( array( 'purplish-drawer', 'purplish-drawer-meter' ), BlockEffects::classes_for( 'woocommerce/mini-cart-contents', array( 'meter' => true ) ) );
		$this->assertSame( array( 'purplish-cartpage', 'purplish-cartpage-meter' ), BlockEffects::classes_for( 'woocommerce/cart', array( 'meter' => true ) ) );
		$this->assertSame(
			array( 'purplish-cartpage', 'purplish-cartpage-meter', 'purplish-cartpage-meterplace-totals' ),
			BlockEffects::classes_for(
				'woocommerce/cart',
				array(
					'meter'      => true,
					'meterplace' => 'totals',
				)
			)
		);
		// The placement means nothing without the meter.
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/cart', array( 'meterplace' => 'totals' ) ) );
	}

	/**
	 * The countdown names the Free shipping method by its title, unless the
	 * title is WooCommerce's default. The block's own wording wins over both.
	 */
	public function test_wording() {
		$this->free_shipping( 'min_amount', '50', 'Free UK delivery' );
		$html = FreeDelivery::markup();
		$this->assertStringContainsString( 'Spend <strong>' . wp_kses_post( wc_price( 20 ) ) . '</strong> more for Free UK delivery.', $html );
		$this->assertStringContainsString( 'data-left="Spend %s more for Free UK delivery."', $html );
		$this->assertStringContainsString( 'data-done="Your order ships free."', $html );

		$own  = array(
			'metertext' => 'Only %s to go for <b>free</b> delivery',
			'meterdone' => 'Delivery is on us.',
		);
		$html = FreeDelivery::markup( '', '', $own );
		$this->assertStringContainsString( 'Only <strong>' . wp_kses_post( wc_price( 20 ) ) . '</strong> to go for free delivery', $html );
		$this->assertStringContainsString( 'data-done="Delivery is on us."', $html );

		WC()->cart->add_to_cart( $this->product, 1 );
		WC()->cart->calculate_totals();
		$this->assertStringContainsString( '>Delivery is on us.</p>', FreeDelivery::markup( '', '', $own ) );

		$sentences = FreeDelivery::sentences( null, array( 'metertext' => '' ) );
		$this->assertSame( 'Spend %s more for free shipping.', $sentences['left'] );
		$this->assertSame( 'Your order ships free.', $sentences['done'] );
	}

	/**
	 * Without a Free shipping minimum there's nothing to count down to.
	 */
	public function test_no_minimum_no_meter() {
		$this->assertSame( '', FreeDelivery::markup() );

		$this->free_shipping( 'coupon' );
		$this->assertSame( '', FreeDelivery::markup() );

		$block = $this->block( 'woocommerce/mini-cart-contents' );
		$this->assertSame( self::DRAWER, ( new FreeDelivery() )->drawer( self::DRAWER, $block ) );
	}

	/**
	 * Under the minimum: the amount left and the share.
	 */
	public function test_amount_left() {
		$this->free_shipping();
		$html = FreeDelivery::markup( '#6b4bd8' );

		$this->assertStringContainsString( 'data-min="5000"', $html );
		$this->assertStringContainsString( 'data-discounts="1"', $html );
		$this->assertStringContainsString( 'Spend <strong>' . wp_kses_post( wc_price( 20 ) ) . '</strong> more for free shipping.', $html );
		$this->assertStringContainsString( '--purplish-meter:0.6;--purplish-effect:#6b4bd8;', $html );
		$this->assertStringContainsString( 'role="status"', $html );
		$this->assertStringNotContainsString( 'is-done', $html );
		$this->assertStringNotContainsString( ' hidden', $html );
	}

	/**
	 * At the minimum the cart ships free and the bar is full. An empty cart
	 * hides the meter.
	 */
	public function test_minimum_reached() {
		$this->free_shipping();
		WC()->cart->add_to_cart( $this->product, 1 );
		WC()->cart->calculate_totals();
		$html = FreeDelivery::markup();

		$this->assertStringContainsString( 'purplish-meter is-done', $html );
		$this->assertStringContainsString( 'Your order ships free.', $html );
		$this->assertStringContainsString( '--purplish-meter:1;', $html );

		WC()->cart->empty_cart();
		$this->assertStringContainsString( ' hidden>', FreeDelivery::markup() );
	}

	/**
	 * The drawer gets it at the top of its footer; the Cart page before the
	 * block, at the width its markup saves. A block without the switch is left alone.
	 */
	public function test_placement() {
		$this->free_shipping();
		$drawer = ( new FreeDelivery() )->drawer( self::DRAWER, $this->block( 'woocommerce/mini-cart-contents' ) );
		$this->assertMatchesRegularExpression( '#wp-block-woocommerce-mini-cart-footer-block"><div class="purplish-meter"[^>]*><p class="purplish-meter-text"#', $drawer );

		$cart = ( new FreeDelivery() )->cart( self::CART, $this->block( 'woocommerce/cart' ) );
		$this->assertStringStartsWith( '<div class="purplish-meter purplish-meter-page alignwide"', $cart );
		$this->assertStringEndsWith( self::CART, $cart );

		$narrow = '<div class="wp-block-woocommerce-cart is-loading"></div>';
		$this->assertStringStartsWith( '<div class="purplish-meter purplish-meter-page"', ( new FreeDelivery() )->cart( $narrow, $this->block( 'woocommerce/cart' ) ) );

		$totals = ( new FreeDelivery() )->cart(
			self::CART,
			$this->block(
				'woocommerce/cart',
				array(
					'purplish' => array(
						'meter'      => true,
						'meterplace' => 'totals',
					),
				)
			)
		);
		$this->assertStringStartsWith( '<div class="purplish-meter purplish-meter-totals"', $totals );

		$off = array(
			'blockName' => 'woocommerce/cart',
			'attrs'     => array( 'purplish' => array( 'rows' => true ) ),
		);
		$this->assertSame( self::CART, ( new FreeDelivery() )->cart( self::CART, $off ) );
	}

	/**
	 * With no country on the package (WooCommerce's "No location by
	 * default"), the meter counts down to the store's own zone. An address
	 * the shopper enters takes over.
	 */
	public function test_no_address_uses_store_zone() {
		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'Store country' );
		$zone->add_location( WC()->countries->get_base_country(), 'country' );
		$zone->save();
		$id = $zone->add_shipping_method( 'free_shipping' );
		update_option(
			'woocommerce_free_shipping_' . $id . '_settings',
			array(
				'requires'   => 'min_amount',
				'min_amount' => '50',
			)
		);
		\WC_Cache_Helper::get_transient_version( 'shipping', true );

		WC()->customer->set_shipping_country( '' );
		WC()->customer->set_shipping_state( '' );
		WC()->customer->set_shipping_postcode( '' );
		$method = FreeDelivery::method();
		$this->assertNotNull( $method );
		$this->assertSame( $id, $method->instance_id );

		WC()->customer->set_shipping_country( 'GB' === WC()->countries->get_base_country() ? 'FR' : 'GB' );
		$this->assertNull( FreeDelivery::method() );

		$zone->delete();
	}

	/**
	 * The editor's sample: the store's minimum for its own address.
	 */
	public function test_editor_sample() {
		$this->assertFalse( FreeDelivery::sample()['ready'] );

		$this->free_shipping();
		$sample = FreeDelivery::sample();
		$this->assertTrue( $sample['ready'] );
		$this->assertSame( html_entity_decode( wp_strip_all_tags( wc_price( 20 ) ), ENT_QUOTES, 'UTF-8' ), $sample['amount'] );
	}

	/**
	 * A parsed block with the meter on.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs More attributes.
	 * @return array
	 */
	private function block( $name, $attrs = array() ) {
		return array(
			'blockName' => $name,
			'attrs'     => array_merge( array( 'purplish' => array( 'meter' => true ) ), $attrs ),
		);
	}
}
