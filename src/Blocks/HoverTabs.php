<?php
/**
 * The Hover Tabs block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the tabs' wrapper with the panel options as classes, around the
 * tabs as a list. The stylesheet stacks the photos in one frame and shows
 * the hovered or focused tab's; there is no script.
 *
 * @since 1.2.0
 */
final class HoverTabs implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'side'   => array( 'end', 'start' ),
		'change' => array( 'fade', 'wipe' ),
		'shape'  => array( 'rounded', 'square' ),
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'hover-tabs';
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
			$classes[] = 'purplish-tabs-' . $option . '-' . ( in_array( $value, $values, true ) ? $value : $values[0] );
		}
		return $classes;
	}

	/**
	 * The wrapper and the tabs. Each tab becomes a list item: the names are
	 * links and the photo is a preview, so a list fits better than ARIA tabs.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$items = Markup::list_items( $content );

		return sprintf(
			'<div %1$s><div class="purplish-tabs-list"%2$s>%3$s</div></div>',
			get_block_wrapper_attributes( array( 'class' => implode( ' ', self::classes( $attributes ) ) ) ),
			null === $items ? '' : ' role="list"',
			null === $items ? $content : $items
		);
	}
}
