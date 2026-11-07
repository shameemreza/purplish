<?php
/**
 * Tests for the card effects added after 1.0: bookmark, depth, overlap and
 * roster in 1.1, and unfold, momentum and wheel in 1.2.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The effects are new values of the 1.0 card effect. Each becomes a wrapper
 * class on the collection, and the stylesheet does the rest.
 */
class CardEffectsTest extends WP_UnitTestCase {

	const NEW_VALUES = array( 'bookmark', 'depth', 'overlap', 'roster' );

	const VALUES_12 = array( 'unfold', 'momentum', 'wheel' );

	const COLLECTION = '<div class="wp-block-woocommerce-product-collection alignwide"><ul class="wc-block-product-template is-product-collection-layout-carousel is-horizontal"><li class="wc-block-product"><h3 class="wp-block-post-title"><a href="/product/lamp/">Lamp</a></h3></li></ul></div>';

	/**
	 * Renders the collection through the class filter.
	 *
	 * @param array $saved Saved options.
	 * @return string
	 */
	private function render( $saved ) {
		return ( new BlockEffects() )->add_classes(
			self::COLLECTION,
			array(
				'blockName' => 'woocommerce/product-collection',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * Each new value becomes its class.
	 */
	public function test_new_values() {
		foreach ( array_merge( self::NEW_VALUES, self::VALUES_12 ) as $hover ) {
			$this->assertSame(
				array( 'purplish-cards', 'purplish-cards-hover-' . $hover ),
				BlockEffects::classes_for( 'woocommerce/product-collection', array( 'hover' => $hover ) ),
				$hover
			);
		}
	}

	/**
	 * Each release's values join the list after the ones before, which stay
	 * as they were.
	 */
	public function test_joins_the_list() {
		$hover = BlockEffects::blocks()['woocommerce/product-collection']['options']['hover'];
		$v1    = array( 'magnetic', 'spotlight', 'reveal', 'deck', 'clothesline', 'fan', 'arc', 'stage', 'expand', 'grow', 'tiltrow', 'parallax', 'zoomin', 'folder' );

		$this->assertSame( array_merge( $v1, self::NEW_VALUES, self::VALUES_12 ), $hover );
	}

	/**
	 * Bad values, and the right names with the wrong type, give nothing.
	 */
	public function test_bad_values() {
		foreach ( array( 'Bookmark', 'coverflow', 'roster ', 'Wheel', 'unfolded', true, array( 'roster' ) ) as $bad ) {
			$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-collection', array( 'hover' => $bad ) ) );
		}
	}

	/**
	 * The effects combine with the 1.0 switches and the effect color, and
	 * the classes go on the collection's wrapper only.
	 */
	public function test_markup_with_switches_and_color() {
		$out = $this->render(
			array(
				'overlay' => true,
				'motion'  => true,
				'hover'   => 'bookmark',
				'color'   => '#720eec',
			)
		);

		$this->assertSame(
			str_replace(
				'<div class="wp-block-woocommerce-product-collection alignwide"',
				'<div style="--purplish-effect:#720eec;--purplish-effect-on:#ffffff;" class="wp-block-woocommerce-product-collection alignwide purplish-cards purplish-cards-overlay purplish-cards-motion purplish-cards-hover-bookmark"',
				self::COLLECTION
			),
			$out
		);
	}

	/**
	 * A carousel effect prints the same class whatever the layout; the
	 * stylesheet leaves a grid as it is.
	 */
	public function test_markup_carousel() {
		foreach ( array( 'depth', 'overlap', 'roster', 'unfold', 'momentum', 'wheel' ) as $hover ) {
			$this->assertSame(
				str_replace( 'alignwide"', 'alignwide purplish-cards purplish-cards-hover-' . $hover . '"', self::COLLECTION ),
				$this->render( array( 'hover' => $hover ) )
			);
		}
	}
}
