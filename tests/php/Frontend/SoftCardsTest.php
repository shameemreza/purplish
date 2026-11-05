<?php
/**
 * Tests for the 1.1 soft cards: WooCommerce's accordion, details, reviews
 * and specifications.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each look becomes a wrapper class on its block.
 */
class SoftCardsTest extends WP_UnitTestCase {

	/**
	 * The server accepts the attribute on all four blocks.
	 */
	public function test_attribute_registered() {
		foreach ( array( 'woocommerce/accordion-group', 'core/details', 'woocommerce/product-reviews', 'woocommerce/product-specifications' ) as $name ) {
			$args = ( new BlockEffects() )->add_attribute( array(), $name );

			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * WooCommerce's accordion prints the same class as 1.0's core accordion,
	 * so the soft style is one look in both.
	 */
	public function test_woocommerce_accordion_shares_the_core_class() {
		$this->assertSame(
			BlockEffects::classes_for( 'core/accordion', array( 'soft' => true ) ),
			BlockEffects::classes_for( 'woocommerce/accordion-group', array( 'soft' => true ) )
		);
		$this->assertSame( array( 'purplish-accordion', 'purplish-accordion-soft' ), BlockEffects::classes_for( 'woocommerce/accordion-group', array( 'soft' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/accordion-group', array( 'soft' => 'yes' ) ) );

		$html = '<div class="wp-block-woocommerce-accordion-group"><div class="wp-block-woocommerce-accordion-item"></div></div>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/accordion-group',
				'attrs'     => array( 'purplish' => array( 'soft' => true ) ),
			)
		);

		$this->assertSame( '<div class="wp-block-woocommerce-accordion-group purplish-accordion purplish-accordion-soft"><div class="wp-block-woocommerce-accordion-item"></div></div>', $out );
	}

	/**
	 * The soft details switch lands on the `<details>` element itself.
	 */
	public function test_details() {
		$this->assertSame( array( 'purplish-details', 'purplish-details-soft' ), BlockEffects::classes_for( 'core/details', array( 'soft' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/details', array( 'soft' => false ) ) );

		$out = ( new BlockEffects() )->add_classes(
			'<details class="wp-block-details"><summary>Shipping</summary><p>Two days.</p></details>',
			array(
				'blockName' => 'core/details',
				'attrs'     => array( 'purplish' => array( 'soft' => true ) ),
			)
		);

		$this->assertStringStartsWith( '<details class="wp-block-details purplish-details purplish-details-soft">', $out );
	}

	/**
	 * Review styles become classes on the reviews wrapper; others are
	 * ignored.
	 */
	public function test_reviews() {
		foreach ( array( 'cards', 'bubbles' ) as $style ) {
			$this->assertSame(
				array( 'purplish-reviews', 'purplish-reviews-style-' . $style ),
				BlockEffects::classes_for( 'woocommerce/product-reviews', array( 'style' => $style ) )
			);
		}

		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-reviews', array( 'style' => 'quotes' ) ) );

		$html = '<div class="wp-block-woocommerce-product-reviews"><ol class="wp-block-woocommerce-product-review-template"><li id="comment-1" class="review"></li></ol></div>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/product-reviews',
				'attrs'     => array( 'purplish' => array( 'style' => 'bubbles' ) ),
			)
		);

		$this->assertStringStartsWith( '<div class="wp-block-woocommerce-product-reviews purplish-reviews purplish-reviews-style-bubbles"><ol class="wp-block-woocommerce-product-review-template">', $out );
	}

	/**
	 * Table styles become classes on the specifications figure; others are
	 * ignored.
	 */
	public function test_specifications() {
		foreach ( array( 'striped', 'cards' ) as $style ) {
			$this->assertSame(
				array( 'purplish-specs', 'purplish-specs-style-' . $style ),
				BlockEffects::classes_for( 'woocommerce/product-specifications', array( 'style' => $style ) )
			);
		}

		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/product-specifications', array( 'style' => true ) ) );

		$html = '<figure class="wp-block-table wp-block-woocommerce-product-specifications"><table><tbody><tr class="wp-block-product-specifications-item"><th scope="row">Weight</th><td>1 kg</td></tr></tbody></table></figure>';
		$out  = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/product-specifications',
				'attrs'     => array( 'purplish' => array( 'style' => 'striped' ) ),
			)
		);

		$this->assertSame( str_replace( 'wp-block-woocommerce-product-specifications"', 'wp-block-woocommerce-product-specifications purplish-specs purplish-specs-style-striped"', $html ), $out );
	}
}
