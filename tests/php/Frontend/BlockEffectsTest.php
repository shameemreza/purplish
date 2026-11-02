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
				'hover'    => 'tilt',
				'arrows'   => false,
			)
		);

		$this->assertSame( array( 'purplish-gallery', 'purplish-gallery-nothumbs', 'purplish-gallery-hover-tilt' ), $classes );
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

	/**
	 * Card options: switches, a hover style from the list, and the effect
	 * color as an inline variable with readable text.
	 */
	public function test_cards() {
		$saved = array(
			'overlay' => true,
			'motion'  => true,
			'hover'   => 'spotlight',
			'color'   => '#7b2cbf',
		);

		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-overlay', 'purplish-cards-motion', 'purplish-cards-hover-spotlight' ),
			BlockEffects::classes_for( 'woocommerce/product-collection', $saved )
		);

		$out = ( new BlockEffects() )->add_classes(
			'<div class="wp-block-woocommerce-product-collection" style="margin:0">Cards</div>',
			array(
				'blockName' => 'woocommerce/product-collection',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
		$this->assertStringContainsString( 'style="margin:0;--purplish-effect:#7b2cbf;--purplish-effect-on:#ffffff;"', $out );
	}

	/**
	 * A hover style outside the list, or a color that isn't hex, is ignored.
	 */
	public function test_cards_bad_input() {
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-collection', array( 'hover' => 'explode' ) ) );

		$out = ( new BlockEffects() )->add_classes(
			'<div class="wp-block-woocommerce-product-collection">Cards</div>',
			array(
				'blockName' => 'woocommerce/product-collection',
				'attrs'     => array(
					'purplish' => array(
						'overlay' => true,
						'color'   => 'red;background:url(x)',
					),
				),
			)
		);
		$this->assertStringNotContainsString( 'style=', $out );
	}

	/**
	 * The accordion and group switches become their classes.
	 */
	public function test_accordion_and_group() {
		$this->assertSame( array( 'purplish-accordion', 'purplish-accordion-soft' ), BlockEffects::classes_for( 'core/accordion', array( 'soft' => true ) ) );
		$this->assertSame( array( 'purplish-group', 'purplish-group-tint' ), BlockEffects::classes_for( 'core/group', array( 'tint' => true ) ) );
	}

	/**
	 * Every motion pack value on the cards and the gallery becomes its class,
	 * and a slide style outside the list is ignored.
	 */
	public function test_motion_pack_values() {
		foreach ( array( 'clothesline', 'fan', 'arc', 'stage', 'expand', 'grow', 'tiltrow', 'parallax', 'zoomin', 'folder' ) as $hover ) {
			$this->assertSame(
				array( 'purplish-cards', 'purplish-cards-hover-' . $hover ),
				BlockEffects::classes_for( 'woocommerce/product-collection', array( 'hover' => $hover ) )
			);
		}

		foreach ( array( 'cube', 'circle', 'reel', 'tunnel' ) as $slider ) {
			$this->assertSame(
				array( 'purplish-gallery', 'purplish-gallery-slider-' . $slider ),
				BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'slider' => $slider ) )
			);
		}

		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'slider' => 'spin' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'slider' => true ) ) );
	}

	/**
	 * Every hover and full-screen style becomes its class, and values
	 * outside the lists are ignored.
	 */
	public function test_hover_and_fullscreen_values() {
		foreach ( array( 'tilt', 'zoom', 'lift', 'parallax', 'glare', 'loupe', 'slowzoom' ) as $hover ) {
			$this->assertSame(
				array( 'purplish-gallery', 'purplish-gallery-hover-' . $hover ),
				BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'hover' => $hover ) )
			);
		}

		foreach ( array( 'snap', 'fade', 'zoom', 'filmstrip', 'cinema' ) as $fullscreen ) {
			$this->assertSame(
				array( 'purplish-gallery', 'purplish-gallery-fullscreen-' . $fullscreen ),
				BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'fullscreen' => $fullscreen ) )
			);
		}

		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'hover' => 'spin' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'fullscreen' => 'slider' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'hover' => true ) ) );
	}

	/**
	 * Tilt and lightbox switches saved before task 23 read as the 3D tilt
	 * hover style and the snap stack, unless a style is saved too.
	 */
	public function test_saved_tilt_and_lightbox() {
		$this->assertSame(
			array( 'purplish-gallery', 'purplish-gallery-rounded', 'purplish-gallery-hover-tilt', 'purplish-gallery-fullscreen-snap' ),
			BlockEffects::classes_for(
				'woocommerce/product-gallery',
				array(
					'rounded'  => true,
					'tilt'     => true,
					'lightbox' => true,
				)
			)
		);

		$this->assertSame(
			array( 'purplish-gallery', 'purplish-gallery-hover-zoom', 'purplish-gallery-fullscreen-cinema' ),
			BlockEffects::classes_for(
				'woocommerce/product-gallery',
				array(
					'tilt'       => true,
					'hover'      => 'zoom',
					'lightbox'   => true,
					'fullscreen' => 'cinema',
				)
			)
		);

		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'tilt' => false ) ) );
	}

	/**
	 * The classic hover style: the select wins, an old tilt checkbox reads
	 * as the tilt, and anything else is none.
	 */
	public function test_classic_hover() {
		$this->assertSame( '', BlockEffects::classic_hover( 'junk' ) );
		$this->assertSame( '', BlockEffects::classic_hover( array() ) );
		$this->assertSame( 'tilt', BlockEffects::classic_hover( array( 'gallery_tilt' => 'yes' ) ) );
		$this->assertSame( 'glare', BlockEffects::classic_hover( array( 'gallery_hover' => 'glare' ) ) );
		$this->assertSame(
			'',
			BlockEffects::classic_hover(
				array(
					'gallery_tilt'  => 'yes',
					'gallery_hover' => 'none',
				)
			)
		);
		$this->assertSame( '', BlockEffects::classic_hover( array( 'gallery_hover' => 'explode' ) ) );
	}
}
