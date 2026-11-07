<?php
/**
 * Tests for the Step Path block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Markup;
use Purplish\Blocks\Registry;
use Purplish\Blocks\StepPath;
use Purplish\Patterns;
use WP_Block_Patterns_Registry;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block registers from the build with its render and no script, prints
 * its wrapper with the option classes and the steps as a list, and its
 * pattern holds only blocks that exist.
 */
class StepPathTest extends WP_UnitTestCase {

	const NAME = 'purplish/step-path';

	/**
	 * One step: a group with a heading and a paragraph.
	 *
	 * @param string $title Step title.
	 * @return string Block markup.
	 */
	private static function step( $title ) {
		return '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . $title . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Text for ' . $title . '.</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
	}

	/**
	 * The block registered on init from the real build, in the Purplish
	 * category, with its render and style but no front script.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Step Path', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'core/group' ), $type->allowed_blocks );
		$this->assertSame( 'across', $type->attributes['direction']['default'] );
		$this->assertSame( 'numbers', $type->attributes['marker']['default'] );
		$this->assertSame( 'plain', $type->attributes['line']['default'] );
		$this->assertEmpty( $type->view_script_handles, 'no front script' );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * With no options saved, the wrapper carries the defaults, and every
	 * step becomes a list item without any other change.
	 */
	public function test_renders_defaults_and_steps_as_a_list() {
		$inner = self::step( 'Order' ) . self::step( 'Pack' ) . self::step( 'Arrive' );
		$html  = do_blocks( '<!-- wp:purplish/step-path -->' . $inner . '<!-- /wp:purplish/step-path -->' );

		$this->assertStringStartsWith(
			'<div class="purplish-path-direction-across purplish-path-marker-numbers purplish-path-line-plain wp-block-purplish-step-path"><div class="purplish-path-steps" role="list">',
			$html
		);
		$this->assertSame( 3, substr_count( $html, 'role="listitem"' ) );

		// Classic themes add an inner container to groups; it stays plain.
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
			'only the steps themselves are list items'
		);
		$this->assertStringContainsString( '<h3 class="wp-block-heading">Pack</h3>', $html );
	}

	/**
	 * A step can hold a group of its own; only the outer step is a list
	 * item.
	 */
	public function test_nested_groups_stay_plain() {
		$nested = '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">' . self::step( 'Inside' ) . '</div><!-- /wp:group -->';
		$html   = do_blocks( '<!-- wp:purplish/step-path -->' . $nested . self::step( 'Next' ) . '<!-- /wp:purplish/step-path -->' );

		$this->assertSame( 2, substr_count( $html, 'role="listitem"' ) );
	}

	/**
	 * An empty block prints the wrapper without a list role, since a list
	 * with no items would be read out wrong.
	 */
	public function test_empty_has_no_list_role() {
		$html = do_blocks( '<!-- wp:purplish/step-path /-->' );

		$this->assertStringContainsString( '<div class="purplish-path-steps"></div>', $html );
		$this->assertStringNotContainsString( 'role=', $html );
		$this->assertNull( Markup::list_items( "\n\n" ) );
	}

	/**
	 * Saved options become classes next to the block supports' own.
	 */
	public function test_renders_option_classes() {
		$html = do_blocks( '<!-- wp:purplish/step-path {"direction":"down","marker":"dots","line":"dashed","align":"wide","anchor":"how"} -->' . self::step( 'Order' ) . '<!-- /wp:purplish/step-path -->' );

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		foreach ( array( 'wp-block-purplish-step-path', 'alignwide', 'purplish-path-direction-down', 'purplish-path-marker-dots', 'purplish-path-line-dashed' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertSame( 'how', $tags->get_attribute( 'id' ) );
	}

	/**
	 * Unknown or missing values fall back to each option's default.
	 */
	public function test_unknown_values_fall_back() {
		$this->assertSame(
			array( 'purplish-path-direction-across', 'purplish-path-marker-numbers', 'purplish-path-line-dashed' ),
			StepPath::classes(
				array(
					'direction' => 'sideways',
					'marker'    => array( 'dots' ),
					'line'      => 'dashed',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-path-direction-across', 'purplish-path-marker-numbers', 'purplish-path-line-plain' ),
			StepPath::classes( array() )
		);
	}

	/**
	 * The pattern holds only registered blocks: a heading, then the path
	 * with three steps, which render as list items.
	 */
	public function test_pattern() {
		( new Patterns() )->register_patterns();
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/step-path' );

		$this->assertNotNull( $pattern );
		$this->assertSame( 'How ordering works', $pattern['title'] );
		$this->assertSame( array( self::NAME ), $pattern['blockTypes'] );

		$blocks = parse_blocks( $pattern['content'] );
		$blocks = array_values( array_filter( $blocks, static fn( $block ) => null !== $block['blockName'] ) );
		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/group', $blocks[0]['blockName'] );
		$this->assertSame( array( 'core/heading', self::NAME ), array_column( $blocks[0]['innerBlocks'], 'blockName' ) );
		$this->assertSame( array( 'core/group', 'core/group', 'core/group' ), array_column( $blocks[0]['innerBlocks'][1]['innerBlocks'], 'blockName' ) );

		foreach ( $this->block_names( $blocks ) as $name ) {
			$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( $name ), $name . ' is registered' );
		}

		$html = do_blocks( $pattern['content'] );
		$this->assertSame( 3, substr_count( $html, 'role="listitem"' ) );
		$this->assertMatchesRegularExpression( '/role="list".*Order.*We pack it.*It arrives/s', $html );
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
