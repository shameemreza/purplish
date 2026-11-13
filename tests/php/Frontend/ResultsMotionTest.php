<?php
/**
 * Tests for Results change on the Product Collection block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\ResultsMotion;
use WP_UnitTestCase;

/**
 * The look becomes a class, and a collection that swaps its results in
 * place gets the script that moves the cards.
 */
class ResultsMotionTest extends WP_UnitTestCase {

	/**
	 * A rendered Product Collection.
	 */
	const COLLECTION = '<div class="wp-block-woocommerce-product-collection" data-wp-router-region="wc-product-collection-0"><ul class="wc-block-product-template"><li>Card</li></ul></div>';

	/**
	 * Drops the script between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( ResultsMotion::HANDLE );
		parent::tear_down();
	}

	/**
	 * A parsed collection block with the given attributes.
	 *
	 * @param array $attrs Block attributes.
	 * @return array
	 */
	private function block( $attrs ) {
		return array(
			'blockName' => ResultsMotion::COLLECTION,
			'attrs'     => $attrs,
		);
	}

	/**
	 * Fade, rise and move are the only looks.
	 */
	public function test_classes() {
		foreach ( array( 'fade', 'rise', 'move' ) as $look ) {
			$this->assertSame( array( 'purplish-cards', 'purplish-cards-results-' . $look ), BlockEffects::classes_for( ResultsMotion::COLLECTION, array( 'results' => $look ) ) );
		}
		$this->assertSame( array(), BlockEffects::classes_for( ResultsMotion::COLLECTION, array( 'results' => 'spin' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( ResultsMotion::COLLECTION, array( 'results' => true ) ) );
	}

	/**
	 * The script loads for a collection with a look, and the markup stays
	 * as it was.
	 */
	public function test_loads_the_script() {
		$out = ( new ResultsMotion() )->load( self::COLLECTION, $this->block( array( 'purplish' => array( 'results' => 'move' ) ) ) );

		$this->assertSame( self::COLLECTION, $out );
		$this->assertTrue( wp_script_is( ResultsMotion::HANDLE, 'enqueued' ) );
	}

	/**
	 * No look, a look outside the list, Force page reload or an empty render
	 * leave the script out.
	 */
	public function test_leaves_the_script_out() {
		$this->assertFalse( ResultsMotion::wants( self::COLLECTION, $this->block( array() ) ) );
		$this->assertFalse( ResultsMotion::wants( self::COLLECTION, $this->block( array( 'purplish' => array( 'results' => 'spin' ) ) ) ) );
		$this->assertFalse(
			ResultsMotion::wants(
				self::COLLECTION,
				$this->block(
					array(
						'purplish'        => array( 'results' => 'fade' ),
						'forcePageReload' => true,
					)
				)
			)
		);
		$this->assertFalse( ResultsMotion::wants( '  ', $this->block( array( 'purplish' => array( 'results' => 'rise' ) ) ) ) );

		( new ResultsMotion() )->load( self::COLLECTION, $this->block( array() ) );
		$this->assertFalse( wp_script_is( ResultsMotion::HANDLE, 'enqueued' ) );
	}
}
