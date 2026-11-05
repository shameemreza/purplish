<?php
/**
 * Tests for the Scroll Lookbook block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Registry;
use Purplish\Blocks\ScrollLookbook;
use Purplish\Frontend\PageData;
use Purplish\Patterns;
use WC_Product_Simple;
use WP_Block_Patterns_Registry;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block registers from the build with its render, prints its options
 * and config on the wrapper, stops Cover videos from playing on their own,
 * colors each card from its product, prints only the inner blocks when
 * there's no Cover, and its pattern holds only blocks that exist.
 */
class ScrollLookbookTest extends WP_UnitTestCase {

	const NAME = 'purplish/scroll-lookbook';

	const SHOT = '<!-- wp:cover {"url":"https://example.com/walk.mp4","backgroundType":"video","dimRatio":0,"minHeight":100,"minHeightUnit":"svh"} --><div class="wp-block-cover" style="min-height:100svh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><video class="wp-block-cover__video-background intrinsic-ignore" autoplay muted loop playsinline src="https://example.com/walk.mp4" data-object-fit="cover"></video><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"className":"is-chapter"} --><p class="is-chapter">The walk</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->';

	const OUTRO = '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading --><h2 class="wp-block-heading">The end</h2><!-- /wp:heading --></div><!-- /wp:group -->';

	/**
	 * Removes the filters.
	 */
	public function tear_down() {
		remove_all_filters( 'purplish_scroll_lookbook_config' );
		remove_all_filters( 'purplish_color_set' );
		parent::tear_down();
	}

	/**
	 * The block registered on init from the real build, in the Purplish
	 * category, with its render and scripts.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Scroll Lookbook', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'core/cover', 'woocommerce/product-collection', 'core/group' ), $type->allowed_blocks );
		$this->assertSame( 'on', $type->attributes['chapters']['default'] );
		$this->assertSame( 'off', $type->attributes['timecode']['default'] );
		$this->assertSame( 'medium', $type->attributes['length']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * With Covers, the wrapper carries the option classes and the config,
	 * and the inner blocks sit in the stage. Cover videos lose autoplay and
	 * loop, so nothing plays on its own without the script.
	 */
	public function test_renders_the_stage() {
		$html = do_blocks( '<!-- wp:purplish/scroll-lookbook {"align":"full","timecode":"on","length":"long"} -->' . self::SHOT . self::SHOT . self::OUTRO . '<!-- /wp:purplish/scroll-lookbook -->' );

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		foreach ( array( 'wp-block-purplish-scroll-lookbook', 'alignfull', 'purplish-lookbook-chapters-on', 'purplish-lookbook-timecode-on', 'purplish-lookbook-length-long' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertEquals(
			array(
				'smoothing' => 9,
				'hold'      => 5,
			),
			json_decode( $tags->get_attribute( 'data-config' ), true ),
			'JSON may print a whole float without the .0'
		);

		$this->assertTrue( $tags->next_tag() );
		$this->assertTrue( $tags->has_class( 'purplish-lookbook-stage' ) );

		$videos = 0;
		while ( $tags->next_tag( 'video' ) ) {
			++$videos;
			$this->assertNull( $tags->get_attribute( 'autoplay' ) );
			$this->assertNull( $tags->get_attribute( 'loop' ) );
			$this->assertTrue( $tags->get_attribute( 'muted' ) );
			$this->assertTrue( $tags->get_attribute( 'playsinline' ) );
		}
		$this->assertSame( 2, $videos );
		$this->assertStringContainsString( 'The end', $html );
	}

	/**
	 * Without a Cover there's nothing to play: the inner blocks print
	 * unchanged in the wrapper, with no stage and no config.
	 */
	public function test_prints_inner_blocks_only_without_a_cover() {
		$html = do_blocks( '<!-- wp:purplish/scroll-lookbook -->' . self::OUTRO . '<!-- /wp:purplish/scroll-lookbook -->' );

		$this->assertSame(
			'<div class="purplish-lookbook-chapters-on purplish-lookbook-timecode-off purplish-lookbook-length-medium wp-block-purplish-scroll-lookbook">' . do_blocks( self::OUTRO ) . '</div>',
			$html
		);
	}

	/**
	 * The filter changes the config; unknown keys are dropped and each
	 * number is kept in range.
	 */
	public function test_config_filter() {
		add_filter(
			'purplish_scroll_lookbook_config',
			static function () {
				return array(
					'smoothing' => 80,
					'hold'      => '2.5',
					'extra'     => 'dropped',
				);
			}
		);
		$this->assertSame(
			array(
				'smoothing' => 30.0,
				'hold'      => 2.5,
			),
			ScrollLookbook::config( array() )
		);

		remove_all_filters( 'purplish_scroll_lookbook_config' );
		add_filter(
			'purplish_scroll_lookbook_config',
			static function () {
				return array(
					'smoothing' => 'fast',
					'hold'      => -4,
				);
			}
		);
		$this->assertSame(
			array(
				'smoothing' => 9.0,
				'hold'      => 0.0,
			),
			ScrollLookbook::config( array() )
		);

		remove_all_filters( 'purplish_scroll_lookbook_config' );
		add_filter( 'purplish_scroll_lookbook_config', '__return_false' );
		$this->assertSame( ScrollLookbook::CONFIG, ScrollLookbook::config( array() ) );
	}

	/**
	 * Each card of the first collection whose product has a color gets the
	 * accent class and the color set as custom properties. Cards without a
	 * color, and cards in a later collection, stay as they are.
	 */
	public function test_cards_carry_their_product_colors() {
		$coat  = $this->product( 'Wool Coat' );
		$plain = $this->product( 'Plain Tee' );
		add_filter(
			'purplish_color_set',
			static function ( $set, $product ) use ( $coat ) {
				return $product->get_id() === $coat ? array( 'color' => '#2a519e' ) : null;
			},
			10,
			2
		);

		$html = ScrollLookbook::prepare(
			'<div class="wp-block-woocommerce-product-collection"><ul class="wc-block-product-template">'
			. '<li class="wc-block-product post-' . $coat . ' product" style="gap:1rem">A</li>'
			. '<li class="wc-block-product post-' . $plain . ' product">B</li>'
			. '</ul></div>'
			. '<div class="wp-block-woocommerce-product-collection"><ul><li class="wc-block-product post-' . $coat . '">C</li></ul></div>'
		);

		$set  = PageData::color_set( '#2a519e' );
		$tags = new WP_HTML_Tag_Processor( $html );

		$this->assertTrue( $tags->next_tag( 'li' ) );
		$this->assertTrue( $tags->has_class( 'purplish-lookbook-accent' ) );
		$this->assertSame( 'gap:1rem;--purplish-accent:#2a519e;--purplish-on:' . $set['on'] . ';--purplish-ink:' . $set['ink'], $tags->get_attribute( 'style' ) );

		$this->assertTrue( $tags->next_tag( 'li' ) );
		$this->assertFalse( $tags->has_class( 'purplish-lookbook-accent' ), 'no color' );
		$this->assertNull( $tags->get_attribute( 'style' ) );

		$this->assertTrue( $tags->next_tag( 'li' ) );
		$this->assertFalse( $tags->has_class( 'purplish-lookbook-accent' ), 'second collection' );
	}

	/**
	 * Unknown or missing values fall back to each option's default.
	 */
	public function test_unknown_values_fall_back() {
		$this->assertSame(
			array( 'purplish-lookbook-chapters-off', 'purplish-lookbook-timecode-off', 'purplish-lookbook-length-medium' ),
			ScrollLookbook::classes(
				array(
					'chapters' => 'off',
					'timecode' => true,
					'length'   => 'endless',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-lookbook-chapters-on', 'purplish-lookbook-timecode-off', 'purplish-lookbook-length-medium' ),
			ScrollLookbook::classes( array() )
		);
	}

	/**
	 * The pattern holds only registered blocks: the lookbook with an
	 * opening shot, three shots, an empty hand-picked collection and the
	 * closing line, then a heading and four products after it. No media.
	 */
	public function test_pattern() {
		( new Patterns() )->register_patterns();
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $pattern );
		$this->assertSame( 'Scroll lookbook storefront', $pattern['title'] );
		$this->assertSame( array( self::NAME ), $pattern['blockTypes'] );

		$blocks = parse_blocks( $pattern['content'] );
		$blocks = array_values( array_filter( $blocks, static fn( $block ) => null !== $block['blockName'] ) );
		$this->assertSame( array( self::NAME, 'core/heading', 'woocommerce/product-collection' ), array_column( $blocks, 'blockName' ) );
		$this->assertSame(
			array( 'core/cover', 'core/cover', 'core/cover', 'core/cover', 'woocommerce/product-collection', 'core/group' ),
			array_column( $blocks[0]['innerBlocks'], 'blockName' )
		);
		$cards = $blocks[0]['innerBlocks'][4]['attrs'];
		$this->assertSame( 'woocommerce/product-collection/hand-picked', $cards['collection'] );
		$this->assertSame( array(), $cards['query']['woocommerceHandPickedProducts'] );
		$this->assertSame( 4, $blocks[2]['attrs']['query']['perPage'] );
		$this->assertSame( 4, $blocks[2]['attrs']['displayLayout']['columns'] );

		foreach ( $this->block_names( $blocks ) as $name ) {
			$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( $name ), $name . ' is registered' );
		}
		$this->assertStringNotContainsString( '<img', $pattern['content'], 'no media' );
		$this->assertStringNotContainsString( '<video', $pattern['content'], 'no media' );
		$this->assertStringNotContainsString( '"url"', $pattern['content'], 'Covers start empty' );
		$this->assertStringNotContainsString( 'var:preset', $pattern['content'], 'no theme presets' );
		$this->assertSame( 3, substr_count( $pattern['content'], 'class="is-chapter"' ) );

		$html = do_blocks( $pattern['content'] );
		$this->assertStringContainsString( 'purplish-lookbook-stage', $html );
	}

	/**
	 * A simple product.
	 *
	 * @param string $name Product name.
	 * @return int Product ID.
	 */
	private function product( $name ) {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( '20' );
		return $product->save();
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
