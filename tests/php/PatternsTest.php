<?php
/**
 * Tests for the block patterns.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\Patterns;
use WP_Block_Pattern_Categories_Registry;
use WP_Block_Patterns_Registry;
use WP_UnitTestCase;

/**
 * Listed patterns register with their file's content in the Purplish
 * category; the filter can add or drop one.
 */
class PatternsTest extends WP_UnitTestCase {

	const PROBE = 'purplish/probe';

	/**
	 * Removes the test pattern, the category and the filters.
	 */
	public function tear_down() {
		if ( WP_Block_Patterns_Registry::get_instance()->is_registered( self::PROBE ) ) {
			unregister_block_pattern( self::PROBE );
		}
		if ( WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( Patterns::CATEGORY ) ) {
			unregister_block_pattern_category( Patterns::CATEGORY );
		}
		remove_all_filters( 'purplish_patterns' );
		parent::tear_down();
	}

	/**
	 * Lists only the probe pattern through the filter.
	 *
	 * @param array $extra Keys to add to or replace in the entry.
	 * @return void
	 */
	private function list_probe( $extra = array() ) {
		add_filter(
			'purplish_patterns',
			static function () use ( $extra ) {
				return array(
					'probe' => array_merge(
						array(
							'title'      => 'Probe',
							'categories' => array( 'featured' ),
							'blockTypes' => array( 'core/group' ),
							'file'       => dirname( __DIR__ ) . '/fixtures/patterns/probe.php',
						),
						$extra
					),
				);
			}
		);
	}

	/**
	 * Patterns register on init.
	 */
	public function test_hooks() {
		$patterns = new Patterns();
		$patterns->register();

		$this->assertSame( 10, has_action( 'init', array( $patterns, 'register_patterns' ) ) );
	}

	/**
	 * The shipped patterns register with the category, and a filter that
	 * drops them all leaves the category out too.
	 */
	public function test_ships_its_patterns() {
		$this->assertSame( array( 'hanging-rail', 'story-deck', 'runway', 'scroll-lookbook', 'flagship-header', 'flagship-phone-menu', 'flagship-footer', 'flagship-home', 'flagship-floor', 'flagship-categories', 'flagship-just-in', 'flagship-brand-band', 'flagship-best-sellers', 'trust-row', 'flagship-newsletter', 'checkout-header', 'guarantee-card', 'empty-cart', 'atelier-shop' ), array_keys( Patterns::patterns() ) );

		( new Patterns() )->register_patterns();

		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/hanging-rail' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/story-deck' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/runway' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/scroll-lookbook' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/flagship-header' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/flagship-phone-menu' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/flagship-footer' ) );
		foreach ( array( 'flagship-home', 'flagship-floor', 'flagship-categories', 'flagship-just-in', 'flagship-brand-band', 'flagship-best-sellers', 'trust-row', 'flagship-newsletter', 'checkout-header', 'guarantee-card', 'empty-cart', 'atelier-shop' ) as $slug ) {
			$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/' . $slug ), $slug );
		}
		$this->assertTrue( WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( Patterns::CATEGORY ) );

		unregister_block_pattern_category( Patterns::CATEGORY );
		add_filter( 'purplish_patterns', '__return_empty_array' );
		( new Patterns() )->register_patterns();

		$this->assertFalse( WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( Patterns::CATEGORY ) );
	}

	/**
	 * A listed pattern registers with its file's content, its categories
	 * plus Purplish's, and its block types.
	 */
	public function test_listed_pattern_registers() {
		$this->list_probe();

		( new Patterns() )->register_patterns();

		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( self::PROBE );
		$this->assertNotNull( $pattern );
		$this->assertSame( 'Probe', $pattern['title'] );
		$this->assertSame( array( Patterns::CATEGORY, 'featured' ), $pattern['categories'] );
		$this->assertSame( array( 'core/group' ), $pattern['blockTypes'] );
		$this->assertStringContainsString( '<p>Probe pattern</p>', $pattern['content'] );
		$this->assertTrue( WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( Patterns::CATEGORY ) );
	}

	/**
	 * Every block in a pattern exists, so the editor shows no missing block.
	 */
	public function test_pattern_blocks_all_exist() {
		$this->list_probe();
		( new Patterns() )->register_patterns();

		$names = $this->block_names( parse_blocks( WP_Block_Patterns_Registry::get_instance()->get_registered( self::PROBE )['content'] ) );

		$this->assertNotEmpty( $names );
		foreach ( $names as $name ) {
			$this->assertNotSame( 'core/missing', $name );
			$this->assertTrue( \WP_Block_Type_Registry::get_instance()->is_registered( $name ), $name . ' is registered' );
		}
	}

	/**
	 * Entries without a title or a readable file are dropped, and so is a
	 * filter result that isn't a list.
	 *
	 * @dataProvider provide_broken_entries
	 *
	 * @param array $extra Keys that break the entry.
	 */
	public function test_broken_entries_are_dropped( $extra ) {
		$this->list_probe( $extra );

		$this->assertSame( array(), Patterns::listed() );
	}

	/**
	 * Entries that can't register.
	 *
	 * @return array<string, array<array>>
	 */
	public function provide_broken_entries() {
		return array(
			'no title'     => array( array( 'title' => '' ) ),
			'no file'      => array( array( 'file' => '' ) ),
			'missing file' => array( array( 'file' => '/nowhere/probe.php' ) ),
		);
	}

	/**
	 * A filter that returns something other than a list registers nothing.
	 */
	public function test_filter_returning_a_non_list() {
		add_filter( 'purplish_patterns', '__return_false' );

		$this->assertSame( array(), Patterns::listed() );
	}

	/**
	 * The names of every block in a parsed tree, inner blocks included.
	 *
	 * @param array[] $blocks Parsed blocks.
	 * @return string[]
	 */
	private function block_names( $blocks ) {
		$names = array();
		foreach ( $blocks as $block ) {
			if ( null !== $block['blockName'] ) {
				$names[] = $block['blockName'];
			}
			$names = array_merge( $names, $this->block_names( $block['innerBlocks'] ) );
		}
		return $names;
	}

	/**
	 * The Flagship header carries the note bar, the condensing bar with the
	 * Wardrobe Shop panel and the cart pill, and offers itself for headers.
	 * The phone menu offers itself for navigation overlays and has a close.
	 */
	public function test_flagship_patterns() {
		$patterns = Patterns::patterns();
		$this->assertSame( array( 'core/template-part/header' ), $patterns['flagship-header']['blockTypes'] );
		$this->assertSame( array( 'core/template-part/navigation-overlay' ), $patterns['flagship-phone-menu']['blockTypes'] );

		$header = Patterns::content( $patterns['flagship-header']['file'] );
		$names  = $this->block_names( parse_blocks( $header ) );
		foreach ( array( 'core/navigation', 'purplish/wardrobe', 'core/site-title', 'core/search', 'woocommerce/customer-account', 'woocommerce/mini-cart' ) as $name ) {
			$this->assertContains( $name, $names );
		}
		$this->assertStringContainsString( '"purplish":{"condense":true}', $header );
		$this->assertStringContainsString( '"purplish":{"pop":true}', $header );

		$menu = Patterns::content( $patterns['flagship-phone-menu']['file'] );
		$this->assertContains( 'core/navigation-overlay-close', $this->block_names( parse_blocks( $menu ) ) );
		$this->assertStringContainsString( '"purplish":{"entrance":"fadeup"}', $menu );
	}

	/**
	 * The Flagship footer offers itself for footers and carries the fit
	 * help, three link menus, ring social icons, the store name rising from
	 * the floor at full width, and the base row. It uses no drawn figures.
	 */
	public function test_flagship_footer() {
		$patterns = Patterns::patterns();
		$this->assertSame( array( 'core/template-part/footer' ), $patterns['flagship-footer']['blockTypes'] );
		$this->assertSame( array( 'footer' ), $patterns['flagship-footer']['categories'] );

		$footer = Patterns::content( $patterns['flagship-footer']['file'] );
		$names  = $this->block_names( parse_blocks( $footer ) );
		foreach ( array( 'core/columns', 'core/buttons', 'core/navigation', 'core/social-links', 'core/heading', 'core/image' ) as $name ) {
			$this->assertContains( $name, $names );
		}
		$this->assertSame( 3, count( array_keys( $names, 'core/navigation', true ) ) );
		$this->assertStringContainsString( '"purplish":{"look":"ring"}', $footer );
		$this->assertStringContainsString( '"fitText":true,"purplish":{"reveal":"floor"}', $footer );
		$this->assertStringContainsString( '>' . esc_html( get_bloginfo( 'name' ) ) . '</h2>', $footer );
		$this->assertStringContainsString( 'assets/icons/lock.svg', $footer );
		$this->assertStringNotContainsString( 'assets/figures/', $footer );
		$this->assertFileExists( PURPLISH_DIR . 'assets/icons/lock.svg' );
	}

	/**
	 * From WooCommerce 11.3 Shop by category lists the store's four biggest
	 * product categories through a Terms Query: each tile is a Featured
	 * Category with the category's name and a button to its page, tilting on
	 * hover. It uses no drawn figures.
	 */
	public function test_flagship_categories_from_terms() {
		if ( version_compare( WC_VERSION, '11.3', '<' ) ) {
			$this->markTestSkipped( 'Needs WooCommerce 11.3.' );
		}

		$tiles = Patterns::content( Patterns::patterns()['flagship-categories']['file'] );
		$names = $this->block_names( parse_blocks( $tiles ) );
		foreach ( array( 'core/terms-query', 'core/term-template', 'woocommerce/featured-category', 'woocommerce/category-title', 'core/button' ) as $name ) {
			$this->assertContains( $name, $names );
		}
		$this->assertNotContains( 'core/cover', $names );
		$this->assertStringContainsString( '"termQuery":{"perPage":4,"taxonomy":"product_cat","order":"desc","orderBy":"count"', $tiles );
		$this->assertStringContainsString( '"bindings":{"url":{"source":"core/term-data","args":{"field":"link"}}}', $tiles );
		$this->assertStringContainsString( '"purplish":{"hover":"tilt"}', $tiles );
		$this->assertStringContainsString( '"purplish":{"entrance":"fadeup"}', $tiles );
		$this->assertStringNotContainsString( 'assets/figures/', $tiles );
	}

	/**
	 * The home pattern is a starter for new pages only, and it carries
	 * every section pattern's markup.
	 */
	public function test_flagship_home_is_a_page_starter() {
		( new Patterns() )->register_patterns();
		$home = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/flagship-home' );

		$this->assertSame( array( 'core/post-content' ), $home['blockTypes'] );
		$this->assertSame( array( 'page' ), $home['postTypes'] );
		$this->assertArrayNotHasKey( 'postTypes', WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/trust-row' ) );

		foreach ( array( 'flagship-floor', 'flagship-categories', 'flagship-just-in', 'flagship-brand-band', 'flagship-best-sellers', 'trust-row', 'flagship-newsletter' ) as $slug ) {
			$section = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/' . $slug );
			$this->assertStringContainsString( trim( $section['content'] ), $home['content'], $slug );
		}
		$this->assertStringContainsString( '<!-- wp:purplish/story-deck', $home['content'] );
	}

	/**
	 * The store patterns: the checkout header offers itself for headers and
	 * links back to the cart with no menu, search or Mini-Cart; the
	 * guarantee card turns its seal; the empty cart uses no block that needs
	 * the Interactivity API, since WooCommerce draws the empty cart again in
	 * the browser, so its product cards have no add to cart button.
	 */
	public function test_store_patterns() {
		$patterns = Patterns::patterns();
		$this->assertSame( array( 'core/template-part/header' ), $patterns['checkout-header']['blockTypes'] );

		$header = Patterns::content( $patterns['checkout-header']['file'] );
		$names  = $this->block_names( parse_blocks( $header ) );
		$this->assertContains( 'core/site-title', $names );
		foreach ( array( 'core/navigation', 'core/search', 'woocommerce/mini-cart', 'purplish/wardrobe' ) as $name ) {
			$this->assertNotContains( $name, $names );
		}
		$this->assertStringContainsString( 'href="' . esc_url( wc_get_cart_url() ) . '"', $header );

		$card = Patterns::content( $patterns['guarantee-card']['file'] );
		$this->assertStringContainsString( '"purplish":{"turn":true}', $card );
		$this->assertStringContainsString( '"purplish":{"entrance":"fadeup"}', $card );
		$this->assertStringNotContainsString( 'figures/', $card );

		$empty = Patterns::content( $patterns['empty-cart']['file'] );
		$names = $this->block_names( parse_blocks( $empty ) );
		foreach ( array( 'woocommerce/product-collection', 'core/cover', 'core/buttons' ) as $name ) {
			$this->assertContains( $name, $names );
		}
		foreach ( array( 'woocommerce/product-button', 'core/navigation', 'core/search', 'core/query-pagination', 'woocommerce/mini-cart', 'woocommerce/product-filters' ) as $name ) {
			$this->assertNotContains( $name, $names );
		}
		$this->assertStringNotContainsString( '"lightbox"', $empty );
		$this->assertStringContainsString( '"purplish":{"hover":"spotlight"}', $empty );
		$this->assertStringContainsString( '"purplish":{"reveal":"rise"}', $empty );
		$this->assertStringNotContainsString( '"entrance"', $empty );
	}

	/**
	 * The Atelier shop is offered in templates only, next to Product
	 * Collection, and holds the full shop page: a title from the archive,
	 * filters that fold away with chips, Sort by, a three column grid that
	 * follows the page's query with a Best seller tag, the photo zoom on
	 * hover, and numbered pages. Every block in it exists.
	 */
	public function test_atelier_shop() {
		( new Patterns() )->register_patterns();
		$shop = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/atelier-shop' );

		$this->assertSame( array( 'woocommerce/product-collection' ), $shop['blockTypes'] );
		$this->assertSame( array( 'wp_template' ), $shop['postTypes'] );
		$this->assertContains( 'woo-commerce', $shop['categories'] );

		$blocks = parse_blocks( $shop['content'] );
		$names  = $this->block_names( $blocks );
		foreach ( array( 'woocommerce/breadcrumbs', 'core/query-title', 'core/term-description', 'woocommerce/product-collection', 'woocommerce/product-filters', 'woocommerce/catalog-sorting', 'woocommerce/product-template', 'purplish/shelf-tag', 'core/query-pagination-numbers' ) as $name ) {
			$this->assertContains( $name, $names );
		}
		foreach ( $names as $name ) {
			$this->assertTrue( \WP_Block_Type_Registry::get_instance()->is_registered( $name ), $name . ' is registered' );
		}
		$this->assertNotContains( 'woocommerce/product-button', $names );

		$collection = $this->find_block( $blocks, 'woocommerce/product-collection' );
		$this->assertTrue( $collection['attrs']['query']['inherit'] );
		$this->assertSame( 3, $collection['attrs']['displayLayout']['columns'] );
		$this->assertSame(
			array(
				'hoveron'    => 'photo',
				'hoverphoto' => 'push',
			),
			$collection['attrs']['purplish']
		);
		$this->assertSame(
			array(
				'hide'  => true,
				'chips' => true,
			),
			$this->find_block( $blocks, 'woocommerce/product-filters' )['attrs']['purplish']
		);
		$this->assertSame( 'bestseller', $this->find_block( $blocks, 'purplish/shelf-tag' )['attrs']['rule'] );
		$this->assertSame( 'archive', $this->find_block( $blocks, 'core/query-title' )['attrs']['type'] );
	}

	/**
	 * The first block with a name, searched depth first.
	 *
	 * @param array  $blocks Parsed blocks.
	 * @param string $name   Block name.
	 * @return array|null
	 */
	private function find_block( $blocks, $name ) {
		foreach ( $blocks as $block ) {
			if ( $name === $block['blockName'] ) {
				return $block;
			}
			$found = $this->find_block( $block['innerBlocks'], $name );
			if ( $found ) {
				return $found;
			}
		}
		return null;
	}

	/**
	 * A section head is the serif heading alone, or a row with the line
	 * under the heading and the link beside it, with every value escaped.
	 */
	public function test_section_head() {
		$plain = Patterns::section_head( 'Just in' );
		$this->assertSame( array( 'core/heading' ), $this->block_names( parse_blocks( $plain ) ) );
		$this->assertStringContainsString( 'margin-bottom:clamp(24px, 3vw, 40px)', $plain );
		$this->assertStringContainsString( 'bodoni-moda', $plain );

		$full  = Patterns::section_head( 'Best <b>sellers</b>', 'Back for a second color.', 'https://example.org/shop/', 'Shop all' );
		$names = $this->block_names( parse_blocks( $full ) );
		$this->assertSame( 'core/group', $names[0] );
		$this->assertSame( 2, count( array_keys( $names, 'core/paragraph', true ) ) );
		$this->assertStringContainsString( 'Best &lt;b&gt;sellers&lt;/b&gt;', $full );
		$this->assertStringContainsString( '<a href="https://example.org/shop/">Shop all &rarr;</a>', $full );
		$this->assertStringContainsString( '"flexWrap":"wrap"', $full );

		$line = Patterns::section_head( 'Worn by you', 'Real orders.' );
		$this->assertStringNotContainsString( '<a ', $line );
		$this->assertStringContainsString( '"contentSize":"44ch"', $line );

		$dark = Patterns::section_head( 'Worn by you', 'Real orders.', '', '', true );
		$this->assertStringContainsString( 'style="color:#ffffff;', $dark );
		$this->assertStringNotContainsString( '#1f0342', $dark );
	}
}
