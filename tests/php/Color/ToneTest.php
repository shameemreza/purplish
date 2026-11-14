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
	 * The orange from the sweater photo, where black wins on WCAG math by a
	 * little, darkens a touch so white text reaches 4.5:1 and wins.
	 */
	public function test_mid_tone_orange_gets_white_text() {
		$accent = Tone::accent( '#d94421' );
		$before = Tone::to_oklch( '#d94421' );
		$after  = Tone::to_oklch( $accent );

		$this->assertSame( Contrast::BLACK, Contrast::on( '#d94421' ) );
		$this->assertSame( Contrast::WHITE, Contrast::on( $accent ) );
		$this->assertGreaterThanOrEqual( 4.5, Contrast::ratio( $accent, Contrast::WHITE ) );
		$this->assertLessThan( $before[0], $after[0] );
		$this->assertLessThanOrEqual( Tone::WHITE_TEXT_MAX_DROP, $before[0] - $after[0] );
		$this->assertLessThan( 1, $this->hue_gap( $before[2], $after[2] ) );
	}

	/**
	 * Accents that already take white text, or where white is far behind,
	 * don't darken.
	 */
	public function test_white_text_leaves_other_accents() {
		$this->assertSame( '#2a519e', Tone::accent( '#2a519e' ) );
		$this->assertSame( '#2a519e', Tone::accent( '#0d317c' ) );

		$light = array(
			'lavender' => '#c9a0dc',
			'pink'     => '#cb859e',
		);
		foreach ( $light as $name => $source ) {
			$accent = Tone::accent( $source );
			$this->assertSame( Contrast::BLACK, Contrast::on( $accent ), $name );
			$this->assertLessThan( Tone::WHITE_TEXT_MIN_RATIO, Contrast::ratio( $accent, Contrast::WHITE ), $name );
		}
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

	/**
	 * Ink is never lighter than the cap, and keeps dark colors as they are.
	 *
	 * @dataProvider provide_colors
	 *
	 * @param string $source Source color.
	 */
	public function test_ink_is_dark_enough_for_text( $source ) {
		$ink = Tone::to_oklch( Tone::ink( $source ) );

		$this->assertLessThanOrEqual( Tone::INK_MAX_LIGHTNESS + 0.01, $ink[0] );
		if ( Tone::to_oklch( $source )[0] <= Tone::INK_MAX_LIGHTNESS ) {
			$this->assertSame( $source, Tone::ink( $source ) );
		}
	}

	/**
	 * On a dark page the tint is the page lifted a little, so light text
	 * still reads on it.
	 *
	 * @dataProvider provide_colors
	 *
	 * @param string $source Source color.
	 */
	public function test_tint_follows_a_dark_page( $source ) {
		foreach ( array( '#111111', '#1a0e30', '#2d2d2d' ) as $ground ) {
			$tint = Tone::tint( $source, $ground );

			$this->assertEqualsWithDelta( Tone::to_oklch( $ground )[0] + Tone::TINT_DARK_LIFT, Tone::to_oklch( $tint )[0], 0.01 );
			$this->assertSame( Contrast::WHITE, Contrast::on( $tint ) );
			$this->assertGreaterThanOrEqual( 7, Contrast::ratio( '#ffffff', $tint ) );
		}
	}

	/**
	 * On a dark page ink is lightened so it reads on the page.
	 *
	 * @dataProvider provide_colors
	 *
	 * @param string $source Source color.
	 */
	public function test_ink_lightens_on_a_dark_page( $source ) {
		$ink = Tone::ink( $source, '#111111' );

		$this->assertGreaterThanOrEqual( Tone::INK_MIN_LIGHTNESS_DARK - 0.01, Tone::to_oklch( $ink )[0] );
		$this->assertGreaterThanOrEqual( 4.5, Contrast::ratio( $ink, '#111111' ) );
	}

	/**
	 * An empty or broken ground keeps the light page colors.
	 */
	public function test_no_ground_keeps_light_colors() {
		foreach ( array( '', 'nope' ) as $ground ) {
			$this->assertSame( Tone::tint( '#3357b7' ), Tone::tint( '#3357b7', $ground ) );
			$this->assertSame( Tone::ink( '#c9a7ff' ), Tone::ink( '#c9a7ff', $ground ) );
		}
	}
}
