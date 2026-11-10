<?php
/**
 * The Look block, only inside Showfloor.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Holds one look's image, name, line, products, marker spots and face
 * crop. It prints nothing: Showfloor reads its attributes and prints every
 * look together.
 *
 * @since 1.3.0
 */
final class ShowfloorLook implements Block {

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'showfloor-look';
	}

	/**
	 * Nothing, since Showfloor prints the look.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		return '';
	}
}
