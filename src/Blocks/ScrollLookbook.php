<?php
/**
 * The Scroll Lookbook block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use Purplish\Frontend\PageData;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the lookbook's wrapper with the panel options as classes and the
 * front-end config, and a stage around the inner blocks. Cover videos
 * inside never play or loop on their own: the view script plays the
 * opening shot once and the scroll moves the rest. Each card of the first
 * Product Collection gets its product's Purplish colors. With no Cover the
 * inner blocks print unchanged.
 *
 * @since 1.1.0
 */
final class ScrollLookbook implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'chapters' => array( 'on', 'off' ),
		'timecode' => array( 'off', 'on' ),
		'length'   => array( 'medium', 'short', 'long' ),
	);

	/**
	 * The front-end numbers the panel doesn't set.
	 *
	 * @var array<string, float>
	 */
	const CONFIG = array(
		'smoothing' => 9.0,
		'hold'      => 5.0,
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'scroll-lookbook';
	}

	/**
	 * Each option's value, with the default for anything missing or unknown.
	 *
	 * @param array $attributes Block attributes.
	 * @return array<string, string>
	 */
	public static function options( $attributes ) {
		$options = array();
		foreach ( self::OPTIONS as $option => $values ) {
			$value              = isset( $attributes[ $option ] ) ? $attributes[ $option ] : '';
			$options[ $option ] = in_array( $value, $values, true ) ? $value : $values[0];
		}
		return $options;
	}

	/**
	 * The option classes.
	 *
	 * @param array $attributes Block attributes.
	 * @return string[] Class names.
	 */
	public static function classes( $attributes ) {
		$classes = array();
		foreach ( self::options( $attributes ) as $option => $value ) {
			$classes[] = 'purplish-lookbook-' . $option . '-' . $value;
		}
		return $classes;
	}

	/**
	 * The front-end config, after the `purplish_scroll_lookbook_config`
	 * filter. Unknown keys are dropped and each number is kept in range.
	 *
	 * @param array $attributes Block attributes.
	 * @return array{smoothing: float, hold: float}
	 */
	public static function config( $attributes ) {
		/**
		 * Filters the Scroll Lookbook numbers that aren't in the block panel.
		 *
		 * @since 1.1.0
		 *
		 * @param array $config     `smoothing` (how fast the film catches up with the scroll, 1 to 30, default 9) and `hold` (the longest the opening video plays on load, in seconds, 0 to 30, default 5).
		 * @param array $attributes Block attributes.
		 */
		$config = apply_filters( 'purplish_scroll_lookbook_config', self::CONFIG, $attributes );
		$config = is_array( $config ) ? $config : array();

		$smoothing = isset( $config['smoothing'] ) && is_numeric( $config['smoothing'] ) ? (float) $config['smoothing'] : self::CONFIG['smoothing'];
		$hold      = isset( $config['hold'] ) && is_numeric( $config['hold'] ) ? (float) $config['hold'] : self::CONFIG['hold'];

		return array(
			'smoothing' => min( 30.0, max( 1.0, $smoothing ) ),
			'hold'      => min( 30.0, max( 0.0, $hold ) ),
		);
	}

	/**
	 * Whether the inner blocks hold a Cover block.
	 *
	 * @param array|null $inner Parsed inner blocks.
	 * @return bool
	 */
	public static function has_cover( $inner ) {
		foreach ( (array) $inner as $block ) {
			if ( isset( $block['blockName'] ) && 'core/cover' === $block['blockName'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The rendered inner blocks with every Cover video stopped from playing
	 * or looping on its own, and each card of the first Product Collection
	 * carrying its product's Purplish colors.
	 *
	 * @param string $html Rendered inner blocks.
	 * @return string
	 */
	public static function prepare( $html ) {
		$tags        = new \WP_HTML_Tag_Processor( $html );
		$collections = 0;
		while ( $tags->next_tag() ) {
			if ( 'VIDEO' === $tags->get_tag() && $tags->has_class( 'wp-block-cover__video-background' ) ) {
				$tags->remove_attribute( 'autoplay' );
				$tags->remove_attribute( 'loop' );
				continue;
			}
			if ( $tags->has_class( 'wp-block-woocommerce-product-collection' ) ) {
				++$collections;
				continue;
			}
			if ( 1 !== $collections || 'LI' !== $tags->get_tag() || ! $tags->has_class( 'wc-block-product' ) ) {
				continue;
			}
			$set = self::card_colors( $tags->class_list() );
			if ( null === $set ) {
				continue;
			}
			$style = trim( (string) $tags->get_attribute( 'style' ) );
			$tags->add_class( 'purplish-lookbook-accent' );
			$tags->set_attribute(
				'style',
				( '' === $style ? '' : rtrim( $style, ';' ) . ';' ) . sprintf(
					'--purplish-accent:%1$s;--purplish-on:%2$s;--purplish-ink:%3$s',
					$set['color'],
					$set['on'],
					$set['ink']
				)
			);
		}
		return $tags->get_updated_html();
	}

	/**
	 * The Purplish colors for the product a card shows, read from the
	 * `post-<ID>` class WordPress prints on it. A variable product uses the
	 * color its default choices point to.
	 *
	 * @param iterable $class_list The card's classes.
	 * @return array{color: string, on: string, ink: string, tint: string}|null Null for no color.
	 */
	public static function card_colors( $class_list ) {
		foreach ( $class_list as $class_name ) {
			if ( 1 !== preg_match( '/^post-(\d+)$/', $class_name, $match ) ) {
				continue;
			}
			$product = wc_get_product( (int) $match[1] );
			if ( ! $product ) {
				return null;
			}
			$data = PageData::for_product( $product, array() );
			return is_array( $data ) && ! empty( $data['default'] ) ? $data['default'] : null;
		}
		return null;
	}

	/**
	 * The wrapper and the stage around the inner blocks. Without a Cover
	 * there's nothing to play, so the inner blocks print unchanged.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$inner = $block instanceof \WP_Block ? $block->parsed_block['innerBlocks'] : array();
		if ( ! self::has_cover( $inner ) ) {
			return sprintf(
				'<div %1$s>%2$s</div>',
				get_block_wrapper_attributes( array( 'class' => implode( ' ', self::classes( $attributes ) ) ) ),
				$content
			);
		}

		return sprintf(
			'<div %1$s><div class="purplish-lookbook-stage">%2$s</div></div>',
			get_block_wrapper_attributes(
				array(
					'class'       => implode( ' ', self::classes( $attributes ) ),
					'data-config' => wp_json_encode( self::config( $attributes ) ),
				)
			),
			self::prepare( $content )
		);
	}
}
