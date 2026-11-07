<?php
/**
 * Tests for the editor previews of the card options: Product Collection,
 * the soft accordion and details, reviews and specifications.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The editor gets the card blocks with the same options as the server.
 */
class CardPreviewsTest extends WP_UnitTestCase {

	/**
	 * The card blocks are in the editor map, with their options.
	 */
	public function test_preview_map() {
		$map    = BlockEffects::preview_map();
		$blocks = BlockEffects::blocks();

		foreach ( array(
			'woocommerce/product-collection',
			'core/accordion',
			'woocommerce/accordion-group',
			'core/details',
			'woocommerce/product-reviews',
			'woocommerce/product-specifications',
		) as $name ) {
			$this->assertArrayHasKey( $name, $map, $name );
			$this->assertSame( $blocks[ $name ]['prefix'], $map[ $name ]['prefix'], $name );
			$this->assertSame( $blocks[ $name ]['options'], $map[ $name ]['options'], $name );
		}
		$this->assertContains( 'roster', $map['woocommerce/product-collection']['options']['hover'] );
		$this->assertTrue( $map['woocommerce/product-collection']['options']['overlay'] );
	}

	/**
	 * Both accordions share one prefix, so the soft style covers both.
	 */
	public function test_accordions_share_the_soft_class() {
		$this->assertSame(
			BlockEffects::classes_for( 'core/accordion', array( 'soft' => true ) ),
			BlockEffects::classes_for( 'woocommerce/accordion-group', array( 'soft' => true ) )
		);
	}
}
