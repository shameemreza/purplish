<?php
/**
 * The theme's own text, background and accent colors as Purplish tokens.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks and effects fell back to the `contrast`, `base` and `accent`
 * palette colors, which many block themes don't have (Purple names its
 * colors `theme-1` to `theme-7`). The page's own colors from global styles
 * are put on `:root` instead, so every style variation, dark ones included,
 * reaches Purplish. A palette color stays a `var()` so the Site Editor's
 * palette edits still follow.
 */
final class ThemeColors {

	/**
	 * Style handle the tokens print with.
	 */
	const HANDLE = 'purplish-theme-colors';

	/**
	 * Accent text and outlines sit on the page background, so an accent
	 * needs at least the contrast WCAG asks of large text and UI parts.
	 */
	const ACCENT_RATIO = 3.0;

	/**
	 * Adds the hook.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'enqueue_block_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Prints the tokens on the page and in the editor canvas.
	 *
	 * @return void
	 */
	public function enqueue() {
		$css = self::css();
		if ( '' === $css ) {
			return;
		}
		wp_register_style( self::HANDLE, false, array(), PURPLISH_VERSION );
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style( self::HANDLE, $css );
	}

	/**
	 * The `:root` rule, or an empty string when the theme sets none of the
	 * colors (classic themes).
	 *
	 * @return string
	 */
	public static function css() {
		$rules = '';
		foreach ( self::tokens() as $name => $value ) {
			$rules .= $name . ':' . $value . ';';
		}
		return '' === $rules ? '' : ':root{' . $rules . '}';
	}

	/**
	 * Each token the theme sets a color for.
	 *
	 * @return array<string, string> Token name to a hex color or a palette `var()`.
	 */
	public static function tokens() {
		if ( ! function_exists( 'wp_get_global_styles' ) ) {
			return array();
		}

		$ink    = self::read( array( 'color', 'text' ) );
		$paper  = self::read( array( 'color', 'background' ) );
		$accent = self::accent(
			$paper,
			array(
				self::read( array( 'elements', 'button', 'color', 'background' ) ),
				self::read( array( 'elements', 'link', 'color', 'text' ) ),
				$ink,
			)
		);

		return array_filter(
			array(
				'--purplish-theme-ink'    => $ink,
				'--purplish-theme-paper'  => $paper,
				'--purplish-theme-accent' => $accent,
			)
		);
	}

	/**
	 * The first color that reads on the page background. A dark theme's
	 * button color is often too dark for text on its dark page, so the link
	 * color and then the text color come next. When the background or a
	 * color can't be measured (a gradient, an unknown slug), the first color
	 * set is used.
	 *
	 * @param string   $paper      Page background.
	 * @param string[] $candidates Colors in order of preference.
	 * @return string
	 */
	private static function accent( $paper, $candidates ) {
		$candidates = array_values( array_filter( $candidates ) );
		if ( empty( $candidates ) ) {
			return '';
		}
		$ground = self::hex( $paper );
		if ( '' === $ground ) {
			return $candidates[0];
		}
		foreach ( $candidates as $color ) {
			$hex = self::hex( $color );
			if ( '' === $hex || Contrast::ratio( $hex, $ground ) >= self::ACCENT_RATIO ) {
				return $color;
			}
		}
		return end( $candidates );
	}

	/**
	 * A safe CSS color from global styles: a hex color, or a palette
	 * reference as `var(--wp--preset--color--slug)`.
	 *
	 * @param string[] $path Global styles path.
	 * @return string Empty when the value is anything else.
	 */
	private static function read( $path ) {
		$value = wp_get_global_styles( $path );
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		if ( preg_match( '/^var\(--wp--preset--color--([a-z0-9-]+)\)$/i', $value, $match ) || preg_match( '/^var:preset\|color\|([a-z0-9-]+)$/i', $value, $match ) ) {
			return 'var(--wp--preset--color--' . strtolower( $match[1] ) . ')';
		}
		return (string) sanitize_hex_color( strtolower( $value ) );
	}

	/**
	 * A token value as hex, for measuring contrast.
	 *
	 * @param string $value Hex color or palette `var()`.
	 * @return string
	 */
	private static function hex( $value ) {
		if ( preg_match( '/^var\(--wp--preset--color--([a-z0-9-]+)\)$/', $value, $match ) ) {
			return Ground::preset( $match[1] );
		}
		return (string) sanitize_hex_color( $value );
	}
}
