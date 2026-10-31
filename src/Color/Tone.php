<?php
/**
 * Turns a source color into the colors each part of the page uses.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Role colors from one source color, worked out in OKLCH so lightness
 * changes keep the hue the eye sees.
 *
 * A photo color is often too dark or too pale for a button, and the raw
 * color mixed into the page background turns grey. So the accent keeps the
 * hue and moves lightness into a range that works for buttons, and the
 * tint is a very pale color of the same hue.
 */
final class Tone {

	/**
	 * Lightness range for an accent tuned from a photo.
	 */
	const ACCENT_MIN_LIGHTNESS = 0.45;
	const ACCENT_MAX_LIGHTNESS = 0.68;

	/**
	 * Yellows only look like yellow when they're light, so they get a higher
	 * ceiling. Hue range in degrees, and the ceiling.
	 */
	const YELLOW_HUES          = array( 70, 125 );
	const YELLOW_MAX_LIGHTNESS = 0.88;

	/**
	 * Lightness range for greys: a black product keeps a near-black accent,
	 * and a white one doesn't vanish on a white page.
	 */
	const GREY_MIN_LIGHTNESS = 0.22;
	const GREY_MAX_LIGHTNESS = 0.6;

	/**
	 * Chroma range for a tuned accent. Below the low end a color reads as
	 * grey, so it stays as it is; above the high end it glares.
	 */
	const GREY_CHROMA       = 0.04;
	const STRONG_CHROMA     = 0.12;
	const ACCENT_MAX_CHROMA = 0.2;

	/**
	 * Lightness and the most chroma of the page tint.
	 */
	const TINT_LIGHTNESS  = 0.955;
	const TINT_MAX_CHROMA = 0.025;

	/**
	 * A photo color tuned into a usable accent: same hue, lightness and
	 * chroma moved into the ranges above.
	 *
	 * @param string $hex Source color.
	 * @return string Hex color, or an empty string when the source isn't a hex color.
	 */
	public static function accent( $hex ) {
		$lch = self::to_oklch( $hex );
		if ( null === $lch ) {
			return '';
		}

		list( $lightness, $chroma, $hue ) = $lch;

		if ( $chroma <= self::GREY_CHROMA ) {
			$lightness = min( self::GREY_MAX_LIGHTNESS, max( self::GREY_MIN_LIGHTNESS, $lightness ) );
			return self::from_oklch( $lightness, $chroma, $hue );
		}

		$is_yellow = $hue >= self::YELLOW_HUES[0] && $hue <= self::YELLOW_HUES[1];
		$ceiling   = $is_yellow ? self::YELLOW_MAX_LIGHTNESS : self::ACCENT_MAX_LIGHTNESS;
		// Muted darks like navy or forest green stay dark; only strong colors
		// get lifted all the way to the accent floor.
		$strength  = min( 1.0, ( $chroma - self::GREY_CHROMA ) / ( self::STRONG_CHROMA - self::GREY_CHROMA ) );
		$floor     = self::GREY_MIN_LIGHTNESS + ( self::ACCENT_MIN_LIGHTNESS - self::GREY_MIN_LIGHTNESS ) * $strength;
		$lightness = min( $ceiling, max( $floor, $lightness ) );
		$chroma    = min( self::ACCENT_MAX_CHROMA, $chroma );

		return self::from_oklch( $lightness, $chroma, $hue );
	}

	/**
	 * A very pale color of the same hue, for the page background.
	 *
	 * @param string $hex Source color.
	 * @return string Hex color, or an empty string when the source isn't a hex color.
	 */
	public static function tint( $hex ) {
		$lch = self::to_oklch( $hex );
		if ( null === $lch ) {
			return '';
		}

		list( , $chroma, $hue ) = $lch;

		return self::from_oklch( self::TINT_LIGHTNESS, min( self::TINT_MAX_CHROMA, $chroma * 0.25 ), $hue );
	}

	/**
	 * OKLCH lightness (0 to 1), chroma and hue (degrees) of a color.
	 *
	 * @param string $hex Color.
	 * @return float[]|null Null when the value isn't a hex color.
	 */
	public static function to_oklch( $hex ) {
		$rgb = Contrast::to_rgb( $hex );
		if ( null === $rgb ) {
			return null;
		}

		list( $r, $g, $b ) = array_map( array( __CLASS__, 'to_linear' ), $rgb );

		$l = self::cbrt( 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b );
		$m = self::cbrt( 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b );
		$s = self::cbrt( 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b );

		$lightness = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
		$a         = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
		$bb        = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

		$hue = rad2deg( atan2( $bb, $a ) );

		return array( $lightness, sqrt( $a * $a + $bb * $bb ), $hue < 0 ? $hue + 360 : $hue );
	}

	/**
	 * A color from OKLCH. Chroma comes down until the color fits in sRGB,
	 * so the hue and lightness stay as asked.
	 *
	 * @param float $lightness Lightness, 0 to 1.
	 * @param float $chroma    Chroma.
	 * @param float $hue       Hue in degrees.
	 * @return string Hex color.
	 */
	public static function from_oklch( $lightness, $chroma, $hue ) {
		$rgb = self::oklch_to_linear( $lightness, $chroma, $hue );
		if ( ! self::in_gamut( $rgb ) ) {
			$low  = 0.0;
			$high = $chroma;
			for ( $i = 0; $i < 20; $i++ ) {
				$mid = ( $low + $high ) / 2;
				if ( self::in_gamut( self::oklch_to_linear( $lightness, $mid, $hue ) ) ) {
					$low = $mid;
				} else {
					$high = $mid;
				}
			}
			$rgb = self::oklch_to_linear( $lightness, $low, $hue );
		}

		$channels = array_map(
			static function ( $channel ) {
				$channel = max( 0.0, min( 1.0, $channel ) );
				$srgb    = $channel <= 0.0031308 ? 12.92 * $channel : 1.055 * pow( $channel, 1 / 2.4 ) - 0.055;
				return (int) round( $srgb * 255 );
			},
			$rgb
		);

		return sprintf( '#%02x%02x%02x', $channels[0], $channels[1], $channels[2] );
	}

	/**
	 * Linear sRGB from OKLCH, possibly outside 0 to 1.
	 *
	 * @param float $lightness Lightness.
	 * @param float $chroma    Chroma.
	 * @param float $hue       Hue in degrees.
	 * @return float[]
	 */
	private static function oklch_to_linear( $lightness, $chroma, $hue ) {
		$a = $chroma * cos( deg2rad( $hue ) );
		$b = $chroma * sin( deg2rad( $hue ) );

		$l = ( $lightness + 0.3963377774 * $a + 0.2158037573 * $b ) ** 3;
		$m = ( $lightness - 0.1055613458 * $a - 0.0638541728 * $b ) ** 3;
		$s = ( $lightness - 0.0894841775 * $a - 1.2914855480 * $b ) ** 3;

		return array(
			4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
			-1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
			-0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
		);
	}

	/**
	 * Whether linear sRGB channels are all inside 0 to 1, with a little slack
	 * for rounding.
	 *
	 * @param float[] $rgb Linear channels.
	 * @return bool
	 */
	private static function in_gamut( $rgb ) {
		foreach ( $rgb as $channel ) {
			if ( $channel < -0.0001 || $channel > 1.0001 ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Linear value of an sRGB channel.
	 *
	 * @param int $channel 0 to 255.
	 * @return float
	 */
	private static function to_linear( $channel ) {
		$channel /= 255;
		return $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
	}

	/**
	 * Cube root that keeps the sign.
	 *
	 * @param float $value Value.
	 * @return float
	 */
	private static function cbrt( $value ) {
		return $value < 0 ? -pow( -$value, 1 / 3 ) : pow( $value, 1 / 3 );
	}
}
