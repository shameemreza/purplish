<?php
/**
 * The Delivery Line block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use DateTimeImmutable;
use Purplish\Delivery;
use Purplish\Frontend\PurpleParts;

defined( 'ABSPATH' ) || exit;

/**
 * Lines next to the buy button: when an order arrives, as a date, the
 * returns policy, and when pickup is ready. The dates come from the
 * Delivery dates settings. The page also carries the next days' answers,
 * so a cached copy shows today's date once its small script runs.
 *
 * @since 1.4.0
 */
final class DeliveryLine implements Block {

	/**
	 * Line icons, drawn on a 24 grid with the text color.
	 *
	 * @var array<string, string>
	 */
	const ICONS = array(
		'date'    => '<path d="M2.75 6.75h10.5v9.5H2.75zM13.25 9.75h4.1l3.9 3.6v2.9h-8" /><circle cx="6.75" cy="17.25" r="1.75" /><circle cx="17.25" cy="17.25" r="1.75" />',
		'returns' => '<path d="M8.75 5.25 5 9l3.75 3.75" /><path d="M5.5 9h9.25a4.25 4.25 0 0 1 0 8.5H10" />',
		'pickup'  => '<path d="M4 9.25 5.25 4.75h13.5L20 9.25M4 9.25a2.67 2.67 0 0 0 5.33 0 2.67 2.67 0 0 0 5.34 0 2.67 2.67 0 0 0 5.33 0M5.25 11.5v7.75h13.5V11.5M10 19.25v-4.5h4v4.5" />',
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'delivery-line';
	}

	/**
	 * Attributes with their defaults, cleaned.
	 *
	 * @param array $attributes Block attributes.
	 * @return array<string, mixed>
	 */
	public static function options( $attributes ) {
		$flag = static function ( $key, $fallback ) use ( $attributes ) {
			return isset( $attributes[ $key ] ) ? (bool) $attributes[ $key ] : $fallback;
		};
		return array(
			'date'      => $flag( 'date', true ),
			'range'     => isset( $attributes['range'] ) && 'range' === $attributes['range'] ? 'range' : 'latest',
			'countdown' => $flag( 'countdown', true ),
			'returns'   => $flag( 'returns', true ),
			'link'      => $flag( 'link', true ),
			'pickup'    => $flag( 'pickup', true ),
			'icons'     => $flag( 'icons', true ),
			'format'    => isset( $attributes['format'] ) && 'site' === $attributes['format'] ? 'site' : 'short',
		);
	}

	/**
	 * The product the line is for: the block's context, the loop's product,
	 * or the current post when it's a product.
	 *
	 * @param \WP_Block|null $block Block instance.
	 * @return \WC_Product|null
	 */
	public static function product( $block ) {
		$id = $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
		if ( ! $id && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
			return $GLOBALS['product'];
		}
		if ( ! $id && 'product' === get_post_type() ) {
			$id = (int) get_the_ID();
		}
		$product = $id ? wc_get_product( $id ) : null;
		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * The sentences each line uses, so PHP and the script build the same
	 * words. Placeholders are numbered, dates and times go in strong.
	 *
	 * @param array<string, mixed>                 $options Cleaned block options.
	 * @param array{free: bool, name: string}|null $pickup  Pickup data.
	 * @return array<string, string>
	 */
	public static function templates( $options, $pickup ) {
		$range = 'range' === $options['range'];
		$texts = array(
			/* translators: %1$s: a delivery date, like Thu, Nov 26. */
			'by'      => __( 'Arrives by %1$s', 'purplish' ),
			/* translators: 1: time left, like 3 hours 20 minutes. 2: a delivery date, like Thu, Nov 26. */
			'by_left' => __( 'Order within %1$s, arrives by %2$s', 'purplish' ),
			/* translators: 1: time left in hours, like 3 hours. 2: minutes, like 20 minutes. */
			'hm'      => _x( '%1$s %2$s', 'hours and minutes left', 'purplish' ),
		);
		if ( $range ) {
			/* translators: 1: the earliest delivery date. 2: the latest delivery date. */
			$texts['range'] = __( 'Arrives %1$s to %2$s', 'purplish' );
			/* translators: 1: time left, like 3 hours 20 minutes. 2: the earliest delivery date. 3: the latest delivery date. */
			$texts['range_left'] = __( 'Order within %1$s, arrives %2$s to %3$s', 'purplish' );
		}
		if ( $pickup ) {
			if ( '' !== $pickup['name'] ) {
				$texts['pickup'] = $pickup['free']
					/* translators: 1: pickup location name. 2: the date it's ready, like Fri, Nov 27. */
					? __( 'Free pickup at %1$s from %2$s', 'purplish' )
					/* translators: 1: pickup location name. 2: the date it's ready, like Fri, Nov 27. */
					: __( 'Pickup at %1$s from %2$s', 'purplish' );
			} else {
				$texts['pickup'] = $pickup['free']
					/* translators: %1$s: the date it's ready, like Fri, Nov 27. */
					? __( 'Free pickup in store from %1$s', 'purplish' )
					/* translators: %1$s: the date it's ready, like Fri, Nov 27. */
					: __( 'Pickup in store from %1$s', 'purplish' );
			}
		}
		return $texts;
	}

	/**
	 * Fills a sentence: the sentence is escaped, each value goes in strong.
	 *
	 * @param string   $template A sentence with numbered placeholders.
	 * @param string[] $values   Values, in order.
	 * @param int[]    $plain    Positions (from 1) printed without strong.
	 * @return string HTML.
	 */
	public static function fill( $template, $values, $plain = array() ) {
		$args = array();
		foreach ( array_values( $values ) as $at => $value ) {
			$args[] = in_array( $at + 1, $plain, true ) ? esc_html( $value ) : '<strong>' . esc_html( $value ) . '</strong>';
		}
		return vsprintf( esc_html( $template ), $args );
	}

	/**
	 * The date line's words for one state.
	 *
	 * @param array<string, string> $texts   Sentences from templates().
	 * @param array<string, mixed>  $state   A state from Delivery::states().
	 * @param array<string, mixed>  $options Cleaned block options.
	 * @param int                   $now     The time now.
	 * @return string HTML.
	 */
	public static function date_text( $texts, $state, $options, $now ) {
		$range = 'range' === $options['range'] && $state['a'] !== $state['b'];
		$dates = $range ? array( $state['a'], $state['b'] ) : array( $state['b'] );
		if ( $options['countdown'] && $state['c'] > $now ) {
			return self::fill( $texts[ $range ? 'range_left' : 'by_left' ], array_merge( array( Delivery::time_left( $state['c'] - $now ) ), $dates ) );
		}
		return self::fill( $texts[ $range ? 'range' : 'by' ], $dates );
	}

	/**
	 * One line: icon and words.
	 *
	 * @param string $kind    date, returns or pickup.
	 * @param string $html    The words, already escaped.
	 * @param bool   $icons   Whether icons show.
	 * @return string
	 */
	private static function row( $kind, $html, $icons ) {
		$icon = $icons
			? '<svg class="purplish-delivery-line__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . self::ICONS[ $kind ] . '</svg>'
			: '';
		return sprintf(
			'<div class="purplish-delivery-line__row purplish-delivery-line__row--%1$s" data-purplish-delivery-row="%1$s">%2$s<span class="purplish-delivery-line__text">%3$s</span></div>',
			esc_attr( $kind ),
			$icon,
			$html
		);
	}

	/**
	 * The block's front-end markup.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved inner blocks (none).
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		if ( ! PurpleParts::keeps( $attributes, 'delivery' ) ) {
			return '';
		}

		$options  = self::options( $attributes );
		$product  = self::product( $block );
		$settings = Delivery::settings_for( $product );
		$dated    = Delivery::dated( $product );
		$pickup   = $options['pickup'] && $dated ? Delivery::pickup() : null;
		$texts    = self::templates( $options, $pickup );

		/**
		 * Filters the moment the Delivery Line counts from. For tests and
		 * previews; leave it alone on a live store.
		 *
		 * @since 1.4.0
		 *
		 * @param int $now Unix timestamp.
		 */
		$now   = (int) apply_filters( 'purplish_delivery_now', time() );
		$clock = ( new DateTimeImmutable( '@' . $now ) )->setTimezone( wp_timezone() );
		$data  = Delivery::states( $clock, $settings, $options['format'] );
		$state = $data['states'][0];
		$rows  = '';

		if ( $options['date'] && $dated ) {
			$rows .= self::row( 'date', self::date_text( $texts, $state, $options, $now ), $options['icons'] );
		}
		if ( $options['returns'] && $settings['returns'] > 0 ) {
			$days  = (int) $settings['returns'];
			$words = 'yes' === $settings['free_returns']
				/* translators: %d: number of days. */
				? sprintf( _n( 'Free returns within %d day', 'Free returns within %d days', $days, 'purplish' ), $days )
				/* translators: %d: number of days. */
				: sprintf( _n( 'Returns within %d day', 'Returns within %d days', $days, 'purplish' ), $days );
			$url   = $options['link'] ? Delivery::returns_url() : '';
			$html  = '' !== $url ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $words ) ) : esc_html( $words );
			$rows .= self::row( 'returns', $html, $options['icons'] );
		}
		if ( $pickup ) {
			$values = '' !== $pickup['name'] ? array( $pickup['name'], $state['p'] ) : array( $state['p'] );
			$rows  .= self::row( 'pickup', self::fill( $texts['pickup'], $values, '' !== $pickup['name'] ? array( 1 ) : array() ), $options['icons'] );
		}

		if ( '' === $rows ) {
			return '';
		}

		$extra = array( 'class' => $options['icons'] ? '' : 'purplish-delivery-line--no-icons' );
		if ( ( $options['date'] && $dated ) || $pickup ) {
			$extra['data-purplish-delivery'] = wp_json_encode(
				array(
					'states'    => $data['states'],
					'end'       => $data['end'],
					'texts'     => $texts,
					'range'     => 'range' === $options['range'],
					'countdown' => $options['countdown'],
					'place'     => $pickup ? $pickup['name'] : '',
					'locale'    => str_replace( '_', '-', determine_locale() ),
				)
			);
		}

		return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( array_filter( $extra ) ), $rows );
	}
}
