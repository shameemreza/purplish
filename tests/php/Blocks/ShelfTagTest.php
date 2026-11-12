<?php
/**
 * Tests for the Shelf Tag block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Registry;
use Purplish\Blocks\ShelfTag;
use WC_Product_Simple;
use WP_Block;
use WP_Block_Type_Registry;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * The block registers from the build inside product cards, prints only on
 * the products its rule matches, keeps the best seller ranks between
 * requests, and gives the editor each product's standing.
 */
class ShelfTagTest extends WP_UnitTestCase {

	const NAME = 'purplish/shelf-tag';

	/**
	 * Starts each test with no kept ranks.
	 */
	public function set_up() {
		parent::set_up();
		ShelfTag::forget_ranks();
	}

	/**
	 * Puts the filters and the user back after each test.
	 */
	public function tear_down() {
		remove_all_filters( 'purplish_shelf_tag_show' );
		ShelfTag::forget_ranks();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * A published simple product.
	 *
	 * @param array $props Sales, age in days, featured and stock.
	 * @return WC_Product_Simple
	 */
	private function product( $props = array() ) {
		$props   = array_merge(
			array(
				'sales'    => 0,
				'days'     => 90,
				'featured' => false,
				'stock'    => 'instock',
			),
			$props
		);
		$product = new WC_Product_Simple();
		$product->set_name( 'Coat' );
		$product->set_status( 'publish' );
		$product->set_regular_price( '100' );
		$product->set_total_sales( $props['sales'] );
		$product->set_featured( $props['featured'] );
		$product->set_stock_status( $props['stock'] );
		$product->set_date_created( time() - $props['days'] * DAY_IN_SECONDS );
		$product->save();
		return $product;
	}

	/**
	 * Renders a tag on a product card.
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $product_id The card's product.
	 * @return string
	 */
	private function render( $attributes, $product_id ) {
		$parsed = parse_blocks( '<!-- wp:purplish/shelf-tag ' . wp_json_encode( (object) $attributes ) . ' /-->' );
		return ( new WP_Block(
			$parsed[0],
			array(
				'postId'   => $product_id,
				'postType' => 'product',
			)
		) )->render();
	}

	/**
	 * The block registered on init from the real build, only inside product
	 * cards, with its defaults, style and editor script.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Shelf Tag', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'woocommerce/product-template', 'core/post-template', 'woocommerce/single-product' ), $type->ancestor );
		$this->assertSame( array( 'postId', 'postType' ), $type->uses_context );
		$this->assertSame( 'bestseller', $type->attributes['rule']['default'] );
		$this->assertSame( 8, $type->attributes['top']['default'] );
		$this->assertSame( 30, $type->attributes['days']['default'] );
		$this->assertSame( 'chip', $type->attributes['look']['default'] );
		$this->assertSame( 'top-right', $type->attributes['corner']['default'] );
		$this->assertEmpty( $type->view_script_handles, 'no front script' );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * Best sellers rank by sales, most first, and only products that sold.
	 */
	public function test_ranks() {
		$most  = $this->product( array( 'sales' => 40 ) );
		$less  = $this->product( array( 'sales' => 12 ) );
		$never = $this->product();

		$this->assertSame( 1, ShelfTag::rank( $most->get_id() ) );
		$this->assertSame( 2, ShelfTag::rank( $less->get_id() ) );
		$this->assertSame( 0, ShelfTag::rank( $never->get_id() ) );
	}

	/**
	 * The ranks are kept until they're forgotten, as an order's sales do.
	 */
	public function test_ranks_are_kept_until_a_sale() {
		$first = $this->product( array( 'sales' => 40 ) );
		ShelfTag::ranks();
		$this->assertSame( array( $first->get_id() ), get_transient( ShelfTag::RANKS ) );

		$second = $this->product( array( 'sales' => 90 ) );
		$this->assertSame( 0, ShelfTag::rank( $second->get_id() ), 'kept ranks' );

		ShelfTag::forget_ranks();

		$this->assertFalse( get_transient( ShelfTag::RANKS ) );
		$this->assertSame( 1, ShelfTag::rank( $second->get_id() ) );
	}

	/**
	 * The best seller rule takes the top few, as many as the block asks.
	 */
	public function test_best_seller_rule() {
		$first  = $this->product( array( 'sales' => 30 ) );
		$second = $this->product( array( 'sales' => 20 ) );

		$this->assertTrue( ShelfTag::matches( $first, array( 'top' => 1 ) ) );
		$this->assertFalse( ShelfTag::matches( $second, array( 'top' => 1 ) ) );
		$this->assertTrue( ShelfTag::matches( $second, array() ), 'top 8 by default' );
		$this->assertTrue( ShelfTag::matches( $first, array( 'top' => -4 ) ), 'at least 1' );
	}

	/**
	 * The new rule counts days from the product's creation.
	 */
	public function test_new_rule() {
		$fresh = $this->product( array( 'days' => 5 ) );
		$old   = $this->product( array( 'days' => 40 ) );

		$this->assertTrue( ShelfTag::matches( $fresh, array( 'rule' => 'new' ) ) );
		$this->assertFalse( ShelfTag::matches( $old, array( 'rule' => 'new' ) ) );
		$this->assertTrue(
			ShelfTag::matches(
				$old,
				array(
					'rule' => 'new',
					'days' => 60,
				)
			)
		);
		$this->assertFalse(
			ShelfTag::matches(
				$fresh,
				array(
					'rule' => 'new',
					'days' => 1,
				)
			)
		);
	}

	/**
	 * The featured rule follows the product's star, and an unknown rule
	 * falls back to best sellers.
	 */
	public function test_featured_and_unknown_rule() {
		$star = $this->product( array( 'featured' => true ) );

		$this->assertTrue( ShelfTag::matches( $star, array( 'rule' => 'featured' ) ) );
		$this->assertFalse( ShelfTag::matches( $this->product(), array( 'rule' => 'featured' ) ) );
		$this->assertFalse( ShelfTag::matches( $star, array( 'rule' => 'trending' ) ), 'never sold' );
	}

	/**
	 * A filter can change where a tag shows.
	 */
	public function test_filter() {
		$product = $this->product();
		add_filter( 'purplish_shelf_tag_show', '__return_true' );

		$this->assertTrue( ShelfTag::matches( $product, array( 'rule' => 'featured' ) ) );
	}

	/**
	 * A matching card gets the tag with its look, corner and rule classes
	 * and the default words.
	 */
	public function test_render() {
		$product = $this->product( array( 'sales' => 5 ) );

		$out = $this->render( array(), $product->get_id() );

		$this->assertStringContainsString( 'wp-block-purplish-shelf-tag', $out );
		$this->assertStringContainsString( 'purplish-shelf-tag-look-chip purplish-shelf-tag-corner-top-right purplish-shelf-tag-rule-bestseller', $out );
		$this->assertStringNotContainsString( 'purplish-shelf-tag-shine', $out );
		$this->assertStringContainsString( '<span class="purplish-shelf-tag-text">Best seller</span>', $out );
	}

	/**
	 * Own words are escaped and stripped, the shine adds its class, and
	 * unknown looks and corners fall back.
	 */
	public function test_render_options() {
		$product = $this->product( array( 'days' => 2 ) );

		$out = $this->render(
			array(
				'rule'   => 'new',
				'label'  => '<b>Just</b> in & fresh',
				'look'   => 'neon',
				'corner' => 'bottom-left',
				'shine'  => true,
			),
			$product->get_id()
		);

		$this->assertStringContainsString( 'purplish-shelf-tag-look-chip purplish-shelf-tag-corner-bottom-left purplish-shelf-tag-rule-new purplish-shelf-tag-shine', $out );
		$this->assertStringContainsString( '>Just in &amp; fresh</span>', $out );
	}

	/**
	 * Nothing prints when the rule doesn't match or there's no product.
	 */
	public function test_render_nothing() {
		$product = $this->product();

		$this->assertSame( '', $this->render( array(), $product->get_id() ) );
		$this->assertSame( '', $this->render( array( 'rule' => 'new' ), 0 ) );
	}

	/**
	 * The REST field gives editors the rank, star, age and stock, and
	 * nobody else anything.
	 */
	public function test_rest_field() {
		$product = $this->product(
			array(
				'sales'    => 3,
				'featured' => true,
				'stock'    => 'outofstock',
			)
		);

		$this->assertNull( ShelfTag::rest_value( array( 'id' => $product->get_id() ) ), 'logged out' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/product/' . $product->get_id() );
		$request->set_param( 'context', 'edit' );
		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( 1, $data['purplish_shelf']['rank'] );
		$this->assertTrue( $data['purplish_shelf']['featured'] );
		$this->assertSame( $product->get_date_created()->getTimestamp(), $data['purplish_shelf']['created'] );
		$this->assertSame( 'out-of-stock', $data['purplish_shelf']['stock']['class'] );
		$this->assertSame( 'Out of stock', $data['purplish_shelf']['stock']['text'] );
	}
}
