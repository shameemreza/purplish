<?php
/**
 * Purplish options on WooCommerce and core blocks.
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
	 * How many googly eyes a button can have.
	 */
	const EYES = array( 'one', 'two' );

	/**
	 * How a group or columns block comes in as it scrolls into view.
	 */
	const ENTRANCES = array( 'fadeup', 'zoomin' );

	/**
	 * The button looks on Proceed to Checkout and Place Order.
	 */
	const BUY_BUTTON_LOOKS = array(
		'glow'     => true,
		'sweep'    => true,
		'glass'    => true,
		'spectrum' => true,
	);

	/**
	 * Purplish's options for each block, with the values each one accepts.
	 * `true` means an on or off switch.
	 *
	 * `legacy` maps an old switch to the option and value it became.
	 *
	 * `rows` marks a block that prints one wrapper per row, so every row
	 * gets the classes.
	 *
	 * `eyes` names the class of the button that the googly eyes go inside.
	 *
	 * `redrawn` marks a block that WooCommerce draws again in the browser,
	 * keeping the wrapper's class but not its style, so the effect color
	 * goes on a class with a matching rule instead.
	 *
	 * `preview` marks a block whose look also shows in the editor.
	 *
	 * @return array<string, array{prefix: string, options: array<string, mixed>, legacy?: array<string, string[]>, rows?: bool, eyes?: string, redrawn?: bool, preview?: bool}>
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
						'bookmark',
						'depth',
						'overlap',
						'roster',
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
					'tint'     => true,
					'entrance' => self::ENTRANCES,
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
			// The photo is its own frame: core's lightbox measures its parent.
			'core/image'                             => array(
				'prefix'  => 'image',
				'options' => array(
					'hover' => self::HOVER_STYLES,
				),
			),
			'core/button'                            => array(
				'prefix'  => 'button',
				'options' => array(
					'glow'     => true,
					'sweep'    => true,
					'glass'    => true,
					'spectrum' => true,
					'eyes'     => self::EYES,
				),
				'eyes'    => 'wp-block-button__link',
			),
			// Shares the attribute with 1.0's page options, which give no class.
			'woocommerce/add-to-cart-with-options'   => array(
				'prefix'  => 'cart',
				'options' => array(
					'glass'    => true,
					'spectrum' => true,
					'eyes'     => self::EYES,
				),
				'eyes'    => 'single_add_to_cart_button',
			),
			'core/heading'                           => array(
				'prefix'  => 'heading',
				'options' => array(
					'reveal' => array( 'rise', 'focus' ),
				),
			),
			// Each column comes in on its own, so stacked columns on phones do too.
			'core/columns'                           => array(
				'prefix'  => 'columns',
				'options' => array(
					'entrance' => self::ENTRANCES,
				),
			),
			'core/cover'                             => array(
				'prefix'  => 'cover',
				'options' => array(
					'scroll' => array( 'slowzoom', 'parallax', 'pill' ),
				),
			),
			'woocommerce/order-confirmation-status'  => array(
				'prefix'  => 'thanks',
				'options' => array(
					'moment' => true,
				),
				'preview' => true,
			),
			'woocommerce/order-confirmation-summary' => array(
				'prefix'  => 'summary',
				'options' => array(
					'receipt' => true,
				),
				'preview' => true,
			),
			'woocommerce/proceed-to-checkout-block'  => array(
				'prefix'  => 'buybutton',
				'options' => self::BUY_BUTTON_LOOKS,
				'redrawn' => true,
				'preview' => true,
			),
			'woocommerce/checkout-actions-block'     => array(
				'prefix'  => 'buybutton',
				'options' => self::BUY_BUTTON_LOOKS,
				'redrawn' => true,
				'preview' => true,
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
	 * The color classes whose rule is already on the page.
	 *
	 * @var array<string, bool>
	 */
	private static $color_rules = array();

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'register_block_type_args', array( $this, 'add_attribute' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'add_classes' ), 10, 2 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_styles' ) );
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
	 * The options of blocks marked `preview`, for the editor script to work
	 * out the same classes as `classes_for()`.
	 *
	 * @return array<string, array{prefix: string, options: array<string, mixed>, legacy: array<string, string[]>}>
	 */
	public static function preview_map() {
		$map = array();
		foreach ( self::blocks() as $name => $block ) {
			if ( empty( $block['preview'] ) ) {
				continue;
			}
			$map[ $name ] = array(
				'prefix'  => $block['prefix'],
				'options' => $block['options'],
				'legacy'  => isset( $block['legacy'] ) ? $block['legacy'] : array(),
			);
		}
		return $map;
	}

	/**
	 * Loads the effect styles into the editor canvas, so blocks marked
	 * `preview` show their look there. The front scripts stay off.
	 *
	 * @return void
	 */
	public function enqueue_editor_styles() {
		if ( ! is_admin() ) {
			return;
		}

		$asset = include PURPLISH_DIR . 'build/effects.asset.php';
		wp_enqueue_style( self::HANDLE, PURPLISH_URL . 'build/style-effects.css', array(), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
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
		self::enqueue();
		if ( $color && ! empty( self::blocks()[ $name ]['redrawn'] ) ) {
			$classes[] = self::color_class( $color );
			$color     = '';
		}
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

		return self::add_eyes( $tags->get_updated_html(), $name, $block['attrs']['purplish'] );
	}

	/**
	 * The class that carries an effect color on a block WooCommerce draws
	 * again in the browser, with its rule added to the effects stylesheet
	 * once per color.
	 *
	 * @param string $color A hex color from sanitize_hex_color().
	 * @return string
	 */
	private static function color_class( $color ) {
		$class = 'purplish-color-' . strtolower( substr( $color, 1 ) );
		if ( ! isset( self::$color_rules[ $class ] ) ) {
			self::$color_rules[ $class ] = true;
			wp_add_inline_style( self::HANDLE, '.' . $class . '{--purplish-effect:' . $color . ';--purplish-effect-on:' . \Purplish\Color\Contrast::on( $color ) . ';}' );
		}
		return $class;
	}

	/**
	 * Prints the googly eyes as the first thing inside the button, so the
	 * button has its full width before the script loads.
	 *
	 * @param string $html  Rendered block.
	 * @param string $name  Block name.
	 * @param mixed  $saved The block's `purplish` attribute.
	 * @return string
	 */
	private static function add_eyes( $html, $name, $saved ) {
		$blocks = self::blocks();
		if ( empty( $blocks[ $name ]['eyes'] ) || ! is_array( $saved ) || ! isset( $saved['eyes'] ) || ! in_array( $saved['eyes'], self::EYES, true ) ) {
			return $html;
		}

		$eye = '<span class="purplish-eye"></span>';
		return Markup::prepend_inside( $html, $blocks[ $name ]['eyes'], '<span class="purplish-eyes" aria-hidden="true">' . ( 'two' === $saved['eyes'] ? $eye . $eye : $eye ) . '</span>' );
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
