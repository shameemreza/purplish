<?php
/**
 * Delivery dates: the store's settings and the date math behind the
 * Delivery Line block.
 *
 * @package Purplish
 */

namespace Purplish;

use DateTimeImmutable;

defined( 'ABSPATH' ) || exit;

/**
 * Works out when an order placed now ships and arrives, from the store-wide
 * settings on WooCommerce > Settings > Shipping > Delivery dates. Days are
 * counted in the store's working days, skipping holidays, in the site's
 * time zone.
 *
 * @since 1.4.0
 */
final class Delivery {

	/**
	 * The option that holds the settings.
	 */
	const OPTION = 'purplish_delivery';

	/**
	 * Product meta: working days a product adds before it ships.
	 */
	const META = '_purplish_processing_days';

	/**
	 * How many days of states a page carries for cached copies.
	 */
	const DAYS_AHEAD = 8;

	/**
	 * The settings a new store starts with.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'processing'   => 1,
			'cutoff'       => '14:00',
			'days'         => array( '1', '2', '3', '4', '5' ),
			'holidays'     => array(),
			'from'         => 2,
			'to'           => 4,
			'returns'      => 30,
			'free_returns' => 'yes',
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
	 * The settings for one product: the store's, plus the product's extra
	 * processing days.
	 *
	 * @param \WC_Product|null $product The product, or null for the store-wide line.
	 * @return array<string, mixed>
	 */
	public static function settings_for( $product ) {
		$settings = self::settings();
		if ( $product instanceof \WC_Product ) {
			$id                      = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
			$settings['processing'] += self::extra_days( get_post_meta( $id, self::META, true ) );
		}

		/**
		 * Filters the delivery settings for a product, after its extra
		 * processing days are added.
		 *
		 * @since 1.4.0
		 *
		 * @param array<string, mixed> $settings Cleaned settings.
		 * @param \WC_Product|null     $product  The product, or null.
		 */
		$filtered = apply_filters( 'purplish_delivery_settings', $settings, $product );
		return is_array( $filtered ) ? self::clean( $filtered ) : $settings;
	}

	/**
	 * Extra processing days from a saved or posted value: 0 to 60.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function extra_days( $value ) {
		return is_numeric( $value ) ? max( 0, min( 60, (int) $value ) ) : 0;
	}

	/**
	 * Cleans raw settings: numbers in range, a valid cut-off, at least one
	 * working day, holidays as `Y-m-d` dates.
	 *
	 * @param array<string, mixed> $raw Raw settings.
	 * @return array<string, mixed>
	 */
	public static function clean( $raw ) {
		$defaults = self::defaults();
		$number   = static function ( $key, $min, $max ) use ( $raw, $defaults ) {
			return isset( $raw[ $key ] ) && is_numeric( $raw[ $key ] ) ? max( $min, min( $max, (int) $raw[ $key ] ) ) : $defaults[ $key ];
		};

		$cutoff = isset( $raw['cutoff'] ) ? trim( (string) $raw['cutoff'] ) : '';
		if ( preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $cutoff, $match ) ) {
			$cutoff = sprintf( '%02d:%s', (int) $match[1], $match[2] );
		} else {
			$cutoff = $defaults['cutoff'];
		}

		$days = isset( $raw['days'] ) ? array_map( 'strval', (array) $raw['days'] ) : $defaults['days'];
		$days = array_values( array_intersect( array( '1', '2', '3', '4', '5', '6', '7' ), $days ) );

		$from = $number( 'from', 0, 60 );

		return array(
			'processing'   => $number( 'processing', 0, 60 ),
			'cutoff'       => $cutoff,
			'days'         => $days ? $days : $defaults['days'],
			'holidays'     => self::holidays( isset( $raw['holidays'] ) ? $raw['holidays'] : array() ),
			'from'         => $from,
			'to'           => max( $from, $number( 'to', 0, 60 ) ),
			'returns'      => $number( 'returns', 0, 365 ),
			'free_returns' => isset( $raw['free_returns'] ) && in_array( $raw['free_returns'], array( 'no', false, 0, '0' ), true ) ? 'no' : 'yes',
		);
	}

	/**
	 * Holidays from a list or from text with one date per line (commas
	 * work too). Keeps real `Y-m-d` dates only, sorted, once each.
	 *
	 * @param string|string[] $raw Raw holidays.
	 * @return string[]
	 */
	public static function holidays( $raw ) {
		$items = is_array( $raw ) ? $raw : preg_split( '/[\s,]+/', (string) $raw );
		$dates = array();
		foreach ( (array) $items as $item ) {
			$item = trim( (string) $item );
			if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $item, $match ) && checkdate( (int) $match[2], (int) $match[3], (int) $match[1] ) ) {
				$dates[] = $item;
			}
		}
		$dates = array_values( array_unique( $dates ) );
		sort( $dates );
		return $dates;
	}

	/**
	 * Whether the store works on a day: a working day that isn't a holiday.
	 *
	 * @param DateTimeImmutable    $day      The day.
	 * @param array<string, mixed> $settings Cleaned settings.
	 * @return bool
	 */
	public static function is_open( DateTimeImmutable $day, $settings ) {
		return in_array( $day->format( 'N' ), $settings['days'], true ) && ! in_array( $day->format( 'Y-m-d' ), $settings['holidays'], true );
	}

	/**
	 * The day after a number of working days. With 0, the same day.
	 *
	 * @param DateTimeImmutable    $day      The day to count from.
	 * @param int                  $count    Working days to add.
	 * @param array<string, mixed> $settings Cleaned settings.
	 * @return DateTimeImmutable
	 */
	public static function add_open_days( DateTimeImmutable $day, $count, $settings ) {
		// A year of holidays at most can sit in the way, so the guard is generous.
		for ( $guard = 0; $count > 0 && $guard < 2000; $guard++ ) {
			$day = $day->modify( '+1 day' );
			if ( self::is_open( $day, $settings ) ) {
				--$count;
			}
		}
		return $day;
	}

	/**
	 * The day itself when the store works on it, or the next one it does.
	 *
	 * @param DateTimeImmutable    $day      The day.
	 * @param array<string, mixed> $settings Cleaned settings.
	 * @return DateTimeImmutable
	 */
	private static function next_open( DateTimeImmutable $day, $settings ) {
		$guard = 0;
		while ( $guard < 2000 && ! self::is_open( $day, $settings ) ) {
			$day = $day->modify( '+1 day' );
			++$guard;
		}
		return $day;
	}

	/**
	 * When an order placed at a moment ships and arrives.
	 *
	 * @param DateTimeImmutable    $now      The moment, in the site's time zone.
	 * @param array<string, mixed> $settings Cleaned settings.
	 * @return array{until: int, ship: DateTimeImmutable, from: DateTimeImmutable, to: DateTimeImmutable}
	 *         `until` is today's cut-off as a timestamp while it still counts, else 0.
	 */
	public static function at( DateTimeImmutable $now, $settings ) {
		$today = $now->setTime( 0, 0 );
		$parts = explode( ':', $settings['cutoff'] );
		$cut   = $today->setTime( (int) $parts[0], (int) $parts[1] );

		$before = $now < $cut && self::is_open( $today, $settings );
		$order  = $before ? $today : self::next_open( $today->modify( '+1 day' ), $settings );
		$ship   = self::add_open_days( $order, $settings['processing'], $settings );

		return array(
			'until' => $before ? $cut->getTimestamp() : 0,
			'ship'  => $ship,
			'from'  => self::add_open_days( $ship, $settings['from'], $settings ),
			'to'    => self::add_open_days( $ship, $settings['to'], $settings ),
		);
	}

	/**
	 * The moments the answer can change, from now for DAYS_AHEAD days:
	 * every midnight and every cut-off. Each state starts at `t` and holds
	 * its formatted dates, so a cached page can pick the right one.
	 *
	 * @param DateTimeImmutable    $now      The moment the page is made.
	 * @param array<string, mixed> $settings Cleaned settings.
	 * @param string               $format   `short` or `site`.
	 * @return array{states: array<int, array<string, mixed>>, end: int}
	 */
	public static function states( DateTimeImmutable $now, $settings, $format ) {
		$parts  = explode( ':', $settings['cutoff'] );
		$end    = $now->setTime( 0, 0 )->modify( '+' . self::DAYS_AHEAD . ' days' );
		$states = array();
		$moment = $now;

		while ( $moment < $end ) {
			$at    = self::at( $moment, $settings );
			$state = array(
				't' => $moment->getTimestamp(),
				'c' => $at['until'],
				'a' => self::format( $at['from'], $format ),
				'b' => self::format( $at['to'], $format ),
				'p' => self::format( $at['ship'], $format ),
			);

			$last = end( $states );
			if ( ! $last || array_slice( $last, 1 ) !== array_slice( $state, 1 ) ) {
				$states[] = $state;
			}

			$cut    = $moment->setTime( (int) $parts[0], (int) $parts[1] );
			$moment = $moment < $cut ? $cut : $moment->setTime( 0, 0 )->modify( '+1 day' );
		}

		return array(
			'states' => $states,
			'end'    => $end->getTimestamp(),
		);
	}

	/**
	 * A day as shoppers read it, in the site's language and time zone.
	 *
	 * @param DateTimeImmutable $day    The day.
	 * @param string            $format `short` (like Thu, Nov 26) or `site` (the site's date format).
	 * @return string
	 */
	public static function format( DateTimeImmutable $day, $format ) {
		/* translators: PHP date format for a delivery date, short, with the weekday. See https://www.php.net/manual/datetime.format.php */
		$pattern = 'site' === $format ? get_option( 'date_format' ) : _x( 'D, M j', 'delivery date format', 'purplish' );
		return wp_date( $pattern, $day->setTime( 12, 0 )->getTimestamp(), $day->getTimezone() );
	}

	/**
	 * Time left as shoppers read it: "3 hours 20 minutes", "45 minutes".
	 *
	 * @param int $seconds Seconds left.
	 * @return string
	 */
	public static function time_left( $seconds ) {
		$minutes = max( 1, (int) floor( $seconds / 60 ) );
		$hours   = intdiv( $minutes, 60 );
		$minutes = $minutes % 60;

		/* translators: %d: number of hours. */
		$hour_text = sprintf( _n( '%d hour', '%d hours', $hours, 'purplish' ), $hours );
		/* translators: %d: number of minutes. */
		$minute_text = sprintf( _n( '%d minute', '%d minutes', $minutes, 'purplish' ), $minutes );

		if ( ! $hours ) {
			return $minute_text;
		}
		if ( ! $minutes ) {
			return $hour_text;
		}
		/* translators: 1: hours, like "3 hours". 2: minutes, like "20 minutes". */
		return sprintf( _x( '%1$s %2$s', 'hours and minutes left', 'purplish' ), $hour_text, $minute_text );
	}

	/**
	 * Whether a product gets a delivery date: it ships, and it's in stock
	 * and not on backorder. With no product, the store-wide line shows.
	 *
	 * @param \WC_Product|null $product The product.
	 * @return bool
	 */
	public static function dated( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return true;
		}
		return $product->needs_shipping() && $product->is_in_stock() && ! $product->is_on_backorder();
	}

	/**
	 * WooCommerce's Local pickup, when it's on with an enabled location.
	 *
	 * @return array{free: bool, name: string}|null Free pickup, and the location's name when there is one location.
	 */
	public static function pickup() {
		$settings  = get_option( 'woocommerce_pickup_location_settings', array() );
		$locations = get_option( 'pickup_location_pickup_locations', array() );
		$pickup    = null;

		if ( is_array( $settings ) && isset( $settings['enabled'] ) && wc_string_to_bool( $settings['enabled'] ) && is_array( $locations ) ) {
			$open = array_values(
				array_filter(
					$locations,
					static function ( $location ) {
						return is_array( $location ) && ! empty( $location['enabled'] );
					}
				)
			);
			if ( $open ) {
				$cost   = isset( $settings['cost'] ) ? trim( (string) $settings['cost'] ) : '';
				$pickup = array(
					'free' => '' === $cost || 0.0 === (float) $cost,
					'name' => 1 === count( $open ) && isset( $open[0]['name'] ) ? (string) $open[0]['name'] : '',
				);
			}
		}

		/**
		 * Filters the pickup line's data. Return null to hide the line.
		 *
		 * @since 1.4.0
		 *
		 * @param array{free: bool, name: string}|null $pickup Pickup data, or null.
		 */
		return apply_filters( 'purplish_delivery_pickup', $pickup );
	}

	/**
	 * The link of WooCommerce's Refund and Returns page, when it's published.
	 *
	 * @return string
	 */
	public static function returns_url() {
		$page = (int) get_option( 'woocommerce_refund_returns_page_id' );
		return $page && 'publish' === get_post_status( $page ) ? (string) get_permalink( $page ) : '';
	}
}
