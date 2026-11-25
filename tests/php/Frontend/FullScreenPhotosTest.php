<?php
/**
 * Tests for the Product Gallery's full-screen photos.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\FullScreenPhotos;
use WP_UnitTestCase;

/**
 * Photos in the closed full-screen view are lazy; the gallery's own photos
 * keep what WooCommerce printed.
 */
class FullScreenPhotosTest extends WP_UnitTestCase {

	/**
	 * Only the photos inside the dialog turn lazy.
	 */
	public function test_dialog_photos_turn_lazy() {
		$html = '<div class="wp-block-woocommerce-product-gallery"><img src="slide.jpg" alt="">'
			. '<dialog inert class="wc-block-product-gallery-dialog"><img src="a.jpg" alt=""><img loading="eager" src="b.jpg" alt=""></dialog>'
			. '<img src="after.jpg" alt=""></div>';

		$out = FullScreenPhotos::render( $html );

		$this->assertStringContainsString( '<img src="slide.jpg" alt="">', $out );
		$this->assertStringContainsString( '<img loading="lazy" src="a.jpg" alt="">', $out );
		$this->assertStringContainsString( '<img loading="lazy" src="b.jpg" alt="">', $out );
		$this->assertStringContainsString( '<img src="after.jpg" alt="">', $out );
	}

	/**
	 * A gallery without the full-screen view comes back unchanged.
	 */
	public function test_gallery_without_dialog_unchanged() {
		$html = '<div class="wp-block-woocommerce-product-gallery"><img src="slide.jpg" alt=""></div>';
		$this->assertSame( $html, FullScreenPhotos::render( $html ) );
	}
}
