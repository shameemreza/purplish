<?php
/**
 * Tests for the Purplish abilities.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\Abilities;
use Purplish\Frontend\BlockEffects;
use Purplish\Options;
use Purplish\Patterns;
use WP_Block_Patterns_Registry;
use WP_UnitTestCase;

/**
 * The four abilities register under the Purplish category, read the
 * options map, change only the purplish attribute, and refuse anything
 * the map doesn't allow.
 */
class AbilitiesTest extends WP_UnitTestCase {

	const NAMES = array(
		'purplish/get-design-catalog',
		'purplish/get-page-designs',
		'purplish/set-block-design',
		'purplish/insert-pattern',
	);

	/**
	 * Skips when the site has no Abilities API, and makes sure the
	 * patterns the insert tests use are registered.
	 */
	public function set_up() {
		parent::set_up();
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API needs WordPress 6.9 or later.' );
		}
		if ( ! WP_Block_Patterns_Registry::get_instance()->is_registered( 'purplish/trust-row' ) ) {
			( new Patterns() )->register_patterns();
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
	}

	/**
	 * Runs an ability.
	 *
	 * @param string $name  Ability name.
	 * @param mixed  $input Ability input.
	 * @return mixed
	 */
	private function run_ability( $name, $input = null ) {
		return wp_get_ability( $name )->execute( $input );
	}

	/**
	 * A page with the given block markup.
	 *
	 * @param string $content Block markup.
	 * @return int Page ID.
	 */
	private function page( $content ) {
		return self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);
	}

	/**
	 * A group with a paragraph inside, and a heading after it.
	 *
	 * @return string
	 */
	private function markup() {
		return "<!-- wp:group {\"anchor\":\"hero\",\"layout\":{\"type\":\"default\"}} -->\n<div id=\"hero\" class=\"wp-block-group\"><!-- wp:paragraph -->\n<p>Hi</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Title</h2>\n<!-- /wp:heading -->";
	}

	/**
	 * The category and all four abilities register with REST and MCP meta.
	 */
	public function test_registers_the_category_and_four_abilities() {
		$category = wp_get_ability_category( Abilities::CATEGORY );
		$this->assertNotNull( $category );
		$this->assertSame( 'Purplish', $category->get_label() );

		foreach ( self::NAMES as $name ) {
			$ability = wp_get_ability( $name );
			$this->assertNotNull( $ability, $name );
			$this->assertSame( Abilities::CATEGORY, $ability->get_category(), $name );
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'], $name );
			$this->assertSame(
				array(
					'public' => true,
					'type'   => 'tool',
				),
				$meta['mcp'],
				$name
			);
			$this->assertFalse( $meta['annotations']['destructive'], $name );
		}

		$this->assertTrue( wp_get_ability( 'purplish/get-design-catalog' )->get_meta()['annotations']['readonly'] );
		$this->assertTrue( wp_get_ability( 'purplish/get-page-designs' )->get_meta()['annotations']['readonly'] );
		$this->assertFalse( wp_get_ability( 'purplish/set-block-design' )->get_meta()['annotations']['readonly'] );
		$this->assertTrue( wp_get_ability( 'purplish/set-block-design' )->get_meta()['annotations']['idempotent'] );
		$this->assertFalse( wp_get_ability( 'purplish/insert-pattern' )->get_meta()['annotations']['idempotent'] );
	}

	/**
	 * The catalog lists the options map, the page options and the patterns.
	 */
	public function test_catalog_comes_from_the_options_map() {
		$catalog = $this->run_ability( 'purplish/get-design-catalog' );

		$this->assertSame( array( 'blocks', 'options', 'page', 'patterns' ), array_keys( $catalog ) );
		$this->assertCount( count( BlockEffects::blocks() ), $catalog['options'] );
		$this->assertCount( count( Patterns::listed() ), $catalog['patterns'] );
		$this->assertContains( 'purplish/showfloor', wp_list_pluck( $catalog['blocks'], 'name' ) );

		$options = array_column( $catalog['options'], 'options', 'name' );
		$this->assertSame( array( 'fadeup', 'zoomin' ), $options['core/group']['entrance'] );
		$this->assertSame( 'switch', $options['woocommerce/add-to-cart-with-options']['glass'] );
		$this->assertSame( 'hex', $options['core/group']['color'] );
		$this->assertSame( Options::TRANSITIONS, $catalog['page']['transition'] );

		$only = $this->run_ability( 'purplish/get-design-catalog', array( 'type' => 'patterns' ) );
		$this->assertSame( array( 'patterns' ), array_keys( $only ) );
		$this->assertContains( 'purplish/trust-row', wp_list_pluck( $only['patterns'], 'name' ) );
	}

	/**
	 * Page designs list mapped blocks with string paths and their classes.
	 */
	public function test_page_designs_lists_mapped_blocks_with_string_paths() {
		$id     = $this->page( str_replace( '{"anchor"', '{"purplish":{"entrance":"fadeup"},"anchor"', $this->markup() ) );
		$result = $this->run_ability( 'purplish/get-page-designs', array( 'post_id' => $id ) );

		$this->assertNotWPError( $result );
		$this->assertSame( $id, $result['target']['post_id'] );
		$this->assertSame( array( '0', '1' ), wp_list_pluck( $result['blocks'], 'path' ) );
		$this->assertSame( array( 'core/group', 'core/heading' ), wp_list_pluck( $result['blocks'], 'name' ) );
		$this->assertSame( array( 'entrance' => 'fadeup' ), $result['blocks'][0]['options'] );
		$this->assertContains( 'purplish-group-entrance-fadeup', $result['blocks'][0]['classes'] );
	}

	/**
	 * Setting a design changes only the purplish attribute, and clears back.
	 */
	public function test_set_block_design_saves_only_the_purplish_attribute() {
		$id = $this->page( $this->markup() );

		$set = $this->run_ability(
			'purplish/set-block-design',
			array(
				'post_id' => $id,
				'path'    => '0',
				'options' => array( 'entrance' => 'zoomin' ),
			)
		);
		$this->assertNotWPError( $set );
		$this->assertTrue( $set['changed'] );
		$this->assertContains( 'purplish-group-entrance-zoomin', $set['block']['classes'] );

		$saved = parse_blocks( get_post( $id )->post_content );
		$this->assertSame( array( 'entrance' => 'zoomin' ), $saved[0]['attrs']['purplish'] );
		$this->assertSame( 'hero', $saved[0]['attrs']['anchor'] );
		$this->assertCount( 1, wp_get_post_revisions( $id ) );

		$again = $this->run_ability(
			'purplish/set-block-design',
			array(
				'post_id' => $id,
				'anchor'  => 'hero',
				'options' => array( 'entrance' => 'zoomin' ),
			)
		);
		$this->assertFalse( $again['changed'] );

		$this->run_ability(
			'purplish/set-block-design',
			array(
				'post_id' => $id,
				'path'    => '0',
				'options' => array( 'entrance' => null ),
			)
		);
		$this->assertSame( $this->markup(), get_post( $id )->post_content );
	}

	/**
	 * Values, options and blocks outside the map are refused unsaved.
	 */
	public function test_set_block_design_refuses_what_the_map_does_not_allow() {
		$id = $this->page( $this->markup() );
		$at = function ( $path, $options ) use ( $id ) {
			return $this->run_ability(
				'purplish/set-block-design',
				array(
					'post_id' => $id,
					'path'    => $path,
					'options' => $options,
				)
			);
		};

		$this->assertSame( 'purplish_invalid_value', $at( '0', array( 'entrance' => 'spin' ) )->get_error_code() );
		$this->assertSame( 'purplish_invalid_value', $at( '0', array( 'color' => 'purple' ) )->get_error_code() );
		$this->assertSame( 'purplish_invalid_option', $at( '0', array( 'wobble' => true ) )->get_error_code() );
		$this->assertSame( 'purplish_invalid_option', $at( '0', array( 'transition' => 'fade' ) )->get_error_code() );
		$this->assertSame( 'purplish_invalid_block', $at( '0.0', array( 'entrance' => 'fadeup' ) )->get_error_code() );
		$this->assertSame( 'purplish_invalid_path', $at( '9', array( 'entrance' => 'fadeup' ) )->get_error_code() );
		$this->assertSame( $this->markup(), get_post( $id )->post_content );
	}

	/**
	 * Page options merge on the Add to Cart with Options block.
	 */
	public function test_page_options_go_on_the_add_to_cart_block() {
		$block = Abilities::PAGE_BLOCK;

		$merged = Abilities::merge_options(
			$block,
			array( 'glass' => true ),
			array(
				'transition' => 'blur',
				'rise'       => true,
				'parts'      => array( 'button', 'price', 'button' ),
				'glass'      => false,
			)
		);
		$this->assertSame(
			array(
				'transition' => 'blur',
				'rise'       => true,
				'parts'      => array( 'button', 'price' ),
			),
			$merged
		);

		$this->assertSame( 'purplish_invalid_value', Abilities::merge_options( $block, array(), array( 'parts' => array( 'nose' ) ) )->get_error_code() );
		$this->assertSame( 'purplish_invalid_value', Abilities::merge_options( $block, array(), array( 'rise' => 'yes' ) )->get_error_code() );
	}

	/**
	 * Patterns go in at the start, the end, or after a nested block.
	 */
	public function test_insert_pattern_at_start_end_and_after_a_block() {
		$id    = $this->page( $this->markup() );
		$count = count( array_filter( wp_list_pluck( parse_blocks( WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/trust-row' )['content'] ), 'blockName' ) ) );

		$end = $this->run_ability(
			'purplish/insert-pattern',
			array(
				'post_id' => $id,
				'pattern' => 'purplish/trust-row',
			)
		);
		$this->assertNotWPError( $end );
		$this->assertSame( '2', $end['path'] );
		$this->assertSame( $count, $end['inserted'] );

		$start = $this->run_ability(
			'purplish/insert-pattern',
			array(
				'post_id'  => $id,
				'pattern'  => 'trust-row',
				'position' => 'start',
			)
		);
		$this->assertSame( '0', $start['path'] );

		$after = $this->run_ability(
			'purplish/insert-pattern',
			array(
				'post_id'  => $id,
				'pattern'  => 'trust-row',
				'position' => 'after',
				'after'    => (string) $count . '.0',
			)
		);
		$this->assertNotWPError( $after );
		$this->assertSame( $count . '.1', $after['path'] );

		$top = array_values( array_filter( parse_blocks( get_post( $id )->post_content ), 'Purplish\Tests\AbilitiesTest::named' ) );
		$this->assertSame( 'core/group', $top[ $count ]['blockName'] );
		$this->assertSame( 'core/paragraph', $top[ $count ]['innerBlocks'][0]['blockName'] );
		$this->assertSame( $top[0]['blockName'], $top[ $count ]['innerBlocks'][1]['blockName'] );
		$this->assertCount( 1 + $count, $top[ $count ]['innerBlocks'] );

		// The new inner blocks print: one null per inner block in innerContent.
		$this->assertCount( count( $top[ $count ]['innerBlocks'] ), array_filter( $top[ $count ]['innerContent'], 'is_null' ) );
	}

	/**
	 * Whether a parsed block has a name.
	 *
	 * @param array $block Parsed block.
	 * @return bool
	 */
	public static function named( $block ) {
		return ! empty( $block['blockName'] );
	}

	/**
	 * Unknown patterns and paths are refused unsaved.
	 */
	public function test_insert_pattern_refuses_unknown_patterns_and_paths() {
		$id = $this->page( $this->markup() );

		$this->assertSame(
			'purplish_invalid_pattern',
			$this->run_ability(
				'purplish/insert-pattern',
				array(
					'post_id' => $id,
					'pattern' => 'nope',
				)
			)->get_error_code()
		);
		$this->assertSame(
			'purplish_invalid_after',
			$this->run_ability(
				'purplish/insert-pattern',
				array(
					'post_id'  => $id,
					'pattern'  => 'trust-row',
					'position' => 'after',
					'after'    => '7',
				)
			)->get_error_code()
		);
		$this->assertSame( $this->markup(), get_post( $id )->post_content );
	}

	/**
	 * A missing or unknown target names what to give.
	 */
	public function test_missing_or_unknown_target_says_what_to_give() {
		$this->assertSame( 'purplish_missing_target', $this->run_ability( 'purplish/get-page-designs', array() )->get_error_code() );
		$this->assertSame( 'purplish_invalid_post_id', $this->run_ability( 'purplish/get-page-designs', array( 'post_id' => 999999 ) )->get_error_code() );
	}

	/**
	 * Permissions follow who can edit the page.
	 */
	public function test_permissions_follow_the_page() {
		$id = $this->page( $this->markup() );

		wp_set_current_user( 0 );
		$this->assertWPError( $this->run_ability( 'purplish/get-design-catalog' ) );
		$this->assertFalse( Abilities::can_edit_target( array( 'post_id' => $id ) ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );
		$this->assertTrue( Abilities::can_read_catalog() );
		$this->assertFalse( Abilities::can_edit_target( array( 'post_id' => $id ) ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertTrue( Abilities::can_edit_target( array( 'post_id' => $id ) ) );
	}
}
