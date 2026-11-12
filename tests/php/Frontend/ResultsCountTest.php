<?php
/**
 * Tests for "Count" on Product Results Count.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\ResultsCount;
use WP_UnitTestCase;

/**
 * The sentence stays for screen readers, the number shows, and nothing
 * changes without the option.
 */
class ResultsCountTest extends WP_UnitTestCase {

	/**
	 * WooCommerce's markup for 16 of 23 results.
	 */
	const HTML = '<div class="woocommerce wc-block-product-results-count wp-block-woocommerce-product-results-count"><p class="woocommerce-result-count" role="status" aria-relevant="all" >Showing 1&ndash;16 of 23 results</p></div>';

	/**
	 * The options add their wrapper classes, and motion needs a count.
	 */
	public function test_classes() {
		$this->assertSame(
			array( 'purplish-count', 'purplish-count-show-brackets', 'purplish-count-look-pill', 'purplish-count-motion-roll' ),
			BlockEffects::classes_for(
				ResultsCount::BLOCK,
				array(
					'show'   => 'brackets',
					'look'   => 'pill',
					'motion' => 'roll',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-count', 'purplish-count-look-outline' ),
			BlockEffects::classes_for(
				ResultsCount::BLOCK,
				array(
					'look'   => 'outline',
					'motion' => 'pop',
				)
			)
		);
	}

	/**
	 * Only known values count.
	 */
	public function test_show() {
		$this->assertSame( 'number', ResultsCount::show( array( 'show' => 'number' ) ) );
		$this->assertSame( '', ResultsCount::show( array( 'show' => 'words' ) ) );
		$this->assertSame( '', ResultsCount::show( array() ) );
		$this->assertSame( '', ResultsCount::show( 'nope' ) );
	}

	/**
	 * The three texts.
	 */
	public function test_text() {
		$this->assertSame( '23', ResultsCount::text( 'number', 23 ) );
		$this->assertSame( '(23)', ResultsCount::text( 'brackets', 23 ) );
		$this->assertSame( '23 products', ResultsCount::text( 'products', 23 ) );
		$this->assertSame( '1 product', ResultsCount::text( 'products', 1 ) );
		$this->assertSame( '(1,200)', ResultsCount::text( 'brackets', 1200 ) );
	}

	/**
	 * The sentence moves into screen reader text and the number shows,
	 * hidden from screen readers, one span per digit, keyed by the total.
	 */
	public function test_replace() {
		$out = ResultsCount::replace( self::HTML, 'brackets', 23 );

		$this->assertStringContainsString( 'role="status" aria-relevant="all" ><span class="screen-reader-text">Showing 1&ndash;16 of 23 results</span>', $out );
		$this->assertStringContainsString( '<span class="purplish-count-number" aria-hidden="true" data-wp-key="purplish-count-23">(<span class="purplish-count-digit" style="--i:0">2</span><span class="purplish-count-digit" style="--i:1">3</span>)</span></p>', $out );
		$this->assertSame( '23 products', wp_strip_all_tags( preg_replace( '#<span class="screen-reader-text">.*?</span>#', '', ResultsCount::replace( self::HTML, 'products', 23 ) ) ) );
	}

	/**
	 * The filter leaves the block alone without the option or a total.
	 */
	public function test_render() {
		$count = new ResultsCount();
		$block = array(
			'blockName' => ResultsCount::BLOCK,
			'attrs'     => array( 'purplish' => array( 'look' => 'pill' ) ),
		);
		$this->assertSame( self::HTML, $count->render( self::HTML, $block ) );

		wc_set_loop_prop( 'total', 0 );
		$block['attrs']['purplish'] = array( 'show' => 'number' );
		$this->assertSame( self::HTML, $count->render( self::HTML, $block ) );

		wc_set_loop_prop( 'total', 23 );
		$this->assertStringContainsString( 'data-wp-key="purplish-count-23"', $count->render( self::HTML, $block ) );
		wc_reset_loop();
	}
}
