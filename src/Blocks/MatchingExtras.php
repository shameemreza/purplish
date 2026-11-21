<?php
/**
 * The Matching Extras block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use Purplish\Frontend\Extras;
use Purplish\Frontend\PurpleParts;

defined( 'ABSPATH' ) || exit;

/**
 * Matching extras as a block of their own, for an Add to Cart part: other
 * products with a checkbox that go into the cart on the same click. It
 * prints the same list as the Matching extras switch on Add to Cart +
 * Options, so the form posts the same field and the same script adds
 * them. On Purple, PurpleParts hooks it into each Add to Cart part.
 *
 * @since 1.4.0
 */
final class MatchingExtras implements Block {

	/**
	 * Whether the Add to Cart + Options block being rendered has its own
	 * Matching extras switch on.
	 *
	 * @var bool
	 */
	private static $switch_on = false;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'render_block_data', array( self::class, 'note_switch' ) );
	}

	/**
	 * Notes whether Add to Cart + Options has its own switch on, before it
	 * renders its part, so a hooked copy can leave the list to it.
	 *
	 * @param array $block Parsed block.
	 * @return array Unchanged.
	 */
	public static function note_switch( $block ) {
		if ( isset( $block['blockName'] ) && 'woocommerce/add-to-cart-with-options' === $block['blockName'] ) {
			self::$switch_on = isset( $block['attrs']['purplish']['extras'] ) && true === $block['attrs']['purplish']['extras'];
		}
		return $block;
	}

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'matching-extras';
	}

	/**
	 * The product the list is for: WooCommerce's current product inside
	 * Add to Cart + Options, or the block's context.
	 *
	 * @param \WP_Block|null $block Block instance.
	 * @return \WC_Product|null
	 */
	private static function product( $block ) {
		if ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
			return $GLOBALS['product'];
		}
		$id      = $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
		$product = $id && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * The block's front-end markup. A hooked copy leaves the list to Add to
	 * Cart + Options when that block's own switch is on, so a product never
	 * shows two lists.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved inner blocks (none).
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		if ( ! PurpleParts::keeps( $attributes, 'extras' ) || ( ! empty( $attributes[ PurpleParts::MARK ] ) && self::$switch_on ) ) {
			return '';
		}

		$product = self::product( $block );
		$list    = $product ? Extras::list_for( $product, isset( $attributes['options'] ) ? $attributes['options'] : array() ) : '';
		if ( '' === $list ) {
			return '';
		}

		return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes(), $list );
	}
}
