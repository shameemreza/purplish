<?php
/**
 * The Account style for the Group block, made for WooCommerce's My Account.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce 11.3 prints My Account with its shortcode, and a theme styles
 * little more than its inputs. A Group with this style around the shortcode
 * gets ruled navigation, a lead greeting, ruled order tables, address cards
 * and login panels, in the theme's colors. The stylesheet loads only on a
 * page with such a Group.
 */
final class AccountStyle {

	const NAME = 'purplish-account';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_style' ) );
		add_filter( 'render_block_core/group', array( self::class, 'render' ), 10, 2 );
	}

	/**
	 * Registers the style on the Group block.
	 *
	 * @return void
	 */
	public function register_style() {
		if ( function_exists( 'register_block_style' ) ) {
			register_block_style(
				'core/group',
				array(
					'name'  => self::NAME,
					'label' => __( 'Account', 'purplish' ),
				)
			);
		}
	}

	/**
	 * Whether a parsed block carries the style.
	 *
	 * @param array $block Parsed block.
	 * @return bool
	 */
	public static function uses( $block ) {
		$class = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		return in_array( 'is-style-' . self::NAME, preg_split( '/\s+/', $class ), true );
	}

	/**
	 * Loads the stylesheet with a Group that carries the style.
	 *
	 * @param string $html  Rendered block.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public static function render( $html, $block ) {
		if ( '' !== $html && self::uses( $block ) ) {
			BlockEffects::enqueue( array( 'account' ) );
		}
		return $html;
	}
}
