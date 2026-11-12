<?php
/**
 * Tests for Layout: Bar on the Product Filters block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\Markup;
use WP_UnitTestCase;

/**
 * The option becomes a class and loads the filters script. Each plain
 * filter's heading becomes the button that opens the rest of the filter;
 * a filter in an Accordion stays as it is.
 */
class FilterBarTest extends WP_UnitTestCase {

	/**
	 * A plain filter, as WooCommerce inserts it.
	 */
	const PLAIN = '<div data-block-name="woocommerce/product-filter-attribute" class="wp-block-woocommerce-product-filter-attribute"><h3 class="wp-block-heading" style="margin-top:0">Color</h3><div data-block-name="woocommerce/product-filter-checkbox-list" class="wc-block-product-filter-checkbox-list"><input type="checkbox" value="navy"><br><svg viewBox="0 0 4 4"><path d="M0 0h4"/></svg></div></div>';

	/**
	 * A filter in a core Accordion, as Purple's shop pattern builds it.
	 */
	const ACCORDION = '<div data-block-name="woocommerce/product-filter-price" class="wp-block-woocommerce-product-filter-price"><div class="wp-block-accordion"><div class="wp-block-accordion-item"><h4 class="wp-block-accordion-heading"><button type="button" class="wp-block-accordion-heading__toggle">Price</button></h4><div class="wp-block-accordion-panel" hidden="until-found"><div class="wc-block-product-filter-price-slider"></div></div></div></div></div>';

	/**
	 * Active filters, which have no list to open.
	 */
	const ACTIVE = '<div data-block-name="woocommerce/product-filter-active" class="wp-block-woocommerce-product-filter-active"><h3 class="wp-block-heading">Active</h3><div data-block-name="woocommerce/product-filter-removable-chips"></div></div>';

	/**
	 * Drops the script between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( BlockEffects::FILTERS_HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE );
		parent::tear_down();
	}

	/**
	 * The Product Filters block around the given filters.
	 *
	 * @param string $inner Filters.
	 * @return string
	 */
	private static function block( $inner ) {
		return '<div class="wp-block-woocommerce-product-filters wc-block-product-filters is-mobile-overlay" data-wp-router-region="x"><div class="wc-block-product-filters__overlay-content">' . $inner . '</div></div>';
	}

	/**
	 * Renders the block with the given Purplish options.
	 *
	 * @param array  $saved Purplish options.
	 * @param string $inner Filters.
	 * @return string
	 */
	private function render( $saved, $inner ) {
		return ( new BlockEffects() )->add_classes(
			self::block( $inner ),
			array(
				'blockName' => 'woocommerce/product-filters',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * Bar is the only layout value; Sidebar can hide prints nothing beside
	 * it.
	 */
	public function test_classes() {
		$this->assertSame( array( 'purplish-filters', 'purplish-filters-layout-bar' ), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'layout' => 'bar' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'layout' => 'grid' ) ) );
		$this->assertSame(
			array( 'purplish-filters', 'purplish-filters-chips', 'purplish-filters-layout-bar' ),
			BlockEffects::classes_for(
				'woocommerce/product-filters',
				array(
					'chips'  => true,
					'hide'   => true,
					'layout' => 'bar',
				)
			)
		);
	}

	/**
	 * Open on hover prints only with a bar.
	 */
	public function test_hover_needs_a_bar() {
		$this->assertSame(
			array( 'purplish-filters', 'purplish-filters-layout-bar', 'purplish-filters-hover' ),
			BlockEffects::classes_for(
				'woocommerce/product-filters',
				array(
					'layout' => 'bar',
					'hover'  => true,
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'hover' => true ) ) );
	}

	/**
	 * Open style prints only with a bar, and only its known values.
	 */
	public function test_open_style_needs_a_bar() {
		$this->assertSame(
			array( 'purplish-filters', 'purplish-filters-layout-bar', 'purplish-filters-open-fold' ),
			BlockEffects::classes_for(
				'woocommerce/product-filters',
				array(
					'layout' => 'bar',
					'open'   => 'fold',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-filters', 'purplish-filters-layout-bar' ),
			BlockEffects::classes_for(
				'woocommerce/product-filters',
				array(
					'layout' => 'bar',
					'open'   => 'spin',
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'open' => 'grow' ) ) );
	}

	/**
	 * A plain filter's heading holds the button, and the rest of the
	 * filter goes in the panel it opens.
	 */
	public function test_plain_filter() {
		$out = $this->render( array( 'layout' => 'bar' ), self::PLAIN );

		$this->assertMatchesRegularExpression(
			'#<h3 class="wp-block-heading" style="margin-top:0"><button type="button" class="purplish-filters-bar-toggle" aria-expanded="false" aria-controls="(purplish-filter-\d+)">Color</button></h3><div class="purplish-filters-bar-panel" id="\1" hidden><div data-block-name="woocommerce/product-filter-checkbox-list"#',
			$out
		);
		$this->assertStringContainsString( '<path d="M0 0h4"/></svg></div></div></div></div></div>', $out );
		$this->assertStringContainsString( 'purplish-filters-layout-bar', $out );
		$this->assertTrue( wp_script_is( BlockEffects::FILTERS_HANDLE, 'enqueued' ) );
	}

	/**
	 * A filter in an Accordion, and Active filters, stay as they are.
	 */
	public function test_accordion_and_active_unchanged() {
		$out = $this->render( array( 'layout' => 'bar' ), self::ACCORDION . self::ACTIVE );

		$this->assertStringContainsString( self::ACCORDION . self::ACTIVE, $out );
		$this->assertStringNotContainsString( 'purplish-filters-bar-toggle', $out );
	}

	/**
	 * Every plain filter gets its own button and panel.
	 */
	public function test_each_filter() {
		$out = Markup::filter_toggles( self::block( self::PLAIN . self::ACCORDION . str_replace( 'Color', 'Size', self::PLAIN ) ) );

		$this->assertSame( 2, substr_count( $out, 'class="purplish-filters-bar-toggle"' ) );
		$this->assertSame( 2, substr_count( $out, 'class="purplish-filters-bar-panel"' ) );
		$this->assertStringContainsString( '>Size</button></h3>', $out );
		preg_match_all( '#aria-controls="([^"]+)"#', $out, $controls );
		preg_match_all( '#class="purplish-filters-bar-panel" id="([^"]+)"#', $out, $panels );
		$this->assertSame( $controls[1], $panels[1] );
		$this->assertNotSame( $controls[1][0], $controls[1][1] );
	}

	/**
	 * Markup that doesn't close is left alone.
	 */
	public function test_unclosed_markup_unchanged() {
		$broken = '<div data-block-name="woocommerce/product-filter-status"><h3>Stock</h3><div>';

		$this->assertSame( $broken, Markup::filter_toggles( $broken ) );
	}

	/**
	 * Without the option, filters print as WooCommerce made them and no
	 * script loads.
	 */
	public function test_off() {
		$out = $this->render( array( 'chips' => true ), self::PLAIN );

		$this->assertStringNotContainsString( 'purplish-filters-bar-toggle', $out );
		$this->assertFalse( wp_script_is( BlockEffects::FILTERS_HANDLE, 'enqueued' ) );
	}

	/**
	 * A bar has no sidebar, so Sidebar can hide prints no button.
	 */
	public function test_no_hide_button_on_a_bar() {
		$out = $this->render(
			array(
				'layout' => 'bar',
				'hide'   => true,
			),
			self::PLAIN
		);

		$this->assertStringNotContainsString( 'purplish-filters-toggle', $out );
		$this->assertStringNotContainsString( 'purplish-filters-hide', $out );
	}
}
