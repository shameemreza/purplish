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
		$this->assertSame( array( 'hanging-rail', 'story-deck', 'runway', 'scroll-lookbook', 'step-path', 'hover-tabs' ), array_keys( Patterns::patterns() ) );

		( new Patterns() )->register_patterns();

		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/hanging-rail' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/story-deck' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/runway' ) );
		$this->assertTrue( WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/scroll-lookbook' ) );
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
}
