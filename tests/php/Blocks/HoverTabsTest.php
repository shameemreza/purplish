<?php
/**
 * Tests for the Hover Tabs block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\HoverTabs;
use Purplish\Blocks\Registry;
use Purplish\Patterns;
use WP_Block_Patterns_Registry;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block registers from the build with its render and no script, prints
 * its wrapper with the option classes and the tabs as a list, and its
 * pattern holds only blocks that exist.
 */
class HoverTabsTest extends WP_UnitTestCase {

	const NAME = 'purplish/hover-tabs';

	/**
	 * One tab: a group with a linked heading and an image.
	 *
	 * @param string $name Tab name.
	 * @return string Block markup.
	 */
	private static function tab( $name ) {
		return '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="https://example.org/' . $name . '">' . $name . '</a></h3><!-- /wp:heading --><!-- wp:image --><figure class="wp-block-image"><img src="https://example.org/' . $name . '.jpg" alt="' . $name . ' photo"/></figure><!-- /wp:image --></div><!-- /wp:group -->';
	}

	/**
	 * The block registered on init from the real build, in the Purplish
	 * category, with its render and style but no front script.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Hover Tabs', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'core/group' ), $type->allowed_blocks );
		$this->assertSame( 'end', $type->attributes['side']['default'] );
		$this->assertSame( 'fade', $type->attributes['change']['default'] );
		$this->assertSame( 'rounded', $type->attributes['shape']['default'] );
		$this->assertEmpty( $type->view_script_handles, 'no front script' );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * With no options saved, the wrapper carries the defaults, and every
	 * tab becomes a list item that keeps its link and photo alt text.
	 */
	public function test_renders_defaults_and_tabs_as_a_list() {
		$html = do_blocks( '<!-- wp:purplish/hover-tabs -->' . self::tab( 'shirts' ) . self::tab( 'shoes' ) . self::tab( 'bags' ) . '<!-- /wp:purplish/hover-tabs -->' );

		$this->assertStringStartsWith(
			'<div class="purplish-tabs-side-end purplish-tabs-change-fade purplish-tabs-shape-rounded wp-block-purplish-hover-tabs"><div class="purplish-tabs-list" role="list">',
			$html
		);

		$tags  = new WP_HTML_Tag_Processor( $html );
		$roles = array();
		while ( $tags->next_tag() ) {
			if ( null !== $tags->get_attribute( 'role' ) ) {
				$roles[] = $tags->get_tag() . ':' . $tags->get_attribute( 'role' ) . ( $tags->has_class( 'wp-block-group' ) ? ':group' : '' );
			}
		}
		$this->assertSame(
			array( 'DIV:list', 'DIV:listitem:group', 'DIV:listitem:group', 'DIV:listitem:group' ),
			$roles,
			'only the tabs themselves are list items'
		);
		$this->assertStringContainsString( '<a href="https://example.org/shoes">shoes</a>', $html );
		$this->assertStringContainsString( 'alt="bags photo"', $html );
	}

	/**
	 * An empty block prints the wrapper without a list role.
	 */
	public function test_empty_has_no_list_role() {
		$html = do_blocks( '<!-- wp:purplish/hover-tabs /-->' );

		$this->assertStringContainsString( '<div class="purplish-tabs-list"></div>', $html );
		$this->assertStringNotContainsString( 'role=', $html );
	}

	/**
	 * Saved options become classes next to the block supports' own.
	 */
	public function test_renders_option_classes() {
		$html = do_blocks( '<!-- wp:purplish/hover-tabs {"side":"start","change":"wipe","shape":"square","align":"wide","anchor":"shop"} -->' . self::tab( 'shirts' ) . '<!-- /wp:purplish/hover-tabs -->' );

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		foreach ( array( 'wp-block-purplish-hover-tabs', 'alignwide', 'purplish-tabs-side-start', 'purplish-tabs-change-wipe', 'purplish-tabs-shape-square' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertSame( 'shop', $tags->get_attribute( 'id' ) );
	}

	/**
	 * Unknown or missing values fall back to each option's default.
	 */
	public function test_unknown_values_fall_back() {
		$this->assertSame(
			array( 'purplish-tabs-side-end', 'purplish-tabs-change-wipe', 'purplish-tabs-shape-rounded' ),
			HoverTabs::classes(
				array(
					'side'   => 'left',
					'change' => 'wipe',
					'shape'  => array( 'square' ),
				)
			)
		);
		$this->assertSame(
			array( 'purplish-tabs-side-end', 'purplish-tabs-change-fade', 'purplish-tabs-shape-rounded' ),
			HoverTabs::classes( array() )
		);
	}

	/**
	 * The pattern holds only registered blocks: the tabs block with three
	 * tabs linked to the shop, each a list item, with empty photos that
	 * print nothing.
	 */
	public function test_pattern() {
		( new Patterns() )->register_patterns();
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/hover-tabs' );

		$this->assertNotNull( $pattern );
		$this->assertSame( 'Shop by category', $pattern['title'] );
		$this->assertSame( array( self::NAME ), $pattern['blockTypes'] );

		$blocks = parse_blocks( $pattern['content'] );
		$blocks = array_values( array_filter( $blocks, static fn( $block ) => null !== $block['blockName'] ) );
		$this->assertCount( 1, $blocks );
		$this->assertSame( self::NAME, $blocks[0]['blockName'] );
		$this->assertSame( 'wide', $blocks[0]['attrs']['align'] );
		$this->assertSame( array( 'core/group', 'core/group', 'core/group' ), array_column( $blocks[0]['innerBlocks'], 'blockName' ) );
		foreach ( $blocks[0]['innerBlocks'] as $tab ) {
			$this->assertSame( array( 'core/heading', 'core/paragraph', 'core/image' ), array_column( $tab['innerBlocks'], 'blockName' ) );
			$this->assertStringContainsString( 'href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '"', $tab['innerBlocks'][0]['innerHTML'] );
		}

		$html = do_blocks( $pattern['content'] );
		$this->assertSame( 3, substr_count( $html, 'role="listitem"' ) );
		$this->assertMatchesRegularExpression( '/role="list".*New in.*Best sellers.*Gifts/s', $html );
		$this->assertStringNotContainsString( '<img', $html, 'empty photos print nothing' );
	}
}
