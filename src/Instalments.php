<?php
/**
 * Instalments: the store's settings and the maths behind the instalment
 * line under the Product Price block.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * Works out the monthly amount a product shows, like "or RM45.75/month for
 * 12 months, 0% interest", from price bands set on WooCommerce > Settings >
 * Products > Instalments, a category or the product itself. Display only:
 * nothing changes at checkout.
 *
 * @since 1.4.1
 */
final class Instalments {

	/**
	 * The option that holds the store's settings.
	 */
	const OPTION = 'purplish_instalments';

	/**
	 * Product and category meta: `mode` (hide or own) and own `bands`.
	 */
	const META = '_purplish_instalments';

	/**
	 * Most bands a store, category or product keeps.
	 */
	const MAX_BANDS = 12;

	/**
	 * The settings a new store starts with.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'bands'     => array(
				array(
					'from'   => 0.0,
					'months' => 12,
					'rate'   => 0.0,
				),
			),
			'minimum'   => 0.0,
			/* translators: {amount}, {months}, {rate} and {total} are placeholders: keep them in English, in braces. */
			'wording'   => __( 'or {amount}/month for {months} months', 'purplish' ),
			/* translators: Added after the wording when there is no interest. Placeholders stay in braces. */
			'zero'      => __( ', 0% interest', 'purplish' ),
			/* translators: Added after the wording when there is interest. {rate} is the yearly rate, {total} the total payable. Placeholders stay in braces. */
			'interest'  => __( ', {rate}% a year, {total} in total', 'purplish' ),
			'link_url'  => '',
			'link_text' => __( 'Learn more', 'purplish' ),
			'logo'      => 0,
			'logo_alt'  => '',
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
	 * Cleans settings: known keys only, bands checked and sorted, text
	 * stripped of tags. Missing keys take their defaults.
	 *
	 * @param array<string, mixed> $raw Settings to clean.
	 * @return array<string, mixed>
	 */
	public static function clean( $raw ) {
		$defaults = self::defaults();
		$clean    = $defaults;

		if ( array_key_exists( 'bands', $raw ) ) {
			$clean['bands'] = self::clean_bands( $raw['bands'] );
		}
		if ( isset( $raw['minimum'] ) ) {
			$clean['minimum'] = self::amount( $raw['minimum'] );
		}
		foreach ( array( 'wording', 'zero', 'interest', 'link_text', 'logo_alt' ) as $key ) {
			if ( isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $raw[ $key ] );
			}
		}
		if ( '' === trim( $clean['wording'] ) ) {
			$clean['wording'] = $defaults['wording'];
		}
		if ( isset( $raw['link_url'] ) && is_string( $raw['link_url'] ) ) {
			$clean['link_url'] = esc_url_raw( trim( $raw['link_url'] ), array( 'http', 'https' ) );
		}
		if ( isset( $raw['logo'] ) && is_scalar( $raw['logo'] ) ) {
			$logo          = absint( $raw['logo'] );
			$clean['logo'] = $logo && wp_attachment_is_image( $logo ) ? $logo : 0;
		}
		return $clean;
	}

	/**
	 * Cleans a list of bands: each needs at least one month, rates stay
	 * between 0 and 100, the same "From price" keeps its last row, and the
	 * list is sorted by "From price".
	 *
	 * @param mixed $raw Bands, as rows of from, months and rate.
	 * @return array<int, array{from: float, months: int, rate: float}>
	 */
	public static function clean_bands( $raw ) {
		$bands = array();
		foreach ( is_array( $raw ) ? $raw : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$months = isset( $row['months'] ) && is_numeric( $row['months'] ) ? (int) $row['months'] : 0;
			if ( $months < 1 ) {
				continue;
			}
			$from                    = self::amount( isset( $row['from'] ) ? $row['from'] : 0 );
			$rate                    = isset( $row['rate'] ) && is_numeric( wc_format_decimal( $row['rate'] ) ) ? (float) wc_format_decimal( $row['rate'] ) : 0.0;
			$bands[ (string) $from ] = array(
				'from'   => $from,
				'months' => min( $months, 120 ),
				'rate'   => round( max( 0.0, min( 100.0, $rate ) ), 2 ),
			);
		}
		usort(
			$bands,
			static function ( $a, $b ) {
				return $a['from'] <=> $b['from'];
			}
		);
		return array_slice( $bands, 0, self::MAX_BANDS );
	}

	/**
	 * A price field's value as a number, 0 or more.
	 *
	 * @param mixed $value Price as typed, in the store's decimal format.
	 * @return float
	 */
	public static function amount( $value ) {
		$value = is_scalar( $value ) ? wc_format_decimal( (string) $value ) : '';
		return is_numeric( $value ) ? max( 0.0, (float) $value ) : 0.0;
	}

	/**
	 * The band a price falls in: the highest one whose "From price" it
	 * reaches. Null below the first band or the minimum price.
	 *
	 * @param float                                                    $price   Price.
	 * @param array<int, array{from: float, months: int, rate: float}> $bands   Bands, sorted.
	 * @param float                                                    $minimum Minimum price.
	 * @return array{from: float, months: int, rate: float}|null
	 */
	public static function band_for( $price, $bands, $minimum = 0.0 ) {
		if ( $price <= 0 || $price < $minimum ) {
			return null;
		}
		$found = null;
		foreach ( $bands as $band ) {
			if ( $price >= $band['from'] ) {
				$found = $band;
			}
		}
		return $found;
	}

	/**
	 * The monthly amount and the total payable, rounded up to the store's
	 * decimals so the months never add up to less than the price. 0% splits
	 * the price; a yearly rate above 0 uses the loan payment formula.
	 *
	 * @param float $price    Price.
	 * @param int   $months   Months.
	 * @param float $rate     Yearly rate in percent.
	 * @param int   $decimals Decimals to round to.
	 * @return array{monthly: float, total: float}
	 */
	public static function payment( $price, $months, $rate, $decimals ) {
		$months = max( 1, (int) $months );
		if ( $rate > 0 ) {
			$r       = $rate / 1200;
			$monthly = $price * $r / ( 1 - pow( 1 + $r, -$months ) );
		} else {
			$monthly = $price / $months;
		}
		$unit = pow( 10, max( 0, (int) $decimals ) );
		// The small nudge keeps an exact split, like 120 / 12, from rounding up a step.
		$monthly = ceil( round( $monthly * $unit, 6 ) ) / $unit;
		return array(
			'monthly' => $monthly,
			'total'   => round( $monthly * $months, max( 0, (int) $decimals ) ),
		);
	}

	/**
	 * Where a product takes its bands from: its own setting, then the first
	 * of its categories that sets one, then the store. A variation follows
	 * its parent.
	 *
	 * @param \WC_Product $product Product.
	 * @return array{mode: string, bands: array<int, array{from: float, months: int, rate: float}>} Mode `store`, `hide` or `own`.
	 */
	public static function source_for( $product ) {
		$id  = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$own = self::clean_override( get_post_meta( $id, self::META, true ) );
		if ( 'store' !== $own['mode'] ) {
			return $own;
		}
		foreach ( wc_get_product_cat_ids( $id ) as $cat ) {
			$set = self::clean_override( get_term_meta( (int) $cat, self::META, true ) );
			if ( 'store' !== $set['mode'] ) {
				return $set;
			}
		}
		return array(
			'mode'  => 'store',
			'bands' => self::settings()['bands'],
		);
	}

	/**
	 * Cleans a saved product or category setting. Own bands with no valid
	 * row count as the store's.
	 *
	 * @param mixed $raw Saved meta.
	 * @return array{mode: string, bands: array<int, array{from: float, months: int, rate: float}>}
	 */
	public static function clean_override( $raw ) {
		$mode = is_array( $raw ) && isset( $raw['mode'] ) ? (string) $raw['mode'] : '';
		if ( 'hide' === $mode ) {
			return array(
				'mode'  => 'hide',
				'bands' => array(),
			);
		}
		$bands = 'own' === $mode && isset( $raw['bands'] ) ? self::clean_bands( $raw['bands'] ) : array();
		return array(
			'mode'  => $bands ? 'own' : 'store',
			'bands' => $bands,
		);
	}

	/**
	 * The price a product shows: the display price, so sales and the tax
	 * display setting count. A variable product shows its lowest variation.
	 *
	 * @param \WC_Product $product Product.
	 * @return float
	 */
	public static function price_of( $product ) {
		if ( $product->is_type( 'variable' ) && $product instanceof \WC_Product_Variable ) {
			return (float) $product->get_variation_price( 'min', true );
		}
		$price = $product->get_price();
		return '' === $price ? 0.0 : (float) wc_get_price_to_display( $product );
	}

	/**
	 * The line's text for a price and a band, with the merchant's wording.
	 * A rate above 0 always shows the total payable.
	 *
	 * @param float                                        $price    Price.
	 * @param array{from: float, months: int, rate: float} $band     Band.
	 * @param array<string, mixed>                         $settings Store settings.
	 * @param bool                                         $from     Whether the amount starts with "from", for variable products.
	 * @return string HTML.
	 */
	public static function text( $price, $band, $settings, $from = false ) {
		$pay      = self::payment( $price, $band['months'], $band['rate'], wc_get_price_decimals() );
		$wording  = $settings['wording'] . ( $band['rate'] > 0 ? $settings['interest'] : $settings['zero'] );
		$has_rate = $band['rate'] > 0;
		if ( $has_rate && false === strpos( $wording, '{total}' ) ) {
			/* translators: %s: the total payable, like RM549.00. */
			$wording .= sprintf( __( ', %s in total', 'purplish' ), '{total}' );
		}
		$amount = '<span class="purplish-instalments__amount">' . wc_price( $pay['monthly'] ) . '</span>';
		if ( $from ) {
			/* translators: %s: the monthly amount of the cheapest option, like RM45.75. */
			$amount = sprintf( esc_html__( 'from %s', 'purplish' ), $amount );
		}
		return strtr(
			esc_html( $wording ),
			array(
				'{amount}' => $amount,
				'{months}' => esc_html( number_format_i18n( $band['months'] ) ),
				'{rate}'   => esc_html( wc_format_localized_decimal( wc_format_decimal( $band['rate'], 2, true ) ) ),
				'{total}'  => wc_price( $pay['total'] ),
			)
		);
	}

	/**
	 * The text a product shows, or an empty string when it shows none: sold
	 * out, hidden by its setting, no price, or below the minimum or the
	 * first band.
	 *
	 * @param \WC_Product          $product  Product.
	 * @param array<string, mixed> $settings Store settings.
	 * @return string HTML.
	 */
	public static function text_for( $product, $settings ) {
		if ( ! $product->is_in_stock() ) {
			return '';
		}
		$source = self::source_for( $product );
		if ( 'hide' === $source['mode'] ) {
			return '';
		}
		$price = self::price_of( $product );
		$band  = self::band_for( $price, $source['bands'], $settings['minimum'] );
		if ( null === $band ) {
			return '';
		}
		$from = $product->is_type( 'variable' ) && $product instanceof \WC_Product_Variable && $product->get_variation_price( 'min', true ) !== $product->get_variation_price( 'max', true );
		return self::text( $price, $band, $settings, $from );
	}

	/**
	 * The whole line for a product: logo, text and info link. Empty when
	 * the product shows no line.
	 *
	 * @param \WC_Product          $product    Product.
	 * @param array<string, mixed> $settings   Store settings.
	 * @param array<string, mixed> $variations The per-variation data for the script, or empty.
	 * @return string HTML.
	 */
	public static function line( $product, $settings, $variations = array() ) {
		$text = self::text_for( $product, $settings );
		if ( '' === $text ) {
			return '';
		}

		$logo = '';
		if ( $settings['logo'] ) {
			$logo = (string) wp_get_attachment_image(
				$settings['logo'],
				'thumbnail',
				false,
				array(
					'class'    => 'purplish-instalments__logo',
					'alt'      => $settings['logo_alt'],
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		}
		$link = '';
		if ( '' !== $settings['link_url'] && '' !== trim( $settings['link_text'] ) ) {
			$link = '<a class="purplish-instalments__link" href="' . esc_url( $settings['link_url'] ) . '">' . esc_html( $settings['link_text'] ) . '</a>';
		}
		$data = $variations ? ' data-purplish-instalments="' . esc_attr( (string) wp_json_encode( $variations ) ) . '"' : '';

		return '<div class="purplish-instalments"' . $data . '>' . $logo . '<span class="purplish-instalments__text">' . $text . '</span>' . $link . '</div>';
	}

	/**
	 * Each variation's text, for the script to swap in when a shopper picks
	 * one. Variations share a text by price, so each text is sent once.
	 * A variation that shows none (sold out, below the minimum) maps to
	 * an empty key, and the script hides the line for it.
	 *
	 * @param \WC_Product_Variable $product  Variable product.
	 * @param array<string, mixed> $settings Store settings.
	 * @return array{v: array<int, array{0: array<string, string>, 1: string}>, l: array<string, string>}|array{}
	 */
	public static function variations( $product, $settings ) {
		$source = self::source_for( $product );
		if ( 'hide' === $source['mode'] ) {
			return array();
		}
		$rows  = array();
		$lines = array();
		foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}
			$key = '';
			if ( $variation->is_in_stock() ) {
				$price = self::price_of( $variation );
				$band  = self::band_for( $price, $source['bands'], $settings['minimum'] );
				if ( null !== $band ) {
					$key = (string) $price;
					if ( ! isset( $lines[ $key ] ) ) {
						$lines[ $key ] = self::text( $price, $band, $settings );
					}
				}
			}
			$attributes = array();
			foreach ( $variation->get_variation_attributes() as $name => $value ) {
				$attributes[ (string) $name ] = (string) $value;
			}
			$rows[] = array( $attributes, $key );
		}
		return $rows ? array(
			'v' => $rows,
			'l' => $lines,
		) : array();
	}
}
