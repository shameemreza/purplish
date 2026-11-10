<?php
/**
 * Tests for the Wardrobe block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Registry;
use Purplish\Blocks\Wardrobe;
use WP_Block;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block registers from the build as a Navigation child, prints a link
 * and a button with its panel, follows the Navigation's submenu settings,
 * and drops to a plain link when there's nothing to open.
 */
class WardrobeTest extends WP_UnitTestCase {

	const NAME = 'purplish/wardrobe';

	const INNER = '<!-- wp:paragraph --><p>Coats and jackets.</p><!-- /wp:paragraph -->';

	/**
	 * Puts the screen back and the filter off after each test.
	 */
	public function tear_down() {
		remove_all_filters( 'purplish_blocks' );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Renders a Wardrobe with the given attributes, inner blocks and
	 * Navigation context.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $inner      Inner block markup.
	 * @param array  $context    Navigation context.
	 * @return string
	 */
	private function render( $attributes = array( 'label' => 'Shop' ), $inner = self::INNER, $context = array() ) {
		$markup = '<!-- wp:purplish/wardrobe ' . wp_json_encode( (object) $attributes ) . ' -->' . $inner . '<!-- /wp:purplish/wardrobe -->';
		$parsed = parse_blocks( $markup );
		return ( new WP_Block( $parsed[0], $context ) )->render();
	}

	/**
	 * The block registered on init from the real build, only inside the
	 * Navigation block, with its render and scripts.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Wardrobe', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'core/navigation' ), $type->parent );
		$this->assertSame( array( 'submenuVisibility', 'openSubmenusOnClick', 'showSubmenuIcon' ), $type->uses_context );
		$this->assertSame( 'wide', $type->attributes['width']['default'] );
		$this->assertSame( 'card', $type->attributes['look']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * The Navigation block accepts Wardrobe as a child.
	 */
	public function test_navigation_allows_it() {
		$navigation = WP_Block_Type_Registry::get_instance()->get_registered( 'core/navigation' );

		$this->assertContains( self::NAME, $navigation->allowed_blocks );
	}

	/**
	 * The metadata filter adds Wardrobe once, only to Navigation, and not
	 * when the `purplish_blocks` filter turned it off.
	 */
	public function test_allow_in_navigation() {
		$navigation = array(
			'name'          => 'core/navigation',
			'allowedBlocks' => array( 'core/navigation-link' ),
		);

		$this->assertSame( array( 'core/navigation-link', self::NAME ), Wardrobe::allow_in_navigation( $navigation )['allowedBlocks'] );
		$this->assertSame( array( 'core/navigation-link', self::NAME ), Wardrobe::allow_in_navigation( Wardrobe::allow_in_navigation( $navigation ) )['allowedBlocks'], 'added once' );

		$group = array(
			'name'          => 'core/group',
			'allowedBlocks' => array( 'core/paragraph' ),
		);
		$this->assertSame( $group, Wardrobe::allow_in_navigation( $group ) );
		$this->assertSame( array( 'name' => 'core/navigation' ), Wardrobe::allow_in_navigation( array( 'name' => 'core/navigation' ) ), 'no list to add to' );

		add_filter(
			'purplish_blocks',
			static function ( $blocks ) {
				return array_diff( $blocks, array( Wardrobe::class ) );
			}
		);
		$this->assertSame( $navigation, Wardrobe::allow_in_navigation( $navigation ) );
	}

	/**
	 * With a panel, the item is a list item holding the link, the button
	 * that controls the panel, and the hidden panel around the inner blocks.
	 */
	public function test_renders_link_button_and_panel() {
		$html = $this->render();
		$tags = new WP_HTML_Tag_Processor( $html );

		$this->assertTrue( $tags->next_tag( 'li' ) );
		foreach ( array( 'wp-block-purplish-wardrobe', 'wp-block-navigation-item', 'purplish-wardrobe-width-wide', 'purplish-wardrobe-look-card' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertSame( 'hover', $tags->get_attribute( 'data-open' ) );

		$this->assertTrue( $tags->next_tag( 'a' ) );
		$this->assertTrue( $tags->has_class( 'purplish-wardrobe-link' ) );
		$this->assertSame( wc_get_page_permalink( 'shop' ), $tags->get_attribute( 'href' ), 'shop page by default' );

		$this->assertTrue( $tags->next_tag( 'button' ) );
		$this->assertSame( 'false', $tags->get_attribute( 'aria-expanded' ) );
		$panel_id = $tags->get_attribute( 'aria-controls' );
		$this->assertStringStartsWith( 'purplish-wardrobe-', $panel_id );
		$this->assertTrue( $tags->next_tag( 'svg' ), 'chevron' );

		$this->assertTrue( $tags->next_tag( 'div' ) );
		$this->assertTrue( $tags->has_class( 'purplish-wardrobe-panel' ) );
		$this->assertSame( $panel_id, $tags->get_attribute( 'id' ) );
		$this->assertTrue( $tags->get_attribute( 'hidden' ) );

		$this->assertStringContainsString( '<div class="purplish-wardrobe-inner">' . do_blocks( self::INNER ) . '</div>', $html );
		$this->assertSame( 2, substr_count( $html, '<span class="wp-block-navigation-item__label">Shop</span>' ), 'label on the link and the button' );
	}

	/**
	 * Each Wardrobe on the page gets its own panel ID.
	 */
	public function test_panel_ids_are_unique() {
		preg_match( '/aria-controls="([^"]+)"/', $this->render(), $first );
		preg_match( '/aria-controls="([^"]+)"/', $this->render(), $second );

		$this->assertNotSame( $first[1], $second[1] );
	}

	/**
	 * A saved link and options reach the markup; unknown values fall back.
	 */
	public function test_renders_link_and_options() {
		$html = $this->render(
			array(
				'label' => 'Shop',
				'url'   => 'https://example.com/coats/',
				'width' => 'full',
				'look'  => 'flat',
			)
		);
		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag( 'li' );
		$this->assertTrue( $tags->has_class( 'purplish-wardrobe-width-full' ) );
		$this->assertTrue( $tags->has_class( 'purplish-wardrobe-look-flat' ) );
		$tags->next_tag( 'a' );
		$this->assertSame( 'https://example.com/coats/', $tags->get_attribute( 'href' ) );

		$this->assertSame(
			array( 'purplish-wardrobe-width-wide', 'purplish-wardrobe-look-card' ),
			Wardrobe::classes(
				array(
					'width' => 'huge',
					'look'  => 3,
				)
			)
		);
	}

	/**
	 * The label loses any markup, and an empty label prints nothing, like a
	 * Navigation link.
	 */
	public function test_label_is_plain_text() {
		$html = $this->render( array( 'label' => '<strong>Shop</strong><script>alert(1)</script>' ) );
		$this->assertStringNotContainsString( '<strong>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '<span class="wp-block-navigation-item__label">Shopalert(1)</span>', $html );

		$this->assertSame( '', $this->render( array( 'label' => '' ) ) );
		$this->assertSame( '', $this->render( array( 'label' => '  <em></em> ' ) ) );
		$this->assertSame( '', $this->render( array() ) );
	}

	/**
	 * With an empty panel the item is the link alone, with nothing to open.
	 */
	public function test_empty_panel_is_a_plain_link() {
		$html = $this->render( array( 'label' => 'Shop' ), '' );

		$this->assertStringContainsString( 'purplish-wardrobe-link', $html );
		$this->assertStringNotContainsString( '<button', $html );
		$this->assertStringNotContainsString( 'purplish-wardrobe-panel', $html );
		$this->assertStringNotContainsString( 'data-open', $html );
	}

	/**
	 * The Navigation's submenu settings decide how the panel opens and
	 * whether the chevron shows.
	 */
	public function test_follows_navigation_context() {
		$click = $this->render( array( 'label' => 'Shop' ), self::INNER, array( 'submenuVisibility' => 'click' ) );
		$this->assertStringContainsString( 'data-open="click"', $click );

		$no_icon = $this->render( array( 'label' => 'Shop' ), self::INNER, array( 'showSubmenuIcon' => false ) );
		$this->assertStringNotContainsString( '<svg', $no_icon );
	}

	/**
	 * Submenu settings, old and new: "always" opens on click, and the
	 * pre-6.9 setting wins when a menu still has it.
	 */
	public function test_opens_on() {
		$this->assertSame( 'hover', Wardrobe::opens_on( array() ) );
		$this->assertSame( 'hover', Wardrobe::opens_on( array( 'submenuVisibility' => 'hover' ) ) );
		$this->assertSame( 'click', Wardrobe::opens_on( array( 'submenuVisibility' => 'click' ) ) );
		$this->assertSame( 'click', Wardrobe::opens_on( array( 'submenuVisibility' => 'always' ) ) );
		$this->assertSame( 'hover', Wardrobe::opens_on( array( 'submenuVisibility' => 'sideways' ) ) );
		$this->assertSame( 'click', Wardrobe::opens_on( array( 'openSubmenusOnClick' => true ) ) );
		$this->assertSame(
			'hover',
			Wardrobe::opens_on(
				array(
					'openSubmenusOnClick' => false,
					'submenuVisibility'   => 'click',
				)
			)
		);
		$this->assertSame(
			'click',
			Wardrobe::opens_on(
				array(
					'openSubmenusOnClick' => null,
					'submenuVisibility'   => 'click',
				)
			)
		);
	}

	/**
	 * The starting layout loads in the editor only: links, three tiles and
	 * a look card, all pointing at the shop.
	 */
	public function test_example_in_editor_only() {
		$block = new Wardrobe();
		$this->assertSame( array(), $block->settings(), 'front end' );

		set_current_screen( 'post' );
		$settings = $block->settings();

		$this->assertSame( 'Shop', $settings['example']['attributes']['label'] );
		$columns = $settings['example']['innerBlocks'][0];
		$this->assertSame( 'core/columns', $columns['name'] );
		$this->assertCount( 3, $columns['innerBlocks'] );

		$links = $columns['innerBlocks'][0]['innerBlocks'][0];
		$this->assertSame( 'purplish-wardrobe-links', $links['attributes']['className'] );
		$this->assertCount( 5, $links['innerBlocks'] );

		$tiles = $columns['innerBlocks'][1]['innerBlocks'][0]['innerBlocks'];
		$this->assertCount( 3, $tiles );
		foreach ( $tiles as $tile ) {
			$this->assertSame( 'purplish-wardrobe-tile', $tile['attributes']['className'] );
			$this->assertNotEmpty( $tile['attributes']['url'] );
		}

		$look = $columns['innerBlocks'][2]['innerBlocks'][0];
		$this->assertSame( 'purplish-wardrobe-look', $look['attributes']['className'] );
	}
}
