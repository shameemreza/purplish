<?php
/**
 * The Purplish panel on the Add to Cart + Options block.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the block's `purplish` attribute on the server, loads the
 * editor panel, and hands the saved options to the page data. The block's
 * markup is never changed.
 */
final class BlockOptions {

	const BLOCK  = 'woocommerce/add-to-cart-with-options';
	const HANDLE = 'purplish-editor';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'register_block_type_args', array( $this, 'add_attribute' ), 10, 2 );
		add_filter( 'render_block_' . self::BLOCK, array( $this, 'read_options' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Lets the block save a `purplish` attribute, so the server accepts it.
	 *
	 * @param array  $args Block type arguments.
	 * @param string $name Block name.
	 * @return array
	 */
	public function add_attribute( $args, $name ) {
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
	 * Hands the block's saved options to the page data. Block themes render
	 * the template before the head, so this runs before the page data is
	 * printed.
	 *
	 * @param string $block_content Rendered block, returned unchanged.
	 * @param array  $block         Parsed block.
	 * @return string
	 */
	public function read_options( $block_content, $block ) {
		if ( ! empty( $block['attrs']['purplish'] ) && is_array( $block['attrs']['purplish'] ) ) {
			PageData::use_block_options( $block['attrs']['purplish'] );
		}
		return $block_content;
	}

	/**
	 * Loads the panel in the block and site editors.
	 *
	 * @return void
	 */
	public function enqueue_editor() {
		$asset = include PURPLISH_DIR . 'build/editor.asset.php';
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/editor.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'purplish' );
	}
}
