<?php
/**
 * Tests for Sidebar can hide on the Product Filters block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The option prints a hidden button before the block and loads its script,
 * except when the overlay covers every screen.
 */
class FiltersHideTest extends WP_UnitTestCase {

	/**
	 * The block's rendered wrapper.
	 */
	const HTML = '<div class="wp-block-woocommerce-product-filters wc-block-product-filters" data-wp-router-region="x"><div class="wc-block-product-filters__content">Filters</div></div>';

	/**
	 * Drops the script and the translation filter between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( BlockEffects::FILTERS_HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE );
		remove_all_filters( 'gettext' );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Renders the block with the given attributes.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function render( $attrs ) {
		return ( new BlockEffects() )->add_classes(
			self::HTML,
			array(
				'blockName' => 'woocommerce/product-filters',
				'attrs'     => $attrs,
			)
		);
	}

	/**
	 * The switch becomes a class, beside Matching chips.
	 */
	public function test_classes() {
		$this->assertSame( array( 'purplish-filters', 'purplish-filters-hide' ), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'hide' => true ) ) );
		$this->assertSame(
			array( 'purplish-filters', 'purplish-filters-chips', 'purplish-filters-hide' ),
			BlockEffects::classes_for(
				'woocommerce/product-filters',
				array(
					'chips' => true,
					'hide'  => true,
				)
			)
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'hide' => 'yes' ) ) );
	}

	/**
	 * The button comes before the block, outside the part WooCommerce
	 * redraws, hidden until the script places it.
	 */
	public function test_button_before_the_block() {
		$out = $this->render( array( 'purplish' => array( 'hide' => true ) ) );

		$this->assertStringStartsWith( '<button type="button" class="purplish-filters-toggle" aria-expanded="true" data-hide="Hide filters" data-show="Show filters" hidden>', $out );
		$this->assertStringContainsString( '<span class="purplish-filters-toggle__text">Hide filters</span></button><div class="wp-block-woocommerce-product-filters wc-block-product-filters purplish-filters purplish-filters-hide"', $out );
		$this->assertStringContainsString( 'aria-hidden="true" focusable="false"', $out );
		$this->assertSame( 1, substr_count( $out, '<button' ) );
		$this->assertTrue( wp_script_is( BlockEffects::FILTERS_HANDLE, 'enqueued' ) );
	}

	/**
	 * Translated words are escaped.
	 */
	public function test_words_escaped() {
		add_filter(
			'gettext',
			function ( $text ) {
				return 'Hide filters' === $text ? 'Hide "x" <b>' : $text;
			}
		);

		$out = $this->render( array( 'purplish' => array( 'hide' => true ) ) );

		$this->assertStringContainsString( 'data-hide="Hide &quot;x&quot; &lt;b&gt;"', $out );
		$this->assertStringContainsString( '>Hide &quot;x&quot; &lt;b&gt;</span>', $out );
	}

	/**
	 * With the overlay on every screen there's no sidebar, so no button
	 * and no script.
	 */
	public function test_overlay_always() {
		$out = $this->render(
			array(
				'overlayMode' => 'always',
				'purplish'    => array( 'hide' => true ),
			)
		);

		$this->assertStringNotContainsString( '<button', $out );
		$this->assertFalse( wp_script_is( BlockEffects::FILTERS_HANDLE, 'enqueued' ) );

		$mobile = $this->render(
			array(
				'overlayMode' => 'mobile',
				'purplish'    => array( 'hide' => true ),
			)
		);
		$this->assertStringContainsString( '<button', $mobile );
	}

	/**
	 * Without the switch, nothing is printed or loaded.
	 */
	public function test_off() {
		$out = $this->render( array( 'purplish' => array( 'chips' => true ) ) );

		$this->assertStringNotContainsString( '<button', $out );
		$this->assertFalse( wp_script_is( BlockEffects::FILTERS_HANDLE, 'enqueued' ) );
	}

	/**
	 * The editor gets the button's words for its look in the canvas, with
	 * nothing that could end the CSS string or the style tag.
	 */
	public function test_editor_label() {
		set_current_screen( 'post' );
		add_filter(
			'gettext',
			function ( $text ) {
				return 'Hide filters' === $text ? 'Hide "x" \\ </style><b>' : $text;
			}
		);

		( new BlockEffects() )->enqueue_editor_styles();

		$after = implode( '', (array) wp_styles()->get_data( BlockEffects::HANDLE, 'after' ) );
		$this->assertStringContainsString( ':root{--purplish-filters-label:"Hide x ";}', $after );
	}
}
