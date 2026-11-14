<?php
/**
 * The Color dots block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use Purplish\Color\Resolver;

defined( 'ABSPATH' ) || exit;

/**
 * The colors a product comes in, as dots or swatches on a product card.
 * A product with a color attribute gets one dot per color; the dot takes
 * the term's swatch color, then the page color of a variation in that
 * color. Without a color attribute, each distinct page color gets a dot,
 * so the dots work in stores that never set one up. With Swap the photo on,
 * each dot is a button that shows its color's variation photo on the card.
 *
 * @since 1.3.0
 */
final class ColorDots implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'look'   => array( 'dots', 'swatches' ),
		'swap'   => array( 'off', 'hover', 'tap' ),
		'motion' => array( 'fade', 'wipe', 'slide' ),
	);

	/**
	 * The script that swaps the card photo.
	 */
	const SWAP_HANDLE = 'purplish-swap';

	/**
	 * The most dots a card can show before the rest become "+N".
	 */
	const MOST = 12;

	/**
	 * The colors worked out on this request, by product ID.
	 *
	 * @var array<int, array[]>
	 */
	private static $colors = array();

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'rest_api_init', array( self::class, 'add_rest_field' ) );
	}

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'color-dots';
	}

	/**
	 * A saved option, or its default when it's missing or unknown.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $option     Option name.
	 * @return string
	 */
	public static function option( $attributes, $option ) {
		$value = isset( $attributes[ $option ] ) ? $attributes[ $option ] : '';
		return in_array( $value, self::OPTIONS[ $option ], true ) ? $value : self::OPTIONS[ $option ][0];
	}

	/**
	 * How many dots show before "+N", from 1 to 12.
	 *
	 * @param array $attributes Block attributes.
	 * @return int
	 */
	public static function limit( $attributes ) {
		return isset( $attributes['limit'] ) ? max( 1, min( self::MOST, (int) $attributes['limit'] ) ) : 4;
	}

	/**
	 * Drops the colors kept for this request.
	 *
	 * @return void
	 */
	public static function forget() {
		self::$colors = array();
	}

	/**
	 * The variation attribute that holds the color, or an empty string.
	 * A WooCommerce swatch attribute wins; then any attribute whose name or
	 * label says color.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return string Attribute name, such as `pa_color`.
	 */
	public static function color_attribute( $product ) {
		$names = array_keys( $product->get_variation_attributes() );

		$swatches = array();
		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			if ( 'wc-visual' === $taxonomy->attribute_type ) {
				$swatches[] = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
			}
		}
		foreach ( $names as $name ) {
			if ( in_array( $name, $swatches, true ) ) {
				return $name;
			}
		}

		foreach ( $names as $name ) {
			if ( preg_match( '/colou?r/i', $name . ' ' . wc_attribute_label( $name, $product ) ) ) {
				return $name;
			}
		}

		return '';
	}

	/**
	 * The colors a product comes in, each with a name when one is known.
	 * Only variations shoppers can see count, so a hidden or sold-out
	 * color (when the store hides those) gets no dot.
	 *
	 * @param \WC_Product $product The card's product.
	 * @return array<int, array{name: string, color: string, image: int}>
	 */
	public static function colors( $product ) {
		$id = $product->get_id();
		if ( isset( self::$colors[ $id ] ) ) {
			return self::$colors[ $id ];
		}

		$colors = array();
		if ( $product->is_type( 'variable' ) ) {
			$colors = self::variation_colors( $product );
		} else {
			$color = Resolver::color( $product );
			if ( '' !== $color ) {
				$colors[] = array(
					'name'  => '',
					'color' => $color,
					'image' => 0,
				);
			}
		}

		/**
		 * Filters the colors a product card shows as dots.
		 *
		 * @since 1.3.0
		 *
		 * @param array[]     $colors  Each with a `name` (may be empty), a hex `color`
		 *                             and the `image` ID of its photo (0 for the main photo).
		 * @param \WC_Product $product The card's product.
		 */
		$colors = apply_filters( 'purplish_color_dots_colors', $colors, $product );

		$clean = array();
		foreach ( is_array( $colors ) ? $colors : array() as $entry ) {
			$color = isset( $entry['color'] ) ? sanitize_hex_color( (string) $entry['color'] ) : '';
			if ( $color ) {
				$clean[] = array(
					'name'  => isset( $entry['name'] ) ? wp_strip_all_tags( (string) $entry['name'] ) : '',
					'color' => $color,
					'image' => isset( $entry['image'] ) ? absint( $entry['image'] ) : 0,
				);
			}
		}

		self::$colors[ $id ] = $clean;
		return $clean;
	}

	/**
	 * One color per color term, or per distinct page color when the
	 * product has no color attribute, in the order the variations come.
	 * Each color keeps the first variation photo that differs from the main one.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return array<int, array{name: string, color: string, image: int}>
	 */
	private static function variation_colors( $product ) {
		$attribute = self::color_attribute( $product );
		$key       = sanitize_title( $attribute );
		$taxonomy  = '' !== $attribute && taxonomy_is_product_attribute( $attribute );
		$main      = (int) $product->get_image_id();
		$groups    = array();

		foreach ( $product->get_visible_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			$picked = $variation->get_attributes();
			$value  = '' !== $key && isset( $picked[ $key ] ) ? (string) $picked[ $key ] : '';
			$image  = (int) $variation->get_image_id( 'edit' );
			$image  = $image === $main ? 0 : $image;
			if ( '' !== $value && isset( $groups[ 'term:' . $value ] ) ) {
				if ( ! $groups[ 'term:' . $value ]['image'] ) {
					$groups[ 'term:' . $value ]['image'] = $image;
				}
				continue;
			}

			$name  = '';
			$color = '';
			if ( '' !== $value && $taxonomy ) {
				$term = get_term_by( 'slug', $value, $attribute );
				if ( $term instanceof \WP_Term ) {
					$name  = $term->name;
					$color = (string) sanitize_hex_color( (string) get_term_meta( $term->term_id, Resolver::TERM_COLOR_META, true ) );
				}
			} elseif ( '' !== $value ) {
				$name = $value;
			}
			if ( '' === $color ) {
				$color = Resolver::color( $variation );
			}
			if ( '' === $color ) {
				continue;
			}

			// A variation that leaves the color open ("Any") counts by its page color.
			$group = '' === $value ? 'hex:' . strtolower( $color ) : 'term:' . $value;
			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = array(
					'name'  => $name,
					'color' => $color,
					'image' => $image,
				);
			} elseif ( ! $groups[ $group ]['image'] ) {
				$groups[ $group ]['image'] = $image;
			}
		}

		return array_values( $groups );
	}

	/**
	 * The card's product, from the loop context or the single product page.
	 *
	 * @param \WP_Block $block Block instance.
	 * @return \WC_Product|null
	 */
	private static function product( $block ) {
		$product_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		if ( ! $product && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
			$product = $GLOBALS['product'];
		}
		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * What a screen reader hears for the dots.
	 *
	 * @param array[] $colors The product's colors.
	 * @return string
	 */
	public static function label( $colors ) {
		$names = array_filter( wp_list_pluck( $colors, 'name' ), 'strlen' );
		if ( count( $names ) === count( $colors ) ) {
			/* translators: %s: color names, separated by commas. */
			return sprintf( __( 'Colors: %s', 'purplish' ), implode( ', ', $names ) );
		}
		/* translators: %d: how many colors the product comes in. */
		return sprintf( _n( '%d color', '%d colors', count( $colors ), 'purplish' ), count( $colors ) );
	}

	/**
	 * The dots, or nothing when the product has no color to show.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved content (none).
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		// Every card in the grid shares these options, and Load more brings
		// cards in without loading scripts, so a card on a later page that
		// swaps needs the script even when no card on the first page does.
		if ( 'off' !== self::option( $attributes, 'swap' ) ) {
			self::enqueue_swap();
		}

		$product = self::product( $block );
		$colors  = $product ? self::colors( $product ) : array();
		if ( ! $colors || ( 1 === count( $colors ) && empty( $attributes['single'] ) ) ) {
			return '';
		}

		$limit  = self::limit( $attributes );
		$shown  = array_slice( $colors, 0, $limit );
		$swap   = self::swaps( $attributes, $shown ) ? self::option( $attributes, 'swap' ) : 'off';
		$dots   = '';
		$number = 0;
		foreach ( $shown as $entry ) {
			++$number;
			$dots .= 'off' === $swap ? sprintf(
				'<span class="purplish-color-dots-dot" style="--purplish-dot:%1$s"%2$s></span>',
				esc_attr( $entry['color'] ),
				'' === $entry['name'] ? '' : ' title="' . esc_attr( $entry['name'] ) . '"'
			) : self::button( $entry, $number );
		}
		$rest = count( $colors ) - $limit;
		if ( $rest > 0 ) {
			$dots .= sprintf( '<span class="purplish-color-dots-more">+%d</span>', $rest );
		}

		$wrapper = array(
			'class'      => 'purplish-color-dots-look-' . self::option( $attributes, 'look' ),
			'role'       => 'img',
			'aria-label' => self::label( $colors ),
		);
		if ( 'off' !== $swap ) {
			$wrapper['class']      .= ' purplish-color-dots-swap purplish-color-dots-swap-' . $swap;
			$wrapper['role']        = 'group';
			$wrapper['data-motion'] = self::option( $attributes, 'motion' );
			/* translators: The photo's alt text while a color's photo shows. 1: the photo's own alt text, 2: the color's name. */
			$wrapper['data-alt'] = __( '%1$s, %2$s', 'purplish' );
		}

		return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( $wrapper ), $dots );
	}

	/**
	 * Whether the dots swap the photo: the option is on and at least one
	 * shown color has a photo of its own.
	 *
	 * @param array   $attributes Block attributes.
	 * @param array[] $shown      The colors with a dot.
	 * @return bool
	 */
	public static function swaps( $attributes, $shown ) {
		return 'off' !== self::option( $attributes, 'swap' ) && count( array_filter( wp_list_pluck( $shown, 'image' ) ) ) > 0;
	}

	/**
	 * A dot as a button that shows its color's photo. A color without a
	 * photo of its own carries none, and the script shows the main photo.
	 *
	 * @param array $entry  The color.
	 * @param int   $number Its place among the dots, from 1.
	 * @return string
	 */
	private static function button( $entry, $number ) {
		/* translators: %d: the color's place among the dots, from 1. */
		$name  = '' === $entry['name'] ? sprintf( __( 'Color %d', 'purplish' ), $number ) : $entry['name'];
		$photo = '';
		$src   = $entry['image'] ? wp_get_attachment_image_url( $entry['image'], 'large' ) : '';
		if ( $src ) {
			$srcset = wp_get_attachment_image_srcset( $entry['image'], 'large' );
			$photo  = ' data-src="' . esc_url( $src ) . '"' . ( $srcset ? ' data-srcset="' . esc_attr( $srcset ) . '"' : '' );
		}

		return sprintf(
			'<button type="button" class="purplish-color-dots-dot" style="--purplish-dot:%1$s" aria-pressed="false" aria-label="%2$s" title="%2$s"%3$s></button>',
			esc_attr( $entry['color'] ),
			esc_attr( $name ),
			$photo
		);
	}

	/**
	 * Loads the swap script once, after the page.
	 *
	 * @return void
	 */
	private static function enqueue_swap() {
		$asset = include PURPLISH_DIR . 'build/swap.asset.php';
		wp_enqueue_script( self::SWAP_HANDLE, PURPLISH_URL . 'build/swap.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
	}

	/**
	 * Gives the editor each product's colors, so the dots in the editor
	 * match the store.
	 *
	 * @return void
	 */
	public static function add_rest_field() {
		register_rest_field(
			'product',
			'purplish_colors',
			array(
				'get_callback' => array( self::class, 'rest_value' ),
				'schema'       => array(
					'description' => __( 'The colors the product comes in, for the Purplish Color Dots block.', 'purplish' ),
					'type'        => 'array',
					'context'     => array( 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'  => array( 'type' => 'string' ),
							'color' => array( 'type' => 'string' ),
							'image' => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);
	}

	/**
	 * The field's value, for people who can edit the product.
	 *
	 * @param array $post The product as the REST API prepares it.
	 * @return array[]|null
	 */
	public static function rest_value( $post ) {
		$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			return null;
		}
		$product = wc_get_product( $id );
		return $product ? self::colors( $product ) : null;
	}
}
