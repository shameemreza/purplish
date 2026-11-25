<?php
/**
 * The Product Gallery's full-screen view: its photos wait until the view
 * opens.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce prints the full-screen view as a closed dialog with every
 * photo at full size, and none of them lazy, so a product page downloads
 * each full-size photo before anyone opens the view. A closed dialog isn't
 * drawn, so lazy photos inside it wait until it opens.
 *
 * @since 1.4.1
 */
final class FullScreenPhotos {

	const DIALOG = 'wc-block-product-gallery-dialog';

	/**
	 * Adds the hook.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_' . PhotoGrid::BLOCK, array( self::class, 'render' ) );
	}

	/**
	 * Marks the photos in the full-screen view lazy.
	 *
	 * @param string $html Rendered gallery.
	 * @return string
	 */
	public static function render( $html ) {
		$html = (string) $html;
		if ( false === strpos( $html, self::DIALOG ) ) {
			return $html;
		}

		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag(
			array(
				'tag_name'   => 'DIALOG',
				'class_name' => self::DIALOG,
			)
		) ) {
			return $html;
		}
		while ( $tags->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag = $tags->get_tag();
			if ( 'DIALOG' === $tag && $tags->is_tag_closer() ) {
				break;
			}
			if ( 'IMG' === $tag ) {
				$tags->set_attribute( 'loading', 'lazy' );
			}
		}

		return $tags->get_updated_html();
	}
}
