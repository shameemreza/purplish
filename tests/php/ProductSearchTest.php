<?php
/**
 * Tests for the instant search route.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\ProductSearch;
use Purplish\Tests\Support\Products;
use WC_Product_Simple;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * The route answers guests without a nonce, checks its input, matches SKUs
 * before titles, follows catalog visibility and the hide out of stock
 * setting, and sends cacheable headers.
 */
class ProductSearchTest extends WP_UnitTestCase {

	/**
	 * Starts each test as a guest who shows out of stock products.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( 0 );
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
	}

	/**
	 * A published simple product.
	 *
	 * @param string $name  Name.
	 * @param string $sku   SKU.
	 * @param string $price Regular price.
	 * @return WC_Product_Simple
	 */
	private function product( $name, $sku = '', $price = '10' ) {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_sku( $sku );
		$product->set_regular_price( $price );
		$product->set_status( 'publish' );
		$product->save();
		return $product;
	}

	/**
	 * Sends a search like the script does.
	 *
	 * @param array<string, mixed> $params Query parameters.
	 * @return \WP_REST_Response
	 */
	private function search( $params ) {
		$request = new WP_REST_Request( 'GET', '/purplish/v1/search' );
		$request->set_query_params( $params );
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Names in an answer, in order.
	 *
	 * @param \WP_REST_Response $response Answer.
	 * @return string[]
	 */
	private function names( $response ) {
		return wp_list_pluck( $response->get_data()['items'], 'name' );
	}

	/**
	 * Too short, too long, missing, or a count or category flag that isn't
	 * a number is a 400.
	 *
	 * @dataProvider bad_params
	 *
	 * @param array<string, mixed> $params Query parameters.
	 */
	public function test_bad_input_is_400( $params ) {
		$this->assertSame( 400, $this->search( $params )->get_status() );
	}

	/**
	 * Requests the route refuses.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function bad_params() {
		return array(
			'missing q'       => array( array() ),
			'one letter'      => array( array( 'q' => 'a' ) ),
			'one after trim'  => array( array( 'q' => '  a   ' ) ),
			'65 letters'      => array( array( 'q' => str_repeat( 'a', 65 ) ) ),
			'count not a num' => array(
				array(
					'q' => 'tv',
					'n' => 'six',
				),
			),
			'cats flag 2'     => array(
				array(
					'q' => 'tv',
					'c' => 2,
				),
			),
			'q an array'      => array( array( 'q' => array( 'tv' ) ) ),
		);
	}

	/**
	 * The query is trimmed, lowercased and its spaces collapsed; 64
	 * characters is still fine; the count is clamped to 3 to 10.
	 */
	public function test_input_is_normalized_and_clamped() {
		$this->assertSame( 'smart tv', ProductSearch::normalize( "  Smart \t  TV " ) );
		$this->assertSame( 200, $this->search( array( 'q' => str_repeat( 'a', 64 ) ) )->get_status() );
		$this->assertSame( 3, ProductSearch::clamp_count( 1 ) );
		$this->assertSame( 10, ProductSearch::clamp_count( 99 ) );
		$this->assertSame(
			200,
			$this->search(
				array(
					'q' => 'tv',
					'n' => 99,
				)
			)->get_status()
		);
	}

	/**
	 * A guest gets results with no nonce and no cookie, with headers an
	 * edge cache can keep.
	 */
	public function test_guest_without_nonce_gets_cacheable_answer() {
		$this->product( 'Studio Monitor', 'SM-1' );

		$response = $this->search( array( 'q' => 'studio' ) );

		$this->assertSame( 200, $response->get_status() );
		$headers = $response->get_headers();
		$this->assertSame( 'public, max-age=300', $headers['Cache-Control'] );
		$this->assertArrayNotHasKey( 'Set-Cookie', $headers );
		$this->assertSame( array( 'Studio Monitor' ), $this->names( $response ) );
	}

	/**
	 * WordPress's no-cache headers stay off for this route even for a
	 * signed-in visitor, and stay on for other routes.
	 */
	public function test_no_cache_headers_skipped_for_route_only() {
		$GLOBALS['wp']->query_vars['rest_route'] = '/purplish/v1/search';
		$this->assertFalse( ProductSearch::keep_cacheable( true ) );

		$GLOBALS['wp']->query_vars['rest_route'] = '/wp/v2/posts';
		$this->assertTrue( ProductSearch::keep_cacheable( true ) );
		unset( $GLOBALS['wp']->query_vars['rest_route'] );
	}

	/**
	 * An exact or prefix SKU matches, and a variation's SKU finds its
	 * product, listed once.
	 */
	public function test_sku_and_variation_sku_match() {
		$this->product( 'Plain Kettle', 'KT-200' );
		$variations = Products::sweater( array( 'Orange', 'Blue' ), array( 'S' ) );
		$variations['Orange/S']->set_sku( 'SW-ORANGE-S' );
		$variations['Orange/S']->save();
		$variations['Blue/S']->set_sku( 'SW-BLUE-S' );
		$variations['Blue/S']->save();

		$this->assertSame( array( 'Plain Kettle' ), $this->names( $this->search( array( 'q' => 'kt-200' ) ) ) );
		$this->assertSame( array( 'Plain Kettle' ), $this->names( $this->search( array( 'q' => 'KT-2' ) ) ) );
		$this->assertSame( array( 'Everyday Crew Sweater' ), $this->names( $this->search( array( 'q' => 'sw-' ) ) ) );
		$this->assertSame( array( 'Everyday Crew Sweater' ), $this->names( $this->search( array( 'q' => 'sw-blue-s' ) ) ) );
	}

	/**
	 * SKU matches come before title matches, an exact SKU before a prefix
	 * one, and a product matching both shows once.
	 */
	public function test_sku_hits_first_then_titles_without_repeats() {
		$this->product( 'XB Speaker', 'SPK-1' );
		$this->product( 'Alpha TV', 'XB-100' );
		$this->product( 'Beta TV', 'XB' );
		$this->product( 'XB Soundbar', 'XB-200' );

		$names = $this->names( $this->search( array( 'q' => 'xb' ) ) );

		$this->assertSame( array( 'Beta TV', 'Alpha TV', 'XB Soundbar', 'XB Speaker' ), $names );
	}

	/**
	 * The count caps the products.
	 */
	public function test_count_caps_results() {
		foreach ( range( 1, 5 ) as $i ) {
			$this->product( "Cable {$i}" );
		}
		$this->assertCount(
			3,
			$this->search(
				array(
					'q' => 'cable',
					'n' => 3,
				)
			)->get_data()['items']
		);
	}

	/**
	 * Products hidden from search, drafts, and products shown only in the
	 * catalog are left out, by SKU and by title.
	 */
	public function test_catalog_visibility_and_status() {
		$this->product( 'Visible Lamp', 'LMP-1' );
		$hidden = $this->product( 'Hidden Lamp', 'LMP-2' );
		$hidden->set_catalog_visibility( 'hidden' );
		$hidden->save();
		$catalog = $this->product( 'Catalog Lamp', 'LMP-3' );
		$catalog->set_catalog_visibility( 'catalog' );
		$catalog->save();
		$draft = $this->product( 'Draft Lamp', 'LMP-4' );
		$draft->set_status( 'draft' );
		$draft->save();

		$this->assertSame( array( 'Visible Lamp' ), $this->names( $this->search( array( 'q' => 'lmp' ) ) ) );
		$this->assertSame( array( 'Visible Lamp' ), $this->names( $this->search( array( 'q' => 'lamp' ) ) ) );
	}

	/**
	 * Out of stock products show as sold, and drop out when the store hides
	 * them.
	 */
	public function test_out_of_stock_follows_store_setting() {
		$this->product( 'Fresh Router', 'RT-1' );
		$sold = $this->product( 'Sold Router', 'RT-2' );
		$sold->set_stock_status( 'outofstock' );
		$sold->save();

		$items = $this->search( array( 'q' => 'router' ) )->get_data()['items'];
		$this->assertSame( array( false, true ), wp_list_pluck( $items, 'sold' ) );

		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$this->assertSame( array( 'Fresh Router' ), $this->names( $this->search( array( 'q' => 'router' ) ) ) );
		$this->assertSame( array( 'Fresh Router' ), $this->names( $this->search( array( 'q' => 'rt-' ) ) ) );
	}

	/**
	 * Matching categories come first, up to three, only with `c=1`, and
	 * each row has a link, a photo, a price and the full results link.
	 */
	public function test_answer_shape_and_categories() {
		$term    = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Smart TVs',
			)
		);
		$product = $this->product( 'Smart TV 55', 'TV-55', '499' );
		wp_set_object_terms( $product->get_id(), array( $term ), 'product_cat' );

		$data = $this->search( array( 'q' => 'smart' ) )->get_data();

		$this->assertSame( 'Smart TVs', $data['cats'][0]['name'] );
		$this->assertSame( 1, $data['cats'][0]['count'] );
		$this->assertSame( get_term_link( $term ), $data['cats'][0]['url'] );
		$item = $data['items'][0];
		$this->assertSame( get_permalink( $product->get_id() ), $item['url'] );
		$this->assertSame( 'TV-55', $item['sku'] );
		$this->assertStringContainsString( '499', $item['price'] );
		$this->assertNotSame( '', $item['img'] );
		$this->assertStringContainsString( 's=smart', $data['all'] );
		$this->assertStringContainsString( 'post_type=product', $data['all'] );

		$this->assertSame(
			array(),
			$this->search(
				array(
					'q' => 'smart',
					'c' => 0,
				)
			)->get_data()['cats']
		);
	}

	/**
	 * A kept answer is used until a product changes, then dropped.
	 */
	public function test_cache_drops_on_product_save() {
		$this->product( 'Pixel Camera', 'PX-1' );
		$this->assertSame( array( 'Pixel Camera' ), $this->names( $this->search( array( 'q' => 'pixel' ) ) ) );

		$key = 'purplish_search:' . md5( wp_cache_get_last_changed( ProductSearch::VERSION_GROUP ) . '|pixel|6|1' );
		$this->assertIsArray( wp_cache_get( $key, ProductSearch::CACHE_GROUP ) );

		$this->product( 'Pixel Lens', 'PX-2' );
		$this->assertSame( array( 'Pixel Camera', 'Pixel Lens' ), $this->names( $this->search( array( 'q' => 'pixel' ) ) ) );
	}

	/**
	 * A signed-in shopper opening the route gets the guest answer, and
	 * stays signed in after.
	 */
	public function test_answer_is_built_as_guest() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );
		$private = $this->product( 'Private Drone', 'DR-1' );
		$private->set_status( 'private' );
		$private->save();

		$this->assertSame( array(), $this->names( $this->search( array( 'q' => 'drone' ) ) ) );
		$this->assertSame( $user, get_current_user_id() );
	}
}
