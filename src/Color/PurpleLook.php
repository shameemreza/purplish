<?php
/**
 * Purplish's theme values, filled in from the Purple theme.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Purplish's cards, panels and checkout looks read their corners, rules,
 * washes and shadows from `settings.custom.purplish` in theme.json, and
 * fall back to soft defaults (a 12px corner, a light tint). Purple is square
 * and flat, so with Purple active the values come from Purple instead: no
 * corner, Purple's Border color for rules, its Base Alt color for washes
 * and no shadow. They go in as the theme's own values, so a store's Global
 * Styles still win, and a value the theme (or a child theme) sets itself is
 * left alone. Other themes keep the defaults and can set the same values.
 */
final class PurpleLook {

	/**
	 * The template that turns the values on; child themes match too.
	 */
	const TEMPLATE = 'purple';

	/**
	 * Purple's palette slug for each color value. Purple renamed its colors
	 * in 1.0.2, so each one is looked up before it's used.
	 */
	const COLORS = array(
		'rule' => 'theme-6',
		'wash' => 'theme-5',
	);

	/**
	 * Photo washes multiply the photo into the wash, so a dark wash would
	 * darken every product photo. Below this luminance Purple's Base Alt is
	 * too dark to be a wash, and Purplish's own light tint stays.
	 */
	const WASH_MIN_LUMINANCE = 0.5;

	/**
	 * Adds the hook. It runs late, after a child theme's own filters.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wp_theme_json_data_theme', array( $this, 'filter' ), 20 );
	}

	/**
	 * Adds Purple's values to the theme's data.
	 *
	 * @param \WP_Theme_JSON_Data $data Theme data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function filter( $data ) {
		if ( self::TEMPLATE !== get_template() || ! is_object( $data ) || ! method_exists( $data, 'get_data' ) ) {
			return $data;
		}

		$json   = $data->get_data();
		$values = self::values( is_array( $json ) ? $json : array(), self::variation_palette() );
		if ( empty( $values ) ) {
			return $data;
		}

		return $data->update_with(
			array(
				'version'  => \WP_Theme_JSON::LATEST_SCHEMA,
				'settings' => array( 'custom' => array( 'purplish' => $values ) ),
			)
		);
	}

	/**
	 * Purple's values the theme doesn't set itself.
	 *
	 * @param array $json      The theme's theme.json data.
	 * @param array $variation Slug to color for the store's chosen palette,
	 *                         which replaces the theme's colors of the same slug.
	 * @return array<string, string>
	 */
	public static function values( $json, $variation = array() ) {
		$own     = isset( $json['settings']['custom']['purplish'] ) && is_array( $json['settings']['custom']['purplish'] ) ? $json['settings']['custom']['purplish'] : array();
		$palette = array_merge( self::palette( isset( $json['settings']['color']['palette'] ) ? $json['settings']['color']['palette'] : array() ), $variation );

		$values = array(
			'radius' => '0px',
			'shadow' => 'none',
		);
		foreach ( self::COLORS as $name => $slug ) {
			if ( ! isset( $palette[ $slug ] ) ) {
				continue;
			}
			if ( 'wash' === $name && ! self::is_light( $palette[ $slug ] ) ) {
				continue;
			}
			$values[ $name ] = 'var(--wp--preset--color--' . $slug . ')';
		}

		return array_diff_key( $values, $own );
	}

	/**
	 * The palette from the store's saved Global Styles, which is where a
	 * chosen color variation lives.
	 *
	 * @return array<string, string>
	 */
	private static function variation_palette() {
		if ( ! class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			return array();
		}
		$user = \WP_Theme_JSON_Resolver::get_user_data()->get_data();
		return self::palette( isset( $user['settings']['color']['palette'] ) ? $user['settings']['color']['palette'] : array() );
	}

	/**
	 * A palette as slug to color. Takes a plain list or one split by origin.
	 *
	 * @param mixed $palette Palette from theme.json data.
	 * @return array<string, string>
	 */
	private static function palette( $palette ) {
		if ( ! is_array( $palette ) ) {
			return array();
		}
		$lists = isset( $palette[0] ) || empty( $palette ) ? array( $palette ) : array_values( $palette );
		$out   = array();
		foreach ( $lists as $list ) {
			foreach ( is_array( $list ) ? $list : array() as $entry ) {
				if ( isset( $entry['slug'], $entry['color'] ) && is_string( $entry['color'] ) ) {
					$out[ strtolower( (string) $entry['slug'] ) ] = $entry['color'];
				}
			}
		}
		return $out;
	}

	/**
	 * Whether a color is light enough to sit behind a photo. A color that
	 * can't be read as hex counts as light, as Purple's Base Alt always is
	 * on its light palettes.
	 *
	 * @param string $color A CSS color.
	 * @return bool
	 */
	private static function is_light( $color ) {
		$hex = sanitize_hex_color( strtolower( trim( $color ) ) );
		if ( ! $hex ) {
			return true;
		}
		return Contrast::luminance( $hex ) >= self::WASH_MIN_LUMINANCE;
	}
}
