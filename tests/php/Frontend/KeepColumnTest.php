<?php
/**
 * Tests for Column, keep in view while the photos scroll.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The switch is a class on the Column block that loads the Columns
 * stylesheet and the keep script; without it the column is untouched.
 */
class KeepColumnTest extends WP_UnitTestCase {

	const COLUMN = '<div class="wp-block-column" style="flex-basis:40%"><h1>Title</h1></div>';

	/**
	 * Drops the script and styles between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( BlockEffects::KEEP_HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE . '-columns' );
		wp_dequeue_style( BlockEffects::HANDLE );
		parent::tear_down();
	}

	/**
	 * Renders a Column with the given Purplish options.
	 *
	 * @param array $saved Purplish options.
	 * @return string
	 */
	private function render( $saved ) {
		return ( new BlockEffects() )->add_classes(
			self::COLUMN,
			array(
				'blockName' => 'core/column',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * The switch becomes its class under the Columns prefix; other values
	 * print nothing.
	 */
	public function test_classes() {
		$this->assertSame( array( 'purplish-columns', 'purplish-columns-keep' ), BlockEffects::classes_for( 'core/column', array( 'keep' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/column', array( 'keep' => 'yes' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/column', array( 'keep' => false ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/columns', array( 'keep' => true ) ) );
	}

	/**
	 * With the switch on, the wrapper gets the classes, keeps its markup,
	 * and the Columns stylesheet and the deferred keep script load.
	 */
	public function test_render_with_the_switch() {
		$out = $this->render( array( 'keep' => true ) );

		$this->assertSame( '<div class="wp-block-column purplish-columns purplish-columns-keep" style="flex-basis:40%"><h1>Title</h1></div>', $out );
		$this->assertTrue( wp_style_is( BlockEffects::HANDLE . '-columns' ) );
		$this->assertTrue( wp_script_is( BlockEffects::KEEP_HANDLE, 'enqueued' ) );
		$this->assertSame( 'defer', wp_scripts()->get_data( BlockEffects::KEEP_HANDLE, 'strategy' ) );
		$this->assertStringEndsWith( 'build/keep.js', wp_scripts()->registered[ BlockEffects::KEEP_HANDLE ]->src );
	}

	/**
	 * Without the switch the column is returned as it is and nothing loads.
	 */
	public function test_render_without_the_switch() {
		$this->assertSame( self::COLUMN, $this->render( array() ) );
		$this->assertSame( self::COLUMN, $this->render( array( 'keep' => false ) ) );
		$this->assertFalse( wp_script_is( BlockEffects::KEEP_HANDLE, 'enqueued' ) );
	}

	/**
	 * The server keeps the attribute, and the editor shows the look.
	 */
	public function test_attribute_and_preview() {
		$args = ( new BlockEffects() )->add_attribute( array(), 'core/column' );
		$map  = BlockEffects::preview_map();

		$this->assertSame( 'object', $args['attributes']['purplish']['type'] );
		$this->assertSame( 'columns', $map['core/column']['prefix'] );
		$this->assertSame( array( 'keep' => true ), $map['core/column']['options'] );
	}
}
