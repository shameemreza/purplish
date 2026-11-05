<?php
/**
 * Tests for the Story Deck block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Registry;
use Purplish\Blocks\StoryDeck;
use Purplish\Patterns;
use WP_Block_Patterns_Registry;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block registers from the build with its render, prints its wrapper
 * with the option classes, the stories, hidden buttons and a status line,
 * and its pattern holds only blocks that exist.
 */
class StoryDeckTest extends WP_UnitTestCase {

	const NAME = 'purplish/story-deck';

	const STORY = '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>Lovely.</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

	/**
	 * The block registered on init from the real build, in the Purplish
	 * category, with its render and scripts.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Story Deck', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'core/group' ), $type->allowed_blocks );
		$this->assertSame( 'left', $type->attributes['side']['default'] );
		$this->assertSame( 'rounded', $type->attributes['shape']['default'] );
		$this->assertSame( 'fanned', $type->attributes['fan']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * With no options saved, the wrapper carries the defaults and the
	 * region's label, the stories come out unchanged, and the buttons wait
	 * hidden for the script.
	 */
	public function test_renders_defaults_stories_and_hidden_buttons() {
		$html = do_blocks( '<!-- wp:purplish/story-deck -->' . self::STORY . self::STORY . '<!-- /wp:purplish/story-deck -->' );

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		$this->assertSame( 'purplish-deck-side-left purplish-deck-shape-rounded purplish-deck-fan-fanned wp-block-purplish-story-deck', $tags->get_attribute( 'class' ) );
		$this->assertSame( 'region', $tags->get_attribute( 'role' ) );
		$this->assertSame( 'Customer stories', $tags->get_attribute( 'aria-label' ) );
		$this->assertSame( 'Story %1$s of %2$s', $tags->get_attribute( 'data-story-label' ) );
		$this->assertSame( 'carousel', $tags->get_attribute( 'data-roledescription' ) );
		$this->assertNull( $tags->get_attribute( 'aria-roledescription' ), 'set by the script, not without it' );

		$this->assertStringContainsString( '<div class="purplish-deck-stories">' . do_blocks( self::STORY . self::STORY ) . '</div>', $html );

		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-deck-nav' ) ) );
		$this->assertTrue( $tags->get_attribute( 'hidden' ), 'buttons hidden without the script' );
		$this->assertTrue( $tags->next_tag( 'button' ) );
		$this->assertSame( 'button', $tags->get_attribute( 'type' ) );
		$this->assertSame( 'Previous story', $tags->get_attribute( 'aria-label' ) );
		$this->assertTrue( $tags->next_tag( 'button' ) );
		$this->assertSame( 'Next story', $tags->get_attribute( 'aria-label' ) );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-deck-status' ) ) );
		$this->assertSame( 'polite', $tags->get_attribute( 'aria-live' ) );
	}

	/**
	 * Saved options become classes next to the block supports' own.
	 */
	public function test_renders_option_classes() {
		$html = do_blocks( '<!-- wp:purplish/story-deck {"side":"right","shape":"square","fan":"flat","align":"wide","anchor":"stories"} -->' . self::STORY . '<!-- /wp:purplish/story-deck -->' );

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		foreach ( array( 'wp-block-purplish-story-deck', 'alignwide', 'purplish-deck-side-right', 'purplish-deck-shape-square', 'purplish-deck-fan-flat' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertSame( 'stories', $tags->get_attribute( 'id' ) );
	}

	/**
	 * Unknown or missing values fall back to each option's default.
	 */
	public function test_unknown_values_fall_back() {
		$this->assertSame(
			array( 'purplish-deck-side-left', 'purplish-deck-shape-square', 'purplish-deck-fan-fanned' ),
			StoryDeck::classes(
				array(
					'side'  => 'top',
					'shape' => 'square',
					'fan'   => array( 'flat' ),
				)
			)
		);
		$this->assertSame(
			array( 'purplish-deck-side-left', 'purplish-deck-shape-rounded', 'purplish-deck-fan-fanned' ),
			StoryDeck::classes( array() )
		);
	}

	/**
	 * The pattern holds only registered blocks: the deck with three
	 * stories, each a photo spot, a quote, a name and a button to the shop.
	 */
	public function test_pattern() {
		( new Patterns() )->register_patterns();
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/story-deck' );

		$this->assertNotNull( $pattern );
		$this->assertSame( 'Customer stories deck', $pattern['title'] );
		$this->assertSame( array( self::NAME ), $pattern['blockTypes'] );
		$this->assertContains( 'testimonials', $pattern['categories'] );

		$blocks = parse_blocks( $pattern['content'] );
		$blocks = array_values( array_filter( $blocks, static fn( $block ) => null !== $block['blockName'] ) );
		$this->assertCount( 1, $blocks );
		$this->assertSame( self::NAME, $blocks[0]['blockName'] );

		$stories = $blocks[0]['innerBlocks'];
		$this->assertSame( array( 'core/group', 'core/group', 'core/group' ), array_column( $stories, 'blockName' ) );
		foreach ( $stories as $story ) {
			$this->assertSame( array( 'core/image', 'core/quote', 'core/paragraph', 'core/buttons' ), array_column( $story['innerBlocks'], 'blockName' ) );
		}

		foreach ( $this->block_names( $blocks ) as $name ) {
			$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( $name ), $name . ' is registered' );
		}

		$html = do_blocks( $pattern['content'] );
		$this->assertSame( 3, substr_count( $html, 'Shop this' ) );
		$this->assertSame( 3, substr_count( $html, 'href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '"' ) );
		$this->assertStringNotContainsString( '<img', $html, 'empty photo spots print nothing' );
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
