<?php
/**
 * Tests for the 1.1 product page looks: grayed-out chips, sale badge and
 * thumbnails.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each look becomes a wrapper class on its WooCommerce block.
 */
class ProductPageLooksTest extends WP_UnitTestCase {

	const ATTRIBUTE = 'woocommerce/add-to-cart-with-options-variation-selector-attribute';

	/**
	 * The server accepts the attribute on all three blocks.
	 */
	public function test_attribute_registered() {
		foreach ( array( self::ATTRIBUTE, 'woocommerce/product-sale-badge', 'woocommerce/product-gallery-thumbnails' ) as $name ) {
			$args = ( new BlockEffects() )->add_attribute( array(), $name );

			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * Each grayed-out look becomes its class; WooCommerce's own fade and
	 * hide are not Purplish values.
	 */
	public function test_grayed_out_chips() {
		foreach ( array( 'cross', 'blur' ) as $look ) {
			$this->assertSame(
				array( 'purplish-chips', 'purplish-chips-soldout-' . $look ),
				BlockEffects::classes_for( self::ATTRIBUTE, array( 'soldout' => $look ) )
			);
		}

		foreach ( array( 'fade', 'hide', true, '' ) as $look ) {
			$this->assertSame( array(), BlockEffects::classes_for( self::ATTRIBUTE, array( 'soldout' => $look ) ) );
		}
	}

	/**
	 * The template block prints one wrapper per attribute. Every row wrapper
	 * gets the classes, and the blocks inside the rows don't.
	 */
	public function test_every_attribute_row_gets_the_classes() {
		$row  = '<div class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute" data-wp-interactive="woocommerce/add-to-cart-with-options">'
			. '<div class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name">Size</div>'
			. '<div class="wp-block-woocommerce-product-filter-chips"><button class="wc-block-product-filter-chips__item" disabled>S</button></div>'
			. '<input type="hidden" name="attribute_size" value="" /></div>';
		$html = $row . str_replace( 'Size', 'Color', $row );

		$out = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => self::ATTRIBUTE,
				'attrs'     => array( 'purplish' => array( 'soldout' => 'cross' ) ),
			)
		);

		$this->assertSame( 2, substr_count( $out, 'class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute purplish-chips purplish-chips-soldout-cross"' ) );
		$this->assertSame( 2, substr_count( $out, 'purplish-chips-soldout-cross' ) );
		$this->assertSame( $html, str_replace( ' purplish-chips purplish-chips-soldout-cross', '', $out ) );
	}

	/**
	 * Badge shape and corner become classes on the badge block's wrapper,
	 * and values outside the lists are ignored.
	 */
	public function test_sale_badge() {
		foreach ( array( 'pill', 'circle', 'tag' ) as $shape ) {
			foreach ( array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ) as $corner ) {
				$this->assertSame(
					array( 'purplish-badge', 'purplish-badge-shape-' . $shape, 'purplish-badge-corner-' . $corner ),
					BlockEffects::classes_for(
						'woocommerce/product-sale-badge',
						array(
							'shape'  => $shape,
							'corner' => $corner,
						)
					)
				);
			}
		}

		$this->assertSame(
			array(),
			BlockEffects::classes_for(
				'woocommerce/product-sale-badge',
				array(
					'shape'  => 'star',
					'corner' => 'middle',
				)
			)
		);

		$html = '<div class="wp-block-woocommerce-product-sale-badge "><div class="wc-block-components-product-sale-badge wc-block-components-product-sale-badge--align-right" style=""><span class="wc-block-components-product-sale-badge__text" aria-hidden="true">Sale</span></div></div>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/product-sale-badge',
				'attrs'     => array( 'purplish' => array( 'shape' => 'circle' ) ),
			)
		);

		$this->assertStringStartsWith( '<div class="wp-block-woocommerce-product-sale-badge purplish-badge purplish-badge-shape-circle">', $out );
		$this->assertStringContainsString( '<div class="wc-block-components-product-sale-badge wc-block-components-product-sale-badge--align-right" style="">', $out );
	}

	/**
	 * Thumbnail shapes become classes; others are ignored.
	 */
	public function test_thumbnails() {
		foreach ( array( 'rounded', 'circle' ) as $shape ) {
			$this->assertSame(
				array( 'purplish-thumbs', 'purplish-thumbs-shape-' . $shape ),
				BlockEffects::classes_for( 'woocommerce/product-gallery-thumbnails', array( 'shape' => $shape ) )
			);
		}

		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-gallery-thumbnails', array( 'shape' => 'hexagon' ) ) );
	}

	/**
	 * Blocks that print one wrapper still get the classes on the first tag
	 * only, as in 1.0.
	 */
	public function test_single_wrapper_blocks_keep_the_first_tag_only() {
		$html = '<div class="wc-block-product-gallery-thumbnails"><div class="wc-block-product-gallery-thumbnails"></div></div>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/product-gallery-thumbnails',
				'attrs'     => array( 'purplish' => array( 'shape' => 'rounded' ) ),
			)
		);

		$this->assertSame( 1, substr_count( $out, 'purplish-thumbs-shape-rounded' ) );
		$this->assertStringStartsWith( '<div class="wc-block-product-gallery-thumbnails purplish-thumbs purplish-thumbs-shape-rounded">', $out );
	}
}
