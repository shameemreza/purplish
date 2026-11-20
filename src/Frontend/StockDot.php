<?php
/**
 * The Stock Indicator's dot: marks low stock, which WooCommerce prints with
 * the same class as in stock, and loads the script that keeps the dot in
 * step with the variation a shopper picks.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce's editor view marks low stock with its own class, its front
 * end doesn't. This adds `purplish-stock-low` on the front end by the same
 * rule, so the dot takes the low stock color in both places.
 */
final class StockDot {

	const BLOCK = 'woocommerce/product-stock-indicator';

	const HANDLE = 'purplish-stock';

	/**
	 * Adds the hook.
	 *
	 * @return void
	 */
	public function register() {
		// After BlockEffects adds the option classes on `render_block`.
		add_filter( 'render_block_' . self::BLOCK, array( self::class, 'render' ), 10, 3 );
	}

	/**
	 * Whether a product's stock is low, as WooCommerce's editor view tells:
	 * it manages stock, has some left, at or under its low stock amount,
	 * and the store shows stock amounts.
	 *
	 * @param \WC_Product|null|false $product Product.
	 * @return bool
	 */
	public static function is_low( $product ) {
		if ( ! $product instanceof \WC_Product || ! $product->managing_stock() || ! $product->is_in_stock() ) {
			return false;
		}
		if ( 'no_amount' === get_option( 'woocommerce_stock_format' ) ) {
			return false;
		}
		$stock = $product->get_stock_quantity();
		return null !== $stock && $stock > 0 && $stock <= wc_get_low_stock_amount( $product );
	}

	/**
	 * Marks low stock on an indicator with the dot, and loads the script
	 * when WooCommerce updates the indicator's text for each variation.
	 *
	 * @param string         $html     Rendered block.
	 * @param array          $block    Parsed block.
	 * @param \WP_Block|null $instance Block instance.
	 * @return string
	 */
	public static function render( $html, $block, $instance = null ) {
		if ( empty( $block['attrs']['purplish']['dot'] ) || true !== $block['attrs']['purplish']['dot'] || '' === $html ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag( array( 'class_name' => 'purplish-stock-dot' ) ) ) {
			return $html;
		}

		$id      = $instance instanceof \WP_Block && isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : 0;
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product instanceof \WC_Product && isset( $GLOBALS['product'] ) ) {
			$product = $GLOBALS['product'];
		}
		if ( self::is_low( $product ) ) {
			$tags->add_class( 'purplish-stock-low' );
		}

		if ( null !== $tags->get_attribute( 'data-wp-text' ) ) {
			$asset = include PURPLISH_DIR . 'build/stock.asset.php';
			wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/stock.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		}
		return $tags->get_updated_html();
	}
}
