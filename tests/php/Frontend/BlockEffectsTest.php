<?php
/**
 * Tests for the options on the gallery, collection and accordion blocks.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Saved options become wrapper classes; nothing else changes.
 */
class BlockEffectsTest extends WP_UnitTestCase {

	/**
	 * Gallery options become classes with the gallery prefix.
	 */
	public function test_gallery_classes() {
		$classes = BlockEffects::classes_for(
			'woocommerce/product-gallery',
			array(
				'nothumbs' => true,
				'tilt'     => true,
				'arrows'   => false,
			)
		);

		$this->assertSame( array( 'purplish-gallery', 'purplish-gallery-nothumbs', 'purplish-gallery-tilt' ), $classes );
	}

	/**
	 * Unknown options, wrong types and other blocks give no classes.
	 */
	public function test_unknown_input() {
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'explode' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'tilt' => 'yes' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/paragraph', array( 'tilt' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', 'tilt' ) );
	}

	/**
	 * The wrapper gets the classes; the rest of the markup stays as it was.
	 */
	public function test_render_adds_classes_to_the_wrapper_only() {
		$html  = '<div class="wp-block-woocommerce-product-gallery" data-x="1"><div class="inner">Photo</div></div>';
		$block = array(
			'blockName' => 'woocommerce/product-gallery',
			'attrs'     => array( 'purplish' => array( 'rounded' => true ) ),
		);

		$out = ( new BlockEffects() )->add_classes( $html, $block );

		$this->assertStringContainsString( 'class="wp-block-woocommerce-product-gallery purplish-gallery purplish-gallery-rounded"', $out );
		$this->assertStringContainsString( '<div class="inner">Photo</div>', $out );
		$this->assertSame( strlen( $html ), strlen( str_replace( ' purplish-gallery purplish-gallery-rounded', '', $out ) ) );
	}

	/**
	 * A block without Purplish options is returned untouched.
	 */
	public function test_render_without_options() {
		$html = '<div class="wp-block-woocommerce-product-gallery">Photo</div>';

		$this->assertSame(
			$html,
			( new BlockEffects() )->add_classes(
				$html,
				array(
					'blockName' => 'woocommerce/product-gallery',
					'attrs'     => array(),
				)
			)
		);
	}

	/**
	 * The server accepts the attribute on the gallery block.
	 */
	public function test_attribute_registered() {
		$args = ( new BlockEffects() )->add_attribute( array(), 'woocommerce/product-gallery' );

		$this->assertSame( 'object', $args['attributes']['purplish']['type'] );
	}
}
