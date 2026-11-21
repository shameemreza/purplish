<?php
/**
 * The theme's section styles as Purplish tokens.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Purplish blocks take their colors from the theme tokens ThemeColors puts
 * on `:root`. A section style (Purple's Style 1 to 3, TT5's Style 1 to 5)
 * colors a Group, Columns or Column band, so inside one those tokens are
 * set again to the band's own text, background and accent. Blocks inherit
 * them from there, nested sections included.
 *
 * The styles come from the merged theme.json data, so a color variation
 * that patches a section for one block (Purple's dark palettes do) reaches
 * the tokens too.
 */
final class SectionColors {

	/**
	 * Blocks a section style goes on.
	 */
	const BLOCKS = array( 'core/group', 'core/columns', 'core/column' );

	/**
	 * Parts a theme can color the same everywhere, as Purple does with its
	 * heading color for the Mini-Cart, Navigation and rating and its muted
	 * color for the price and summary. In a dark band they disappear, along
	 * with a Wardrobe in that Navigation, so in a section they take the
	 * band's text color. A color picked on the block itself still wins.
	 */
	const FOLLOW = '.wp-block-woocommerce-mini-cart,.wp-block-navigation,.wc-block-components-product-price,.wc-block-components-product-rating,.wc-block-components-product-summary';

	/**
	 * Each section style that sets a color, as a selector and its tokens.
	 *
	 * @return array<string, array<string, string>> Selector to token values.
	 */
	public static function sections() {
		if ( ! class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			return array();
		}
		$styles = \WP_Theme_JSON_Resolver::get_merged_data()->get_raw_data();
		$blocks = isset( $styles['styles']['blocks'] ) && is_array( $styles['styles']['blocks'] ) ? $styles['styles']['blocks'] : array();

		$sections = array();
		foreach ( self::BLOCKS as $block ) {
			$variations = isset( $blocks[ $block ]['variations'] ) && is_array( $blocks[ $block ]['variations'] ) ? $blocks[ $block ]['variations'] : array();
			foreach ( $variations as $slug => $style ) {
				$tokens = self::tokens( is_array( $style ) ? $style : array() );
				if ( ! empty( $tokens ) && preg_match( '/^[a-z0-9-]+$/i', (string) $slug ) ) {
					$sections[ '.' . wp_get_block_default_classname( $block ) . '.is-style-' . strtolower( $slug ) ] = $tokens;
				}
			}
		}
		return $sections;
	}

	/**
	 * Tokens for one section style.
	 *
	 * @param array $style The variation's styles.
	 * @return array<string, string>
	 */
	public static function tokens( $style ) {
		$ink   = ThemeColors::color( self::get( $style, array( 'color', 'text' ) ) );
		$paper = ThemeColors::color( self::get( $style, array( 'color', 'background' ) ) );
		if ( '' === $ink && '' === $paper ) {
			return array();
		}
		$accent = ThemeColors::accent(
			$paper,
			array(
				ThemeColors::color( self::get( $style, array( 'elements', 'button', 'color', 'background' ) ) ),
				ThemeColors::color( self::get( $style, array( 'elements', 'link', 'color', 'text' ) ) ),
				$ink,
			)
		);

		return array_filter(
			array(
				'--purplish-theme-ink'    => $ink,
				'--purplish-theme-paper'  => $paper,
				'--purplish-theme-accent' => $accent,
				'--purplish-ground'       => $paper,
			)
		);
	}

	/**
	 * The rules: tokens for each section, grouped by value, then soft fills
	 * worked out again against the section's background on a page with a
	 * page color, then the parts in FOLLOW in the section's text color.
	 *
	 * @return string
	 */
	public static function css() {
		$sections = self::sections();
		if ( empty( $sections ) ) {
			return '';
		}

		$groups = array();
		foreach ( $sections as $selector => $tokens ) {
			$body = '';
			foreach ( $tokens as $name => $value ) {
				$body .= $name . ':' . $value . ';';
			}
			$groups[ $body ][] = $selector;
		}

		$css = '';
		foreach ( $groups as $body => $selectors ) {
			$css .= implode( ',', $selectors ) . '{' . $body . '}';
		}

		$all = ':is(' . implode( ',', array_keys( $sections ) ) . ')';

		return $css
			. 'body.purplish-active ' . $all . '{--purplish-fx-tint:color-mix(in oklab, var(--purplish-tint) 55%, var(--purplish-ground, #fff))}'
			. $all . ' :is(' . self::FOLLOW . '){color:inherit}';
	}

	/**
	 * A string value at a path in a styles array.
	 *
	 * @param array    $style Styles.
	 * @param string[] $path  Keys.
	 * @return string
	 */
	private static function get( $style, $path ) {
		foreach ( $path as $key ) {
			if ( ! is_array( $style ) || ! isset( $style[ $key ] ) ) {
				return '';
			}
			$style = $style[ $key ];
		}
		return is_string( $style ) ? $style : '';
	}
}
