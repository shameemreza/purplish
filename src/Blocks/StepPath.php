<?php
/**
 * The Step Path block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the path's wrapper with the panel options as classes, around the
 * steps as a list. The stylesheet draws the markers and the line, and
 * draws the line on scroll; there is no script.
 *
 * @since 1.2.0
 */
final class StepPath implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'direction' => array( 'across', 'down' ),
		'marker'    => array( 'numbers', 'dots' ),
		'line'      => array( 'plain', 'dashed' ),
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'step-path';
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
			$classes[] = 'purplish-path-' . $option . '-' . ( in_array( $value, $values, true ) ? $value : $values[0] );
		}
		return $classes;
	}

	/**
	 * The wrapper and the steps. Each step becomes a list item, so screen
	 * readers hear how many steps there are.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$items = Markup::list_items( $content );

		return sprintf(
			'<div %1$s><div class="purplish-path-steps"%2$s>%3$s</div></div>',
			get_block_wrapper_attributes( array( 'class' => implode( ' ', self::classes( $attributes ) ) ) ),
			null === $items ? '' : ' role="list"',
			null === $items ? $content : $items
		);
	}
}
