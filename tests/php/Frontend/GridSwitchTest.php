<?php
/**
 * Tests for the grid switch on the Product Collection block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The option becomes a class, and a grid with its own column count gets
 * two buttons and the script that switches it.
 */
class GridSwitchTest extends WP_UnitTestCase {

	/**
	 * A rendered Product Collection.
	 */
	const COLLECTION = '<div class="wp-block-woocommerce-product-collection alignwide" data-wp-router-region="wc-product-collection-0"><ul class="wc-block-product-template__responsive columns-4 wc-block-product-template"><li>Card</li></ul></div>';

	/**
	 * Drops the script between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( BlockEffects::GRID_HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE );
		parent::tear_down();
	}

	/**
	 * Renders the block with the given Purplish options and layout.
	 *
	 * @param array $saved  Purplish options.
	 * @param array $layout The block's displayLayout.
	 * @return string
	 */
	private function render( $saved, $layout ) {
		$effects = new BlockEffects();
		$block   = array(
			'blockName' => 'woocommerce/product-collection',
			'attrs'     => array(
				'purplish'      => $saved,
				'displayLayout' => $layout,
			),
		);
		return $effects->add_grid_switch( $effects->add_classes( self::COLLECTION, $block ), $block );
	}

	/**
	 * A column count from 2 to 6 is the only value, and Roster turns it off.
	 */
	public function test_classes() {
		$this->assertSame( array( 'purplish-cards', 'purplish-cards-grid-2' ), BlockEffects::classes_for( 'woocommerce/product-collection', array( 'grid' => '2' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-collection', array( 'grid' => '9' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-collection', array( 'grid' => 2 ) ) );
		$this->assertSame(
			array( 'purplish-cards', 'purplish-cards-hover-roster' ),
			BlockEffects::classes_for(
				'woocommerce/product-collection',
				array(
					'grid'  => '2',
					'hover' => 'roster',
				)
			)
		);
	}

	/**
	 * The buttons and the recall script print before the grid, own size
	 * pressed, bigger cards first with big squares.
	 */
	public function test_switch() {
		$out = $this->render(
			array( 'grid' => '2' ),
			array(
				'type'    => 'flex',
				'columns' => 4,
			)
		);

		$this->assertMatchesRegularExpression( '#^<div class="purplish-grid-switch" role="group" aria-label="Card size" hidden>#', $out );
		$this->assertMatchesRegularExpression( '#data-size="other" aria-pressed="false" aria-label="Larger cards".*data-size="own" aria-pressed="true" aria-label="Smaller cards"#', $out );
		$this->assertSame( 4 + 9, substr_count( $out, '<rect ' ) );
		$this->assertStringContainsString( 'sessionStorage.getItem("purplish-cards-grid")', $out );
		$this->assertLessThan( strpos( $out, 'wp-block-woocommerce-product-collection' ), strpos( $out, '</script>' ) );
		$this->assertStringContainsString( 'purplish-cards-grid-2', $out );
		$this->assertTrue( wp_script_is( BlockEffects::GRID_HANDLE, 'enqueued' ) );
	}

	/**
	 * A carousel, a list, a grid with no column count, or a second count
	 * that matches the grid's own gets no buttons.
	 */
	public function test_no_switch() {
		$layouts = array(
			array(
				'type'    => 'carousel',
				'columns' => 4,
			),
			array( 'type' => 'list' ),
			array( 'type' => 'flex' ),
			array(
				'type'    => 'flex',
				'columns' => 2,
			),
		);
		foreach ( $layouts as $layout ) {
			$out = $this->render( array( 'grid' => '2' ), $layout );
			$this->assertStringNotContainsString( 'purplish-grid-switch', $out );
		}
		$this->assertFalse( wp_script_is( BlockEffects::GRID_HANDLE, 'enqueued' ) );
	}

	/**
	 * The switch waits for WooCommerce's own filters.
	 */
	public function test_runs_after_woocommerce() {
		$effects = new BlockEffects();
		$effects->register();

		$this->assertSame( 20, has_filter( 'render_block_woocommerce/product-collection', array( $effects, 'add_grid_switch' ) ) );
	}

	/**
	 * Without the option, the grid prints as WooCommerce made it.
	 */
	public function test_off() {
		$out = $this->render(
			array( 'overlay' => true ),
			array(
				'type'    => 'flex',
				'columns' => 4,
			)
		);

		$this->assertStringNotContainsString( 'purplish-grid-switch', $out );
		$this->assertFalse( wp_script_is( BlockEffects::GRID_HANDLE, 'enqueued' ) );
	}
}
