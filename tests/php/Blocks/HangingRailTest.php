<?php
/**
 * Tests for the Hanging Rail block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\HangingRail;
use Purplish\Blocks\Registry;
use Purplish\Patterns;
use WC_Product_Simple;
use WP_Block_Patterns_Registry;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * The block registers from the build with its render, prints its wrapper
 * with the option classes around the inner blocks, and its pattern holds
 * only blocks that exist.
 */
class HangingRailTest extends WP_UnitTestCase {

	const NAME = 'purplish/hanging-rail';

	const INNER = '<!-- wp:heading --><h2 class="wp-block-heading">New in</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Fresh pieces.</p><!-- /wp:paragraph -->';

	/**
	 * The block registered on init from the real build, in the Purplish
	 * category, with its render and scripts.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Hanging Rail', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'woocommerce/product-collection', 'core/heading', 'core/paragraph' ), $type->allowed_blocks );
		$this->assertSame( 'peg', $type->attributes['clip']['default'] );
		$this->assertSame( 'little', $type->attributes['sag']['default'] );
		$this->assertSame( 'medium', $type->attributes['length']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * With no options saved, the wrapper carries the defaults, and the inner
	 * blocks come out unchanged inside the stage when there's no collection.
	 */
	public function test_renders_defaults_and_inner_blocks_unchanged() {
		$html = do_blocks( '<!-- wp:purplish/hanging-rail -->' . self::INNER . '<!-- /wp:purplish/hanging-rail -->' );

		$this->assertSame(
			'<div class="purplish-rail-clip-peg purplish-rail-sag-little purplish-rail-length-medium wp-block-purplish-hanging-rail"><div class="purplish-rail-stage is-layout-constrained has-global-padding">' . do_blocks( self::INNER ) . '</div></div>',
			$html
		);
	}

	/**
	 * Saved options become classes next to the block supports' own.
	 */
	public function test_renders_option_classes() {
		$html = do_blocks( '<!-- wp:purplish/hanging-rail {"clip":"binder","sag":"more","length":"long","align":"full","anchor":"rail"} -->' . self::INNER . '<!-- /wp:purplish/hanging-rail -->' );

		$tags = new \WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		foreach ( array( 'wp-block-purplish-hanging-rail', 'alignfull', 'purplish-rail-clip-binder', 'purplish-rail-sag-more', 'purplish-rail-length-long' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertSame( 'rail', $tags->get_attribute( 'id' ) );
	}

	/**
	 * Unknown or missing values fall back to each option's default.
	 */
	public function test_unknown_values_fall_back() {
		$this->assertSame(
			array( 'purplish-rail-clip-peg', 'purplish-rail-sag-little', 'purplish-rail-length-short' ),
			HangingRail::classes(
				array(
					'clip'   => 'staple',
					'sag'    => array( 'more' ),
					'length' => 'short',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-rail-clip-peg', 'purplish-rail-sag-little', 'purplish-rail-length-medium' ),
			HangingRail::classes( array() )
		);
	}

	/**
	 * The pattern holds only registered blocks, starts with the rail, and
	 * renders the newest products inside the stage.
	 */
	public function test_pattern() {
		( new Patterns() )->register_patterns();
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/hanging-rail' );

		$this->assertNotNull( $pattern );
		$this->assertSame( 'Hanging rail of new arrivals', $pattern['title'] );
		$this->assertSame( array( self::NAME ), $pattern['blockTypes'] );

		$blocks = parse_blocks( $pattern['content'] );
		$blocks = array_values( array_filter( $blocks, static fn( $block ) => null !== $block['blockName'] ) );
		$this->assertCount( 1, $blocks );
		$this->assertSame( self::NAME, $blocks[0]['blockName'] );
		$this->assertSame(
			array( 'core/heading', 'core/paragraph', 'woocommerce/product-collection' ),
			array_column( $blocks[0]['innerBlocks'], 'blockName' )
		);
		$this->assertSame( 8, $blocks[0]['innerBlocks'][2]['attrs']['query']['perPage'] );

		foreach ( $this->block_names( $blocks ) as $name ) {
			$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( $name ), $name . ' is registered' );
		}

		$products = array(
			'Linen Shirt' => '2026-01-01',
			'Wool Coat'   => '2026-02-01',
		);
		foreach ( $products as $name => $date ) {
			$product = new WC_Product_Simple();
			$product->set_name( $name );
			$product->set_regular_price( '20' );
			$product->set_date_created( $date );
			$product->save();
		}

		$html = do_blocks( $pattern['content'] );
		$this->assertStringContainsString( 'purplish-rail-stage', $html );
		$this->assertMatchesRegularExpression( '/purplish-rail-stage.*wc-block-product-template.*Wool Coat.*Linen Shirt/s', $html );
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
}
