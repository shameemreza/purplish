<?php
/**
 * The Hanging Rail block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the rail's wrapper with the panel options as classes, around the
 * inner blocks. The stylesheet draws the rope and clips on the first
 * Product Collection inside, and the view script moves it.
 *
 * @since 1.1.0
 */
final class HangingRail implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'clip'   => array( 'peg', 'binder', 'tape' ),
		'sag'    => array( 'little', 'more' ),
		'length' => array( 'medium', 'short', 'long' ),
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'hanging-rail';
	}

	/**
	 * The option classes, with the default for anything missing or unknown.
	 *
	 * @param array $attributes Block attributes.
	 * @return string[] Class names.
	 */
	public static function classes( $attributes ) {
		$classes = array();
		foreach ( self::OPTIONS as $option => $values ) {
			$value     = isset( $attributes[ $option ] ) ? $attributes[ $option ] : '';
			$classes[] = 'purplish-rail-' . $option . '-' . ( in_array( $value, $values, true ) ? $value : $values[0] );
		}
		return $classes;
	}

	/**
	 * The wrapper and a stage that holds the inner blocks, so the section
	 * can stick while the page scrolls through it.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		return sprintf(
			'<div %1$s><div class="purplish-rail-stage is-layout-constrained has-global-padding">%2$s</div></div>',
			get_block_wrapper_attributes( array( 'class' => implode( ' ', self::classes( $attributes ) ) ) ),
			$content
		);
	}
}
