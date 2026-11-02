<?php
/**
 * Tests for the page's color data.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\PageData;
use Purplish\Meta;
use Purplish\Options;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * The data lists each colored variation, and the default follows the
 * product's default attributes.
 */
class PageDataTest extends WP_UnitTestCase {

	/**
	 * Sweater variations as the page data lists them.
	 *
	 * @return array
	 */
	private function sweater_data() {
		$orange = PageData::color_set( '#d94421' );
		$blue   = PageData::color_set( '#2a519e' );
		return array(
			array(
				'attributes' => array(
					'attribute_color' => 'Orange',
					'attribute_size'  => 'S',
				),
			) + $orange,
			array(
				'attributes' => array(
					'attribute_color' => 'Orange',
					'attribute_size'  => 'M',
				),
			) + $orange,
			array(
				'attributes' => array(
					'attribute_color' => 'Blue',
					'attribute_size'  => 'S',
				),
			) + $blue,
			array(
				'attributes' => array(
					'attribute_color' => 'Blue',
					'attribute_size'  => 'M',
				),
			) + $blue,
		);
	}

	/**
	 * Picking a color before a size already gives that color.
	 */
	public function test_color_before_size() {
		$set = PageData::match(
			$this->sweater_data(),
			array(
				'attribute_color' => 'Blue',
				'attribute_size'  => '',
			)
		);

		$this->assertSame( '#2a519e', $set['color'] );
	}

	/**
	 * A full selection gives its variation's color.
	 */
	public function test_full_selection() {
		$set = PageData::match(
			$this->sweater_data(),
			array(
				'attribute_color' => 'Orange',
				'attribute_size'  => 'M',
			)
		);

		$this->assertSame( '#d94421', $set['color'] );
		$this->assertSame( '#000000', $set['on'] );
	}

	/**
	 * A size alone matches both colors, so there is no single color.
	 */
	public function test_size_alone_is_ambiguous() {
		$this->assertNull( PageData::match( $this->sweater_data(), array( 'attribute_size' => 'S' ) ) );
	}

	/**
	 * Nothing picked gives nothing, so the page uses its default.
	 */
	public function test_nothing_picked() {
		$this->assertNull(
			PageData::match(
				$this->sweater_data(),
				array(
					'attribute_color' => '',
					'attribute_size'  => '',
				)
			)
		);
	}

	/**
	 * An empty value in a variation means "any".
	 */
	public function test_any_value_in_a_variation() {
		$variations = array(
			array(
				'attributes' => array(
					'attribute_color' => 'Blue',
					'attribute_size'  => '',
				),
			) + PageData::color_set( '#2a519e' ),
		);

		$set = PageData::match(
			$variations,
			array(
				'attribute_color' => 'Blue',
				'attribute_size'  => 'XL',
			)
		);

		$this->assertSame( '#2a519e', $set['color'] );
	}

	/**
	 * A value no variation has gives nothing.
	 */
	public function test_unknown_value() {
		$this->assertNull( PageData::match( $this->sweater_data(), array( 'attribute_color' => 'Green' ) ) );
	}

	/**
	 * The data lists colored variations and the default from the product's
	 * default attributes.
	 */
	public function test_for_product() {
		$variations = Products::sweater();
		foreach ( $variations as $key => $variation ) {
			$variation->update_meta_data( Meta::AUTO_COLOR, 0 === strpos( $key, 'Orange' ) ? '#d94421' : '#0d317c' );
			$variation->save();
		}
		$product = wc_get_product( reset( $variations )->get_parent_id() );
		$product->set_default_attributes(
			array(
				'color' => 'Blue',
				'size'  => 'M',
			)
		);
		$product->save();

		$data = PageData::for_product( wc_get_product( $product->get_id() ), Options::defaults() );

		$this->assertCount( 4, $data['variations'] );
		$this->assertSame( '#ffffff', $data['default']['on'] );
		$this->assertNotSame( '#0d317c', $data['default']['color'], 'photo colors are tuned' );
		$this->assertSame( array( 'button', 'chip', 'badge' ), $data['options']['parts'] );
	}

	/**
	 * Variations set to Off and products without colors are left out.
	 */
	public function test_off_and_uncolored() {
		$variations = Products::sweater();
		$product    = wc_get_product( reset( $variations )->get_parent_id() );

		$this->assertNull( PageData::for_product( $product, Options::defaults() ), 'no colors at all' );

		$variations['Orange/S']->update_meta_data( Meta::AUTO_COLOR, '#d94421' );
		$variations['Orange/S']->save();
		$variations['Orange/M']->update_meta_data( Meta::AUTO_COLOR, '#d94421' );
		$variations['Orange/M']->update_meta_data( Meta::MODE, Meta::MODE_OFF );
		$variations['Orange/M']->save();

		$data = PageData::for_product( wc_get_product( $product->get_id() ), Options::defaults() );
		$this->assertCount( 1, $data['variations'] );
		$this->assertNull( $data['default'], 'no default attributes' );
	}

	/**
	 * A simple product without a color gets no data.
	 */
	public function test_simple_product_without_color() {
		$product = new \WC_Product_Simple();
		$product->save();

		$this->assertNull( PageData::for_product( $product, Options::defaults() ) );
	}

	/**
	 * A simple product's own color is the default, with no variations.
	 */
	public function test_simple_product_with_color() {
		$product = new \WC_Product_Simple();
		$product->update_meta_data( Meta::AUTO_COLOR, '#0d317c' );
		$product->save();

		$data = PageData::for_product( wc_get_product( $product->get_id() ), Options::defaults() );

		$this->assertSame( array(), $data['variations'] );
		$this->assertSame( '#ffffff', $data['default']['on'] );
		$this->assertNotSame( '#0d317c', $data['default']['color'], 'photo colors are tuned' );
	}

	/**
	 * Custom and Off work on simple products.
	 */
	public function test_simple_product_custom_and_off() {
		$product = new \WC_Product_Simple();
		$product->update_meta_data( Meta::AUTO_COLOR, '#0d317c' );
		$product->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$product->update_meta_data( Meta::CUSTOM_COLOR, '#7b2cbf' );
		$product->save();

		$this->assertSame( '#7b2cbf', PageData::for_product( wc_get_product( $product->get_id() ), Options::defaults() )['default']['color'] );

		$product->update_meta_data( Meta::MODE, Meta::MODE_OFF );
		$product->save();
		$this->assertNull( PageData::for_product( wc_get_product( $product->get_id() ), Options::defaults() ) );
	}

	/**
	 * Options are cleaned against the allowlist.
	 */
	public function test_options_are_cleaned() {
		$clean = Options::clean(
			array(
				'parts'      => array( 'button', 'tint', 'everything', '<b>' ),
				'transition' => 'explode',
			)
		);

		$this->assertSame( array( 'button', 'tint' ), $clean['parts'] );
		$this->assertSame( 'blur', $clean['transition'] );
		$this->assertSame( Options::defaults(), Options::clean( 'nope' ) );
	}

	/**
	 * Every photo transition survives cleaning.
	 */
	public function test_every_transition_is_allowed() {
		foreach ( array( 'none', 'fade', 'blur', 'zoom', 'slide', 'flip', 'wipe', 'circle', 'wash' ) as $transition ) {
			$this->assertSame( $transition, Options::clean( array( 'transition' => $transition ) )['transition'] );
		}
	}

	/**
	 * A color option gets a dot; a size that comes in two colors gets none.
	 */
	public function test_dot_colors() {
		$dots = PageData::dot_colors( $this->sweater_data() );

		$this->assertSame( '#d94421', $dots['attribute_color-orange'] );
		$this->assertSame( '#2a519e', $dots['attribute_color-blue'] );
		$this->assertArrayNotHasKey( 'attribute_size-s', $dots );
	}

	/**
	 * Rise and dots are off by default and switch on from saved options.
	 */
	public function test_rise_and_dots_switches() {
		$this->assertFalse( Options::defaults()['rise'] );
		$this->assertFalse( Options::defaults()['dots'] );

		$clean = Options::clean(
			array(
				'rise' => true,
				'dots' => 'yes',
			)
		);
		$this->assertTrue( $clean['rise'] );
		$this->assertTrue( $clean['dots'] );
		$this->assertFalse( Options::clean( array( 'rise' => 'maybe' ) )['rise'] );
	}

	/**
	 * The motion switches are off by default, switch on from saved options,
	 * and each one puts its class on the product page's body.
	 */
	public function test_motion_switches() {
		foreach ( array( 'focus', 'glow', 'halo', 'mesh' ) as $switch ) {
			$this->assertFalse( Options::defaults()[ $switch ], $switch );
			$this->assertTrue( Options::clean( array( $switch => true ) )[ $switch ], $switch );
			$this->assertFalse( Options::clean( array( $switch => 'maybe' ) )[ $switch ], $switch );
		}

		$product = new \WC_Product_Simple();
		$product->update_meta_data( Meta::AUTO_COLOR, '#0d317c' );
		$product->save();
		$this->go_to( get_permalink( $product->get_id() ) );

		$block_options = new \ReflectionProperty( PageData::class, 'block_options' );
		$block_options->setAccessible( true );
		try {
			PageData::use_block_options(
				array(
					'focus' => true,
					'glow'  => true,
					'halo'  => false,
					'mesh'  => true,
				)
			);
			$classes = ( new PageData() )->body_class( array() );
		} finally {
			$block_options->setValue( null, null );
		}

		$this->assertContains( 'purplish-focus', $classes );
		$this->assertContains( 'purplish-glow', $classes );
		$this->assertContains( 'purplish-mesh', $classes );
		$this->assertNotContains( 'purplish-halo', $classes );
		$this->assertNotContains( 'purplish-rise', $classes );
	}
}
