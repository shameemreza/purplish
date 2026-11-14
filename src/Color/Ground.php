<?php
/**
 * The theme's page background, so tints follow a dark style.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * The page background from the theme's global styles, the active style
 * variation included. Tints were made for a light page; on a dark one they
 * glow behind the theme's light text, so they're worked out from this
 * background instead.
 */
final class Ground {

	/**
	 * The background, worked out once per request.
	 *
	 * @var string|null
	 */
	private static $color = null;

	/**
	 * The page background as a hex color, or an empty string when the theme
	 * doesn't set one in its global styles (most classic themes).
	 *
	 * @return string
	 */
	public static function color() {
		if ( null === self::$color ) {
			self::$color = self::resolve();
		}
		return self::$color;
	}

	/**
	 * The background, only when it's dark enough to need light text.
	 *
	 * @return string Hex color, or an empty string on a light or unknown background.
	 */
	public static function dark() {
		$color = self::color();
		return '' !== $color && Contrast::WHITE === Contrast::on( $color ) ? $color : '';
	}

	/**
	 * Forgets the worked out background, for tests and style changes.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$color = null;
	}

	/**
	 * Reads the background from global styles. A preset reference becomes
	 * that palette color.
	 *
	 * @return string
	 */
	private static function resolve() {
		if ( ! function_exists( 'wp_get_global_styles' ) ) {
			return '';
		}

		$value = wp_get_global_styles( array( 'color', 'background' ) );
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}

		if ( preg_match( '/^var\(--wp--preset--color--([a-z0-9-]+)\)$/i', $value, $match ) || preg_match( '/^var:preset\|color\|([a-z0-9-]+)$/i', $value, $match ) ) {
			$value   = '';
			$palette = wp_get_global_settings( array( 'color', 'palette' ) );
			foreach ( array( 'custom', 'theme', 'default' ) as $origin ) {
				foreach ( isset( $palette[ $origin ] ) && is_array( $palette[ $origin ] ) ? $palette[ $origin ] : array() as $entry ) {
					if ( isset( $entry['slug'], $entry['color'] ) && strtolower( $entry['slug'] ) === strtolower( $match[1] ) ) {
						$value = (string) $entry['color'];
						break 2;
					}
				}
			}
		}

		return (string) sanitize_hex_color( strtolower( $value ) );
	}
}
