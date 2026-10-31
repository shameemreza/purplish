<?php
/**
 * Tests for the variation color order.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\Resolver;
use Purplish\Color\Tone;
use Purplish\Meta;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * Custom, then term swatch, then auto. Off means none.
 */
class ResolverTest extends WP_UnitTestCase {

	/**
	 * With nothing saved, a variation keeps the theme colors.
	 */
	public function test_no_color_by_default() {
		$variation = Products::sweater()['Orange/S'];

		$this->assertSame( '', Resolver::color( $variation ) );
	}

	/**
	 * Automatic mode uses the color read from the photo.
	 */
	public function test_auto_color() {
		$variation = Products::sweater()['Orange/S'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#dc4a24' );
		$variation->save();

		$this->assertSame( '#dc4a24', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * Custom mode beats the photo color.
	 */
	public function test_custom_beats_auto() {
		$variation = Products::sweater()['Orange/S'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#dc4a24' );
		$variation->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$variation->update_meta_data( Meta::CUSTOM_COLOR, '#7b2cbf' );
		$variation->save();

		$this->assertSame( '#7b2cbf', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * A custom color saved earlier is ignored once the mode is Automatic.
	 */
	public function test_custom_color_ignored_in_auto_mode() {
		$variation = Products::sweater()['Orange/S'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#dc4a24' );
		$variation->update_meta_data( Meta::CUSTOM_COLOR, '#7b2cbf' );
		$variation->save();

		$this->assertSame( '#dc4a24', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * Off returns nothing, even with every other color saved.
	 */
	public function test_off_returns_nothing() {
		$variation = Products::sweater()['Orange/S'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#dc4a24' );
		$variation->update_meta_data( Meta::CUSTOM_COLOR, '#7b2cbf' );
		$variation->update_meta_data( Meta::MODE, Meta::MODE_OFF );
		$variation->save();

		$this->assertSame( '', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * WooCommerce's swatch color for the term beats the photo color.
	 */
	public function test_term_color_beats_auto() {
		$variation = Products::with_global_color( array( 'Teal' => '#008080' ) )['teal'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#dc4a24' );
		$variation->save();

		$this->assertSame( '#008080', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * Custom beats the term swatch color.
	 */
	public function test_custom_beats_term_color() {
		$variation = Products::with_global_color( array( 'Teal' => '#008080' ) )['teal'];
		$variation->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$variation->update_meta_data( Meta::CUSTOM_COLOR, '#7b2cbf' );
		$variation->save();

		$this->assertSame( '#7b2cbf', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * A term without a swatch color falls through to the photo color.
	 */
	public function test_term_without_swatch_falls_through() {
		$variation = Products::with_global_color( array( 'Plain' => '' ) )['plain'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#3357b7' );
		$variation->save();

		$this->assertSame( '#3357b7', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * Custom mode with no valid color saved falls through to the next source.
	 */
	public function test_custom_without_color_falls_through() {
		$variation = Products::sweater()['Orange/S'];
		$variation->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$variation->update_meta_data( Meta::CUSTOM_COLOR, 'red; background: url(x)' );
		$variation->update_meta_data( Meta::AUTO_COLOR, '#dc4a24' );
		$variation->save();

		$this->assertSame( '#dc4a24', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * A broken term meta value is ignored.
	 */
	public function test_invalid_term_color_is_ignored() {
		$variation = Products::with_global_color( array( 'Broken' => 'not-a-color' ) )['broken'];

		$this->assertSame( '', Resolver::color( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * A photo color is tuned for the page; the raw color stays the record.
	 */
	public function test_accent_tunes_photo_colors() {
		$variation = Products::sweater()['Blue/S'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#0c3a90' );
		$variation->save();
		$variation = wc_get_product( $variation->get_id() );

		$this->assertSame( '#0c3a90', Resolver::color( $variation ) );
		$this->assertSame( Tone::accent( '#0c3a90' ), Resolver::accent( $variation ) );
		$this->assertNotSame( '#0c3a90', Resolver::accent( $variation ) );
	}

	/**
	 * A custom color is the merchant's choice and is never tuned.
	 */
	public function test_accent_keeps_custom_colors() {
		$variation = Products::sweater()['Blue/S'];
		$variation->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$variation->update_meta_data( Meta::CUSTOM_COLOR, '#0c3a90' );
		$variation->save();

		$this->assertSame( '#0c3a90', Resolver::accent( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * A swatch color is the merchant's choice and is never tuned.
	 */
	public function test_accent_keeps_swatch_colors() {
		$variation = Products::with_global_color( array( 'Night' => '#0c3a90' ) )['night'];

		$this->assertSame( '#0c3a90', Resolver::accent( wc_get_product( $variation->get_id() ) ) );
	}

	/**
	 * Off gives no accent.
	 */
	public function test_accent_off() {
		$variation = Products::sweater()['Blue/S'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#0c3a90' );
		$variation->update_meta_data( Meta::MODE, Meta::MODE_OFF );
		$variation->save();

		$this->assertSame( '', Resolver::accent( wc_get_product( $variation->get_id() ) ) );
	}
}
