<?php
/**
 * Tests for Purplish blocks in Purple's Add to Cart parts.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Admin\Settings;
use Purplish\Frontend\PurpleParts;
use WP_Block_Template;
use WP_UnitTestCase;

/**
 * On Purple, the turned-on blocks go at the end of the simple, variable
 * and grouped parts through Block Hooks, marked as hooked. A part that has
 * the block, or one the store took it out of, gets no second copy, and a
 * hooked copy stops printing once its switch is off.
 */
class PurplePartsTest extends WP_UnitTestCase {

	const PART = '<!-- wp:woocommerce/product-stock-indicator /--><!-- wp:group {"layout":{"type":"flex"}} --><div class="wp-block-group"><!-- wp:woocommerce/product-button /--></div><!-- /wp:group -->';

	/**
	 * The active template the test pretends to have.
	 *
	 * @var string
	 */
	private $template = 'purple';

	/**
	 * Fakes the template.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'template', array( $this, 'filter_template' ) );
	}

	/**
	 * Puts the template, the option and the page template back.
	 */
	public function tear_down() {
		remove_filter( 'template', array( $this, 'filter_template' ) );
		$this->template = 'purple';
		delete_option( Settings::OPTION );
		unset( $GLOBALS['_wp_current_template_content'] );
		parent::tear_down();
	}

	/**
	 * The faked template.
	 *
	 * @return string
	 */
	public function filter_template() {
		return $this->template;
	}

	/**
	 * Turns the given features on.
	 *
	 * @param string[] $features Keys of PurpleParts::BLOCKS.
	 * @return void
	 */
	private function turn_on( $features ) {
		$saved = array();
		foreach ( $features as $feature ) {
			$saved[ 'purple_' . $feature ] = 'yes';
		}
		update_option( Settings::OPTION, $saved );
	}

	/**
	 * A part as core builds it from the theme file or a saved copy.
	 *
	 * @param string $slug    Part slug.
	 * @param string $content Part content.
	 * @return WP_Block_Template
	 */
	private function part( $slug, $content = self::PART ) {
		$part          = new WP_Block_Template();
		$part->type    = 'wp_template_part';
		$part->theme   = 'purple';
		$part->slug    = $slug;
		$part->id      = 'purple//' . $slug;
		$part->content = $content;
		return $part;
	}

	/**
	 * The part's content after core runs Block Hooks on it, the way it does
	 * for a part: wrapped in a template part block, then unwrapped.
	 *
	 * @param WP_Block_Template $part    The part.
	 * @param array             $ignored Hooked blocks the store took out.
	 * @return string
	 */
	private function hooked( $part, $ignored = array() ) {
		$attrs   = $ignored ? array( 'metadata' => array( 'ignoredHookedBlocks' => $ignored ) ) : array();
		$wrapped = get_comment_delimited_block_content( 'core/template-part', $attrs, $part->content );
		return apply_block_hooks_to_content( $wrapped, $part, 'insert_hooked_blocks' );
	}

	/**
	 * The hooks are added.
	 */
	public function test_hooks() {
		$parts = new PurpleParts();
		$parts->register();

		$this->assertSame( 10, has_filter( 'hooked_block_types', array( $parts, 'hook' ) ) );
		$this->assertSame( 10, has_filter( 'hooked_block_purplish/delivery-line', array( $parts, 'mark' ) ) );
		$this->assertSame( 10, has_filter( 'hooked_block_purplish/matching-extras', array( $parts, 'mark' ) ) );
	}

	/**
	 * Both blocks go at the end of each part, after its own blocks, in
	 * order and marked as hooked.
	 */
	public function test_turned_on_blocks_go_at_the_end_of_each_part() {
		$this->turn_on( array( 'delivery', 'extras' ) );

		foreach ( PurpleParts::PARTS as $slug ) {
			$html = $this->hooked( $this->part( $slug ) );
			$this->assertStringEndsWith( '<!-- /wp:group --><!-- wp:purplish/delivery-line {"purplishHooked":true} /--><!-- wp:purplish/matching-extras {"purplishHooked":true} /--><!-- /wp:template-part -->', $html, $slug );
		}
	}

	/**
	 * Only the turned-on block goes in.
	 */
	public function test_only_the_turned_on_block() {
		$this->turn_on( array( 'extras' ) );

		$html = $this->hooked( $this->part( 'simple-product-add-to-cart-with-options' ) );
		$this->assertStringContainsString( 'wp:purplish/matching-extras', $html );
		$this->assertStringNotContainsString( 'wp:purplish/delivery-line', $html );
	}

	/**
	 * Nothing goes in with the switches off, on another theme, or into the
	 * external part or any other part.
	 */
	public function test_nothing_outside_its_parts() {
		$html = $this->hooked( $this->part( 'simple-product-add-to-cart-with-options' ) );
		$this->assertStringNotContainsString( 'wp:purplish/', $html, 'Switches off.' );

		$this->turn_on( array( 'delivery', 'extras' ) );
		foreach ( array( 'external-product-add-to-cart-with-options', 'header', 'mini-cart' ) as $slug ) {
			$this->assertStringNotContainsString( 'wp:purplish/', $this->hooked( $this->part( $slug ) ), $slug );
		}

		$this->template = 'twentytwentyfive';
		$this->assertStringNotContainsString( 'wp:purplish/', $this->hooked( $this->part( 'simple-product-add-to-cart-with-options' ) ), 'Another theme.' );
	}

	/**
	 * A part that has the block already, moved there by the store, keeps
	 * just that one; one the store took out stays out.
	 */
	public function test_store_edits_win() {
		$this->turn_on( array( 'delivery', 'extras' ) );

		$moved = '<!-- wp:purplish/delivery-line {"purplishHooked":true} /-->' . self::PART;
		$html  = $this->hooked( $this->part( 'simple-product-add-to-cart-with-options', $moved ) );
		$this->assertSame( 1, substr_count( $html, 'wp:purplish/delivery-line' ) );
		$this->assertStringContainsString( 'wp:purplish/matching-extras', $html );

		$html = $this->hooked( $this->part( 'grouped-product-add-to-cart-with-options' ), array( 'purplish/matching-extras' ) );
		$this->assertStringNotContainsString( 'wp:purplish/matching-extras', $html );
		$this->assertStringContainsString( 'wp:purplish/delivery-line', $html );
	}

	/**
	 * The mark goes on the hooked copy only.
	 */
	public function test_mark() {
		$parts = new PurpleParts();
		$block = array(
			'blockName' => 'purplish/delivery-line',
			'attrs'     => array(),
		);
		$part  = $this->part( 'variable-product-add-to-cart-with-options' );

		$this->assertTrue( $parts->mark( $block, 'purplish/delivery-line', 'last_child', array(), $part )['attrs'][ PurpleParts::MARK ] );
		$this->assertSame( $block, $parts->mark( $block, 'purplish/delivery-line', 'after', array(), $part ) );
		$this->assertSame( $block, $parts->mark( $block, 'purplish/delivery-line', 'last_child', array(), $this->part( 'header' ) ) );
		$this->assertNull( $parts->mark( null, 'purplish/delivery-line', 'last_child', array(), $part ) );
	}

	/**
	 * A copy the store placed always prints. A hooked copy prints while its
	 * switch is on, on Purple, and when the page template has no copy of
	 * its own.
	 */
	public function test_keeps() {
		$hooked = array( PurpleParts::MARK => true );
		$this->assertTrue( PurpleParts::keeps( array(), 'delivery' ) );
		$this->assertFalse( PurpleParts::keeps( $hooked, 'delivery' ), 'Switch off.' );

		$this->turn_on( array( 'delivery' ) );
		$this->assertTrue( PurpleParts::keeps( $hooked, 'delivery' ) );
		$this->assertFalse( PurpleParts::keeps( $hooked, 'extras' ), 'Other switch off.' );

		$GLOBALS['_wp_current_template_content'] = '<!-- wp:purplish/delivery-line /-->'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WordPress's own global.
		$this->assertFalse( PurpleParts::keeps( $hooked, 'delivery' ), 'Template has one.' );
		unset( $GLOBALS['_wp_current_template_content'] );

		$this->template = 'twentytwentyfive';
		$this->assertFalse( PurpleParts::keeps( $hooked, 'delivery' ), 'Another theme.' );
	}
}
