<?php
/**
 * Tests for the trade-in choice in the Add to Cart + Options block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\TradeInField;
use Purplish\TradeIn;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * The choice goes in the block's form before the quantity row only when
 * the block option is on, the product takes trade-in and the table has a
 * device. It works without JavaScript: radios and one grouped select.
 */
class TradeInFieldTest extends WP_UnitTestCase {

	/**
	 * Puts options and assets back after each test.
	 */
	public function tear_down() {
		delete_option( TradeIn::OPTION );
		delete_option( TradeIn::DEVICES );
		wp_dequeue_style( TradeInField::HANDLE );
		wp_dequeue_script( TradeInField::HANDLE );
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * A saved simple product.
	 *
	 * @return int Product ID.
	 */
	private function product() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( '4599' );
		return $product->save();
	}

	/**
	 * The block's form, shaped like WooCommerce 11.3 prints it.
	 *
	 * @return string
	 */
	private function html() {
		return '<div class="wp-block-woocommerce-add-to-cart-with-options"><form data-wp-on--submit="actions.addToCart">'
			. '<div class="wp-block-group is-layout-flex"><div data-block-name="woocommerce/add-to-cart-with-options-quantity-selector" class="q">qty</div>'
			. '<div data-block-name="woocommerce/product-button" class="b">button</div></div></form></div>';
	}

	/**
	 * Renders the block on the product's page.
	 *
	 * @param array $saved Purplish options.
	 * @param int   $id    Product ID.
	 * @return string
	 */
	private function render( $saved, $id ) {
		$GLOBALS['post'] = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The product page.
		return TradeInField::render(
			$this->html(),
			array(
				'blockName' => TradeInField::BLOCK,
				'attrs'     => array( 'purplish' => $saved ),
			),
			new \WP_Block( array( 'blockName' => TradeInField::BLOCK ), array( 'postId' => $id ) )
		);
	}

	/**
	 * Off, no devices, or a product without trade-in: nothing changes.
	 */
	public function test_needs_option_devices_and_product() {
		$id = $this->product();
		$this->assertSame( $this->html(), $this->render( array( 'tradeIn' => true ), $id ), 'no devices' );

		TradeIn::save_devices( array( array( 'Apple', 'iPhone 13', '128GB', 600, 680 ) ) );
		$this->assertSame( $this->html(), $this->render( array(), $id ), 'option off' );
		$this->assertSame( $this->html(), $this->render( array( 'tradeIn' => 'yes' ), $id ), 'only true turns it on' );

		update_post_meta( $id, TradeIn::META, 'off' );
		$this->assertSame( $this->html(), $this->render( array( 'tradeIn' => true ), $id ), 'product off' );
		$this->assertFalse( wp_script_is( TradeInField::HANDLE, 'enqueued' ) );
	}

	/**
	 * The choice goes before the quantity and button row, posts without
	 * JavaScript, and loads its assets.
	 */
	public function test_renders_before_quantity_row() {
		$id = $this->product();
		TradeIn::save_devices(
			array(
				array( 'Apple', 'iPhone 13', '128GB', 600, 680 ),
				array( 'Samsung', 'Galaxy S23', '', 900, 900 ),
			)
		);
		$html = $this->render( array( 'tradeIn' => true ), $id );

		$this->assertLessThan( strpos( $html, '<div class="wp-block-group' ), strpos( $html, '<fieldset class="purplish-trade-in"' ) );
		$this->assertGreaterThan( strpos( $html, '<form' ), strpos( $html, '<fieldset' ) );
		$this->assertMatchesRegularExpression( '/<input type="radio" name="purplish_trade_in" value="no" checked>/', $html, 'No is picked by default' );
		$this->assertStringContainsString( 'name="purplish_trade_in_device"', $html );
		$this->assertStringContainsString( '<optgroup label="Apple"><option value="' . TradeIn::key( TradeIn::devices()[0] ) . '"', $html );
		$this->assertStringContainsString( '>iPhone 13, 128GB</option>', $html );
		$this->assertStringContainsString( '>Galaxy S23</option>', $html );
		$this->assertStringContainsString( 'role="status"', $html );
		$this->assertStringNotContainsString( 'purplish-trade-in__net', $html, 'price after trade-in is off by default' );
		$this->assertStringNotContainsString( 'type="checkbox"', $html, 'terms are off by default' );
		$this->assertTrue( wp_script_is( TradeInField::HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( TradeInField::HANDLE, 'enqueued' ) );
		$this->assertSame( $html, $this->render_again( $html, $id ), 'renders once' );
	}

	/**
	 * Renders an already rendered form again.
	 *
	 * @param string $html Rendered form.
	 * @param int    $id   Product ID.
	 * @return string
	 */
	private function render_again( $html, $id ) {
		return TradeInField::render(
			$html,
			array( 'attrs' => array( 'purplish' => array( 'tradeIn' => true ) ) ),
			new \WP_Block( array( 'blockName' => TradeInField::BLOCK ), array( 'postId' => $id ) )
		);
	}

	/**
	 * The net line, the terms box and the notes follow their settings.
	 */
	public function test_optional_parts() {
		$id = $this->product();
		TradeIn::save_devices( array( array( 'Apple', 'iPhone 13', '128GB', 600, 680 ) ) );
		update_option(
			TradeIn::OPTION,
			array(
				'terms'     => true,
				'terms_url' => 'https://example.org/terms',
				'link_url'  => 'https://example.org/trade-in',
				'condition' => '',
			)
		);
		$html = $this->render(
			array(
				'tradeIn'    => true,
				'tradeInNet' => true,
			),
			$id
		);
		$this->assertStringContainsString( 'purplish-trade-in__net', $html );
		$this->assertStringContainsString( 'name="purplish_trade_in_terms" value="1"', $html );
		$this->assertStringContainsString( 'href="https://example.org/terms"', $html );
		$this->assertStringContainsString( '<p class="purplish-trade-in__notes">The final value is set when we inspect the device. <a href="https://example.org/trade-in">How it works</a></p>', $html );

		$data = TradeInField::data( wc_get_product( $id ), true );
		$this->assertSame( array( $id ), $data['i'] );
		$this->assertSame( 4599.0, $data['p'] );
		$this->assertFalse( $data['f'] );
	}

	/**
	 * Without the quantity or button, the choice goes before the form ends.
	 */
	public function test_insert_falls_back_to_form_end() {
		$this->assertSame( '<form><i>x</i>MARK</form>', TradeInField::insert( '<form><i>x</i></form>', 'MARK' ) );
		$this->assertSame(
			'<form><div class="wp-block-group"><div data-block-name="woocommerce/add-to-cart-with-options-variation-selector"></div>MARK<div data-block-name="woocommerce/product-button"></div></div></form>',
			TradeInField::insert( '<form><div class="wp-block-group"><div data-block-name="woocommerce/add-to-cart-with-options-variation-selector"></div><div data-block-name="woocommerce/product-button"></div></div></form>', 'MARK' ),
			'a group that holds another block keeps the choice inside it'
		);
	}
}
