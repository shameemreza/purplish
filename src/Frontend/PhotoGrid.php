<?php
/**
 * The Product Gallery's photo grid: hides the slides outside the gallery's
 * starting set on the server, so the grid is drawn right from the first
 * paint.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce prints a slide for every photo, variation photos included,
 * and its script hides the ones outside the current set when the page
 * wakes up. A slider doesn't show the change; a grid would redraw. This
 * marks the same slides `hidden` before the page is sent.
 */
final class PhotoGrid {

	const BLOCK = 'woocommerce/product-gallery';

	const SLIDE = 'wc-block-product-gallery-large-image__wrapper';

	/**
	 * Adds the hook.
	 *
	 * @return void
	 */
	public function register() {
		// After BlockEffects adds the option classes on `render_block`.
		add_filter( 'render_block_' . self::BLOCK, array( self::class, 'render' ) );
	}

	/**
	 * Hides the slides whose photo isn't in the set, on galleries with a
	 * grid layout.
	 *
	 * @param string $html Rendered gallery.
	 * @return string
	 */
	public static function render( $html ) {
		$html = (string) $html;
		if ( false === strpos( $html, 'purplish-gallery-layout-' ) ) {
			return $html;
		}

		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() || ! self::has_layout( (string) $tags->get_attribute( 'class' ) ) ) {
			return $html;
		}
		$context = json_decode( (string) $tags->get_attribute( 'data-wp-context' ), true );
		$set     = is_array( $context ) && ! empty( $context['imageData'] ) && is_array( $context['imageData'] ) ? array_map( 'intval', $context['imageData'] ) : array();
		if ( ! $set ) {
			return $html;
		}

		while ( $tags->next_tag( array( 'class_name' => self::SLIDE ) ) ) {
			$tags->set_bookmark( 'slide' );
			$id = self::photo_id( $tags );
			$tags->seek( 'slide' );
			if ( null !== $id && ! in_array( $id, $set, true ) ) {
				$tags->set_attribute( 'hidden', true );
			}
		}
		$tags->release_bookmark( 'slide' );

		return $tags->get_updated_html();
	}

	/**
	 * Whether a class list names a grid layout.
	 *
	 * @param string $classes Class attribute.
	 * @return bool
	 */
	private static function has_layout( $classes ) {
		return (bool) preg_match( '/(^|\s)purplish-gallery-layout-(grid|mosaic)(\s|$)/', $classes );
	}

	/**
	 * The photo id inside the slide the processor is on, read up to the
	 * next slide.
	 *
	 * @param \WP_HTML_Tag_Processor $tags Processor on a slide.
	 * @return int|null
	 */
	private static function photo_id( $tags ) {
		while ( $tags->next_tag() ) {
			if ( $tags->has_class( self::SLIDE ) ) {
				return null;
			}
			$id = $tags->get_attribute( 'data-image-id' );
			if ( null !== $id && is_numeric( $id ) ) {
				return (int) $id;
			}
		}
		return null;
	}
}
