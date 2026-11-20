<?php
/**
 * Photo swatches on the Variation Selector: each color option shows the
 * photo of the variation in that color.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;
use Purplish\Blocks\ColorDots;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce's chips draw a swatch for any option that carries a
 * `visual`, the same shape its "Color swatches for attributes" feature
 * gives each term. This adds the product's own variation photos as those
 * visuals, for one attribute, in both places WooCommerce reads them: the
 * chips it prints and the row's list the browser draws them again from.
 */
final class PhotoSwatches {

	const BLOCK = 'woocommerce/add-to-cart-with-options-variation-selector-attribute';

	const CHIPS = 'woocommerce/product-filter-chips';

	const ROW_CLASS = 'wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute';

	const NAME_CLASS = 'wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name';

	const HANDLE = 'purplish-picked';

	/**
	 * Saved Purplish options of the attribute blocks being rendered.
	 *
	 * @var array[]
	 */
	private static $stack = array();

	/**
	 * Whether the chips are being drawn again with the photos.
	 *
	 * @var bool
	 */
	private static $drawing = false;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_data', array( self::class, 'push' ) );
		add_filter( 'render_block_' . self::CHIPS, array( self::class, 'chips' ), 10, 3 );
		// After BlockEffects adds its classes, before SizeGuide wraps the size name.
		add_filter( 'render_block_' . self::BLOCK, array( self::class, 'rows' ), 15 );
	}

	/**
	 * The attribute that shows photos: the picked one when the product has
	 * it, otherwise the color attribute the color dots use.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @param string               $picked  Saved attribute, or '' for Auto.
	 * @return string Attribute name as the product keys it, or ''.
	 */
	public static function attribute( $product, $picked ) {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return '';
		}
		if ( '' !== $picked ) {
			foreach ( array_keys( $product->get_variation_attributes() ) as $name ) {
				if ( sanitize_title( $name ) === sanitize_title( $picked ) ) {
					return (string) $name;
				}
			}
		}
		return ColorDots::color_attribute( $product );
	}

	/**
	 * What each option of the attribute shows: the photo of its first
	 * variation that has one, then the term's own swatch (WooCommerce's
	 * color or image, or the Purplish swatch color). Options with neither
	 * are left out, and show as text chips.
	 *
	 * @param \WC_Product_Variable $product   Variable product.
	 * @param string               $attribute Attribute name, as `attribute()` returns it.
	 * @return array<string, array{type: string, value: string}> Visuals by option value.
	 */
	public static function visuals( $product, $attribute ) {
		if ( ! $product instanceof \WC_Product_Variable || '' === $attribute ) {
			return array();
		}
		$options = $product->get_variation_attributes();
		if ( empty( $options[ $attribute ] ) ) {
			return array();
		}

		$key    = sanitize_title( $attribute );
		$photos = array();
		foreach ( $product->get_visible_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}
			$picked = $variation->get_attributes();
			$value  = isset( $picked[ $key ] ) ? (string) $picked[ $key ] : '';
			// The variation's own photo, even when it's also the main one.
			$image = (int) $variation->get_image_id( 'edit' );
			if ( '' === $value || isset( $photos[ $value ] ) || ! $image ) {
				continue;
			}
			$url = wp_get_attachment_image_url( $image, 'thumbnail' );
			if ( $url ) {
				$photos[ $value ] = array(
					'type'  => 'image',
					'value' => $url,
				);
			}
		}

		$taxonomy = taxonomy_is_product_attribute( $attribute );
		$visuals  = array();
		foreach ( $options[ $attribute ] as $value ) {
			$value = (string) $value;
			if ( isset( $photos[ $value ] ) ) {
				$visuals[ $value ] = $photos[ $value ];
			} elseif ( $taxonomy ) {
				$visual = self::term_visual( $attribute, $value );
				if ( $visual ) {
					$visuals[ $value ] = $visual;
				}
			}
		}

		/**
		 * Filters what each option shows as a photo swatch on the Variation
		 * Selector.
		 *
		 * @since 1.4.0
		 *
		 * @param array[]              $visuals   By option value, each a `type` of `image` (with a URL `value`)
		 *                                        or `color` (with a hex `value`). Missing options show as text.
		 * @param \WC_Product_Variable $product   The product.
		 * @param string               $attribute The attribute, such as `pa_color`.
		 */
		$visuals = apply_filters( 'purplish_photo_swatch_visuals', $visuals, $product, $attribute );

		$clean = array();
		foreach ( is_array( $visuals ) ? $visuals : array() as $value => $visual ) {
			$type = isset( $visual['type'] ) ? $visual['type'] : '';
			$raw  = isset( $visual['value'] ) ? (string) $visual['value'] : '';
			$safe = 'image' === $type ? esc_url_raw( $raw ) : ( 'color' === $type ? (string) sanitize_hex_color( $raw ) : '' );
			if ( '' !== $safe ) {
				$clean[ (string) $value ] = array(
					'type'  => $type,
					'value' => $safe,
				);
			}
		}
		return $clean;
	}

	/**
	 * A term's own swatch: WooCommerce's color or image on a "Color / image"
	 * attribute, or the color saved in the term's `color` meta.
	 *
	 * @param string $taxonomy Attribute taxonomy.
	 * @param string $slug     Term slug.
	 * @return array{type: string, value: string}|null
	 */
	private static function term_visual( $taxonomy, $slug ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term instanceof \WP_Term ) {
			return null;
		}
		if ( class_exists( VisualAttributeTermMeta::class ) && VisualAttributeTermMeta::is_visual_attribute_taxonomy( $taxonomy ) ) {
			$visual = VisualAttributeTermMeta::get_term_visual( $term->term_id );
			if ( isset( $visual['type'], $visual['value'] ) && in_array( $visual['type'], array( 'image', 'color' ), true ) && '' !== $visual['value'] ) {
				return array(
					'type'  => (string) $visual['type'],
					'value' => (string) $visual['value'],
				);
			}
		}
		$color = (string) sanitize_hex_color( (string) get_term_meta( $term->term_id, 'color', true ) );
		return '' === $color ? null : array(
			'type'  => 'color',
			'value' => $color,
		);
	}

	/**
	 * The look's settings from the block's saved options.
	 *
	 * @param array $saved The block's `purplish` attribute.
	 * @return array{on: bool, attribute: string, name: bool}
	 */
	private static function settings( $saved ) {
		return array(
			'on'        => isset( $saved['swatch'] ) && 'photos' === $saved['swatch'],
			'attribute' => isset( $saved['swatchattr'] ) && is_string( $saved['swatchattr'] ) ? $saved['swatchattr'] : '',
			'name'      => ! ( isset( $saved['swatchname'] ) && false === $saved['swatchname'] ),
		);
	}

	/**
	 * Keeps the attribute block's options while its rows render.
	 *
	 * @param array $parsed Parsed block.
	 * @return array
	 */
	public static function push( $parsed ) {
		if ( isset( $parsed['blockName'] ) && self::BLOCK === $parsed['blockName'] ) {
			self::$stack[] = isset( $parsed['attrs']['purplish'] ) && is_array( $parsed['attrs']['purplish'] ) ? $parsed['attrs']['purplish'] : array();
		}
		return $parsed;
	}

	/**
	 * The photo attribute and its visuals for the block being rendered, or
	 * null when the look is off or there's nothing to show.
	 *
	 * @param array $saved The block's `purplish` attribute.
	 * @return array{attribute: string, visuals: array, name: bool}|null
	 */
	private static function current( $saved ) {
		global $product;
		$settings = self::settings( $saved );
		if ( ! $settings['on'] || ! $product instanceof \WC_Product_Variable ) {
			return null;
		}
		$attribute = self::attribute( $product, $settings['attribute'] );
		$visuals   = self::visuals( $product, $attribute );
		return $visuals ? array(
			'attribute' => $attribute,
			'visuals'   => $visuals,
			'name'      => $settings['name'],
		) : null;
	}

	/**
	 * The option list with each option's visual.
	 *
	 * @param array $items   WooCommerce's selectable items or options.
	 * @param array $visuals Visuals by option value.
	 * @return array
	 */
	private static function with_visuals( $items, $visuals ) {
		foreach ( $items as $index => $item ) {
			$value = is_array( $item ) && isset( $item['value'] ) ? (string) $item['value'] : null;
			if ( null !== $value && isset( $visuals[ $value ] ) ) {
				$items[ $index ]['visual'] = $visuals[ $value ];
			}
		}
		return $items;
	}

	/**
	 * Whether WooCommerce's items belong to the attribute. The chips don't
	 * get the attribute's name, but WooCommerce starts each item's ID with
	 * the attribute's field name, like `attribute_pa_color-red`.
	 *
	 * @param array  $items     Selectable items.
	 * @param string $attribute Attribute name.
	 * @return bool
	 */
	private static function is_row_of( $items, $attribute ) {
		$first  = reset( $items );
		$prefix = sanitize_title( wc_variation_attribute_name( $attribute ) ) . '-';
		return is_array( $first ) && isset( $first['id'], $first['value'] ) && sanitize_title( (string) $first['id'] ) === $prefix . sanitize_title( (string) $first['value'] );
	}

	/**
	 * Draws the photo attribute's chips again with the photos, so
	 * WooCommerce prints them as swatches, and marks them.
	 *
	 * @param string    $html     Rendered chips.
	 * @param array     $block    Parsed block.
	 * @param \WP_Block $instance Block instance.
	 * @return string
	 */
	public static function chips( $html, $block, $instance = null ) {
		if ( self::$drawing || ! self::$stack || ! $instance instanceof \WP_Block ) {
			return $html;
		}
		$context = $instance->context;
		$items   = isset( $context['woocommerce/selectableItems'] ) && is_array( $context['woocommerce/selectableItems'] ) ? $context['woocommerce/selectableItems'] : array();
		if ( ! isset( $items['storeNamespace'], $items['items'] ) || 'woocommerce/add-to-cart-with-options' !== $items['storeNamespace'] || ! is_array( $items['items'] ) ) {
			return $html;
		}
		$current = self::current( end( self::$stack ) );
		if ( ! $current || ! self::is_row_of( $items['items'], $current['attribute'] ) ) {
			return $html;
		}

		$items['items']                         = self::with_visuals( $items['items'], $current['visuals'] );
		$context['woocommerce/selectableItems'] = $items;
		self::$drawing                          = true;
		$redrawn                                = ( new \WP_Block( $instance->parsed_block, $context ) )->render();
		self::$drawing                          = false;

		$tags = new \WP_HTML_Tag_Processor( $redrawn );
		if ( ! $tags->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-filter-chips' ) ) ) {
			return $html;
		}
		$tags->add_class( 'purplish-photo-swatches' );
		return $tags->get_updated_html();
	}

	/**
	 * Gives the photo attribute's row the photos in the list the browser
	 * draws from, and the picked name inside the attribute name.
	 *
	 * @param string $html Rendered rows.
	 * @return string
	 */
	public static function rows( $html ) {
		$saved = self::$stack ? array_pop( self::$stack ) : array();
		if ( '' === $html || false === strpos( $html, 'purplish-photo-swatches' ) ) {
			return $html;
		}
		$current = self::current( $saved );
		if ( ! $current ) {
			return $html;
		}

		$field = wc_variation_attribute_name( $current['attribute'] );
		$tags  = new \WP_HTML_Tag_Processor( $html );
		$found = false;
		$label = '';
		while ( $tags->next_tag() ) {
			if ( $tags->has_class( self::ROW_CLASS ) && null !== $tags->get_attribute( 'data-wp-context' ) ) {
				$tags->set_bookmark( 'row' );
			} elseif ( 'INPUT' === $tags->get_tag() && $field === $tags->get_attribute( 'name' ) && $tags->has_bookmark( 'row' ) ) {
				$tags->seek( 'row' );
				$context = json_decode( (string) $tags->get_attribute( 'data-wp-context' ), true );
				if ( is_array( $context ) && isset( $context['variationAttributeOptions'] ) && is_array( $context['variationAttributeOptions'] ) ) {
					$context['variationAttributeOptions'] = self::with_visuals( $context['variationAttributeOptions'], $current['visuals'] );
					foreach ( $context['variationAttributeOptions'] as $option ) {
						if ( isset( $context['selectedValue'], $option['value'], $option['label'] ) && (string) $option['value'] === (string) $context['selectedValue'] ) {
							$label = (string) $option['label'];
						}
					}
					$tags->set_attribute( 'data-wp-context', (string) wp_json_encode( $context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
					$found = true;
				}
				break;
			}
		}
		$tags->release_bookmark( 'row' );
		$html = $tags->get_updated_html();
		if ( ! $found || ! $current['name'] ) {
			return $html;
		}

		return self::add_picked( $html, $field, $label );
	}

	/**
	 * Puts the picked-name slot after the attribute name in the row that
	 * ends with the field's hidden input, and loads the script that fills it.
	 *
	 * @param string $html  Rendered rows.
	 * @param string $field Hidden input name, like `attribute_pa_color`.
	 * @param string $label Name of the option picked on load, or ''.
	 * @return string
	 */
	private static function add_picked( $html, $field, $label ) {
		$at = strpos( $html, '<input type="hidden" name="' . esc_attr( $field ) . '"' );
		if ( false === $at ) {
			return $html;
		}
		// The row runs from the end of the row before it to this input.
		$start = strrpos( substr( $html, 0, $at ), '/></div>' );
		$start = false === $start ? 0 : $start + 8;
		$row   = substr( $html, $start, $at - $start );
		$name  = '#<(span|div|label|p|h[1-6])\b[^>]*class="[^"]*\b' . preg_quote( self::NAME_CLASS, '#' ) . '\b[^"]*"[^>]*>.*?</\1>#s';
		$count = 0;
		$row   = preg_replace_callback(
			$name,
			static function ( $found ) use ( $label ) {
				// Inside the name, so it takes the name's type size and color.
				$end = strrpos( $found[0], '</' );
				return substr( $found[0], 0, $end ) . '<span class="purplish-photo-swatches__picked" aria-hidden="true">' . esc_html( $label ) . '</span>' . substr( $found[0], $end );
			},
			$row,
			1,
			$count
		);
		if ( ! $count ) {
			return $html;
		}

		$asset = include PURPLISH_DIR . 'build/picked.asset.php';
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/picked.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		return substr( $html, 0, $start ) . $row . substr( $html, $at );
	}
}
