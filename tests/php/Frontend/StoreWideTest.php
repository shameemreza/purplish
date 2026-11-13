<?php
/**
 * Tests for the 1.1 store wide options: matching filter chips and the
 * mini-cart count pop.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each switch becomes a wrapper class on its block.
 */
class StoreWideTest extends WP_UnitTestCase {

	/**
	 * The server accepts the attribute on both blocks.
	 */
	public function test_attribute_registered() {
		foreach ( array( 'woocommerce/product-filters', 'woocommerce/mini-cart' ) as $name ) {
			$args = ( new BlockEffects() )->add_attribute( array(), $name );

			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * Matching chips is a switch on the filters block, the parent of both
	 * the chips and the active filter chips. Anything but `true` is ignored.
	 */
	public function test_filters() {
		$this->assertSame( array( 'purplish-filters', 'purplish-filters-chips' ), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'chips' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'chips' => 'yes' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-filters', array( 'chips' => false ) ) );

		$html = '<div data-block-name="woocommerce/product-filters" class="wp-block-woocommerce-product-filters wc-block-product-filters" data-wp-router-region="wc-product-filters-1"><div class="wc-block-product-filters__overlay"><div data-block-name="woocommerce/product-filter-active" class="wp-block-woocommerce-product-filter-active"></div></div></div>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/product-filters',
				'attrs'     => array( 'purplish' => array( 'chips' => true ) ),
			)
		);

		$this->assertSame( str_replace( 'wc-block-product-filters"', 'wc-block-product-filters purplish-filters purplish-filters-chips"', $html ), $out );
	}

	/**
	 * Matching chips stays on the filters block. The chips on their own,
	 * as Add to Cart + Options also uses them on the product page, only
	 * take Names under swatches.
	 */
	public function test_chips_block_is_left_alone() {
		$this->assertSame( array( 'names' ), array_keys( BlockEffects::blocks()['woocommerce/product-filter-chips']['options'] ) );

		$html = '<div class="wp-block-woocommerce-product-filter-chips wc-block-product-filter-chips"></div>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/product-filter-chips',
				'attrs'     => array( 'purplish' => array( 'chips' => true ) ),
			)
		);

		$this->assertSame( $html, $out );
	}

	/**
	 * The count pop lands on the mini-cart's outer element, around the
	 * button and its badge.
	 */
	public function test_mini_cart() {
		$this->assertSame( array( 'purplish-minicart', 'purplish-minicart-pop' ), BlockEffects::classes_for( 'woocommerce/mini-cart', array( 'pop' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/mini-cart', array( 'pop' => 1 ) ) );

		$html = '<div data-wp-interactive="woocommerce/mini-cart" class="wc-block-mini-cart wp-block-woocommerce-mini-cart " style=""><button class="wc-block-mini-cart__button"><span class="wc-block-mini-cart__quantity-badge"><span class="wc-block-mini-cart__badge">2</span></span></button></div>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/mini-cart',
				'attrs'     => array( 'purplish' => array( 'pop' => true ) ),
			)
		);

		$this->assertStringStartsWith( '<div data-wp-interactive="woocommerce/mini-cart" class="wc-block-mini-cart wp-block-woocommerce-mini-cart purplish-minicart purplish-minicart-pop" style="">', $out );
		$this->assertStringContainsString( '<span class="wc-block-mini-cart__badge">2</span>', $out );
	}
}
