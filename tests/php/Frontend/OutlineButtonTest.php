<?php
/**
 * Tests for the outline button under product cards, added in 1.3.2.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Outline is a value of the card button style, for the button under the
 * card only.
 */
class OutlineButtonTest extends WP_UnitTestCase {

	const NAME = 'woocommerce/product-collection';

	/**
	 * Outline becomes its class, and joins the card hover fill.
	 */
	public function test_outline_class() {
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-buttonstyle-outline' ),
			BlockEffects::classes_for( self::NAME, array( 'buttonstyle' => 'outline' ) )
		);
		$this->assertContains(
			'purplish-cards-buttonstyle-outline',
			BlockEffects::classes_for(
				self::NAME,
				array(
					'buttonstyle' => 'outline',
					'cardfill'    => true,
				)
			)
		);
	}

	/**
	 * The button on the photo and details on the photo drop it.
	 */
	public function test_dropped_with_the_button_on_the_photo() {
		foreach ( array( 'overlay', 'details' ) as $switch ) {
			$this->assertNotContains(
				'purplish-cards-buttonstyle-outline',
				BlockEffects::classes_for(
					self::NAME,
					array(
						'buttonstyle' => 'outline',
						$switch       => true,
					)
				),
				$switch
			);
		}
	}

	/**
	 * Other values give nothing.
	 */
	public function test_bad_values() {
		foreach ( array( 'Outline', 'ghost', 'bar', true, array( 'outline' ) ) as $bad ) {
			$this->assertSame( array(), BlockEffects::classes_for( self::NAME, array( 'buttonstyle' => $bad ) ) );
		}
	}
}
