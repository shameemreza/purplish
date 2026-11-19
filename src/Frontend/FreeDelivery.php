<?php
/**
 * "Free shipping meter" on the Mini-Cart drawer and the Cart page.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Prints how far the cart is from the store's free shipping minimum, with
 * a bar, in the drawer's footer and before the Cart block. The minimum
 * comes from the Free shipping method in the shopper's shipping zone, and
 * the cart is counted as WooCommerce counts it for that method. The script
 * keeps the meter up to date as the cart changes.
 */
final class FreeDelivery {

	const DRAWER = 'woocommerce/mini-cart-contents';
	const CART   = 'woocommerce/cart';
	const HANDLE = 'purplish-meter';

	/**
	 * Hooks in after BlockEffects, so the block keeps its own classes.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_' . self::DRAWER, array( $this, 'drawer' ), 20, 2 );
		add_filter( 'render_block_' . self::CART, array( $this, 'cart' ), 20, 2 );
	}

	/**
	 * Whether the block has the meter on.
	 *
	 * @param array $block Parsed block.
	 * @return bool
	 */
	public static function wanted( $block ) {
		return isset( $block['attrs']['purplish']['meter'] ) && true === $block['attrs']['purplish']['meter'];
	}

	/**
	 * Adds the meter at the top of the drawer's footer, above the subtotal.
	 * The footer is part of the filled drawer, so an empty cart hides it.
	 *
	 * @param string $html  Rendered block.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public function drawer( $html, $block ) {
		$meter = self::wanted( $block ) ? self::markup( self::color( $block ), '', self::saved( $block ) ) : '';
		return '' === $meter ? $html : Markup::prepend_inside( $html, 'wc-block-mini-cart__footer', $meter );
	}

	/**
	 * Adds the meter before the Cart block, at the block's width. React
	 * redraws everything inside the block, so the meter sits beside it.
	 * Placed in the totals column, it prints hidden there and the script
	 * moves it to the top of the column once WooCommerce draws it.
	 *
	 * @param string $html  Rendered block.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public function cart( $html, $block ) {
		if ( ! self::wanted( $block ) ) {
			return $html;
		}
		// The block saves its width as a class, not always as an attribute.
		$align = '';
		$tags  = new \WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			foreach ( array( 'alignwide', 'alignfull' ) as $class ) {
				$align = $tags->has_class( $class ) ? ' ' . $class : $align;
			}
		}
		$saved = self::saved( $block );
		$extra = isset( $saved['meterplace'] ) && 'totals' === $saved['meterplace'] ? 'purplish-meter-totals' : 'purplish-meter-page' . $align;
		$meter = self::markup( self::color( $block ), $extra, $saved );
		return '' === $meter ? $html : $meter . $html;
	}

	/**
	 * The block's saved Purplish options.
	 *
	 * @param array $block Parsed block.
	 * @return array
	 */
	private static function saved( $block ) {
		return isset( $block['attrs']['purplish'] ) && is_array( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
	}

	/**
	 * The block's saved effect color, or ''.
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	private static function color( $block ) {
		$color = isset( $block['attrs']['purplish']['color'] ) ? sanitize_hex_color( (string) $block['attrs']['purplish']['color'] ) : '';
		return $color ? $color : '';
	}

	/**
	 * The Free shipping method that has a minimum, in the zone the cart
	 * ships to. A method that needs a coupon on top of the minimum, or a
	 * coupon only, has no amount to count down to.
	 *
	 * @return \WC_Shipping_Method|null
	 */
	public static function method() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! wc_shipping_enabled() ) {
			return null;
		}
		$packages = WC()->cart->get_shipping_packages();
		$package  = reset( $packages );
		if ( ! is_array( $package ) ) {
			return null;
		}
		// With "No location by default" a new shopper's package has no
		// country, so it lands in "Locations not covered". Count down to the
		// store's own zone until they enter an address.
		if ( empty( $package['destination']['country'] ) ) {
			$package['destination'] = array_merge( (array) ( $package['destination'] ?? array() ), self::base_destination() );
		}
		return self::method_for( $package );
	}

	/**
	 * The store's own address, as a package destination.
	 *
	 * @return array{country: string, state: string, postcode: string}
	 */
	private static function base_destination() {
		return array(
			'country'  => WC()->countries->get_base_country(),
			'state'    => WC()->countries->get_base_state(),
			'postcode' => WC()->countries->get_base_postcode(),
		);
	}

	/**
	 * The Free shipping method with a minimum in the zone a package ships to.
	 *
	 * @param array $package Shipping package, with its destination.
	 * @return \WC_Shipping_Method|null
	 */
	public static function method_for( $package ) {
		foreach ( \WC_Shipping_Zones::get_zone_matching_package( $package )->get_shipping_methods( true ) as $method ) {
			if ( 'free_shipping' === $method->id && in_array( $method->requires, array( 'min_amount', 'either' ), true ) && (float) wc_format_decimal( $method->min_amount ) > 0 ) {
				return $method;
			}
		}
		return null;
	}

	/**
	 * What the editor shows: the store's minimum for its own address, with a
	 * sample cart at 60% of it. The editor has no cart.
	 *
	 * @return array{ready: bool, amount: string, share: float}
	 */
	public static function sample() {
		$method = null;
		if ( function_exists( 'WC' ) && wc_shipping_enabled() ) {
			$method = self::method_for( array( 'destination' => self::base_destination() ) );
		}
		$left = $method ? 0.4 * (float) wc_format_decimal( $method->min_amount ) : 20;
		return array(
			'ready'  => null !== $method,
			'left'   => self::sentences( $method )['left'],
			'amount' => function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $left ) ), ENT_QUOTES, 'UTF-8' ) : '',
			'share'  => 0.6,
		);
	}

	/**
	 * The cart total the method compares with its minimum, as WooCommerce
	 * works it out.
	 *
	 * @param \WC_Cart $cart      The cart.
	 * @param bool     $discounts Whether discounts come off.
	 * @return float
	 */
	public static function counted( $cart, $discounts ) {
		$total = $cart->get_displayed_subtotal();
		if ( $discounts ) {
			$total -= $cart->get_discount_total();
			if ( $cart->display_prices_including_tax() ) {
				$total -= $cart->get_discount_tax();
			}
		}
		return round( $total, wc_get_price_decimals() );
	}

	/**
	 * Whether a free shipping coupon is in the cart.
	 *
	 * @param \WC_Cart $cart The cart.
	 * @return bool
	 */
	private static function has_coupon( $cart ) {
		foreach ( $cart->get_coupons() as $coupon ) {
			if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The sentence for an amount left, or for free shipping reached.
	 *
	 * @param bool $done Whether the cart ships free.
	 * @return string With `%s` for the amount left.
	 */
	public static function sentence( $done ) {
		/* translators: %s: amount left to spend, such as $12.00. */
		return $done ? __( 'Your order ships free.', 'purplish' ) : __( 'Spend %s more for free shipping.', 'purplish' );
	}

	/**
	 * The meter's two sentences: the block's own wording when it has some,
	 * else the countdown names the Free shipping method by its title, so
	 * the meter and the shipping options call it the same thing. A title
	 * left as WooCommerce's default keeps the plain sentence.
	 *
	 * @param \WC_Shipping_Method|null $method The Free shipping method.
	 * @param array                    $saved  The block's saved options.
	 * @return array{left: string, done: string} The amount-left sentence has `%s` for the amount.
	 */
	public static function sentences( $method, $saved = array() ) {
		$own = array();
		foreach ( array(
			'left' => 'metertext',
			'done' => 'meterdone',
		) as $key => $option ) {
			$own[ $key ] = isset( $saved[ $option ] ) && is_string( $saved[ $option ] ) ? sanitize_text_field( $saved[ $option ] ) : '';
		}

		$left  = self::sentence( false );
		$title = $method ? trim( wp_strip_all_tags( (string) $method->get_title() ) ) : '';
		$plain = $method ? array( 'free shipping', strtolower( (string) $method->get_method_title() ) ) : array();
		if ( '' !== $title && ! in_array( strtolower( $title ), $plain, true ) ) {
			/* translators: 1: amount left to spend, such as $12.00, 2: the store's name for its free shipping, such as Free UK delivery. */
			$left = sprintf( __( 'Spend %1$s more for %2$s.', 'purplish' ), '%s', $title );
		}

		return array(
			'left' => '' !== $own['left'] ? $own['left'] : $left,
			'done' => '' !== $own['done'] ? $own['done'] : self::sentence( true ),
		);
	}

	/**
	 * The meter, or '' when the store has no minimum to count down to.
	 *
	 * @param string $color Effect color, or ''.
	 * @param string $extra Extra class.
	 * @param array  $saved The block's saved options, for its wording.
	 * @return string
	 */
	public static function markup( $color = '', $extra = '', $saved = array() ) {
		$method = self::method();
		if ( ! $method ) {
			return '';
		}

		$cart      = WC()->cart;
		$discounts = 'yes' !== $method->ignore_discounts;
		$min       = (float) wc_format_decimal( $method->min_amount );
		$counted   = self::counted( $cart, $discounts );
		$done      = $counted >= $min || ( 'either' === $method->requires && self::has_coupon( $cart ) );
		$share     = $done ? 1 : max( 0, min( 1, $counted / $min ) );
		$minor     = 10 ** wc_get_price_decimals();

		$says = self::sentences( $method, $saved );
		$text = esc_html( $says['done'] );
		if ( ! $done ) {
			$parts = explode( '%s', esc_html( $says['left'] ), 2 );
			$text  = $parts[0] . '<strong>' . wp_kses_post( wc_price( $min - $counted ) ) . '</strong>' . ( isset( $parts[1] ) ? $parts[1] : '' );
		}

		self::enqueue();

		$style = '--purplish-meter:' . round( $share, 4 ) . ';' . ( $color ? '--purplish-effect:' . $color . ';' : '' );
		return sprintf(
			'<div class="%1$s" data-min="%2$d" data-tax="%3$d" data-discounts="%4$d" data-either="%5$d" data-endpoint="%6$s" data-left="%10$s" data-done="%11$s" style="%7$s"%8$s><p class="purplish-meter-text" role="status">%9$s</p><div class="purplish-meter-bar" aria-hidden="true"><span></span></div></div>',
			esc_attr( trim( 'purplish-meter ' . ( $done ? 'is-done ' : '' ) . $extra ) ),
			(int) round( $min * $minor ),
			$cart->display_prices_including_tax() ? 1 : 0,
			$discounts ? 1 : 0,
			'either' === $method->requires ? 1 : 0,
			esc_url( rest_url( 'wc/store/v1/cart' ) ),
			esc_attr( $style ),
			$cart->is_empty() ? ' hidden' : '',
			$text,
			esc_attr( $says['left'] ),
			esc_attr( $says['done'] )
		);
	}

	/**
	 * Loads the meter's styles, and its script once.
	 *
	 * @return void
	 */
	private static function enqueue() {
		BlockEffects::enqueue( array( 'meter' ) );
		if ( wp_script_is( self::HANDLE ) ) {
			return;
		}
		$asset = include PURPLISH_DIR . 'build/meter.asset.php';
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/meter.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		wp_set_script_translations( self::HANDLE, 'purplish' );
	}
}
