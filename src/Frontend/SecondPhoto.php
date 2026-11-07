<?php
/**
 * The "Second photo" hover look on product cards.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Adds each product's first gallery photo under its main photo, inside a
 * Product Collection whose photo hover look is "Second photo". The effects
 * stylesheet keeps it hidden until the card is hovered.
 */
final class SecondPhoto {

	const COLLECTION = 'woocommerce/product-collection';

	/**
	 * One entry per Product Collection being rendered, innermost last:
	 * whether that collection shows the second photo.
	 *
	 * @var bool[]
	 */
	private $open = array();

	/**
	 * Hooks in.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_data', array( $this, 'enter' ) );
		add_filter( 'render_block_' . self::COLLECTION, array( $this, 'leave' ) );
		add_filter( 'render_block_woocommerce/product-image', array( $this, 'add_photo' ), 10, 3 );
	}

	/**
	 * Notes whether a Product Collection about to render shows the second
	 * photo, before its product images render.
	 *
	 * @param array $parsed_block The block about to render.
	 * @return array
	 */
	public function enter( $parsed_block ) {
		if ( isset( $parsed_block['blockName'] ) && self::COLLECTION === $parsed_block['blockName'] ) {
			$this->open[] = self::wants( isset( $parsed_block['attrs']['purplish'] ) ? $parsed_block['attrs']['purplish'] : array() );
		}
		return $parsed_block;
	}

	/**
	 * Closes the collection opened in `enter()`.
	 *
	 * @param string $block_content The collection's HTML.
	 * @return string
	 */
	public function leave( $block_content ) {
		array_pop( $this->open );
		return $block_content;
	}

	/**
	 * Whether saved collection options show the second photo, with the
	 * same rules as the class it prints.
	 *
	 * @param mixed $saved The collection's saved Purplish options.
	 * @return bool
	 */
	public static function wants( $saved ) {
		return is_array( $saved ) && in_array( 'purplish-cards-hoverphoto-second', BlockEffects::classes_for( self::COLLECTION, $saved ), true );
	}

	/**
	 * Adds the product's first gallery photo to its Product Image block.
	 * Products without a gallery photo keep the main photo still.
	 *
	 * @param string    $block_content The Product Image block's HTML.
	 * @param array     $block         The parsed block.
	 * @param \WP_Block $instance      The block instance, for its product.
	 * @return string
	 */
	public function add_photo( $block_content, $block, $instance ) {
		if ( ! end( $this->open ) || empty( $instance->context['postId'] ) ) {
			return $block_content;
		}

		$product = wc_get_product( $instance->context['postId'] );
		$gallery = $product ? $product->get_gallery_image_ids() : array();
		if ( empty( $gallery ) ) {
			return $block_content;
		}

		// WooCommerce prints this container after the photo, with or
		// without the product link, so the second photo goes right before it.
		$marker = '<div class="wc-block-components-product-image__inner-container">';
		$at     = strpos( $block_content, $marker );
		if ( false === $at ) {
			return $block_content;
		}

		$photo = wp_get_attachment_image(
			(int) reset( $gallery ),
			'woocommerce_thumbnail',
			false,
			array(
				'class'       => 'purplish-second-photo',
				'loading'     => 'lazy',
				'decoding'    => 'async',
				'alt'         => '',
				'aria-hidden' => 'true',
			)
		);

		return '' === $photo ? $block_content : substr_replace( $block_content, $photo, $at, 0 );
	}
}
