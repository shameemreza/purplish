<?php
/**
 * Neutral panels: soft panels keep a fill from the theme's text color
 * instead of the page color.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Soft panels (accordions, the cart drawer, fields, the cart) mix from the
 * page color by default. With Tint panels with the page color off, the body
 * gets a class and the CSS sets the panel tint from the theme's text color.
 * The page background tint and Group's take the page color option keep the
 * page color.
 *
 * @since 1.4.1
 */
final class NeutralPanels {

	const KEY = 'tint_panels';

	const BODY_CLASS = 'purplish-neutral-panels';

	/**
	 * Adds the hook.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Whether the store turned panel tints off. Unsaved means on.
	 *
	 * @return bool
	 */
	public static function neutral() {
		$saved = get_option( Settings::OPTION, array() );
		return is_array( $saved ) && isset( $saved[ self::KEY ] ) && 'no' === $saved[ self::KEY ];
	}

	/**
	 * Adds the class when panel tints are off.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( self::neutral() ) {
			$classes[] = self::BODY_CLASS;
		}
		return $classes;
	}
}
