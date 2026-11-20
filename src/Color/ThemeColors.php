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
	 * Purplish's patterns use Purple's color names. On a theme without
	 * them, each name points at the theme's own colors instead, so the
	 * patterns still pick up that theme's look.
	 */
	const PURPLE_NAMES = array(
		'theme-1' => 'var(--purplish-theme-paper, #fff)',
		'theme-2' => 'var(--purplish-theme-ink, #111)',
		'theme-3' => 'var(--purplish-theme-accent, #1f0342)',
		'theme-4' => 'var(--purplish-theme-ink, #322f37)',
		'theme-5' => 'color-mix(in srgb, var(--purplish-theme-accent, #720eec) 8%, var(--purplish-theme-paper, #fff))',
		'theme-6' => 'color-mix(in srgb, var(--purplish-theme-ink, #111) 20%, transparent)',
		'theme-7' => 'var(--purplish-theme-accent, #720eec)',
	);

	/**
	 * Purple colors the Mini-Cart with its heading color everywhere, so in
	 * a dark section band the bag and count disappear. Inside Purple's
	 * section styles the Mini-Cart takes the band's text color instead.
	 */
	const SECTION_RULE = ':is(.is-style-section-1,.is-style-section-2,.is-style-section-3) .wp-block-woocommerce-mini-cart{color:inherit}';

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
	 * The `:root` rule, when the theme lacks a color, and the section rule.
	 *
	 * @return string
	 */
	public static function css() {
		$rules = '';
		foreach ( self::tokens() as $name => $value ) {
			$rules .= $name . ':' . $value . ';';
		}
		$slugs = self::slugs();
		foreach ( self::PURPLE_NAMES as $slug => $value ) {
			if ( ! isset( $slugs[ $slug ] ) ) {
				$rules .= '--wp--preset--color--' . $slug . ':' . $value . ';';
			}
		}
		return ( '' === $rules ? '' : ':root{' . $rules . '}' ) . self::SECTION_RULE;
	}

	/**
	 * Every palette slug the site has, from any origin.
	 *
	 * @return array<string, true>
	 */
	private static function slugs() {
		$slugs   = array();
		$palette = wp_get_global_settings( array( 'color', 'palette' ) );
		foreach ( is_array( $palette ) ? $palette : array() as $entries ) {
			foreach ( is_array( $entries ) ? $entries : array() as $entry ) {
				if ( isset( $entry['slug'] ) ) {
					$slugs[ strtolower( (string) $entry['slug'] ) ] = true;
				}
			}
		}
		return $slugs;
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
