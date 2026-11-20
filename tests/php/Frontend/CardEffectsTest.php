<?php
/**
 * Tests for the card effects added after 1.0: bookmark, depth, overlap and
 * roster in 1.1, and unfold, momentum, wheel, helix and reel in 1.2, with
 * details on the photo.
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

	const VALUES_12 = array( 'unfold', 'momentum', 'wheel', 'helix', 'reel' );

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
		foreach ( array( 'Bookmark', 'coverflow', 'roster ', 'Wheel', 'unfolded', 'dna', 'Reel', true, array( 'roster' ) ) as $bad ) {
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
		foreach ( array( 'depth', 'overlap', 'roster', 'unfold', 'momentum', 'wheel', 'helix', 'reel' ) as $hover ) {
			$this->assertSame(
				str_replace( 'alignwide"', 'alignwide purplish-cards purplish-cards-hover-' . $hover . '"', self::COLLECTION ),
				$this->render( array( 'hover' => $hover ) )
			);
		}
	}

	/**
	 * Details on the photo prints its class, takes the place of the button
	 * on the photo, and stays off with Roster, which lists the names itself.
	 */
	public function test_details_on_the_photo() {
		$name = 'woocommerce/product-collection';

		$this->assertSame( array( 'purplish-cards', 'purplish-cards-details' ), BlockEffects::classes_for( $name, array( 'details' => true ) ) );
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-details', 'purplish-cards-hover-reel' ),
			BlockEffects::classes_for(
				$name,
				array(
					'overlay' => true,
					'details' => true,
					'hover'   => 'reel',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-hover-roster' ),
			BlockEffects::classes_for(
				$name,
				array(
					'details' => true,
					'hover'   => 'roster',
				)
			)
		);
		foreach ( array( 'yes', 1, 'true' ) as $bad ) {
			$this->assertSame( array(), BlockEffects::classes_for( $name, array( 'details' => $bad ) ) );
		}
	}

	/**
	 * The bar look prints its class with the button on the photo only, not
	 * on its own and not with details on the photo, and takes no other value.
	 */
	public function test_button_look_bar() {
		$name = 'woocommerce/product-collection';

		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-overlay', 'purplish-cards-buttonlook-bar' ),
			BlockEffects::classes_for(
				$name,
				array(
					'overlay'    => true,
					'buttonlook' => 'bar',
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( $name, array( 'buttonlook' => 'bar' ) ) );
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-details' ),
			BlockEffects::classes_for(
				$name,
				array(
					'overlay'    => true,
					'details'    => true,
					'buttonlook' => 'bar',
				)
			)
		);
		foreach ( array( 'pill', 'Bar', true, array( 'bar' ) ) as $bad ) {
			$this->assertSame(
				array( 'purplish-cards', 'purplish-cards-overlay' ),
				BlockEffects::classes_for(
					$name,
					array(
						'overlay'    => true,
						'buttonlook' => $bad,
					)
				)
			);
		}
	}

	/**
	 * Ruled, Photo wash and Card hover fills the button print their classes,
	 * and Roster, which lays the cards out as a list, turns off Ruled and
	 * the button fill.
	 */
	public function test_ruled_wash_and_cardfill() {
		$saved = array(
			'ruled'    => true,
			'wash'     => true,
			'cardfill' => true,
		);

		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-ruled', 'purplish-cards-wash', 'purplish-cards-cardfill' ),
			BlockEffects::classes_for( 'woocommerce/product-collection', $saved )
		);
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-hover-roster', 'purplish-cards-wash' ),
			BlockEffects::classes_for( 'woocommerce/product-collection', $saved + array( 'hover' => 'roster' ) )
		);
		$this->assertSame(
			array( 'purplish-gallery', 'purplish-gallery-wash' ),
			BlockEffects::classes_for( 'woocommerce/product-gallery', array( 'wash' => true ) )
		);
	}

	/**
	 * Peek prints its size, ignores sizes it doesn't know, and stays off
	 * with a carousel card effect, which places the cards itself. On-hover
	 * and scroll-in effects keep it.
	 */
	public function test_peek() {
		foreach ( array( 'small', 'medium', 'large' ) as $size ) {
			$this->assertSame(
				array( 'purplish-cards', 'purplish-cards-peek-' . $size ),
				BlockEffects::classes_for( 'woocommerce/product-collection', array( 'peek' => $size ) )
			);
		}

		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-collection', array( 'peek' => 'huge' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-collection', array( 'peek' => true ) ) );

		foreach ( BlockEffects::CAROUSEL_EFFECTS as $effect ) {
			$this->assertSame(
				array( 'purplish-cards', 'purplish-cards-hover-' . $effect ),
				BlockEffects::classes_for(
					'woocommerce/product-collection',
					array(
						'peek'  => 'medium',
						'hover' => $effect,
					)
				),
				$effect
			);
		}

		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-hover-spotlight', 'purplish-cards-peek-large' ),
			BlockEffects::classes_for(
				'woocommerce/product-collection',
				array(
					'peek'  => 'large',
					'hover' => 'spotlight',
				)
			)
		);
	}

	/**
	 * Every effect Peek gives way to is a card effect the block accepts, so
	 * a misspelt name can't slip into the list.
	 */
	public function test_carousel_effects_are_card_effects() {
		$hovers = BlockEffects::blocks()['woocommerce/product-collection']['options']['hover'];
		$this->assertSame( array(), array_diff( BlockEffects::CAROUSEL_EFFECTS, $hovers ) );
	}

	/**
	 * The tablet count and Closing rule print only with Ruled cards, and
	 * not under Roster.
	 */
	public function test_ruled_tablet_and_closing_rule() {
		$saved = array(
			'tablet'     => '3',
			'ruledclose' => true,
		);

		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-ruled', 'purplish-cards-tablet-3', 'purplish-cards-ruledclose' ),
			BlockEffects::classes_for( 'woocommerce/product-collection', $saved + array( 'ruled' => true ) )
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-collection', $saved ) );
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-hover-roster' ),
			BlockEffects::classes_for(
				'woocommerce/product-collection',
				$saved + array(
					'ruled' => true,
					'hover' => 'roster',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-ruled' ),
			BlockEffects::classes_for(
				'woocommerce/product-collection',
				array(
					'ruled'  => true,
					'tablet' => '6',
				)
			)
		);
	}

	/**
	 * The ring settings print only while the Ring card look plays: on the
	 * card or all, with no card effect that moves the card.
	 */
	public function test_ring_settings_need_the_ring() {
		$settings = array(
			'ringinset'  => true,
			'ringsquare' => true,
			'ringstrong' => true,
		);
		$ring     = array(
			'hoveron'   => 'card',
			'hovercard' => 'ring',
		);

		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-hoveron-card', 'purplish-cards-hovercard-ring', 'purplish-cards-ringinset', 'purplish-cards-ringsquare', 'purplish-cards-ringstrong' ),
			BlockEffects::classes_for( 'woocommerce/product-collection', $ring + $settings )
		);
		$this->assertContains(
			'purplish-cards-ringinset',
			BlockEffects::classes_for( 'woocommerce/product-collection', array( 'hoveron' => 'all' ) + $ring + $settings )
		);

		$off = array(
			'lift'       => array(
				'hoveron'   => 'card',
				'hovercard' => 'lift',
			),
			'photo only' => array(
				'hoveron'   => 'photo',
				'hovercard' => 'ring',
			),
			'mover'      => $ring + array( 'hover' => 'fan' ),
			'no ring'    => array(),
		);
		foreach ( $off as $label => $saved ) {
			$classes = BlockEffects::classes_for( 'woocommerce/product-collection', $saved + $settings );
			foreach ( array_keys( $settings ) as $option ) {
				$this->assertNotContains( 'purplish-cards-' . $option, $classes, $label );
			}
		}
	}
}
