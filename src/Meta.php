<?php
/**
 * Meta keys Purplish stores on variations.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * Meta key names, in one place so the field, the resolver, the detection
 * jobs and uninstall agree.
 */
final class Meta {

	/**
	 * `auto`, `custom` or `off`. Missing means `auto`.
	 */
	const MODE = '_purplish_mode';

	/**
	 * The color the merchant picked, as a hex color.
	 */
	const CUSTOM_COLOR = '_purplish_custom_color';

	/**
	 * The color read from the variation photo, as a hex color.
	 */
	const AUTO_COLOR = '_purplish_auto_color';

	/**
	 * The photo the auto color came from: attachment ID and modified time.
	 */
	const AUTO_SOURCE = '_purplish_auto_source';

	const MODE_AUTO   = 'auto';
	const MODE_CUSTOM = 'custom';
	const MODE_OFF    = 'off';

	/**
	 * Every mode the field accepts.
	 *
	 * @return string[]
	 */
	public static function modes() {
		return array( self::MODE_AUTO, self::MODE_CUSTOM, self::MODE_OFF );
	}

	/**
	 * A variation's mode, falling back to `auto` for anything missing or
	 * unknown.
	 *
	 * @param \WC_Product $variation Variation.
	 * @return string
	 */
	public static function mode( $variation ) {
		$mode = (string) $variation->get_meta( self::MODE );
		return in_array( $mode, self::modes(), true ) ? $mode : self::MODE_AUTO;
	}
}
