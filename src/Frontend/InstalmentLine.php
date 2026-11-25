<?php
/**
 * The instalment line under the Product Price block.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\Instalments;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the line after the price when the block's Instalment line option is
 * on, wherever the block sits: the product page, product cards or the
 * Single Product block. Each placement has its own switch.
 *
 * @since 1.4.1
 */
final class InstalmentLine {

	const BLOCK = 'woocommerce/product-price';

	const HANDLE = 'purplish-instalments';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'register_block_type_args', array( self::class, 'add_attribute' ), 10, 2 );
		add_filter( 'render_block_' . self::BLOCK, array( self::class, 'render' ), 10, 3 );
		add_action( 'rest_api_init', array( self::class, 'add_rest_field' ) );
		add_action( 'enqueue_block_assets', array( self::class, 'enqueue_editor_style' ) );
	}

	/**
	 * Loads the line's styles into the editor canvas, for the cards that
	 * show it there.
	 *
	 * @return void
	 */
	public static function enqueue_editor_style() {
		if ( is_admin() ) {
			self::enqueue( false );
		}
	}

	/**
	 * Lets the Product Price block save a `purplish` attribute.
	 *
	 * @param array  $args Block type arguments.
	 * @param string $name Block name.
	 * @return array
	 */
	public static function add_attribute( $args, $name ) {
		if ( self::BLOCK !== $name ) {
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
	 * Puts the line inside the block's wrapper, after the price, and loads
	 * its styles. On the product page of a variable product the line also
	 * carries each variation's text and loads the script that swaps it.
	 *
	 * @param string         $html     Rendered block.
	 * @param array          $block    Parsed block.
	 * @param \WP_Block|null $instance Block instance.
	 * @return string
	 */
	public static function render( $html, $block, $instance = null ) {
		if ( empty( $block['attrs']['purplish']['instalments'] ) || true !== $block['attrs']['purplish']['instalments'] || '' === trim( $html ) ) {
			return $html;
		}

		$id      = $instance instanceof \WP_Block && isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : 0;
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product instanceof \WC_Product && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
			$product = $GLOBALS['product'];
		}
		if ( ! $product instanceof \WC_Product ) {
			return $html;
		}

		$settings   = Instalments::settings();
		$variations = array();
		$page       = is_product() && (int) get_queried_object_id() === $product->get_id();
		if ( $page && $product instanceof \WC_Product_Variable ) {
			$variations = Instalments::variations( $product, $settings );
		}
		$line = Instalments::line( $product, $settings, $variations );
		if ( '' === $line ) {
			return $html;
		}

		self::enqueue( (bool) $variations );
		$end = strrpos( $html, '</div>' );
		return false === $end ? $html . $line : substr_replace( $html, $line, $end, 0 );
	}

	/**
	 * Loads the styles, and the script when the line follows variations.
	 *
	 * @param bool $script Whether the variation script is needed.
	 * @return void
	 */
	public static function enqueue( $script ) {
		$asset = include PURPLISH_DIR . 'build/instalments.asset.php';
		wp_enqueue_style( self::HANDLE, PURPLISH_URL . 'build/style-instalments.css', array(), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
		if ( $script ) {
			wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/instalments.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		}
	}

	/**
	 * Gives the editor each product's line, so cards in the editor show
	 * the line the store prints.
	 *
	 * @return void
	 */
	public static function add_rest_field() {
		register_rest_field(
			'product',
			'purplish_instalments',
			array(
				'get_callback' => array( self::class, 'rest_value' ),
				'schema'       => array(
					'description' => __( 'The product’s Purplish instalment line, as HTML. Empty when it shows none.', 'purplish' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * The field's value, for people who can edit the product.
	 *
	 * @param array $post The product as the REST API prepares it.
	 * @return string|null
	 */
	public static function rest_value( $post ) {
		$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			return null;
		}
		$product = wc_get_product( $id );
		return $product ? Instalments::line( $product, Instalments::settings() ) : null;
	}
}
