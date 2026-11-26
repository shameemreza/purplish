<?php
/**
 * Trade-in: the store's device table, its settings, and the checks behind
 * the trade-in choice on the product page.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the device table set on WooCommerce > Settings > Products >
 * Trade-in (brand, model, storage and a low and high value), cleans it,
 * reads and writes it as CSV, and looks a posted device up again. Display
 * only: the estimate travels with the cart item and the order, and the
 * price never changes.
 *
 * @since 1.4.1
 */
final class TradeIn {

	/**
	 * The option that holds the store's settings.
	 */
	const OPTION = 'purplish_trade_in';

	/**
	 * The option that holds the device table. It isn't autoloaded, since
	 * only product pages with trade-in on read it.
	 */
	const DEVICES = 'purplish_trade_in_devices';

	/**
	 * Product and category meta: `on` or `off`. No meta follows the next
	 * one up.
	 */
	const META = '_purplish_trade_in';

	/**
	 * The cart item key that holds the device picked.
	 */
	const CART_KEY = 'purplish_trade_in';

	/**
	 * Posted fields, the same on the classic form and the Store API add.
	 */
	const FIELD        = 'purplish_trade_in';
	const DEVICE_FIELD = 'purplish_trade_in_device';
	const TERMS_FIELD  = 'purplish_trade_in_terms';

	/**
	 * Most rows a table keeps.
	 */
	const MAX_DEVICES = 5000;

	/**
	 * The settings a new store starts with.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'scope'       => 'all',
			'condition'   => __( 'Estimate for a device in good condition.', 'purplish' ),
			'inspection'  => __( 'The final value is set when we inspect the device.', 'purplish' ),
			'link_url'    => '',
			'link_text'   => __( 'How it works', 'purplish' ),
			'terms'       => false,
			'terms_label' => __( 'I accept the trade-in terms', 'purplish' ),
			'terms_url'   => '',
		);
	}

	/**
	 * The saved settings, cleaned.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings() {
		$saved = get_option( self::OPTION, array() );
		return self::clean( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Cleans settings: known keys only, text stripped of tags, links kept
	 * to http, https and paths within the site. Missing keys take their defaults.
	 *
	 * @param array<string, mixed> $raw Settings to clean.
	 * @return array<string, mixed>
	 */
	public static function clean( $raw ) {
		$defaults = self::defaults();
		$clean    = $defaults;

		if ( isset( $raw['scope'] ) && 'chosen' === $raw['scope'] ) {
			$clean['scope'] = 'chosen';
		}
		foreach ( array( 'condition', 'inspection', 'link_text', 'terms_label' ) as $key ) {
			if ( isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $raw[ $key ] );
			}
		}
		if ( '' === trim( $clean['terms_label'] ) ) {
			$clean['terms_label'] = $defaults['terms_label'];
		}
		foreach ( array( 'link_url', 'terms_url' ) as $key ) {
			$url = isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? trim( $raw[ $key ] ) : '';
			// Only full http(s) links and links within the site: esc_url_raw()
			// would turn plain words into http://words.
			if ( preg_match( '#^(https?://|/)\S+$#i', $url ) ) {
				$clean[ $key ] = esc_url_raw( $url, array( 'http', 'https' ) );
			} elseif ( isset( $raw[ $key ] ) ) {
				$clean[ $key ] = '';
			}
		}
		if ( isset( $raw['terms'] ) ) {
			$clean['terms'] = true === $raw['terms'] || 'yes' === $raw['terms'] || '1' === $raw['terms'];
		}
		return $clean;
	}

	/**
	 * The saved device table, cleaned.
	 *
	 * @return array<int, array{brand: string, model: string, storage: string, low: float, high: float}>
	 */
	public static function devices() {
		$saved = get_option( self::DEVICES, array() );
		return self::clean_devices( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Saves the device table, cleaned, without autoloading it.
	 *
	 * @param mixed $rows Rows of brand, model, storage, low and high.
	 * @return array<int, array{brand: string, model: string, storage: string, low: float, high: float}> What was saved.
	 */
	public static function save_devices( $rows ) {
		$devices = self::clean_devices( $rows );
		delete_option( self::DEVICES );
		if ( $devices ) {
			add_option( self::DEVICES, $devices, '', false );
		}
		return $devices;
	}

	/**
	 * Cleans one row, or null when it can't be used: it needs a brand, a
	 * model and a high value above 0. A low value above the high one swaps
	 * with it, and an empty low value takes the high one.
	 *
	 * @param mixed $row A row, keyed or in column order.
	 * @return array{brand: string, model: string, storage: string, low: float, high: float}|null
	 */
	public static function clean_device( $row ) {
		if ( ! is_array( $row ) ) {
			return null;
		}
		if ( isset( $row[0] ) && ! isset( $row['brand'] ) ) {
			$row = array_combine( array( 'brand', 'model', 'storage', 'low', 'high' ), array_pad( array_slice( array_values( $row ), 0, 5 ), 5, '' ) );
		}
		$text  = static function ( $key ) use ( $row ) {
			return isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? sanitize_text_field( (string) $row[ $key ] ) : '';
		};
		$brand = $text( 'brand' );
		$model = $text( 'model' );
		$low   = isset( $row['low'] ) ? self::amount( $row['low'] ) : null;
		$high  = isset( $row['high'] ) ? self::amount( $row['high'] ) : null;
		if ( null === $high || ( null !== $low && $low > $high ) ) {
			list( $low, $high ) = array( $high, $low );
		}
		if ( null === $low ) {
			$low = $high;
		}
		if ( '' === $brand || '' === $model || null === $high || $high <= 0 ) {
			return null;
		}
		return array(
			'brand'   => $brand,
			'model'   => $model,
			'storage' => $text( 'storage' ),
			'low'     => $low,
			'high'    => $high,
		);
	}

	/**
	 * Cleans a table: unusable rows go, the same device keeps its last
	 * row, and rows sort by brand, model and storage in natural order, so
	 * 64GB comes before 128GB.
	 *
	 * @param mixed $rows Rows.
	 * @return array<int, array{brand: string, model: string, storage: string, low: float, high: float}>
	 */
	public static function clean_devices( $rows ) {
		$devices = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$device = self::clean_device( $row );
			if ( null !== $device ) {
				$devices[ self::key( $device ) ] = $device;
			}
		}
		usort( $devices, array( self::class, 'compare' ) );
		return array_slice( $devices, 0, self::MAX_DEVICES );
	}

	/**
	 * Orders two devices by brand, model and storage.
	 *
	 * @param array{brand: string, model: string, storage: string} $a Device.
	 * @param array{brand: string, model: string, storage: string} $b Device.
	 * @return int
	 */
	public static function compare( $a, $b ) {
		foreach ( array( 'brand', 'model', 'storage' ) as $key ) {
			$order = strnatcasecmp( $a[ $key ], $b[ $key ] );
			if ( 0 !== $order ) {
				return $order;
			}
		}
		return 0;
	}

	/**
	 * A value as a number, 0 or more, or null when it isn't one. Takes the
	 * store's decimal format and drops a currency sign around the number.
	 *
	 * @param mixed $value Value as typed.
	 * @return float|null
	 */
	public static function amount( $value ) {
		if ( ! is_scalar( $value ) ) {
			return null;
		}
		$value = trim( preg_replace( '/[^0-9.,\-]/', '', (string) $value ) );
		if ( '' === $value ) {
			return null;
		}
		$value = wc_format_decimal( $value );
		return is_numeric( $value ) ? round( max( 0.0, (float) $value ), wc_get_price_decimals() ) : null;
	}

	/**
	 * A device's key: the same brand, model and storage always give the
	 * same key, so a key stays valid when the table is saved or imported
	 * again.
	 *
	 * @param array{brand: string, model: string, storage: string} $device Device.
	 * @return string 12 hex characters.
	 */
	public static function key( $device ) {
		$name = strtolower( $device['brand'] . "\x1f" . $device['model'] . "\x1f" . $device['storage'] );
		return substr( md5( $name ), 0, 12 );
	}

	/**
	 * The device with a key, from the saved table, or null.
	 *
	 * @param mixed $key Posted key.
	 * @return array{brand: string, model: string, storage: string, low: float, high: float}|null
	 */
	public static function find( $key ) {
		if ( ! is_string( $key ) || ! preg_match( '/^[0-9a-f]{12}$/', $key ) ) {
			return null;
		}
		foreach ( self::devices() as $device ) {
			if ( self::key( $device ) === $key ) {
				return $device;
			}
		}
		return null;
	}

	/**
	 * Whether a product takes trade-in: its own setting, then the first of
	 * its categories that sets one, then the store. A variation follows
	 * its parent.
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	public static function accepts( $product ) {
		$id  = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$own = self::clean_override( get_post_meta( $id, self::META, true ) );
		if ( '' !== $own ) {
			return 'on' === $own;
		}
		foreach ( wc_get_product_cat_ids( $id ) as $cat ) {
			$set = self::clean_override( get_term_meta( (int) $cat, self::META, true ) );
			if ( '' !== $set ) {
				return 'on' === $set;
			}
		}
		return 'all' === self::settings()['scope'];
	}

	/**
	 * A saved product or category setting: `on`, `off`, or '' to follow
	 * the next one up.
	 *
	 * @param mixed $raw Saved meta.
	 * @return string
	 */
	public static function clean_override( $raw ) {
		return in_array( $raw, array( 'on', 'off' ), true ) ? $raw : '';
	}

	/**
	 * Reads a trade-in choice from posted fields and checks it against the
	 * table. Null means no trade-in: No was picked, or the product doesn't
	 * take trade-in. Yes needs a device from the table, and the terms box
	 * when the store asks for it.
	 *
	 * @param array<string, mixed> $input   Posted fields.
	 * @param \WC_Product          $product Product being added.
	 * @return array{brand: string, model: string, storage: string, low: float, high: float}|\WP_Error|null
	 */
	public static function choice( $input, $product ) {
		if ( ! isset( $input[ self::FIELD ] ) || 'yes' !== $input[ self::FIELD ] || ! self::accepts( $product ) ) {
			return null;
		}
		$device = self::find( isset( $input[ self::DEVICE_FIELD ] ) ? $input[ self::DEVICE_FIELD ] : '' );
		if ( null === $device ) {
			return new \WP_Error( 'purplish_trade_in_device', self::device_message() );
		}
		if ( self::settings()['terms'] && ( empty( $input[ self::TERMS_FIELD ] ) || '1' !== (string) $input[ self::TERMS_FIELD ] ) ) {
			return new \WP_Error( 'purplish_trade_in_terms', self::terms_message() );
		}
		return $device;
	}

	/**
	 * The message when Yes has no device.
	 *
	 * @return string
	 */
	public static function device_message() {
		return __( 'Choose the device you’re trading in, or pick No for trade-in.', 'purplish' );
	}

	/**
	 * The message when the terms box is required and not ticked.
	 *
	 * @return string
	 */
	public static function terms_message() {
		return __( 'Tick the box to accept the trade-in terms, or pick No for trade-in.', 'purplish' );
	}

	/**
	 * A device's name, like "Apple iPhone 13, 128GB".
	 *
	 * @param array{brand: string, model: string, storage: string} $device Device.
	 * @return string
	 */
	public static function name( $device ) {
		$name = $device['brand'] . ' ' . $device['model'];
		if ( '' === $device['storage'] ) {
			return $name;
		}
		/* translators: 1: brand and model, like Apple iPhone 13. 2: storage, like 128GB. */
		return sprintf( __( '%1$s, %2$s', 'purplish' ), $name, $device['storage'] );
	}

	/**
	 * A price as plain text, like RM600.00, for cart lines and order meta.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function price_text( $amount ) {
		return trim( html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ) );
	}

	/**
	 * The estimate as plain text: a range, or one figure when low and high
	 * are the same.
	 *
	 * @param array{low: float, high: float} $device Device.
	 * @return string
	 */
	public static function estimate( $device ) {
		if ( $device['low'] === $device['high'] ) {
			return self::price_text( $device['high'] );
		}
		/* translators: 1: lowest trade-in value. 2: highest trade-in value. */
		return sprintf( __( '%1$s to %2$s', 'purplish' ), self::price_text( $device['low'] ), self::price_text( $device['high'] ) );
	}

	/**
	 * The lines a cart item and its order line show, as label and value.
	 *
	 * @param mixed $device   The saved device.
	 * @param int   $quantity Quantity in the cart.
	 * @return array<int, array{0: string, 1: string}>
	 */
	public static function lines( $device, $quantity ) {
		$device = self::clean_device( $device );
		if ( null === $device ) {
			return array();
		}
		$estimate = self::estimate( $device );
		if ( $quantity > 1 ) {
			/* translators: %s: the trade-in estimate, like RM600.00 to RM680.00. */
			$estimate = sprintf( __( '%s (one device per unit)', 'purplish' ), $estimate );
		}
		return array(
			array( __( 'Trade-in', 'purplish' ), self::name( $device ) ),
			array( __( 'Estimated trade-in value', 'purplish' ), $estimate ),
		);
	}

	/**
	 * Adds imported rows to a table, or replaces it. A device in both
	 * takes the imported values.
	 *
	 * @param array<int, array<string, mixed>> $current  The table now.
	 * @param array<int, array<string, mixed>> $imported Imported rows.
	 * @param bool                             $replace  Whether the import replaces the table.
	 * @return array<int, array{brand: string, model: string, storage: string, low: float, high: float}>
	 */
	public static function merge( $current, $imported, $replace ) {
		return self::clean_devices( $replace ? $imported : array_merge( $current, $imported ) );
	}
}
