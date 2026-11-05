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
	 * The gallery's hover styles, on block and classic themes.
	 */
	const HOVER_STYLES = array( 'tilt', 'zoom', 'lift', 'parallax', 'glare', 'loupe', 'slowzoom' );

	/**
	 * Purplish's options for each block, with the values each one accepts.
	 * `true` means an on or off switch.
	 *
	 * `legacy` maps an old switch to the option and value it became.
	 *
	 * `rows` marks a block that prints one wrapper per row, so every row
	 * gets the classes.
	 *
	 * @return array<string, array{prefix: string, options: array<string, mixed>, legacy?: array<string, string[]>, rows?: bool}>
	 */
	public static function blocks() {
		return array(
			'woocommerce/product-gallery'            => array(
				'prefix'  => 'gallery',
				'options' => array(
					'nothumbs'   => true,
					'rounded'    => true,
					'arrows'     => true,
					'hover'      => self::HOVER_STYLES,
					'fullscreen' => array( 'snap', 'fade', 'zoom', 'filmstrip', 'cinema' ),
					'slider'     => array( 'cube', 'circle', 'reel', 'tunnel' ),
				),
				// Switches saved before task 23, read as the style they became.
				'legacy'  => array(
					'tilt'     => array( 'hover', 'tilt' ),
					'lightbox' => array( 'fullscreen', 'snap' ),
				),
			),
			'woocommerce/product-collection'         => array(
				'prefix'  => 'cards',
				'options' => array(
					'overlay' => true,
					'motion'  => true,
					'hover'   => array(
						'magnetic',
						'spotlight',
						'reveal',
						'deck',
						'clothesline',
						'fan',
						'arc',
						'stage',
						'expand',
						'grow',
						'tiltrow',
						'parallax',
						'zoomin',
						'folder',
					),
				),
			),
			'core/accordion'                         => array(
				'prefix'  => 'accordion',
				'options' => array(
					'soft' => true,
				),
			),
			'core/group'                             => array(
				'prefix'  => 'group',
				'options' => array(
					'tint' => true,
				),
			),
			// WooCommerce grays out and hides invalid chips itself.
			'woocommerce/add-to-cart-with-options-variation-selector-attribute' => array(
				'prefix'  => 'chips',
				'options' => array(
					'soldout' => array( 'cross', 'blur' ),
				),
				'rows'    => true,
			),
			'woocommerce/product-sale-badge'         => array(
				'prefix'  => 'badge',
				'options' => array(
					'shape'  => array( 'pill', 'circle', 'tag' ),
					'corner' => array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ),
				),
			),
			'woocommerce/product-gallery-thumbnails' => array(
				'prefix'  => 'thumbs',
				'options' => array(
					'shape' => array( 'rounded', 'circle' ),
				),
			),
			// Same class as the core accordion, so both share one soft style.
			'woocommerce/accordion-group'            => array(
				'prefix'  => 'accordion',
				'options' => array(
					'soft' => true,
				),
			),
			'core/details'                           => array(
				'prefix'  => 'details',
				'options' => array(
					'soft' => true,
				),
			),
			'woocommerce/product-reviews'            => array(
				'prefix'  => 'reviews',
				'options' => array(
					'style' => array( 'cards', 'bubbles' ),
				),
			),
			'woocommerce/product-specifications'     => array(
				'prefix'  => 'specs',
				'options' => array(
					'style' => array( 'striped', 'cards' ),
				),
			),
			// On the parent, the only block that holds both kinds of chips.
			'woocommerce/product-filters'            => array(
				'prefix'  => 'filters',
				'options' => array(
					'chips' => true,
				),
			),
			'woocommerce/mini-cart'                  => array(
				'prefix'  => 'minicart',
				'options' => array(
					'pop' => true,
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
	 * Turns off WooCommerce's hover zoom on classic galleries when a hover
	 * style is on: the two would move the same photo at once.
	 *
	 * @param bool $enabled Whether the zoom is on.
	 * @return bool
	 */
	public function classic_zoom( $enabled ) {
		foreach ( self::classic_classes() as $class ) {
			if ( 0 === strpos( $class, 'purplish-classic-gallery-hover-' ) ) {
				return false;
			}
		}
		return $enabled;
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

		$legacy = isset( $blocks[ $name ]['legacy'] ) ? $blocks[ $name ]['legacy'] : array();
		foreach ( $legacy as $old => $now ) {
			if ( isset( $saved[ $old ] ) && true === $saved[ $old ] && ! isset( $saved[ $now[0] ] ) ) {
				$saved[ $now[0] ] = $now[1];
			}
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

		/**
		 * Filters the classes Purplish adds to a block's wrapper for its
		 * saved options.
		 *
		 * @since 1.1.0
		 *
		 * @param string[] $classes Class names.
		 * @param string   $name    Block name, such as `woocommerce/product-collection`.
		 * @param array    $saved   The block's saved Purplish options.
		 */
		$classes = apply_filters( 'purplish_block_classes', self::classes_for( $name, $block['attrs']['purplish'] ), $name, $block['attrs']['purplish'] );
		$classes = is_array( $classes ) ? array_filter( array_map( 'sanitize_html_class', array_filter( $classes, 'is_string' ) ) ) : array();
		if ( empty( $classes ) ) {
			return $block_content;
		}

		$rows  = ! empty( self::blocks()[ $name ]['rows'] );
		$query = $rows ? array( 'class_name' => 'wp-block-' . str_replace( '/', '-', $name ) ) : null;
		$tags  = new \WP_HTML_Tag_Processor( $block_content );
		if ( ! $tags->next_tag( $query ) ) {
			return $block_content;
		}

		// The collection's effect color, and readable text on it.
		$color = isset( $block['attrs']['purplish']['color'] ) ? sanitize_hex_color( (string) $block['attrs']['purplish']['color'] ) : '';
		do {
			foreach ( $classes as $class ) {
				$tags->add_class( $class );
			}
			if ( $color ) {
				$style = trim( (string) $tags->get_attribute( 'style' ) );
				$style = ( '' === $style ? '' : rtrim( $style, ';' ) . ';' ) . '--purplish-effect:' . $color . ';--purplish-effect-on:' . \Purplish\Color\Contrast::on( $color ) . ';';
				$tags->set_attribute( 'style', $style );
			}
		} while ( $rows && $tags->next_tag( $query ) );

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
		foreach ( array( 'nothumbs', 'rounded' ) as $option ) {
			if ( is_array( $saved ) && isset( $saved[ 'gallery_' . $option ] ) && 'yes' === $saved[ 'gallery_' . $option ] ) {
				$classes[] = 'purplish-classic-gallery-' . $option;
			}
		}
		$hover = self::classic_hover( $saved );
		if ( '' !== $hover ) {
			$classes[] = 'purplish-classic-gallery-hover-' . $hover;
		}
		return $classes;
	}

	/**
	 * The classic hover style from the saved settings, or '' for none. A
	 * `gallery_tilt` saved before task 23 reads as the tilt until
	 * `gallery_hover` is saved.
	 *
	 * @param mixed $saved The saved settings.
	 * @return string
	 */
	public static function classic_hover( $saved ) {
		if ( ! is_array( $saved ) ) {
			return '';
		}
		if ( isset( $saved['gallery_hover'] ) ) {
			return in_array( $saved['gallery_hover'], self::HOVER_STYLES, true ) ? $saved['gallery_hover'] : '';
		}
		return isset( $saved['gallery_tilt'] ) && 'yes' === $saved['gallery_tilt'] ? 'tilt' : '';
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
		wp_set_script_translations( self::HANDLE, 'purplish' );
	}
}
