<?php
/**
 * Tests for Load more on Query Pagination inside Product Collection.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\LoadMore;
use WP_Block;
use WP_Query;
use WP_UnitTestCase;

/**
 * The option swaps the page numbers for a link, and the link's flag widens
 * the query to every page so far, up to a cap.
 */
class LoadMoreTest extends WP_UnitTestCase {

	/**
	 * Rendered core pagination.
	 */
	const PAGES = '<nav class="wp-block-query-pagination purplish-pages-more-button" aria-label="Pagination"><a href="?query-0-page=2" class="wp-block-query-pagination-next">Next</a></nav>';

	/**
	 * The class under test.
	 *
	 * @var LoadMore
	 */
	private $more;

	/**
	 * Fresh instance, clean request.
	 */
	public function set_up() {
		parent::set_up();
		$this->more = new LoadMore();
		$_GET       = array();
	}

	/**
	 * Clean request, script dropped.
	 */
	public function tear_down() {
		$_GET = array();
		wp_dequeue_script( LoadMore::HANDLE );
		parent::tear_down();
	}

	/**
	 * A parsed pagination block with the given Load more value.
	 *
	 * @param string $more The `more` option.
	 * @return array
	 */
	private static function pagination( $more ) {
		return array(
			'blockName'   => LoadMore::PAGINATION,
			'attrs'       => array( 'purplish' => array( 'more' => $more ) ),
			'innerBlocks' => array(),
		);
	}

	/**
	 * Notes a collection with Load more on a pagination inside a group.
	 *
	 * @param array $attrs Extra collection attributes.
	 * @return void
	 */
	private function note( $attrs = array() ) {
		$this->more->note(
			array(
				'blockName'   => LoadMore::COLLECTION,
				'attrs'       => array_merge( array( 'queryId' => 0 ), $attrs ),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(),
						'innerBlocks' => array( self::pagination( 'button' ) ),
					),
				),
			)
		);
	}

	/**
	 * A block instance with Product Collection context.
	 *
	 * @param array $query Query context.
	 * @return WP_Block
	 */
	private static function instance( $query ) {
		return new WP_Block(
			self::pagination( 'button' ),
			array(
				'queryId' => 0,
				'query'   => array_merge(
					array(
						'postType'                 => 'product',
						'isProductCollectionBlock' => true,
						'inherit'                  => false,
					),
					$query
				),
			)
		);
	}

	/**
	 * Makes published products.
	 *
	 * @param int $count How many.
	 * @return void
	 */
	private function products( $count ) {
		self::factory()->post->create_many(
			$count,
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Button and fade are the only values.
	 */
	public function test_mode() {
		$this->assertSame( 'button', LoadMore::mode( array( 'purplish' => array( 'more' => 'button' ) ) ) );
		$this->assertSame( 'fade', LoadMore::mode( array( 'purplish' => array( 'more' => 'fade' ) ) ) );
		$this->assertSame( '', LoadMore::mode( array( 'purplish' => array( 'more' => 'all' ) ) ) );
		$this->assertSame( '', LoadMore::mode( array() ) );
		$this->assertSame( '', LoadMore::mode( 'nope' ) );
	}

	/**
	 * Without a noted collection, nothing changes.
	 */
	public function test_untouched_without_collection() {
		$_GET['query-0-more'] = '1';
		$query                = array(
			'posts_per_page' => 4,
			'offset'         => 4,
		);
		$this->assertSame( $query, $this->more->widen_query( $query, self::instance( array() ), 2 ) );
		$this->assertSame( self::PAGES, $this->more->swap_pagination( self::PAGES, self::pagination( 'button' ), self::instance( array() ) ) );
	}

	/**
	 * With the flag, page N's query becomes pages 1 to N, keeping the
	 * collection's own offset.
	 */
	public function test_widen_query() {
		$this->note();
		$block = self::instance( array() );
		$query = array(
			'posts_per_page' => 4,
			'offset'         => 9,
			'paged'          => 3,
		);

		$this->assertSame( $query, $this->more->widen_query( $query, $block, 3 ), 'No flag.' );

		$_GET['query-0-more'] = '1';
		$wide                 = $this->more->widen_query( $query, $block, 3 );
		$this->assertSame( 1, $wide['offset'] );
		$this->assertSame( 12, $wide['posts_per_page'] );
		$this->assertSame( 1, $wide['paged'] );

		$this->assertSame( $query, $this->more->widen_query( $query, $block, 1 ), 'Page 1.' );
		$this->assertSame( $query, $this->more->widen_query( $query, $block, 51 ), 'Over the cap.' );
		$this->assertSame( $query, $this->more->widen_query( $query, self::instance( array( 'inherit' => true ) ), 3 ), 'Archive query.' );
	}

	/**
	 * A collection without Load more is forgotten.
	 */
	public function test_note_forgets() {
		$this->note();
		$this->more->note(
			array(
				'blockName'   => LoadMore::COLLECTION,
				'attrs'       => array( 'queryId' => 0 ),
				'innerBlocks' => array( self::pagination( '' ) ),
			)
		);
		$_GET['query-0-more'] = '1';
		$query                = array( 'posts_per_page' => 4 );
		$this->assertSame( $query, $this->more->widen_query( $query, self::instance( array() ), 2 ) );
	}

	/**
	 * Page 1 gets a link to page 2 with the flag, WooCommerce's navigation
	 * and the script.
	 */
	public function test_swap() {
		$this->products( 5 );
		$this->note();
		$out = $this->more->swap_pagination( self::PAGES, self::pagination( 'button' ), self::instance( array( 'perPage' => 2 ) ) );

		$this->assertStringStartsWith( '<nav class="wp-block-query-pagination purplish-pages-more-button purplish-more" aria-label="Pagination">', $out );
		$this->assertStringContainsString( 'query-0-page=2', $out );
		$this->assertStringContainsString( 'query-0-more=1', $out );
		$this->assertStringContainsString( 'data-wp-on--click="woocommerce/product-collection::actions.navigate"', $out );
		$this->assertStringContainsString( '#purplish-more-0-2"', $out );
		$this->assertStringContainsString( '<div class="wp-block-button purplish-more__wrap">', $out );
		$this->assertMatchesRegularExpression( '/<a [^>]*class="wp-block-button__link wp-element-button purplish-more__button"[^>]*>Load more<span class="purplish-more__spin" aria-hidden="true"><\/span><\/a><\/div><\/nav>$/', $out );
		$this->assertStringNotContainsString( 'wp-block-query-pagination-next', $out );
		$this->assertTrue( wp_script_is( LoadMore::HANDLE, 'enqueued' ) );
	}

	/**
	 * With full page loads on, the link is a plain link and no script loads.
	 */
	public function test_swap_page_reload() {
		$this->products( 5 );
		$this->note( array( 'forcePageReload' => true ) );
		$out = $this->more->swap_pagination( self::PAGES, self::pagination( 'button' ), self::instance( array( 'perPage' => 2 ) ) );

		$this->assertStringContainsString( 'data-purplish-more ', $out );
		$this->assertStringNotContainsString( 'data-wp-on--click', $out );
		$this->assertFalse( wp_script_is( LoadMore::HANDLE, 'enqueued' ) );
	}

	/**
	 * The button takes the outline style and the Button block's looks.
	 */
	public function test_swap_looks() {
		$this->products( 5 );
		$this->note();
		$block                       = self::pagination( 'button' );
		$block['attrs']['purplish'] += array(
			'outline' => true,
			'button'  => array(
				'glow'  => true,
				'eyes'  => 'two',
				'color' => '#ff0000',
			),
		);

		$out = $this->more->swap_pagination( self::PAGES, $block, self::instance( array( 'perPage' => 2 ) ) );
		$this->assertMatchesRegularExpression( '/<div [^>]*class="wp-block-button is-style-outline[^"]* purplish-button purplish-button-glow[^"]*purplish-more__wrap"/', $out );
		$this->assertStringContainsString( '--purplish-effect:#ff0000', $out );
		$this->assertStringContainsString( '<span class="purplish-eyes" aria-hidden="true"><span class="purplish-eye"></span><span class="purplish-eye"></span></span>', $out );
	}

	/**
	 * A widened grid marks the first card of the newest page for the link's
	 * fragment.
	 */
	public function test_mark_new() {
		$this->note();
		$grid  = '<ul class="wc-block-product-template"><li class="wc-block-product">1</li><li class="wc-block-product">2</li><li class="wc-block-product">3</li><li class="wc-block-product">4</li></ul>';
		$block = array( 'blockName' => 'woocommerce/product-template' );

		$this->assertSame( $grid, $this->more->mark_new( $grid, $block, self::instance( array( 'perPage' => 2 ) ) ), 'Page 1.' );

		$_GET = array(
			'query-0-page' => '2',
			'query-0-more' => '1',
		);
		$this->assertStringContainsString( '<li id="purplish-more-0-2" class="wc-block-product">3</li>', $this->more->mark_new( $grid, $block, self::instance( array( 'perPage' => 2 ) ) ) );

		$_GET = array( 'query-0-page' => '2' );
		$this->assertSame( $grid, $this->more->mark_new( $grid, $block, self::instance( array( 'perPage' => 2 ) ) ), 'No flag.' );
	}

	/**
	 * Every product showing drops the pagination; page N without the flag
	 * keeps the page numbers.
	 */
	public function test_swap_ends() {
		$this->products( 4 );
		$this->note();
		$block = self::pagination( 'button' );

		$_GET = array(
			'query-0-page' => '2',
			'query-0-more' => '1',
		);
		$this->assertSame( '', $this->more->swap_pagination( self::PAGES, $block, self::instance( array( 'perPage' => 2 ) ) ) );

		$_GET = array( 'query-0-page' => '2' );
		$this->assertSame( self::PAGES, $this->more->swap_pagination( self::PAGES, $block, self::instance( array( 'perPage' => 2 ) ) ) );
	}

	/**
	 * The next page past the cap keeps the page numbers.
	 */
	public function test_swap_cap() {
		global $wp_query;
		$wp_query = new WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query->set( 'posts_per_page', 150 );
		$wp_query->found_posts = 500;
		$this->note();
		$block = self::instance( array( 'inherit' => true ) );
		$this->assertSame( self::PAGES, $this->more->swap_pagination( self::PAGES, self::pagination( 'button' ), $block ) );

		$wp_query->set( 'posts_per_page', 12 );
		$this->assertStringContainsString( 'query-more=1', $this->more->swap_pagination( self::PAGES, self::pagination( 'button' ), $block ) );
	}

	/**
	 * The archive's main query widens with the flag, and keeps its URL.
	 */
	public function test_widen_main_query() {
		global $wp_query, $wp_the_query;
		$query = new WP_Query();
		$query->set( 'wc_query', 'product_query' );
		$query->set( 'paged', 2 );
		$query->set( 'posts_per_page', 12 );
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->more->widen_main_query( $query );
		$this->assertSame( 12, $query->get( 'posts_per_page' ), 'No flag.' );
		$this->assertSame( 'https://x.test/', $this->more->keep_url( 'https://x.test/' ) );

		$_GET['query-more'] = '1';
		$this->more->widen_main_query( $query );
		$this->assertSame( 24, $query->get( 'posts_per_page' ) );
		$this->assertSame( 1, $query->get( 'paged' ) );
		$this->assertSame( 2, $query->get( 'purplish_more_page' ) );
		$this->assertFalse( $this->more->keep_url( 'https://x.test/' ) );
	}
}
