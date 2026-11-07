<?php
/**
 * Tests for the block registry.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\HangingRail;
use Purplish\Blocks\Registry;
use Purplish\Blocks\Runway;
use Purplish\Blocks\ScrollLookbook;
use Purplish\Blocks\StepPath;
use Purplish\Blocks\StoryDeck;
use Purplish\Tests\Support\ProbeBlock;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * Listed block classes register from the blocks manifest with their render;
 * the filter can drop one; the category shows only with a block.
 */
class RegistryTest extends WP_UnitTestCase {

	const PROBE = 'purplish/probe';

	/**
	 * Removes the test block and filters.
	 */
	public function tear_down() {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( self::PROBE ) ) {
			unregister_block_type( self::PROBE );
		}
		remove_all_filters( 'purplish_blocks' );
		parent::tear_down();
	}

	/**
	 * A registry reading the test build folder, which holds a manifest and
	 * a block folder with no block.json.
	 *
	 * @return Registry
	 */
	private function registry() {
		return new Registry( dirname( __DIR__, 2 ) . '/fixtures/build' );
	}

	/**
	 * Lists only the probe block through the filter. The shipped blocks
	 * registered on init already, and the test build has no folder for them.
	 *
	 * @return void
	 */
	private function list_probe() {
		add_filter(
			'purplish_blocks',
			static function () {
				return array( ProbeBlock::class );
			}
		);
	}

	/**
	 * The registry starts on init.
	 */
	public function test_hooks() {
		$registry = new Registry();
		$registry->register();

		$this->assertSame( 10, has_action( 'init', array( $registry, 'register_blocks' ) ) );
		$this->assertSame( 10, has_filter( 'block_categories_all', array( $registry, 'add_category' ) ) );
	}

	/**
	 * The shipped blocks register on init from the real build, and nothing
	 * else under Purplish's name does.
	 */
	public function test_ships_its_blocks() {
		$this->assertSame( array( HangingRail::class, StoryDeck::class, Runway::class, ScrollLookbook::class, StepPath::class ), Registry::blocks() );

		$ours = array_filter(
			array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() ),
			static function ( $name ) {
				return 0 === strpos( $name, 'purplish/' );
			}
		);
		$this->assertSame( array( 'purplish/hanging-rail', 'purplish/story-deck', 'purplish/runway', 'purplish/scroll-lookbook', 'purplish/step-path' ), array_values( $ours ) );
	}

	/**
	 * A listed block registers with the manifest's metadata and its own
	 * render callback.
	 */
	public function test_listed_block_registers_from_the_manifest() {
		$this->list_probe();

		$this->registry()->register_blocks();

		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::PROBE );
		$this->assertNotNull( $type );
		$this->assertSame( 'Probe', $type->title, 'the title comes from the manifest, since the folder has no block.json' );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertSame( '<p class="purplish-probe">Probe</p>', do_blocks( '<!-- wp:purplish/probe /-->' ) );
	}

	/**
	 * The filter can remove a block, and anything that isn't a block class
	 * is dropped.
	 */
	public function test_filter_removes_a_block() {
		$this->list_probe();
		add_filter(
			'purplish_blocks',
			static function ( $blocks ) {
				return array_merge( array_diff( $blocks, array( ProbeBlock::class ) ), array( 'Not\\A\\Class', \stdClass::class, 42 ) );
			},
			20
		);

		$this->assertSame( array(), Registry::listed() );

		$this->registry()->register_blocks();
		$this->assertFalse( WP_Block_Type_Registry::get_instance()->is_registered( self::PROBE ) );
	}

	/**
	 * A filter that returns something other than a list registers nothing.
	 */
	public function test_filter_returning_a_non_list() {
		add_filter( 'purplish_blocks', '__return_false' );

		$this->assertSame( array(), Registry::listed() );
	}

	/**
	 * The category goes at the end of the list once there is a block, and
	 * only once.
	 */
	public function test_category() {
		$registry   = $this->registry();
		$categories = array(
			array(
				'slug'  => 'text',
				'title' => 'Text',
				'icon'  => null,
			),
		);

		add_filter( 'purplish_blocks', '__return_empty_array' );
		$this->assertSame( $categories, $registry->add_category( $categories ), 'no block, no category' );
		remove_filter( 'purplish_blocks', '__return_empty_array' );

		$this->list_probe();
		$with = $registry->add_category( $categories );

		$this->assertCount( 2, $with );
		$this->assertSame( Registry::CATEGORY, $with[1]['slug'] );
		$this->assertSame( 'Purplish', $with[1]['title'] );
		$this->assertSame( $with, $registry->add_category( $with ), 'not added twice' );
	}

	/**
	 * The category reaches the editor through WordPress's own list.
	 */
	public function test_category_in_the_editor_list() {
		$this->list_probe();

		$slugs = wp_list_pluck( get_block_categories( get_post( self::factory()->post->create() ) ), 'slug' );

		$this->assertContains( Registry::CATEGORY, $slugs );
	}
}
