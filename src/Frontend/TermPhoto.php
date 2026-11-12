<?php
/**
 * "Use the category photo" on Cover.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * On a category, tag or other term page whose term has a photo (the
 * `thumbnail_id` term meta WooCommerce uses for product categories and
 * brands), a Cover shows that photo. Core's "Use featured image" shows the
 * first product's photo there instead. Other pages, and terms without a
 * photo, keep the cover's own image.
 */
final class TermPhoto {

	const BLOCK = 'core/cover';

	/**
	 * Hooks in.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_' . self::BLOCK, array( $this, 'render' ), 10, 2 );
	}

	/**
	 * Swaps the cover's image for the term's photo.
	 *
	 * @param string $block_content The Cover HTML.
	 * @param array  $block         The parsed block.
	 * @return string
	 */
	public function render( $block_content, $block ) {
		$saved = isset( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
		if ( ! is_array( $saved ) || ! in_array( 'purplish-cover-termphoto', BlockEffects::classes_for( self::BLOCK, $saved ), true ) ) {
			return $block_content;
		}

		$id = self::photo_id();
		if ( ! $id ) {
			return $block_content;
		}

		return self::swap( $block_content, $id, ! empty( $block['attrs']['useFeaturedImage'] ) );
	}

	/**
	 * The current term's photo.
	 *
	 * @return int 0 off a term page or when the term has none.
	 */
	public static function photo_id() {
		$term = ( is_category() || is_tag() || is_tax() ) ? get_queried_object() : null;
		if ( ! $term instanceof \WP_Term ) {
			return 0;
		}
		$id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		return $id && wp_attachment_is_image( $id ) ? $id : 0;
	}

	/**
	 * Puts a photo in a cover: on its background image, or its parallax or
	 * repeated background. A "Use featured image" cover with no image gets
	 * one. Covers inside the cover's content are left alone.
	 *
	 * @param string $html     The Cover HTML.
	 * @param int    $id       Attachment ID.
	 * @param bool   $featured Whether the cover uses the featured image.
	 * @return string
	 */
	public static function swap( $html, $id, $featured = false ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $src ) {
			return $html;
		}

		$tags = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag() ) {
			if ( $tags->has_class( 'wp-block-cover__inner-container' ) ) {
				break;
			}
			if ( ! $tags->has_class( 'wp-block-cover__image-background' ) ) {
				continue;
			}

			if ( 'IMG' === $tags->get_tag() ) {
				$srcset = wp_get_attachment_image_srcset( $id, 'full' );
				$tags->set_attribute( 'src', $src[0] );
				$tags->set_attribute( 'width', (string) $src[1] );
				$tags->set_attribute( 'height', (string) $src[2] );
				$tags->set_attribute( 'alt', trim( wp_strip_all_tags( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) );
				if ( $srcset ) {
					$tags->set_attribute( 'srcset', $srcset );
					$tags->set_attribute( 'sizes', (string) wp_get_attachment_image_sizes( $id, 'full' ) );
				} else {
					$tags->remove_attribute( 'srcset' );
					$tags->remove_attribute( 'sizes' );
				}
				foreach ( $tags->class_list() as $class ) {
					if ( 'wp-post-image' === $class || preg_match( '/^wp-image-\d+$/', $class ) ) {
						$tags->remove_class( $class );
					}
				}
				$tags->add_class( 'wp-image-' . $id );
			} else {
				$style = (string) $tags->get_attribute( 'style' );
				$tags->set_attribute( 'style', (string) preg_replace( '/background-image:\s*url\([^)]*\)/i', 'background-image:url(' . esc_url( $src[0] ) . ')', $style ) );
			}
			return $tags->get_updated_html();
		}

		if ( ! $featured ) {
			return $html;
		}

		$image = wp_get_attachment_image(
			$id,
			'full',
			false,
			array(
				'class'           => 'wp-block-cover__image-background wp-image-' . $id,
				'data-object-fit' => 'cover',
			)
		);
		return (string) preg_replace( '/<div\b[^>]*\bclass="(?:[^"]*\s)?wp-block-cover(?=[\s"])[^"]*"[^>]*>/', '$0' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $image ), $html, 1 );
	}
}
