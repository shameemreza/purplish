<?php
/**
 * Purplish options on the Product Gallery, Product Collection and Accordion
 * blocks.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers each block's `purplish` attribute on the server, and turns the
 * saved options into classes on the block's wrapper. The classes are the
 * only change to the block's markup; the effects stylesheet does the rest.
 */
final class BlockEffects {

	const HANDLE = 'purplish-effects';

	/**
	 * Purplish's options for each block, with the values each one accepts.
	 * `true` means an on or off switch.
	 *
	 * @return array<string, array{prefix: string, options: array<string, mixed>}>
	 */
	public static function blocks() {
		return array(
			'woocommerce/product-gallery' => array(
				'prefix'  => 'gallery',
				'options' => array(
					'nothumbs' => true,
					'rounded'  => true,
					'arrows'   => true,
					'tilt'     => true,
					'lightbox' => true,
				),
			),
		);
	}

	/**
	 * Whether an effect stylesheet is needed on this page.
	 *
	 * @var bool
	 */
	private static $used = false;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'register_block_type_args', array( $this, 'add_attribute' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'add_classes' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_classic' ), 20 );
		add_filter( 'body_class', array( $this, 'classic_body_classes' ) );
		add_filter( 'woocommerce_single_product_zoom_enabled', array( $this, 'classic_zoom' ) );
	}

	/**
	 * Turns off WooCommerce's hover zoom on classic galleries when the 3D
	 * tilt is on: the two would move the same photo at once.
	 *
	 * @param bool $enabled Whether the zoom is on.
	 * @return bool
	 */
	public function classic_zoom( $enabled ) {
		return in_array( 'purplish-classic-gallery-tilt', self::classic_classes(), true ) ? false : $enabled;
	}

	/**
	 * Lets each block save a `purplish` attribute, so the server keeps it.
	 *
	 * @param array  $args Block type arguments.
	 * @param string $name Block name.
	 * @return array
	 */
	public function add_attribute( $args, $name ) {
		if ( ! isset( self::blocks()[ $name ] ) ) {
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
	 * The wrapper classes for a block's saved options. Unknown options and
	 * values are ignored.
	 *
	 * @param string $name  Block name.
	 * @param mixed  $saved The block's `purplish` attribute.
	 * @return string[]
	 */
	public static function classes_for( $name, $saved ) {
		$blocks = self::blocks();
		if ( ! isset( $blocks[ $name ] ) || ! is_array( $saved ) ) {
			return array();
		}

		$prefix  = 'purplish-' . $blocks[ $name ]['prefix'];
		$classes = array();
		foreach ( $blocks[ $name ]['options'] as $option => $allowed ) {
			if ( ! isset( $saved[ $option ] ) ) {
				continue;
			}
			if ( true === $allowed && true === $saved[ $option ] ) {
				$classes[] = $prefix . '-' . $option;
			} elseif ( is_array( $allowed ) && in_array( $saved[ $option ], $allowed, true ) ) {
				$classes[] = $prefix . '-' . $option . '-' . $saved[ $option ];
			}
		}

		if ( $classes ) {
			array_unshift( $classes, $prefix );
		}
		return $classes;
	}

	/**
	 * Adds the option classes to the wrapper of a block that has Purplish
	 * options, and loads the effects.
	 *
	 * @param string $block_content Rendered block.
	 * @param array  $block         Parsed block.
	 * @return string
	 */
	public function add_classes( $block_content, $block ) {
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( ! isset( self::blocks()[ $name ] ) || empty( $block['attrs']['purplish'] ) ) {
			return $block_content;
		}

		$classes = self::classes_for( $name, $block['attrs']['purplish'] );
		if ( empty( $classes ) ) {
			return $block_content;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_content );
		if ( ! $tags->next_tag() ) {
			return $block_content;
		}
		foreach ( $classes as $class ) {
			$tags->add_class( $class );
		}

		self::enqueue();
		return $tags->get_updated_html();
	}

	/**
	 * The classic gallery classes from the saved settings, on classic themes.
	 *
	 * @return string[]
	 */
	public static function classic_classes() {
		if ( wp_is_block_theme() || ! is_product() ) {
			return array();
		}

		$saved   = get_option( \Purplish\Admin\Settings::OPTION, array() );
		$classes = array();
		foreach ( array( 'nothumbs', 'rounded', 'tilt' ) as $option ) {
			if ( is_array( $saved ) && isset( $saved[ 'gallery_' . $option ] ) && 'yes' === $saved[ 'gallery_' . $option ] ) {
				$classes[] = 'purplish-classic-gallery-' . $option;
			}
		}
		return $classes;
	}

	/**
	 * Adds the classic gallery classes to the body.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function classic_body_classes( $classes ) {
		return array_merge( $classes, self::classic_classes() );
	}

	/**
	 * Loads the effects on classic product pages with a classic option on.
	 *
	 * @return void
	 */
	public function enqueue_classic() {
		if ( self::classic_classes() ) {
			self::enqueue();
		}
	}

	/**
	 * Loads the effects stylesheet and script, once.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( self::$used ) {
			return;
		}
		self::$used = true;

		$asset = include PURPLISH_DIR . 'build/effects.asset.php';
		wp_enqueue_style( self::HANDLE, PURPLISH_URL . 'build/style-effects.css', array(), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/effects.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
	}
}
