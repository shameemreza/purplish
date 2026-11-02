<?php
/**
 * Tests for the text color picker.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\Contrast;
use WP_UnitTestCase;

/**
 * Black or white text by WCAG contrast.
 */
class ContrastTest extends WP_UnitTestCase {

	/**
	 * Each color gets the text with more contrast.
	 *
	 * @dataProvider provide_colors
	 *
	 * @param string $background Background color.
	 * @param string $expected   Text color.
	 */
	public function test_picks_the_higher_contrast_text( $background, $expected ) {
		$this->assertSame( $expected, Contrast::on( $background ) );
	}

	/**
	 * Colors from the test store and the edges.
	 *
	 * @return array<string, string[]>
	 */
	public function provide_colors() {
		return array(
			// Luminance 0.2025: 5.05:1 with black, 4.16:1 with white.
			'orange sweater'  => array( '#dc4a24', Contrast::BLACK ),
			// Luminance 0.1094: 3.19:1 with black, 6.59:1 with white.
			'blue sweater'    => array( '#3357b7', Contrast::WHITE ),
			'pale purple'     => array( '#f2edff', Contrast::BLACK ),
			'black'           => array( '#000000', Contrast::WHITE ),
			'white'           => array( '#ffffff', Contrast::BLACK ),
			'short hex'       => array( '#00f', Contrast::WHITE ),
			'uppercase'       => array( '#3357B7', Contrast::WHITE ),
			'not a color'     => array( 'blue', Contrast::BLACK ),
			'missing the #'   => array( '3357b7', Contrast::BLACK ),
			'injection guard' => array( '#fff;}body{', Contrast::BLACK ),
		);
	}

	/**
	 * The ratio matches the WCAG formula, in either order.
	 */
	public function test_ratio() {
		$this->assertEqualsWithDelta( 21.0, Contrast::ratio( '#000000', '#ffffff' ), 0.001 );
		$this->assertEqualsWithDelta( 21.0, Contrast::ratio( '#ffffff', '#000000' ), 0.001 );
		$this->assertEqualsWithDelta( 1.0, Contrast::ratio( '#3357b7', '#3357b7' ), 0.001 );
		// Luminance 0.2025: 4.16:1 with white.
		$this->assertEqualsWithDelta( 4.16, Contrast::ratio( '#dc4a24', Contrast::WHITE ), 0.01 );
		$this->assertNull( Contrast::ratio( 'blue', '#ffffff' ) );
		$this->assertNull( Contrast::ratio( '#ffffff', '' ) );
	}

	/**
	 * Luminance matches the WCAG formula.
	 */
	public function test_luminance() {
		$this->assertEqualsWithDelta( 0.2025, Contrast::luminance( '#dc4a24' ), 0.001 );
		$this->assertEqualsWithDelta( 0.1094, Contrast::luminance( '#3357b7' ), 0.001 );
		$this->assertSame( 0.0, (float) Contrast::luminance( '#000000' ) );
		$this->assertEqualsWithDelta( 1.0, Contrast::luminance( '#ffffff' ), 0.0001 );
		$this->assertNull( Contrast::luminance( 'nope' ) );
	}
}
