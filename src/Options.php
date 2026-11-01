<?php
/**
 * The page options: which parts take the color, and the photo transition.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * Defaults and the allowlist for the options, shared by the block panel,
 * the classic settings and the page data.
 */
final class Options {

	const PART_BUTTON = 'button';
	const PART_CHIP   = 'chip';
	const PART_BADGE  = 'badge';
	const PART_PRICE  = 'price';
	const PART_TINT   = 'tint';

	const TRANSITIONS = array( 'none', 'fade', 'blur' );

	/**
	 * Every part, with whether it's on by default.
	 *
	 * @return array<string, bool>
	 */
	public static function part_defaults() {
		return array(
			self::PART_BUTTON => true,
			self::PART_CHIP   => true,
			self::PART_BADGE  => true,
			self::PART_PRICE  => false,
			self::PART_TINT   => false,
		);
	}

	/**
	 * The default options.
	 *
	 * @return array{parts: string[], transition: string, rise: bool, dots: bool}
	 */
	public static function defaults() {
		return array(
			'parts'      => array_keys( array_filter( self::part_defaults() ) ),
			'transition' => 'blur',
			'rise'       => false,
			'dots'       => false,
		);
	}

	/**
	 * Options from any source, cleaned against the allowlist. Missing keys
	 * take the defaults.
	 *
	 * @param mixed $raw Options as saved: an array with `parts`, `transition`, `rise` and `dots`.
	 * @return array{parts: string[], transition: string, rise: bool, dots: bool}
	 */
	public static function clean( $raw ) {
		$options = self::defaults();
		if ( ! is_array( $raw ) ) {
			return $options;
		}

		if ( isset( $raw['parts'] ) && is_array( $raw['parts'] ) ) {
			$known            = array_keys( self::part_defaults() );
			$options['parts'] = array_values( array_intersect( $known, array_map( 'sanitize_key', $raw['parts'] ) ) );
		}

		if ( isset( $raw['transition'] ) ) {
			$transition = sanitize_key( $raw['transition'] );
			if ( in_array( $transition, self::TRANSITIONS, true ) ) {
				$options['transition'] = $transition;
			}
		}

		foreach ( array( 'rise', 'dots' ) as $switch ) {
			if ( isset( $raw[ $switch ] ) ) {
				$options[ $switch ] = true === $raw[ $switch ] || 'yes' === $raw[ $switch ];
			}
		}

		return $options;
	}
}
