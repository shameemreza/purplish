<?php
/**
 * Tests for the Cover looks on Featured Category and Featured Product.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Since WooCommerce 11.3 the two Featured blocks style like a cover, so
 * they take the Cover block's scroll and hover looks.
 */
class FeaturedCoverLooksTest extends WP_UnitTestCase {

	const FEATURED = '<div data-block-name="woocommerce/featured-product" data-product-id="13" class="wc-block-featured-product has-background-dim wp-block-woocommerce-featured-product"><div class="wc-block-featured-product__wrapper"><div class="background-dim__overlay"></div><img alt="" class="wc-block-featured-product__background-image" src="x.jpg" /></div></div>';

	/**
	 * Both blocks share the Cover block's prefix and options.
	 */
	public function test_same_looks_as_cover() {
		$blocks = BlockEffects::blocks();

		foreach ( array( 'woocommerce/featured-category', 'woocommerce/featured-product' ) as $name ) {
			$this->assertSame( $blocks['core/cover']['prefix'], $blocks[ $name ]['prefix'], $name );
			// The category photo is Cover's own: these blocks already show a term's or product's photo.
			$this->assertSame( array_diff_key( $blocks['core/cover']['options'], array( 'termphoto' => true ) ), $blocks[ $name ]['options'], $name );

			$args = ( new BlockEffects() )->add_attribute( array(), $name );
			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * The classes go on the block's outer element, and only known looks.
	 */
	public function test_classes() {
		$html = ( new BlockEffects() )->add_classes(
			self::FEATURED,
			array(
				'blockName' => 'woocommerce/featured-product',
				'attrs'     => array(
					'purplish' => array(
						'scroll' => 'slowzoom',
						'hover'  => 'tilt',
					),
				),
			)
		);

		$this->assertStringContainsString( 'wp-block-woocommerce-featured-product purplish-cover purplish-cover-scroll-slowzoom purplish-cover-hover-tilt"', $html );
		$this->assertStringContainsString( '<img alt="" class="wc-block-featured-product__background-image"', $html, 'the photo keeps its classes' );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/featured-category', array( 'hover' => 'loupe' ) ) );
	}

	/**
	 * Only the hover look shows in the editor, as on a cover.
	 */
	public function test_hover_previews() {
		$map = BlockEffects::preview_map();

		foreach ( array( 'woocommerce/featured-category', 'woocommerce/featured-product' ) as $name ) {
			$this->assertSame( array( 'hover' => array( 'push', 'tilt', 'lift', 'glare' ) ), $map[ $name ]['options'], $name );
		}
	}
}
