<?php
/**
 * The Story Deck block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the deck's wrapper with the panel options as classes, around the
 * stories, plus the previous and next buttons and a status line for screen
 * readers. The buttons stay hidden until the view script turns the stories
 * into a deck, so without it the stories are a plain list.
 *
 * @since 1.1.0
 */
final class StoryDeck implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'side'  => array( 'left', 'right' ),
		'shape' => array( 'rounded', 'square' ),
		'fan'   => array( 'fanned', 'flat' ),
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'story-deck';
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
			$classes[] = 'purplish-deck-' . $option . '-' . ( in_array( $value, $values, true ) ? $value : $values[0] );
		}
		return $classes;
	}

	/**
	 * The wrapper, the stories, the buttons and the status line.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$wrapper = get_block_wrapper_attributes(
			array(
				'class'                => implode( ' ', self::classes( $attributes ) ),
				'role'                 => 'region',
				'aria-label'           => __( 'Customer stories', 'purplish' ),
				/* translators: 1: story number, 2: number of stories. */
				'data-story-label'     => __( 'Story %1$s of %2$s', 'purplish' ),
				'data-roledescription' => __( 'carousel', 'purplish' ),
			)
		);

		$arrow = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="%s"/></svg>';

		return sprintf(
			'<div %1$s><div class="purplish-deck-stories">%2$s</div><div class="purplish-deck-nav" hidden><button type="button" class="purplish-deck-prev" aria-label="%3$s">%4$s</button><span class="purplish-deck-count" aria-hidden="true"></span><button type="button" class="purplish-deck-next" aria-label="%5$s">%6$s</button></div><p class="purplish-deck-status" aria-live="polite"></p></div>',
			$wrapper,
			$content,
			esc_attr__( 'Previous story', 'purplish' ),
			is_rtl() ? sprintf( $arrow, 'M9 5l7 7-7 7' ) : sprintf( $arrow, 'M15 5l-7 7 7 7' ),
			esc_attr__( 'Next story', 'purplish' ),
			is_rtl() ? sprintf( $arrow, 'M15 5l-7 7 7 7' ) : sprintf( $arrow, 'M9 5l7 7-7 7' )
		);
	}
}
