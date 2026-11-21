<?php
/**
 * Tests for the store block styles.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\BlockStyles;
use Purplish\Color\ThemeColors;
use WP_Block_Styles_Registry;
use WP_UnitTestCase;

/**
 * The styles show in the Styles panel and Global Styles, and every color
 * comes from a palette slug that ThemeColors backs on any theme.
 */
class BlockStylesTest extends WP_UnitTestCase {

	/**
	 * Every block and the styles it gets.
	 *
	 * @return void
	 */
	public function test_registers_each_style_on_its_block() {
		$expected = array(
			'woocommerce/product-template'   => array( 'purplish-panel', 'purplish-outline', 'purplish-section' ),
			'woocommerce/product-image'      => array( 'purplish-plate' ),
			'woocommerce/product-button'     => array( 'purplish-contrast' ),
			'woocommerce/product-sale-badge' => array( 'purplish-contrast' ),
			'woocommerce/mini-cart-contents' => array( 'purplish-panel' ),
		);
		$registry = WP_Block_Styles_Registry::get_instance();
		foreach ( $expected as $block => $names ) {
			$styles = $registry->get_registered_styles_for_block( $block );
			foreach ( $names as $name ) {
				$this->assertArrayHasKey( $name, $styles, "$block lacks $name" );
				$this->assertNotEmpty( $styles[ $name ]['style_data'], "$block $name has no style data" );
			}
		}
	}

	/**
	 * The styles reach the merged theme.json data, which is what Global
	 * Styles lists and the editor and front end print.
	 *
	 * @return void
	 */
	public function test_styles_reach_global_styles() {
		wp_clean_theme_json_cache();
		$data = \WP_Theme_JSON_Resolver::get_merged_data()->get_raw_data();
		foreach ( BlockStyles::styles() as $style ) {
			$this->assertArrayHasKey( $style['name'], $data['styles']['blocks'][ $style['block'] ]['variations'] ?? array(), $style['block'] . ' ' . $style['name'] );
		}
	}

	/**
	 * No fixed colors: only palette slugs ThemeColors gives a fallback for,
	 * so a theme without Purple's names still gets its own colors.
	 *
	 * @return void
	 */
	public function test_colors_are_backed_palette_slugs() {
		$json = (string) wp_json_encode( BlockStyles::styles() );
		$this->assertDoesNotMatchRegularExpression( '/#[0-9a-f]{3,8}\b|rgba?\(/i', $json );

		preg_match_all( '/--wp--preset--color--([a-z0-9-]+)/', $json, $slugs );
		$this->assertNotEmpty( $slugs[1] );
		foreach ( array_unique( $slugs[1] ) as $slug ) {
			$this->assertArrayHasKey( $slug, ThemeColors::PURPLE_NAMES, "$slug has no fallback" );
		}
	}

	/**
	 * Card and plate corners read Purplish's radius token, which is 0 on
	 * Purple; the button and badge keep the theme's own corner.
	 *
	 * @return void
	 */
	public function test_corners_follow_the_radius_token() {
		foreach ( BlockStyles::styles() as $style ) {
			$json = (string) wp_json_encode( $style['style_data'] );
			if ( false !== strpos( $json, 'radius' ) ) {
				$this->assertStringContainsString( 'var(--wp--custom--purplish--radius', $json, $style['name'] );
			}
			if ( in_array( $style['block'], array( 'woocommerce/product-button', 'woocommerce/product-sale-badge' ), true ) ) {
				$this->assertStringNotContainsString( 'radius', $json, $style['block'] );
			}
		}
	}

	/**
	 * Text on Base Alt surfaces takes Contrast, since some palettes set body
	 * text to a color that's too faint there.
	 *
	 * @return void
	 */
	public function test_base_alt_surfaces_set_contrast_text() {
		foreach ( BlockStyles::styles() as $style ) {
			if ( 'purplish-panel' !== $style['name'] ) {
				continue;
			}
			$this->assertSame( 'var(--wp--preset--color--theme-2)', $style['style_data']['color']['text'], $style['block'] );
		}
	}
}
