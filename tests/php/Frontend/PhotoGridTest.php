<?php
/**
 * Tests for the Product Gallery's photo grid.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\PhotoGrid;
use WP_UnitTestCase;

/**
 * Each layout prints its class, and the server hides the slides outside
 * the gallery's starting set, the way WooCommerce's script will, so a grid
 * is drawn right from the first paint.
 */
class PhotoGridTest extends WP_UnitTestCase {

	/**
	 * A gallery with three slides, two of them in the set.
	 *
	 * @param string $classes Extra wrapper classes.
	 * @param string $context The data-wp-context value, or '' for none.
	 * @return string
	 */
	private function gallery( $classes, $context = '{"imageData":[11,12],"selectedImageId":11}' ) {
		$data   = '' === $context ? '' : ' data-wp-context="' . esc_attr( $context ) . '"';
		$slides = '';
		foreach ( array( 11, 12, 13 ) as $id ) {
			$slides .= '<li class="wc-block-product-gallery-large-image__wrapper"><div class="wc-block-components-product-image"><img data-image-id="' . $id . '" src="p' . $id . '.jpg" alt=""></div></li>';
		}
		return '<div class="wp-block-woocommerce-product-gallery ' . $classes . '"' . $data . '><div class="wc-block-product-gallery-large-image"><ul class="wc-block-product-gallery-large-image__container">' . $slides . '</ul></div><div class="wc-block-product-gallery-thumbnails"><img data-image-id="13" alt=""></div></div>';
	}

	/**
	 * The ids of the slides that print `hidden`.
	 *
	 * @param string $html Gallery markup.
	 * @return int[]
	 */
	private function hidden_ids( $html ) {
		preg_match_all( '/<li( hidden)? class="wc-block-product-gallery-large-image__wrapper">(?:(?!<\/li>).)*?data-image-id="(\d+)"/', $html, $found, PREG_SET_ORDER );
		$ids = array();
		foreach ( $found as $slide ) {
			if ( ' hidden' === $slide[1] ) {
				$ids[] = (int) $slide[2];
			}
		}
		return $ids;
	}

	/**
	 * Each layout becomes its class; anything else prints nothing.
	 */
	public function test_layout_classes() {
		foreach ( array( 'grid', 'mosaic' ) as $layout ) {
			$this->assertSame(
				array( 'purplish-gallery', 'purplish-gallery-layout-' . $layout ),
				BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'layout' => $layout ) )
			);
		}
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'layout' => 'masonry' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'layout' => true ) ) );
	}

	/**
	 * With a grid layout, the slides outside the set are hidden, and only
	 * they are. The thumbnails are left for WooCommerce.
	 */
	public function test_hides_slides_outside_the_set() {
		$out = PhotoGrid::render( $this->gallery( 'purplish-gallery purplish-gallery-layout-mosaic' ) );

		$this->assertSame( array( 13 ), $this->hidden_ids( $out ) );
		$this->assertStringContainsString( '<div class="wc-block-product-gallery-thumbnails"><img data-image-id="13" alt=""></div>', $out );
		$this->assertSame( array( 13 ), $this->hidden_ids( PhotoGrid::render( $this->gallery( 'purplish-gallery-layout-grid' ) ) ) );
	}

	/**
	 * Without a grid layout, a context or a set, the markup stays as it is.
	 */
	public function test_leaves_other_galleries_alone() {
		$plain = $this->gallery( 'purplish-gallery purplish-gallery-rounded' );
		$this->assertSame( $plain, PhotoGrid::render( $plain ) );

		$no_context = $this->gallery( 'purplish-gallery-layout-grid', '' );
		$this->assertSame( $no_context, PhotoGrid::render( $no_context ) );

		$no_set = $this->gallery( 'purplish-gallery-layout-grid', '{"selectedImageId":11}' );
		$this->assertSame( $no_set, PhotoGrid::render( $no_set ) );

		$empty_set = $this->gallery( 'purplish-gallery-layout-grid', '{"imageData":[]}' );
		$this->assertSame( $empty_set, PhotoGrid::render( $empty_set ) );
	}

	/**
	 * A slide without a photo id, like a video WooCommerce can't name, stays
	 * shown.
	 */
	public function test_keeps_slides_without_an_id() {
		$html = str_replace( ' data-image-id="12"', '', $this->gallery( 'purplish-gallery-layout-grid' ) );
		$this->assertSame( array( 13 ), $this->hidden_ids( PhotoGrid::render( $html ) ) );
	}

	/**
	 * The filter runs on the gallery block.
	 */
	public function test_hooked_on_the_gallery() {
		( new PhotoGrid() )->register();
		$this->assertNotFalse( has_filter( 'render_block_woocommerce/product-gallery', array( PhotoGrid::class, 'render' ) ) );
	}
}
