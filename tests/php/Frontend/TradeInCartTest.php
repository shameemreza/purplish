<?php
/**
 * Tests for the trade-in on cart items and orders.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Purplish\Frontend\TradeInCart;
use Purplish\TradeIn;
use WC_Order_Item_Product;
use WC_Product_Simple;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * A Store API add and a posted add both put the device from the table on
 * the cart item, a Yes without a device stops the add, and the cart and
 * order show the device and the estimate.
 */
class TradeInCartTest extends WP_UnitTestCase {

	/**
	 * Product.
	 *
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * The saved device's key.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * A product and a one-device table.
	 */
	public function set_up() {
		parent::set_up();
		$this->product = new WC_Product_Simple();
		$this->product->set_regular_price( '4599' );
		$this->product->save();
		$saved     = TradeIn::save_devices( array( array( 'Apple', 'iPhone 13', '128GB', 600, 680 ) ) );
		$this->key = TradeIn::key( $saved[0] );
	}

	/**
	 * Clears posted fields, notices and options.
	 */
	public function tear_down() {
		$_REQUEST = array();
		wc_clear_notices();
		delete_option( TradeIn::OPTION );
		delete_option( TradeIn::DEVICES );
		parent::tear_down();
	}

	/**
	 * A Store API add-item request with these params.
	 *
	 * @param array $params Params.
	 * @return WP_REST_Request
	 */
	private function request( $params ) {
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	/**
	 * The Store API add carries the device from the table, not the one sent.
	 */
	public function test_store_api_adds_device() {
		$data = TradeInCart::store_api_data(
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			),
			$this->request(
				array(
					TradeIn::FIELD        => 'yes',
					TradeIn::DEVICE_FIELD => $this->key,
					'high'                => 99999,
				)
			)
		);
		$this->assertSame( 680.0, $data['cart_item_data'][ TradeIn::CART_KEY ]['high'] );

		$none = TradeInCart::store_api_data( array( 'id' => $this->product->get_id() ), $this->request( array( TradeIn::FIELD => 'no' ) ) );
		$this->assertArrayNotHasKey( 'cart_item_data', $none );
	}

	/**
	 * A Store API Yes without a device fails with the message.
	 */
	public function test_store_api_rejects_missing_device() {
		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( TradeIn::device_message() );
		TradeInCart::store_api_data( array( 'id' => $this->product->get_id() ), $this->request( array( TradeIn::FIELD => 'yes' ) ) );
	}

	/**
	 * A Store API Yes fails when the terms box is required and not ticked.
	 */
	public function test_store_api_rejects_unticked_terms() {
		update_option( TradeIn::OPTION, array( 'terms' => true ) );
		try {
			TradeInCart::store_api_data(
				array( 'id' => $this->product->get_id() ),
				$this->request(
					array(
						TradeIn::FIELD        => 'yes',
						TradeIn::DEVICE_FIELD => $this->key,
					)
				)
			);
			$this->fail( 'Expected the add to stop.' );
		} catch ( RouteException $e ) {
			$this->assertSame( 'purplish_trade_in_terms', $e->getErrorCode() );
		}
	}

	/**
	 * A posted Yes without a device stops the add with a notice; with one,
	 * the device goes on the cart item.
	 */
	public function test_posted_add() {
		$_REQUEST = array( TradeIn::FIELD => 'yes' );
		$this->assertFalse( TradeInCart::validate_posted( true, $this->product->get_id() ) );
		$this->assertSame( 1, wc_notice_count( 'error' ) );

		$this->assertTrue( TradeInCart::validate_posted( true, $this->product->get_id(), 1, 0, array(), array( TradeIn::CART_KEY => array() ) ), 'a Store API add already checked' );

		$_REQUEST = array(
			TradeIn::FIELD        => 'yes',
			TradeIn::DEVICE_FIELD => $this->key,
		);
		$this->assertTrue( TradeInCart::validate_posted( true, $this->product->get_id() ) );
		$data = TradeInCart::posted_item_data( array(), $this->product->get_id() );
		$this->assertSame( 'iPhone 13', $data[ TradeIn::CART_KEY ]['model'] );

		$kept = array( TradeIn::CART_KEY => array( 'from' => 'store api' ) );
		$this->assertSame( $kept, TradeInCart::posted_item_data( $kept, $this->product->get_id() ) );
	}

	/**
	 * A real add to cart through the classic path puts the lines on the item.
	 */
	public function test_cart_and_order_lines() {
		$_REQUEST = array(
			TradeIn::FIELD        => 'yes',
			TradeIn::DEVICE_FIELD => $this->key,
		);
		WC()->cart->empty_cart();
		$cart_key = WC()->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->assertNotFalse( $cart_key );
		$item = WC()->cart->get_cart_item( $cart_key );

		$this->assertSame(
			array(
				array(
					'key'   => 'Trade-in',
					'value' => 'Apple iPhone 13, 128GB',
				),
				array(
					'key'   => 'Estimated trade-in value',
					'value' => '$600.00 to $680.00 (one device per unit)',
				),
			),
			apply_filters( 'woocommerce_get_item_data', array(), $item ) // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter, run as the cart does.
		);

		$line = new WC_Order_Item_Product();
		TradeInCart::order_item_meta( $line, $cart_key, $item );
		$this->assertSame( 'Apple iPhone 13, 128GB', $line->get_meta( 'Trade-in' ) );
		$this->assertSame( '$600.00 to $680.00 (one device per unit)', $line->get_meta( 'Estimated trade-in value' ) );

		$plain = new WC_Order_Item_Product();
		TradeInCart::order_item_meta( $plain, 'x', array( 'quantity' => 1 ) );
		$this->assertSame( array(), $plain->get_meta_data() );
		WC()->cart->empty_cart();
	}
}
