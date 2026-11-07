<?php
/**
 * Tests for the editor previews of the product page options: the 1.1 block
 * looks and the 1.0 page options on Add to Cart + Options.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\BlockOptions;
use Purplish\Frontend\PageData;
use Purplish\Options;
use WP_UnitTestCase;

/**
 * The editor gets the product page blocks, the page parts and a sample
 * color, and the page styles load into the editor canvas only.
 */
class ProductPagePreviewsTest extends WP_UnitTestCase {

	/**
	 * The product page blocks are in the editor map, with their options.
	 */
	public function test_preview_map() {
		$map    = BlockEffects::preview_map();
		$blocks = BlockEffects::blocks();

		foreach ( array(
			'woocommerce/add-to-cart-with-options',
			'woocommerce/product-gallery',
			'woocommerce/product-gallery-thumbnails',
			'woocommerce/product-sale-badge',
			'woocommerce/add-to-cart-with-options-variation-selector-attribute',
		) as $name ) {
			$this->assertArrayHasKey( $name, $map, $name );
			$this->assertSame( $blocks[ $name ]['prefix'], $map[ $name ]['prefix'], $name );
			$this->assertSame( $blocks[ $name ]['options'], $map[ $name ]['options'], $name );
		}
		$this->assertSame( $blocks['woocommerce/product-gallery']['legacy'], $map['woocommerce/product-gallery']['legacy'] );
	}

	/**
	 * The page data has every part with its default, the switches with a
	 * resting or hover look, and the sample color set.
	 */
	public function test_page_preview() {
		$page = BlockOptions::page_preview();

		$this->assertSame( Options::part_defaults(), $page['parts'] );
		$this->assertSame( array( 'glow', 'mesh' ), $page['switches'] );
		$this->assertSame( PageData::color_set( BlockOptions::PREVIEW_COLOR ), $page['sample'] );
		$this->assertSame( '#8f43ee', $page['sample']['color'] );
	}

	/**
	 * The page styles load into the editor canvas, and not on the front end
	 * through this hook.
	 */
	public function test_canvas_styles() {
		wp_dequeue_style( PageData::HANDLE );
		( new BlockOptions() )->enqueue_canvas_styles();
		$this->assertFalse( wp_style_is( PageData::HANDLE ), 'front end' );

		set_current_screen( 'post' );
		( new BlockOptions() )->enqueue_canvas_styles();
		$this->assertTrue( wp_style_is( PageData::HANDLE ), 'editor' );
		set_current_screen( 'front' );
	}
}
