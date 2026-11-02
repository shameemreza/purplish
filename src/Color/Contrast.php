<?php
/**
 * Picks readable text for a background color.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Black or white text, whichever has more contrast on a color, by the
 * WCAG relative luminance formula.
 */
final class Contrast {

	const BLACK = '#000000';
	const WHITE = '#ffffff';

	/**
	 * The text color for a background.
	 *
	 * @param string $hex Background, as #rgb or #rrggbb.
	 * @return string `#000000` or `#ffffff`. Black for anything that isn't a hex color.
	 */
	public static function on( $hex ) {
		$luminance = self::luminance( $hex );
		if ( null === $luminance ) {
			return self::BLACK;
		}

		// Contrast ratio is (lighter + 0.05) / (darker + 0.05). Black is 0, white is 1.
		$with_black = ( $luminance + 0.05 ) / 0.05;
		$with_white = 1.05 / ( $luminance + 0.05 );

		return $with_black >= $with_white ? self::BLACK : self::WHITE;
	}

	/**
	 * The WCAG contrast ratio between two colors.
	 *
	 * @param string $first  Color, as #rgb or #rrggbb.
	 * @param string $second Color, as #rgb or #rrggbb.
	 * @return float|null From 1 to 21, or null when either value isn't a hex color.
	 */
	public static function ratio( $first, $second ) {
		$a = self::luminance( $first );
		$b = self::luminance( $second );
		if ( null === $a || null === $b ) {
			return null;
		}

		return ( max( $a, $b ) + 0.05 ) / ( min( $a, $b ) + 0.05 );
	}

	/**
	 * WCAG relative luminance of a color, from 0 for black to 1 for white.
	 *
	 * @param string $hex Color, as #rgb or #rrggbb.
	 * @return float|null Null when the value isn't a hex color.
	 */
	public static function luminance( $hex ) {
		$rgb = self::to_rgb( $hex );
		if ( null === $rgb ) {
			return null;
		}

		$linear = array_map(
			static function ( $channel ) {
				$channel /= 255;
				return $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
			},
			$rgb
		);

		return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
	}

	/**
	 * Red, green and blue of a hex color.
	 *
	 * @param string $hex Color, as #rgb or #rrggbb.
	 * @return int[]|null Three values from 0 to 255, or null when the value isn't a hex color.
	 */
	public static function to_rgb( $hex ) {
		$hex = sanitize_hex_color( (string) $hex );
		if ( ! $hex ) {
			return null;
		}

		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}
}
