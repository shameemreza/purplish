<?php
/**
 * The Runway block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the runway's wrapper with the panel options as classes, a stage
 * holding one look per product photo, and the inner blocks. The products
 * come from the first Product Collection inside, read from the cards it
 * rendered, and their photos print here with srcset, so the page needs no
 * Store API request. The stage stays hidden until the view script turns it
 * on, so without it the collection shows as WooCommerce draws it.
 *
 * @since 1.1.0
 */
final class Runway implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'looks'  => array( 'all', 'featured' ),
		'shape'  => array( 'framed', 'cutout' ),
		'number' => array( 'on', 'off' ),
		'fly'    => array( 'on', 'off' ),
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'runway';
	}

	/**
	 * Each option's value, with the default for anything missing or unknown.
	 *
	 * @param array $attributes Block attributes.
	 * @return array<string, string>
	 */
	public static function options( $attributes ) {
		$options = array();
		foreach ( self::OPTIONS as $option => $values ) {
			$value              = isset( $attributes[ $option ] ) ? $attributes[ $option ] : '';
			$options[ $option ] = in_array( $value, $values, true ) ? $value : $values[0];
		}
		return $options;
	}

	/**
	 * The option classes.
	 *
	 * @param array $attributes Block attributes.
	 * @return string[] Class names.
	 */
	public static function classes( $attributes ) {
		$classes = array();
		foreach ( self::options( $attributes ) as $option => $value ) {
			$classes[] = 'purplish-runway-' . $option . '-' . $value;
		}
		return $classes;
	}

	/**
	 * The IDs of the products the first Product Collection rendered, in
	 * card order. WordPress prints `post-<ID>` on each card.
	 *
	 * @param string $html Rendered inner blocks.
	 * @return int[] Product IDs.
	 */
	public static function product_ids( $html ) {
		$ids       = array();
		$tags      = new \WP_HTML_Tag_Processor( $html );
		$collected = false;
		while ( $tags->next_tag() ) {
			if ( $tags->has_class( 'wp-block-woocommerce-product-collection' ) ) {
				if ( $collected ) {
					break;
				}
				$collected = true;
				continue;
			}
			if ( ! $collected || 'LI' !== $tags->get_tag() || ! $tags->has_class( 'wc-block-product' ) ) {
				continue;
			}
			foreach ( $tags->class_list() as $class_name ) {
				if ( 1 === preg_match( '/^post-(\d+)$/', $class_name, $match ) ) {
					$ids[] = (int) $match[1];
					break;
				}
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * One look per product photo: the featured image, then the gallery
	 * images unless only the featured photo is wanted. Products without a
	 * photo give no look.
	 *
	 * @param int[]  $ids   Product IDs.
	 * @param string $looks all or featured.
	 * @return array<int, array{product: \WC_Product, image: int}> Looks, in order.
	 */
	public static function looks( $ids, $looks ) {
		$found = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$images = array( (int) $product->get_image_id() );
			if ( 'featured' !== $looks ) {
				$images = array_merge( $images, array_map( 'intval', $product->get_gallery_image_ids() ) );
			}
			foreach ( array_unique( array_filter( $images ) ) as $image ) {
				if ( wp_attachment_is_image( $image ) ) {
					$found[] = array(
						'product' => $product,
						'image'   => $image,
					);
				}
			}
		}
		return $found;
	}

	/**
	 * The wrapper, the stage with its looks, and the inner blocks. With
	 * fewer than two looks there's no line to walk, so only the inner
	 * blocks print inside the wrapper.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$options = self::options( $attributes );
		$looks   = self::looks( self::product_ids( $content ), $options['looks'] );
		$inner   = '<div class="purplish-runway-content">' . $content . '</div>';

		if ( count( $looks ) < 2 ) {
			return sprintf(
				'<div %1$s>%2$s</div>',
				get_block_wrapper_attributes( array( 'class' => implode( ' ', self::classes( $attributes ) ) ) ),
				$inner
			);
		}

		$line = '';
		foreach ( $looks as $index => $look ) {
			$line .= sprintf(
				'<div class="purplish-runway-look" data-product="%1$d" data-url="%2$s" data-name="%3$s">%4$s</div>',
				$look['product']->get_id(),
				esc_url( $look['product']->get_permalink() ),
				esc_attr( wp_strip_all_tags( $look['product']->get_name() ) ),
				wp_get_attachment_image(
					$look['image'],
					'large',
					false,
					array(
						'alt'       => '',
						'class'     => 'purplish-runway-photo',
						'sizes'     => '(max-width: 820px) 80vw, 45vw',
						'loading'   => 0 === $index ? 'eager' : 'lazy',
						'decoding'  => 'async',
						'draggable' => 'false',
					)
				)
			);
		}

		$arrow = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="%s"/></svg>';
		$back  = sprintf( $arrow, 'M15 5l-7 7 7 7' );
		$on    = sprintf( $arrow, 'M9 5l7 7-7 7' );

		$wrapper = get_block_wrapper_attributes(
			array(
				'class'           => implode( ' ', self::classes( $attributes ) ),
				/* translators: 1: look number, 2: number of looks. */
				'data-look-label' => __( 'Runway, look %1$s of %2$s', 'purplish' ),
				/* translators: %s: product name. */
				'data-shop-label' => __( 'Shop the look: %s', 'purplish' ),
			)
		);

		return sprintf(
			'<div %1$s><div class="purplish-runway-stage" tabindex="0" role="group" aria-label="%2$s" hidden><div class="purplish-runway-line" aria-hidden="true">%3$s</div><div class="purplish-runway-tag"><p class="purplish-runway-number" aria-hidden="true"><span>%4$s</span> <span class="purplish-runway-window"><span class="purplish-runway-roll"></span></span></p><div class="purplish-runway-nav"><button type="button" class="purplish-runway-prev" aria-label="%5$s">%6$s</button><button type="button" class="purplish-runway-next" aria-label="%7$s">%8$s</button></div><a class="purplish-runway-shop" href="%9$s">%10$s</a></div><p class="purplish-runway-status" aria-live="polite"></p></div>%11$s</div>',
			$wrapper,
			esc_attr__( 'Runway', 'purplish' ),
			$line,
			esc_html__( 'Look', 'purplish' ),
			esc_attr__( 'Previous look', 'purplish' ),
			is_rtl() ? $on : $back,
			esc_attr__( 'Next look', 'purplish' ),
			is_rtl() ? $back : $on,
			esc_url( $looks[0]['product']->get_permalink() ),
			esc_html__( 'Shop the look', 'purplish' ),
			$inner
		);
	}
}
