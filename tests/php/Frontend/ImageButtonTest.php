<?php
/**
 * Tests for the 1.1 image and button options: hover styles on any image,
 * looks on any button and on the add to cart button.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each option becomes a wrapper class on its block, and googly eyes are
 * printed inside the button.
 */
class ImageButtonTest extends WP_UnitTestCase {

	const ADD_TO_CART = '<form class="wp-block-add-to-cart-with-options wc-block-add-to-cart-with-options"><div class="wp-block-button wc-block-components-product-button"><button hidden class="wp-block-button__link wp-element-button single_add_to_cart_button disabled" type="submit" value="308"><span data-wp-text="state.addToCartText">Add to cart</span></button></div></form>';

	/**
	 * Renders a block through the class filter.
	 *
	 * @param string $name  Block name.
	 * @param string $html  Rendered block.
	 * @param array  $saved Saved options.
	 * @return string
	 */
	private function render( $name, $html, $saved ) {
		return ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => $name,
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * The server accepts the attribute on all three blocks.
	 */
	public function test_attribute_registered() {
		foreach ( array( 'core/image', 'core/button', 'woocommerce/add-to-cart-with-options' ) as $name ) {
			$args = ( new BlockEffects() )->add_attribute( array(), $name );

			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * Every gallery hover style works on the image block, and only those.
	 */
	public function test_image_hover() {
		foreach ( BlockEffects::HOVER_STYLES as $style ) {
			$this->assertSame( array( 'purplish-image', 'purplish-image-hover-' . $style ), BlockEffects::classes_for( 'core/image', array( 'hover' => $style ) ), $style );
		}
		$this->assertSame( array(), BlockEffects::classes_for( 'core/image', array( 'hover' => 'magnetic' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/image', array( 'tilt' => true ) ), 'no legacy switch on images' );
	}

	/**
	 * The classes land on the figure, and the photo, link and caption stay
	 * as WordPress printed them.
	 */
	public function test_image_markup_unchanged() {
		$html = '<figure class="wp-block-image size-large has-custom-border"><a href="/x"><img src="x.jpg" alt="" class="wp-image-1" style="border-radius:20px"/></a><figcaption class="wp-element-caption">Cap</figcaption></figure>';
		$out  = $this->render( 'core/image', $html, array( 'hover' => 'loupe' ) );

		$this->assertSame( str_replace( 'has-custom-border"', 'has-custom-border purplish-image purplish-image-hover-loupe"', $html ), $out );
	}

	/**
	 * Each button look is its own key, so they combine.
	 */
	public function test_button_looks() {
		$this->assertSame(
			array( 'purplish-button', 'purplish-button-glow', 'purplish-button-sweep', 'purplish-button-glass', 'purplish-button-spectrum', 'purplish-button-eyes-two' ),
			BlockEffects::classes_for(
				'core/button',
				array(
					'glow'     => true,
					'sweep'    => true,
					'glass'    => true,
					'spectrum' => true,
					'eyes'     => 'two',
				)
			)
		);
		$this->assertSame( array( 'purplish-button', 'purplish-button-eyes-one' ), BlockEffects::classes_for( 'core/button', array( 'eyes' => 'one' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/button', array( 'eyes' => 'three' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/button', array( 'glass' => 'yes' ) ) );
	}

	/**
	 * Classes go on the wrapper, the effect color on its style, and the
	 * eyes inside the link, before the text.
	 */
	public function test_button_markup() {
		$html = '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">Shop <strong>now</strong></a></div>';
		$out  = $this->render(
			'core/button',
			$html,
			array(
				'glow'  => true,
				'eyes'  => 'two',
				'color' => '#7b2cbf',
			)
		);

		$this->assertSame( '<div style="--purplish-effect:#7b2cbf;--purplish-effect-on:#ffffff;" class="wp-block-button purplish-button purplish-button-glow purplish-button-eyes-two"><a class="wp-block-button__link wp-element-button" href="/shop/"><span class="purplish-eyes" aria-hidden="true"><span class="purplish-eye"></span><span class="purplish-eye"></span></span>Shop <strong>now</strong></a></div>', $out );
	}

	/**
	 * A button block printed as a `button` tag gets one eye inside it.
	 */
	public function test_button_tag_one_eye() {
		$html = '<div class="wp-block-button is-style-outline"><button type="button" class="wp-block-button__link wp-element-button">Go</button></div>';
		$out  = $this->render( 'core/button', $html, array( 'eyes' => 'one' ) );

		$this->assertStringContainsString( '<button type="button" class="wp-block-button__link wp-element-button"><span class="purplish-eyes" aria-hidden="true"><span class="purplish-eye"></span></span>Go</button>', $out );
	}

	/**
	 * Without a link inside, the wrapper still gets its classes and nothing
	 * else is printed.
	 */
	public function test_button_without_link() {
		$out = $this->render( 'core/button', '<div class="wp-block-button"></div>', array( 'eyes' => 'two' ) );

		$this->assertSame( '<div class="wp-block-button purplish-button purplish-button-eyes-two"></div>', $out );
	}

	/**
	 * The add to cart looks sit next to 1.0's page options in the same
	 * attribute; the 1.0 keys give no wrapper class.
	 */
	public function test_add_to_cart_looks() {
		$this->assertSame(
			array( 'purplish-cart', 'purplish-cart-glass', 'purplish-cart-spectrum', 'purplish-cart-eyes-one' ),
			BlockEffects::classes_for(
				'woocommerce/add-to-cart-with-options',
				array(
					'parts'    => array( 'button' ),
					'glow'     => true,
					'glass'    => true,
					'spectrum' => true,
					'eyes'     => 'one',
				)
			)
		);

		$saved = array(
			'parts'      => array( 'button', 'chip' ),
			'transition' => 'blur',
			'glow'       => true,
			'halo'       => true,
		);
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/add-to-cart-with-options', $saved ) );
		$this->assertSame( self::ADD_TO_CART, $this->render( 'woocommerce/add-to-cart-with-options', self::ADD_TO_CART, $saved ) );
	}

	/**
	 * The eyes go inside WooCommerce's add to cart button, before the span
	 * whose text WooCommerce swaps.
	 */
	public function test_add_to_cart_eyes() {
		$out = $this->render( 'woocommerce/add-to-cart-with-options', self::ADD_TO_CART, array( 'eyes' => 'two' ) );

		$this->assertStringStartsWith( '<form class="wp-block-add-to-cart-with-options wc-block-add-to-cart-with-options purplish-cart purplish-cart-eyes-two">', $out );
		$this->assertStringContainsString( 'value="308"><span class="purplish-eyes" aria-hidden="true"><span class="purplish-eye"></span><span class="purplish-eye"></span></span><span data-wp-text="state.addToCartText">Add to cart</span></button>', $out );
	}

	/**
	 * WooCommerce prints its notices div around the form. The classes go on
	 * that div and the eyes still find the button inside.
	 */
	public function test_add_to_cart_inside_notices() {
		$html = '<div data-wp-interactive="woocommerce/store-notices" class="wc-block-components-notices alignwide"><template><div class="wc-block-components-notice-banner"><button class="wc-block-components-notice-banner__dismiss">x</button></div></template>' . self::ADD_TO_CART . '</div>';
		$out  = $this->render(
			'woocommerce/add-to-cart-with-options',
			$html,
			array(
				'glass' => true,
				'eyes'  => 'one',
			)
		);

		$this->assertStringStartsWith( '<div data-wp-interactive="woocommerce/store-notices" class="wc-block-components-notices alignwide purplish-cart purplish-cart-glass purplish-cart-eyes-one">', $out );
		$this->assertStringContainsString( '<button class="wc-block-components-notice-banner__dismiss">x</button>', $out );
		$this->assertStringContainsString( 'value="308"><span class="purplish-eyes" aria-hidden="true"><span class="purplish-eye"></span></span><span data-wp-text', $out );
	}
}
