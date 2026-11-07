<?php
/**
 * Tests for the hover looks on product cards.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\SecondPhoto;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * A Product Collection saves where hover plays and one look per part. Each
 * look prints its class only where the target reaches and the card effect
 * leaves that part free.
 */
class CardHoverTest extends WP_UnitTestCase {

	const NAME = 'woocommerce/product-collection';

	const LOOKS = array(
		'hovercard'   => 'lift',
		'hoverphoto'  => 'push',
		'hoverbutton' => 'glow',
	);

	const IMAGE = '<div class="wc-block-components-product-image"><a href="/product/lamp/"><img class="wc-block-components-product-image__image" src="lamp.jpg" alt="Lamp"><div class="wc-block-components-product-image__inner-container"></div></a></div>';

	/**
	 * The hover classes for saved options.
	 *
	 * @param array $saved Saved options.
	 * @return string[]
	 */
	private function hover_classes( $saved ) {
		return array_values(
			array_filter(
				BlockEffects::classes_for( self::NAME, $saved ),
				function ( $name ) {
					foreach ( array_keys( self::LOOKS ) as $key ) {
						if ( 0 === strpos( $name, 'purplish-cards-' . $key . '-' ) ) {
							return true;
						}
					}
					return false;
				}
			)
		);
	}

	/**
	 * Each target prints the looks it reaches, and only those.
	 */
	public function test_targets() {
		$expected = array(
			'card'   => array( 'purplish-cards-hovercard-lift' ),
			'photo'  => array( 'purplish-cards-hoverphoto-push' ),
			'button' => array( 'purplish-cards-hoverbutton-glow' ),
			'all'    => array( 'purplish-cards-hovercard-lift', 'purplish-cards-hoverphoto-push', 'purplish-cards-hoverbutton-glow' ),
		);
		foreach ( $expected as $target => $classes ) {
			$this->assertSame( $classes, $this->hover_classes( array( 'hoveron' => $target ) + self::LOOKS ), $target );
		}
	}

	/**
	 * Without a target, saved looks stay saved and print nothing, so a
	 * block with hover off draws as before.
	 */
	public function test_off_keeps_looks_quiet() {
		$this->assertSame( array(), BlockEffects::classes_for( self::NAME, self::LOOKS ) );
		$this->assertSame( array(), $this->hover_classes( array( 'hoveron' => 'nowhere' ) + self::LOOKS ) );
	}

	/**
	 * A card effect that moves the card leaves the card look out; one that
	 * changes the photo leaves the photo look out; Roster leaves all out.
	 */
	public function test_clashing_effects() {
		$all = array( 'hoveron' => 'all' ) + self::LOOKS;

		$this->assertSame(
			array( 'purplish-cards-hoverphoto-push', 'purplish-cards-hoverbutton-glow' ),
			$this->hover_classes( array( 'hover' => 'deck' ) + $all )
		);
		$this->assertSame(
			array( 'purplish-cards-hovercard-lift', 'purplish-cards-hoverbutton-glow' ),
			$this->hover_classes( array( 'hover' => 'reveal' ) + $all )
		);
		$this->assertSame( array(), $this->hover_classes( array( 'hover' => 'roster' ) + $all ) );

		foreach ( array( 'arc', 'stage', 'grow', 'tiltrow', 'parallax', 'zoomin' ) as $hover ) {
			$this->assertCount( 3, $this->hover_classes( array( 'hover' => $hover ) + $all ), $hover );
		}
	}

	/**
	 * Unknown looks and wrong types give nothing.
	 */
	public function test_bad_values() {
		foreach ( array( 'Lift', 'bounce', true, array( 'lift' ) ) as $bad ) {
			$this->assertSame(
				array(),
				$this->hover_classes(
					array(
						'hoveron'   => 'card',
						'hovercard' => $bad,
					)
				)
			);
		}
	}

	/**
	 * A 1.1 block with Button motion keeps its class and draws the same.
	 */
	public function test_button_motion_unchanged() {
		$this->assertSame( array( 'purplish-cards', 'purplish-cards-motion' ), BlockEffects::classes_for( self::NAME, array( 'motion' => true ) ) );
	}

	/**
	 * The editor gets the same rules as the server.
	 */
	public function test_preview_map_has_needs() {
		$this->assertSame( BlockEffects::blocks()[ self::NAME ]['needs'], BlockEffects::preview_map()[ self::NAME ]['needs'] );
	}

	/**
	 * The second photo goes into the product image inside a collection
	 * that shows it, and nowhere else.
	 */
	public function test_second_photo() {
		$gallery = self::factory()->attachment->create_object(
			'lamp-back.jpg',
			0,
			array( 'post_mime_type' => 'image/jpeg' )
		);
		$product = new WC_Product_Simple();
		$product->set_name( 'Lamp' );
		$product->set_gallery_image_ids( array( $gallery ) );
		$product->save();

		$plain = new WC_Product_Simple();
		$plain->set_name( 'Plain lamp' );
		$plain->save();

		$second   = new SecondPhoto();
		$instance = (object) array( 'context' => array( 'postId' => $product->get_id() ) );
		$shows    = array(
			'hoveron'    => 'photo',
			'hoverphoto' => 'second',
		);

		$this->assertSame( self::IMAGE, $second->add_photo( self::IMAGE, array(), $instance ), 'outside a collection' );

		$second->enter(
			array(
				'blockName' => self::NAME,
				'attrs'     => array( 'purplish' => $shows ),
			)
		);
		$out = $second->add_photo( self::IMAGE, array(), $instance );
		$this->assertStringContainsString( 'purplish-second-photo', $out );
		$this->assertStringContainsString( 'aria-hidden="true"', $out );
		$this->assertLessThan( strpos( $out, 'inner-container' ), strpos( $out, 'purplish-second-photo' ) );
		$this->assertSame(
			self::IMAGE,
			$second->add_photo( self::IMAGE, array(), (object) array( 'context' => array( 'postId' => $plain->get_id() ) ) ),
			'no gallery photo'
		);
		$second->leave( '' );

		$this->assertSame( self::IMAGE, $second->add_photo( self::IMAGE, array(), $instance ), 'after the collection' );

		$this->assertFalse( SecondPhoto::wants( array( 'hoverphoto' => 'second' ) ), 'no target' );
		$this->assertFalse( SecondPhoto::wants( array( 'hover' => 'reveal' ) + $shows ), 'photo effect' );
		$this->assertTrue( SecondPhoto::wants( array( 'hover' => 'arc' ) + $shows ), 'carousel effect' );
	}
}
