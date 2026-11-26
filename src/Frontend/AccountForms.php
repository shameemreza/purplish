<?php
/**
 * Login and register options for WooCommerce's My Account forms.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * The options live in three places, so a store can build the page any way:
 * the Account Group, the Shortcode block that holds `[woocommerce_my_account]`
 * (WooCommerce's own My Account page), and Purplish's settings for pages
 * with neither, like classic themes and the classic editor. Each turns into
 * `purplish-account-<option>-<value>` classes on an element around the
 * forms. The stylesheet and the script load only for logged-out visitors,
 * the only ones who see the forms.
 */
final class AccountForms {

	/**
	 * Where the options sit in a block's `purplish` attribute.
	 */
	const KEY = 'account';

	/**
	 * The shortcode WooCommerce prints My Account with.
	 */
	const SHORTCODE = 'woocommerce_my_account';

	/**
	 * Handle of the forms script.
	 */
	const HANDLE = 'purplish-login';

	/**
	 * Whether a block on this page already put options around the forms,
	 * so the saved settings stay out of it.
	 *
	 * @var bool
	 */
	private static $placed = false;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'register_block_type_args', array( $this, 'add_attribute' ), 10, 2 );
		add_filter( 'render_block_core/shortcode', array( self::class, 'render_shortcode_block' ), 10, 2 );
		add_filter( 'do_shortcode_tag', array( self::class, 'wrap_classic' ), 10, 2 );
	}

	/**
	 * The options and the values each accepts, shaped like an entry in
	 * `BlockEffects::blocks()`. `true` is an on or off switch.
	 *
	 * @return array{prefix: string, options: array<string, mixed>, needs: array<string, mixed>}
	 */
	public static function config() {
		return array(
			'prefix'  => 'account',
			'options' => array(
				'fields'    => array( 'soft', 'filled', 'underline', 'draw', 'fill', 'corners' ),
				'focus'     => true,
				'labels'    => array( 'inside', 'floating', 'flip', 'wave' ),
				'switch'    => array( 'tabs', 'flip', 'slide' ),
				'tabs'      => array( 'line' ),
				'slide'     => array( 'stretch', 'flood', 'blinds', 'page', 'shear', 'shutter', 'liquid', 'depth', 'elastic', 'mosaic', 'fold', 'iris' ),
				'panel'     => array( 'card', 'glass' ),
				'aurora'    => true,
				'spotlight' => true,
				'button'    => array( 'bloom', 'ripple', 'sweep', 'spectrum', 'trace', 'roll', 'split', 'glass' ),
			),
			'needs'   => array(
				// Slide shows tabs on phones, so both take the tab look.
				'tabs'  => array( array( 'switch', array( 'tabs', 'slide' ), true ) ),
				'slide' => array( array( 'switch', array( 'slide' ), true ) ),
			),
		);
	}

	/**
	 * The classes for saved options, `purplish-account` first, or none
	 * when no option is set.
	 *
	 * @param mixed $saved The saved options.
	 * @return string[]
	 */
	public static function classes( $saved ) {
		return BlockEffects::classes_from( self::config(), $saved );
	}

	/**
	 * The options saved on a block, or an empty array.
	 *
	 * @param array $block Parsed block.
	 * @return array
	 */
	public static function saved_on( $block ) {
		$saved = isset( $block['attrs']['purplish'][ self::KEY ] ) ? $block['attrs']['purplish'][ self::KEY ] : array();
		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * The options from the settings page, read from the `account_` keys.
	 *
	 * @return array
	 */
	public static function saved_in_settings() {
		$settings = get_option( Settings::OPTION, array() );
		$saved    = array();
		if ( ! is_array( $settings ) ) {
			return $saved;
		}
		foreach ( self::config()['options'] as $option => $allowed ) {
			$value = isset( $settings[ 'account_' . $option ] ) ? $settings[ 'account_' . $option ] : null;
			if ( true === $allowed && 'yes' === $value ) {
				$saved[ $option ] = true;
			} elseif ( is_array( $allowed ) && in_array( $value, $allowed, true ) ) {
				$saved[ $option ] = $value;
			}
		}
		return $saved;
	}

	/**
	 * Lets the Shortcode block save a `purplish` attribute. The Group has
	 * one already, from BlockEffects.
	 *
	 * @param array  $args Block type arguments.
	 * @param string $name Block name.
	 * @return array
	 */
	public function add_attribute( $args, $name ) {
		if ( 'core/shortcode' !== $name ) {
			return $args;
		}
		$args['attributes']             = isset( $args['attributes'] ) && is_array( $args['attributes'] ) ? $args['attributes'] : array();
		$args['attributes']['purplish'] = array(
			'type'    => 'object',
			'default' => array(),
		);
		return $args;
	}

	/**
	 * Whether rendered markup holds the My Account shortcode or forms.
	 *
	 * @param string $html Markup.
	 * @return bool
	 */
	private static function holds_account( $html ) {
		return false !== strpos( $html, '[' . self::SHORTCODE ) || false !== strpos( $html, 'woocommerce-form-login' );
	}

	/**
	 * Adds the option classes to an Account Group, unless a Shortcode block
	 * inside it has options of its own.
	 *
	 * @param string $html  Rendered Group.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public static function render_group( $html, $block ) {
		$classes = self::classes( self::saved_on( $block ) );
		if ( ! $classes || false !== strpos( $html, 'purplish-account-forms' ) ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() ) {
			return $html;
		}
		foreach ( self::with_state( $classes ) as $class ) {
			$tags->add_class( $class );
		}
		self::placed();
		return $tags->get_updated_html();
	}

	/**
	 * Puts the options of a Shortcode block that holds My Account on an
	 * element around it. The shortcode itself runs later, inside it.
	 *
	 * @param string $html  Rendered block.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public static function render_shortcode_block( $html, $block ) {
		$classes = self::classes( self::saved_on( $block ) );
		if ( ! $classes || ! self::holds_account( $html ) ) {
			return $html;
		}
		self::placed();
		return self::wrap( $html, $classes );
	}

	/**
	 * Puts the saved settings around My Account on a page where no block
	 * set options, like a classic theme or the classic editor.
	 *
	 * @param string $output Shortcode output.
	 * @param string $tag    Shortcode name.
	 * @return string
	 */
	public static function wrap_classic( $output, $tag ) {
		if ( self::SHORTCODE !== $tag || self::$placed || ! is_string( $output ) ) {
			return $output;
		}
		$classes = self::classes( self::saved_in_settings() );
		if ( ! $classes ) {
			return $output;
		}
		self::placed();
		return self::wrap( $output, $classes );
	}

	/**
	 * Wraps markup in an element with the classes.
	 *
	 * @param string   $html    Markup.
	 * @param string[] $classes Class names.
	 * @return string
	 */
	private static function wrap( $html, $classes ) {
		$classes   = self::with_state( $classes );
		$classes[] = 'purplish-account-forms';
		return '<div class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ) . '">' . $html . '</div>';
	}

	/**
	 * Adds `purplish-account-open-register` when the register form was just
	 * sent back with errors, so the switch opens on it.
	 *
	 * @param string[] $classes Option classes.
	 * @return string[]
	 */
	public static function with_state( $classes ) {
		// WooCommerce checks the nonce; only whether the form was sent matters here.
		if ( isset( $_POST['register'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$classes[] = 'purplish-account-open-register';
		}
		return $classes;
	}

	/**
	 * Marks the page as done and loads the stylesheet and script for a
	 * logged-out visitor.
	 *
	 * @return void
	 */
	private static function placed() {
		self::$placed = true;
		if ( is_user_logged_in() ) {
			return;
		}
		BlockEffects::enqueue( array( 'login' ) );
		$asset = include PURPLISH_DIR . 'build/login.asset.php';
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/login.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		wp_set_script_translations( self::HANDLE, 'purplish' );
	}

	/**
	 * Forgets that options were placed, for tests.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$placed = false;
	}
}
