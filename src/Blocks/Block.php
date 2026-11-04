<?php
/**
 * What every Purplish block class provides.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * A block's folder name and its server render. The metadata comes from the
 * block's block.json, through the blocks manifest.
 *
 * @since 1.1.0
 */
interface Block {

	/**
	 * The block's folder in build/blocks, which is also the part of its name
	 * after `purplish/`.
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * The block's front-end markup.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block );
}
