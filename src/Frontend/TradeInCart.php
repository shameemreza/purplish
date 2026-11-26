<?php
/**
 * Trade-in in the cart, the checkout and the order.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Purplish\TradeIn;

defined( 'ABSPATH' ) || exit;

/**
 * Carries the device a shopper picked into the cart item, shows it as
 * plain lines in the cart, mini-cart and checkout, classic and block, and
 * saves the lines on the order item so emails and the admin order show
 * them. Both ways in, the Store API add and the posted form, look the
 * device up in the table again. The price never changes.
 *
 * @since 1.4.1
 */
final class TradeInCart {

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_store_api_add_to_cart_data', array( self::class, 'store_api_data' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( self::class, 'validate_posted' ), 10, 6 );
		add_filter( 'woocommerce_add_cart_item_data', array( self::class, 'posted_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( self::class, 'item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( self::class, 'order_item_meta' ), 10, 3 );
	}

	/**
	 * Reads the trade-in fields the Purplish script adds to a Store API
	 * add, and puts the device found in the table on the cart item. A Yes
	 * that fails the checks stops the add with a notice.
	 *
	 * @param array            $data    Add to cart data: id, quantity, variation, cart_item_data.
	 * @param \WP_REST_Request $request The add-item request.
	 * @return array
	 * @throws RouteException When Yes has no device, or the terms box is required and not ticked.
	 */
	public static function store_api_data( $data, $request ) {
		$id      = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product instanceof \WC_Product || ! $request instanceof \WP_REST_Request ) {
			return $data;
		}
		$input = array();
		foreach ( array( TradeIn::FIELD, TradeIn::DEVICE_FIELD, TradeIn::TERMS_FIELD ) as $field ) {
			$value = $request->get_param( $field );
			if ( is_scalar( $value ) ) {
				$input[ $field ] = sanitize_text_field( (string) $value );
			}
		}
		$choice = TradeIn::choice( $input, $product );
		if ( is_wp_error( $choice ) ) {
			throw new RouteException( esc_html( $choice->get_error_code() ), esc_html( $choice->get_error_message() ), 400 );
		}
		if ( is_array( $choice ) ) {
			$data['cart_item_data']                      = isset( $data['cart_item_data'] ) && is_array( $data['cart_item_data'] ) ? $data['cart_item_data'] : array();
			$data['cart_item_data'][ TradeIn::CART_KEY ] = $choice;
		}
		return $data;
	}

	/**
	 * The trade-in fields a classic form posted, or a link with them in
	 * the query string. WooCommerce's add to cart form has no nonce, and
	 * every value is checked against the table, so nothing posted is
	 * trusted.
	 *
	 * @return array<string, string>
	 */
	private static function posted() {
		$input = array();
		foreach ( array( TradeIn::FIELD, TradeIn::DEVICE_FIELD, TradeIn::TERMS_FIELD ) as $field ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce's add to cart has no nonce; the value is checked against the table.
			if ( isset( $_REQUEST[ $field ] ) && is_scalar( $_REQUEST[ $field ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- As above.
				$input[ $field ] = sanitize_text_field( wp_unslash( (string) $_REQUEST[ $field ] ) );
			}
		}
		return $input;
	}

	/**
	 * Stops a posted add when Yes fails the checks, with a notice. The
	 * Store API runs this filter too: its cart item already carries the
	 * device, or the add had no trade-in fields.
	 *
	 * @param bool  $passed         Whether the add passed so far.
	 * @param int   $product_id     Product ID.
	 * @param int   $quantity       Quantity.
	 * @param int   $variation_id   Variation ID.
	 * @param array $variations     Variation attributes.
	 * @param array $cart_item_data Cart item data from the Store API.
	 * @return bool
	 */
	public static function validate_posted( $passed, $product_id, $quantity = 1, $variation_id = 0, $variations = array(), $cart_item_data = array() ) {
		unset( $quantity, $variations );
		if ( ! $passed || ( is_array( $cart_item_data ) && isset( $cart_item_data[ TradeIn::CART_KEY ] ) ) ) {
			return $passed;
		}
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return $passed;
		}
		$choice = TradeIn::choice( self::posted(), $product );
		if ( is_wp_error( $choice ) ) {
			wc_add_notice( esc_html( $choice->get_error_message() ), 'error' );
			return false;
		}
		return $passed;
	}

	/**
	 * Puts the device a classic form posted on the cart item. A Store API
	 * add already set it.
	 *
	 * @param array $data         Cart item data.
	 * @param int   $product_id   Product ID.
	 * @param int   $variation_id Variation ID.
	 * @return array
	 */
	public static function posted_item_data( $data, $product_id, $variation_id = 0 ) {
		if ( ! is_array( $data ) || isset( $data[ TradeIn::CART_KEY ] ) ) {
			return $data;
		}
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return $data;
		}
		$choice = TradeIn::choice( self::posted(), $product );
		if ( is_array( $choice ) ) {
			$data[ TradeIn::CART_KEY ] = $choice;
		}
		return $data;
	}

	/**
	 * The trade-in lines under the item in the cart, mini-cart and
	 * checkout. The block cart and checkout read the same data through
	 * the Store API.
	 *
	 * @param array $item_data Item data lines.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public static function item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item[ TradeIn::CART_KEY ] ) ) {
			return $item_data;
		}
		$item_data = is_array( $item_data ) ? $item_data : array();
		$quantity  = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
		foreach ( TradeIn::lines( $cart_item[ TradeIn::CART_KEY ], $quantity ) as $line ) {
			$item_data[] = array(
				'key'   => $line[0],
				'value' => $line[1],
			);
		}
		return $item_data;
	}

	/**
	 * Saves the trade-in lines on the order item, which HPOS keeps with
	 * the order. Emails and the admin order show item meta as it is.
	 *
	 * @param \WC_Order_Item_Product $item      Order item.
	 * @param string                 $cart_key  Cart item key.
	 * @param array                  $cart_item Cart item.
	 * @return void
	 */
	public static function order_item_meta( $item, $cart_key, $cart_item ) {
		unset( $cart_key );
		if ( empty( $cart_item[ TradeIn::CART_KEY ] ) || ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}
		$quantity = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
		foreach ( TradeIn::lines( $cart_item[ TradeIn::CART_KEY ], $quantity ) as $line ) {
			$item->add_meta_data( $line[0], $line[1], true );
		}
	}
}
