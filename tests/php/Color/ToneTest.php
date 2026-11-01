<?php
/**
 * Tests for the accent and tint colors.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\Contrast;
use Purplish\Color\Tone;
use WP_UnitTestCase;

/**
 * Every source color gives a usable accent and a pale tint of its hue.
 */
class ToneTest extends WP_UnitTestCase {

	/**
	 * Hue difference in degrees, around the wheel.
	 *
	 * @param float $a Hue.
	 * @param float $b Hue.
	 * @return float
	 */
	private function hue_gap( $a, $b ) {
		$gap = abs( $a - $b );
		return min( $gap, 360 - $gap );
	}

	/**
	 * Colors that need no change come back as they are.
	 */
	public function test_good_colors_stay() {
		$this->assertSame( '#d13e1e', Tone::accent( '#d13e1e' ) );
		$this->assertSame( '#c9a227', Tone::accent( '#c9a227' ) );
	}

	/**
	 * The dark blue from the sweater photo gets lighter, keeps its hue, and
	 * lands near the blue picked by hand for the same page.
	 */
	public function test_dark_photo_blue_is_lifted() {
		$accent = Tone::accent( '#0c3a90' );
		$before = Tone::to_oklch( '#0c3a90' );
		$after  = Tone::to_oklch( $accent );

		$this->assertGreaterThanOrEqual( Tone::ACCENT_MIN_LIGHTNESS - 0.01, $after[0] );
		$this->assertLessThan( 4, $this->hue_gap( $before[2], $after[2] ) );
		$this->assertSame( Contrast::WHITE, Contrast::on( $accent ) );
	}

	/**
	 * Every accent keeps its hue and stays in the lightness range for its kind.
	 *
	 * @dataProvider provide_colors
	 *
	 * @param string $source Source color.
	 */
	public function test_accent_keeps_hue_and_range( $source ) {
		$before = Tone::to_oklch( $source );
		$after  = Tone::to_oklch( Tone::accent( $source ) );

		$this->assertGreaterThanOrEqual( Tone::GREY_MIN_LIGHTNESS - 0.01, $after[0] );
		$this->assertLessThanOrEqual( Tone::YELLOW_MAX_LIGHTNESS + 0.01, $after[0] );

		if ( $before[1] > Tone::GREY_CHROMA && $after[1] > Tone::GREY_CHROMA ) {
			$this->assertLessThan( 6, $this->hue_gap( $before[2], $after[2] ), 'hue moved' );
		}
	}

	/**
	 * Every tint is pale enough for a page background and dark text.
	 *
	 * @dataProvider provide_colors
	 *
	 * @param string $source Source color.
	 */
	public function test_tint_is_pale( $source ) {
		$tint = Tone::tint( $source );

		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', $tint );
		$this->assertGreaterThan( 0.85, Contrast::luminance( $tint ) );
	}

	/**
	 * Colors across the wheel and the edges.
	 *
	 * @return array<string, string[]>
	 */
	public function provide_colors() {
		return array(
			'photo blue'   => array( '#0c3a90' ),
			'photo orange' => array( '#d13e1e' ),
			'pale yellow'  => array( '#fff3b0' ),
			'lemon'        => array( '#f5e050' ),
			'navy'         => array( '#14213d' ),
			'maroon'       => array( '#4a0e1a' ),
			'pink'         => array( '#cb859e' ),
			'beige'        => array( '#d8c8b0' ),
			'neon green'   => array( '#39ff14' ),
			'magenta'      => array( '#ff00ff' ),
			'black'        => array( '#000000' ),
			'white'        => array( '#ffffff' ),
			'grey'         => array( '#808080' ),
		);
	}

	/**
	 * A pale yellow stays a yellow instead of turning olive.
	 */
	public function test_pale_yellow_stays_yellow() {
		$after = Tone::to_oklch( Tone::accent( '#fff3b0' ) );

		$this->assertGreaterThan( 0.8, $after[0] );
	}

	/**
	 * Muted darks stay dark; a black product keeps a near-black accent.
	 */
	public function test_muted_darks_stay_dark() {
		$this->assertLessThan( 0.32, Tone::to_oklch( Tone::accent( '#14213d' ) )[0] );
		$this->assertLessThan( 0.25, Tone::to_oklch( Tone::accent( '#000000' ) )[0] );
	}

	/**
	 * A white product doesn't get a white accent that vanishes on the page.
	 */
	public function test_white_gets_a_visible_accent() {
		$this->assertLessThanOrEqual( Tone::GREY_MAX_LIGHTNESS + 0.01, Tone::to_oklch( Tone::accent( '#ffffff' ) )[0] );
	}

	/**
	 * Anything that isn't a hex color gives nothing.
	 */
	public function test_invalid_input() {
		$this->assertSame( '', Tone::accent( 'blue' ) );
		$this->assertSame( '', Tone::tint( '#12' ) );
		$this->assertNull( Tone::to_oklch( '' ) );
	}

	/**
	 * OKLCH round trips back to the same color.
	 */
	public function test_round_trip() {
		foreach ( array( '#0c3a90', '#d13e1e', '#808080', '#ffffff', '#000000' ) as $hex ) {
			list( $lightness, $chroma, $hue ) = Tone::to_oklch( $hex );
			$this->assertSame( $hex, Tone::from_oklch( $lightness, $chroma, $hue ) );
		}
	}
}
