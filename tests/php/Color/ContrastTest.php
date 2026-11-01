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
