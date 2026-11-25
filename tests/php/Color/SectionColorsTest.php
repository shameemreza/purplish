<?php
/**
 * Tests for the section style tokens.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\SectionColors;
use Purplish\Color\ThemeColors;
use WP_UnitTestCase;

/**
 * Purplish blocks inside a section style take the section's colors.
 */
class SectionColorsTest extends WP_UnitTestCase {

	/**
	 * Styles the theme.json filter adds.
	 *
	 * @var array
	 */
	private $styles = array();

	/**
	 * Block styles registered by a test, as block name and style name.
	 *
	 * @var array<int, string[]>
	 */
	private $registered = array();

	/**
	 * Adds a palette shaped like Purple's through the theme.json filter.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		wp_clean_theme_json_cache();
	}

	/**
	 * Removes the filter and the styles.
	 */
	public function tear_down() {
		remove_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		foreach ( $this->registered as $style ) {
			unregister_block_style( $style[0], $style[1] );
		}
		$this->registered = array();
		$this->styles     = array();
		wp_clean_theme_json_cache();
		parent::tear_down();
	}

	/**
	 * Sets the palette and styles.
	 *
	 * @param \WP_Theme_JSON_Data $data Theme data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function filter_theme_json( $data ) {
		$palette = array();
		foreach ( array(
			'theme-1' => '#ffffff',
			'theme-2' => '#111111',
			'theme-3' => '#1f0342',
			'theme-4' => '#322f37',
			'theme-5' => '#f2edff',
		) as $slug => $color ) {
			$palette[] = array(
				'slug'  => $slug,
				'name'  => $slug,
				'color' => $color,
			);
		}
		return $data->update_with(
			array(
				'version'  => 3,
				'settings' => array( 'color' => array( 'palette' => $palette ) ),
				'styles'   => $this->styles,
			)
		);
	}

	/**
	 * Registers a section style on each block it names.
	 *
	 * @param string   $name   Style name.
	 * @param array    $styles The style's theme.json styles.
	 * @param string[] $blocks Block names.
	 */
	private function section( $name, $styles, $blocks = SectionColors::BLOCKS ) {
		foreach ( $blocks as $block ) {
			register_block_style(
				$block,
				array(
					'name'       => $name,
					'label'      => $name,
					'style_data' => $styles,
				)
			);
			$this->registered[] = array( $block, $name );
		}
		wp_clean_theme_json_cache();
	}

	/**
	 * Purple's Style 1: dark band, light text, a white button.
	 *
	 * @return array
	 */
	private function style_one() {
		return array(
			'color'    => array(
				'background' => 'var(--wp--preset--color--theme-2)',
				'text'       => 'var(--wp--preset--color--theme-5)',
			),
			'elements' => array(
				'button' => array( 'color' => array( 'background' => 'var(--wp--preset--color--theme-1)' ) ),
				'link'   => array( 'color' => array( 'text' => 'var(--wp--preset--color--theme-5)' ) ),
			),
		);
	}

	/**
	 * A section sets the tokens to its own text and background.
	 */
	public function test_section_sets_ink_paper_and_ground() {
		$this->section( 'p295-one', $this->style_one() );
		$tokens = SectionColors::sections()['.wp-block-group.is-style-p295-one'];
		$this->assertSame( 'var(--wp--preset--color--theme-5)', $tokens['--purplish-theme-ink'] );
		$this->assertSame( 'var(--wp--preset--color--theme-2)', $tokens['--purplish-theme-paper'] );
		$this->assertSame( 'var(--wp--preset--color--theme-2)', $tokens['--purplish-ground'] );
	}

	/**
	 * The accent is the section's button color when it reads on the band.
	 */
	public function test_accent_is_the_button_color() {
		$this->section( 'p295-one', $this->style_one() );
		$this->assertSame( 'var(--wp--preset--color--theme-1)', SectionColors::sections()['.wp-block-column.is-style-p295-one']['--purplish-theme-accent'] );
	}

	/**
	 * A button color too close to the band falls back to the link color.
	 */
	public function test_accent_skips_a_button_that_does_not_read() {
		$style = $this->style_one();
		$style['elements']['button']['color']['background'] = 'var(--wp--preset--color--theme-4)';
		$this->section( 'p295-low', $style );
		$this->assertSame( 'var(--wp--preset--color--theme-5)', SectionColors::sections()['.wp-block-group.is-style-p295-low']['--purplish-theme-accent'] );
	}

	/**
	 * Groups, Columns and Column each get a selector.
	 */
	public function test_every_section_block_gets_a_selector() {
		$this->section( 'p295-one', $this->style_one() );
		$sections = SectionColors::sections();
		$this->assertArrayHasKey( '.wp-block-group.is-style-p295-one', $sections );
		$this->assertArrayHasKey( '.wp-block-columns.is-style-p295-one', $sections );
		$this->assertArrayHasKey( '.wp-block-column.is-style-p295-one', $sections );
	}

	/**
	 * Selectors with the same tokens share one rule.
	 */
	public function test_same_tokens_share_a_rule() {
		$this->section( 'p295-one', $this->style_one() );
		$css = SectionColors::css();
		$this->assertStringContainsString( '.wp-block-group.is-style-p295-one,.wp-block-columns.is-style-p295-one,.wp-block-column.is-style-p295-one{--purplish-theme-ink:var(--wp--preset--color--theme-5);', $css );
	}

	/**
	 * A color variation that patches a section for one block reaches that
	 * block's tokens only, as Purple's dark palettes patch Column's Style 2.
	 */
	public function test_a_patch_for_one_block_stays_on_that_block() {
		$this->section( 'p295-one', $this->style_one() );
		$this->styles = array(
			'blocks' => array(
				'core/column' => array(
					'variations' => array(
						'p295-one' => array( 'color' => array( 'text' => 'var(--wp--preset--color--theme-1)' ) ),
					),
				),
			),
		);
		wp_clean_theme_json_cache();
		$sections = SectionColors::sections();
		$this->assertSame( 'var(--wp--preset--color--theme-1)', $sections['.wp-block-column.is-style-p295-one']['--purplish-theme-ink'] );
		$this->assertSame( 'var(--wp--preset--color--theme-5)', $sections['.wp-block-group.is-style-p295-one']['--purplish-theme-ink'] );
	}

	/**
	 * Hex colors and the short preset form come through; anything else,
	 * like a gradient or a color-mix, is left out.
	 */
	public function test_values_are_hex_or_palette_references() {
		$this->section(
			'p295-mixed',
			array(
				'color' => array(
					'background' => 'color-mix(in srgb, red 50%, blue)',
					'text'       => '#ABCDEF',
				),
			),
			array( 'core/group' )
		);
		$tokens = SectionColors::sections()['.wp-block-group.is-style-p295-mixed'];
		$this->assertSame( '#abcdef', $tokens['--purplish-theme-ink'] );
		$this->assertArrayNotHasKey( '--purplish-theme-paper', $tokens );
		$this->assertArrayNotHasKey( '--purplish-ground', $tokens );

		$this->assertSame( 'var(--wp--preset--color--theme-3)', ThemeColors::color( 'var:preset|color|theme-3' ) );
	}

	/**
	 * A style with no colors, or on another block, isn't a section.
	 */
	public function test_styles_without_colors_or_on_other_blocks_are_skipped() {
		$this->section( 'p295-plain', array( 'spacing' => array( 'padding' => '1rem' ) ), array( 'core/group' ) );
		$this->section( 'p295-quote', $this->style_one(), array( 'core/quote' ) );
		$sections = SectionColors::sections();
		$this->assertArrayNotHasKey( '.wp-block-group.is-style-p295-plain', $sections );
		$this->assertArrayNotHasKey( '.wp-block-quote.is-style-p295-quote', $sections );
	}

	/**
	 * The theme's fixed-color parts and soft fills follow every section found.
	 */
	public function test_fixed_parts_and_soft_fills_follow_the_sections() {
		$this->section( 'p295-one', $this->style_one() );
		$css = SectionColors::css();
		$this->assertMatchesRegularExpression( '/:is\([^)]*\.wp-block-column\.is-style-p295-one[^)]*\) :is\(' . preg_quote( SectionColors::FOLLOW, '/' ) . '\)\{color:inherit\}$/', $css );
		foreach ( array( 'mini-cart', 'wp-block-navigation', 'product-price', 'product-rating', 'product-summary' ) as $part ) {
			$this->assertStringContainsString( $part, SectionColors::FOLLOW );
		}
		$this->assertStringContainsString( 'body.purplish-active :is(', $css );
		$this->assertStringContainsString( '{--purplish-page-tint:color-mix(in oklab, var(--purplish-tint) 55%, var(--purplish-ground, #fff));--purplish-fx-tint:var(--purplish-page-tint)}', $css );
		$this->assertStringContainsString( 'body.purplish-neutral-panels :is(', $css );
	}

	/**
	 * ThemeColors prints the section rules after its own.
	 */
	public function test_theme_colors_prints_the_section_rules() {
		$this->section( 'p295-one', $this->style_one() );
		$this->assertStringEndsWith( SectionColors::css(), ThemeColors::css() );
	}

	/**
	 * The page's ink and paper keep a second name on `:root`, which a
	 * section doesn't change.
	 */
	public function test_page_tokens_stay_on_root() {
		$this->styles = array(
			'color' => array(
				'background' => 'var:preset|color|theme-1',
				'text'       => 'var:preset|color|theme-4',
			),
		);
		wp_clean_theme_json_cache();
		$css = ThemeColors::css();
		$this->assertStringContainsString( '--purplish-page-ink:var(--wp--preset--color--theme-4);', $css );
		$this->assertStringContainsString( '--purplish-page-paper:var(--wp--preset--color--theme-1);', $css );
		$this->assertStringNotContainsString( '--purplish-page-accent', $css );
	}
}
