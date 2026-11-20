<?php
/**
 * Tests for the Stock Indicator's dot.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Blocks\ShelfTag;
use Purplish\Frontend\StockDot;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * Low stock gets its class, by the rule WooCommerce's editor view uses, and
 * the script loads only where WooCommerce swaps the text per variation.
 */
class StockDotTest extends WP_UnitTestCase {

	/**
	 * Resets the store's stock settings and the script.
	 */
	public function tear_down() {
		delete_option( 'woocommerce_stock_format' );
		delete_option( 'woocommerce_notify_low_stock_amount' );
		wp_dequeue_script( StockDot::HANDLE );
		wp_deregister_script( StockDot::HANDLE );
		unset( $GLOBALS['product'] );
		parent::tear_down();
	}

	/**
	 * A simple product with stock managed.
	 *
	 * @param int|null $stock Stock, or null to leave stock unmanaged.
	 * @return WC_Product_Simple
	 */
	private function product( $stock ) {
		$product = new WC_Product_Simple();
		$product->set_name( 'Tee' );
		$product->set_regular_price( '20' );
		if ( null !== $stock ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( $stock );
		}
		$product->save();
		return wc_get_product( $product->get_id() );
	}

	/**
	 * An indicator as BlockEffects leaves it.
	 *
	 * @param string $extra Extra attributes.
	 * @return string
	 */
	private function html( $extra = '' ) {
		return '<div class="wc-block-components-product-stock-indicator wc-block-components-product-stock-indicator--in-stock purplish-stock purplish-stock-dot"' . $extra . '>2 in stock</div>';
	}

	/**
	 * The block as parsed.
	 *
	 * @param array $saved Purplish options.
	 * @return array
	 */
	private function block( $saved ) {
		return array(
			'blockName' => StockDot::BLOCK,
			'attrs'     => array( 'purplish' => $saved ),
		);
	}

	/**
	 * A block instance with a product in its context.
	 *
	 * @param int $id Product ID.
	 * @return \WP_Block
	 */
	private function instance( $id ) {
		return new \WP_Block( array( 'blockName' => StockDot::BLOCK ), array( 'postId' => $id ) );
	}

	/**
	 * The filter is on.
	 */
	public function test_registers() {
		( new StockDot() )->register();
		$this->assertSame( 10, has_filter( 'render_block_' . StockDot::BLOCK, array( StockDot::class, 'render' ) ) );
	}

	/**
	 * Low is at or under the low stock amount, with some left and stock
	 * amounts shown.
	 */
	public function test_is_low() {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );
		$this->assertTrue( StockDot::is_low( $this->product( 2 ) ) );
		$this->assertTrue( StockDot::is_low( $this->product( 1 ) ) );
		$this->assertFalse( StockDot::is_low( $this->product( 3 ) ), 'over the amount' );
		$this->assertFalse( StockDot::is_low( $this->product( 0 ) ), 'none left' );
		$this->assertFalse( StockDot::is_low( $this->product( null ) ), 'stock not managed' );
		$this->assertFalse( StockDot::is_low( null ) );

		$own = $this->product( 4 );
		$own->set_low_stock_amount( 5 );
		$own->save();
		$this->assertTrue( StockDot::is_low( wc_get_product( $own->get_id() ) ), 'the product\'s own amount wins' );

		update_option( 'woocommerce_stock_format', 'no_amount' );
		$this->assertFalse( StockDot::is_low( $this->product( 1 ) ), 'the store hides stock amounts' );
	}

	/**
	 * A low stock card gets the class; others and blocks without the dot
	 * stay as they are.
	 */
	public function test_render_marks_low_stock() {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );
		$low  = $this->product( 2 );
		$many = $this->product( 9 );

		$out = StockDot::render( $this->html(), $this->block( array( 'dot' => true ) ), $this->instance( $low->get_id() ) );
		$this->assertStringContainsString( 'purplish-stock-dot purplish-stock-low"', $out );

		$this->assertSame( $this->html(), StockDot::render( $this->html(), $this->block( array( 'dot' => true ) ), $this->instance( $many->get_id() ) ) );
		$this->assertSame( $this->html(), StockDot::render( $this->html(), $this->block( array( 'soldout' => 'pill' ) ), $this->instance( $low->get_id() ) ) );
		$this->assertSame( '', StockDot::render( '', $this->block( array( 'dot' => true ) ), $this->instance( $low->get_id() ) ) );
	}

	/**
	 * On a single product page the global product counts.
	 */
	public function test_render_falls_back_to_the_global_product() {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );
		$GLOBALS['product'] = $this->product( 1 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.
		$out                = StockDot::render( $this->html(), $this->block( array( 'dot' => true ) ) );
		$this->assertStringContainsString( 'purplish-stock-low', $out );
	}

	/**
	 * The script loads only where WooCommerce swaps the text for each
	 * variation.
	 */
	public function test_script_only_for_variation_text() {
		$product = $this->product( 9 );
		StockDot::render( $this->html(), $this->block( array( 'dot' => true ) ), $this->instance( $product->get_id() ) );
		$this->assertFalse( wp_script_is( StockDot::HANDLE, 'enqueued' ) );

		StockDot::render( $this->html( ' data-wp-text="state.productInContext.stock_availability.text"' ), $this->block( array( 'dot' => true ) ), $this->instance( $product->get_id() ) );
		$this->assertTrue( wp_script_is( StockDot::HANDLE, 'enqueued' ) );
	}

	/**
	 * The editor reads the low flag from the Shelf Tag field.
	 */
	public function test_shelf_field_has_the_low_flag() {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$low  = $this->product( 2 );
		$many = $this->product( 9 );
		$this->assertTrue( ShelfTag::rest_value( array( 'id' => $low->get_id() ) )['stock']['low'] );
		$this->assertFalse( ShelfTag::rest_value( array( 'id' => $many->get_id() ) )['stock']['low'] );
	}
}
