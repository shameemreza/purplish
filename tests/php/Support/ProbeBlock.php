<?php
/**
 * A block class for the Registry tests.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Support;

use Purplish\Blocks\Block;

/**
 * The `purplish/probe` block from tests/fixtures/build.
 */
class ProbeBlock implements Block {

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'probe';
	}

	/**
	 * Prints the block's own markup, so the test can tell it ran.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		return '<p class="purplish-probe">Probe</p>';
	}
}
