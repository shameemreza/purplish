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
 * so the dots work in stores that never set one up.
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
		'look' => array( 'dots', 'swatches' ),
	);

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
	 * @return array<int, array{name: string, color: string}>
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
				);
			}
		}

		/**
		 * Filters the colors a product card shows as dots.
		 *
		 * @since 1.3.0
		 *
		 * @param array[]     $colors  Each with a `name` (may be empty) and a hex `color`.
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
				);
			}
		}

		self::$colors[ $id ] = $clean;
		return $clean;
	}

	/**
	 * One color per color term, or per distinct page color when the
	 * product has no color attribute, in the order the variations come.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return array<int, array{name: string, color: string}>
	 */
	private static function variation_colors( $product ) {
		$attribute = self::color_attribute( $product );
		$key       = sanitize_title( $attribute );
		$taxonomy  = '' !== $attribute && taxonomy_is_product_attribute( $attribute );
		$groups    = array();

		foreach ( $product->get_visible_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			$picked = $variation->get_attributes();
			$value  = '' !== $key && isset( $picked[ $key ] ) ? (string) $picked[ $key ] : '';
			if ( '' !== $value && isset( $groups[ 'term:' . $value ] ) ) {
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
				);
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
		$product = self::product( $block );
		$colors  = $product ? self::colors( $product ) : array();
		if ( ! $colors || ( 1 === count( $colors ) && empty( $attributes['single'] ) ) ) {
			return '';
		}

		$limit = self::limit( $attributes );
		$dots  = '';
		foreach ( array_slice( $colors, 0, $limit ) as $entry ) {
			$dots .= sprintf(
				'<span class="purplish-color-dots-dot" style="--purplish-dot:%1$s"%2$s></span>',
				esc_attr( $entry['color'] ),
				'' === $entry['name'] ? '' : ' title="' . esc_attr( $entry['name'] ) . '"'
			);
		}
		$rest = count( $colors ) - $limit;
		if ( $rest > 0 ) {
			$dots .= sprintf( '<span class="purplish-color-dots-more">+%d</span>', $rest );
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(
				array(
					'class'      => 'purplish-color-dots-look-' . self::option( $attributes, 'look' ),
					'role'       => 'img',
					'aria-label' => self::label( $colors ),
				)
			),
			$dots
		);
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
