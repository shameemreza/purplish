<?php
/**
 * Tests for the theme color tokens.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\ThemeColors;
use WP_UnitTestCase;

/**
 * The page's own colors reach Purplish, whatever the palette slugs are.
 */
class ThemeColorsTest extends WP_UnitTestCase {

	/**
	 * Styles the theme.json filter sets.
	 *
	 * @var array
	 */
	private $styles = array();

	/**
	 * Adds a palette shaped like Purple's through the theme.json filter.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
	}

	/**
	 * Removes the filter.
	 */
	public function tear_down() {
		remove_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		$this->styles = array();
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
			'theme-1' => '#301f12',
			'theme-2' => '#e5e5e5',
			'theme-3' => '#343d2a',
			'theme-4' => '#a4a4a4',
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
	 * Uses these styles for the next read.
	 *
	 * @param array $styles Global styles.
	 */
	private function use_styles( $styles ) {
		$this->styles = $styles;
		wp_clean_theme_json_cache();
	}

	/**
	 * Text and background come through as palette references.
	 */
	public function test_ink_and_paper_keep_the_palette_reference() {
		$this->use_styles(
			array(
				'color' => array(
					'background' => 'var:preset|color|theme-1',
					'text'       => 'var:preset|color|theme-4',
				),
			)
		);
		$tokens = ThemeColors::tokens();
		$this->assertSame( 'var(--wp--preset--color--theme-4)', $tokens['--purplish-theme-ink'] );
		$this->assertSame( 'var(--wp--preset--color--theme-1)', $tokens['--purplish-theme-paper'] );
	}

	/**
	 * A button color that reads on the page is the accent.
	 */
	public function test_button_color_is_the_accent_when_it_reads() {
		$this->use_styles(
			array(
				'color'    => array( 'background' => '#ffffff' ),
				'elements' => array( 'button' => array( 'color' => array( 'background' => 'var:preset|color|theme-3' ) ) ),
			)
		);
		$this->assertSame( 'var(--wp--preset--color--theme-3)', ThemeColors::tokens()['--purplish-theme-accent'] );
	}

	/**
	 * On a dark page a dark button color gives way to the link color.
	 */
	public function test_dark_button_on_dark_page_falls_to_the_link_color() {
		$this->use_styles(
			array(
				'color'    => array(
					'background' => 'var:preset|color|theme-1',
					'text'       => 'var:preset|color|theme-4',
				),
				'elements' => array(
					'button' => array( 'color' => array( 'background' => 'var:preset|color|theme-3' ) ),
					'link'   => array( 'color' => array( 'text' => 'var:preset|color|theme-2' ) ),
				),
			)
		);
		$this->assertSame( 'var(--wp--preset--color--theme-2)', ThemeColors::tokens()['--purplish-theme-accent'] );
	}

	/**
	 * Values that aren't a color are left out, and no colors print nothing.
	 */
	public function test_unsafe_values_are_left_out() {
		$this->use_styles(
			array(
				'color' => array(
					'background' => 'red;}body{display:none',
					'text'       => 'linear-gradient(#000,#fff)',
				),
			)
		);
		$this->assertArrayNotHasKey( '--purplish-theme-paper', ThemeColors::tokens() );
		$this->assertArrayNotHasKey( '--purplish-theme-ink', ThemeColors::tokens() );
		$this->assertStringNotContainsString( 'display', ThemeColors::css() );
	}

	/**
	 * The rule is a single `:root` block.
	 */
	public function test_css_is_a_root_rule() {
		$this->use_styles( array( 'color' => array( 'text' => '#A4A4A4' ) ) );
		$this->assertStringContainsString( ':root{--purplish-theme-ink:#a4a4a4;', ThemeColors::css() );
	}

	/**
	 * Purple's color names the theme lacks point at its own colors.
	 */
	public function test_missing_purple_names_point_at_the_theme() {
		$this->use_styles( array( 'color' => array( 'text' => '#A4A4A4' ) ) );
		$css = ThemeColors::css();
		$this->assertStringNotContainsString( '--wp--preset--color--theme-1:', $css );
		$this->assertStringNotContainsString( '--wp--preset--color--theme-4:', $css );
		$this->assertStringContainsString( '--wp--preset--color--theme-5:color-mix(', $css );
		$this->assertStringContainsString( '--wp--preset--color--theme-7:var(--purplish-theme-accent, #720eec);', $css );
	}

	/**
	 * A theme without section styles gets no section rules.
	 */
	public function test_no_section_rules_without_sections() {
		$this->assertStringNotContainsString( 'mini-cart', ThemeColors::css() );
	}
}
