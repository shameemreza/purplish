<?php
/**
 * Tests for the theme's page background.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\Ground;
use WP_UnitTestCase;

/**
 * The page background comes from global styles, presets resolved.
 */
class GroundTest extends WP_UnitTestCase {

	/**
	 * Background value the theme.json filter sets.
	 *
	 * @var string
	 */
	private $background = '';

	/**
	 * Adds a theme palette and background through the theme.json filter.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		Ground::reset();
	}

	/**
	 * Removes the filter and forgets the background.
	 */
	public function tear_down() {
		remove_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		$this->background = '';
		Ground::reset();
		wp_clean_theme_json_cache();
		parent::tear_down();
	}

	/**
	 * Sets the palette and background.
	 *
	 * @param \WP_Theme_JSON_Data $data Theme data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function filter_theme_json( $data ) {
		$json = array(
			'version'  => 3,
			'settings' => array(
				'color' => array(
					'palette' => array(
						array(
							'slug'  => 'base',
							'name'  => 'Base',
							'color' => '#1A1A1A',
						),
						array(
							'slug'  => 'paper',
							'name'  => 'Paper',
							'color' => '#fbfaf8',
						),
					),
				),
			),
		);
		if ( '' !== $this->background ) {
			$json['styles'] = array( 'color' => array( 'background' => $this->background ) );
		}
		return $data->update_with( $json );
	}

	/**
	 * Uses the given background for the next read.
	 *
	 * @param string $value Background value.
	 */
	private function use_background( $value ) {
		$this->background = $value;
		wp_clean_theme_json_cache();
		Ground::reset();
	}

	/**
	 * A preset reference resolves to the palette color.
	 */
	public function test_preset_resolves() {
		$this->use_background( 'var(--wp--preset--color--base)' );
		$this->assertSame( '#1a1a1a', Ground::color() );
		$this->assertSame( '#1a1a1a', Ground::dark() );

		$this->use_background( 'var:preset|color|base' );
		$this->assertSame( '#1a1a1a', Ground::color() );
	}

	/**
	 * A plain hex background is used as it is.
	 */
	public function test_hex_background() {
		$this->use_background( '#0B1020' );
		$this->assertSame( '#0b1020', Ground::dark() );
	}

	/**
	 * A light background is found but isn't dark.
	 */
	public function test_light_background_is_not_dark() {
		$this->use_background( 'var(--wp--preset--color--paper)' );
		$this->assertSame( '#fbfaf8', Ground::color() );
		$this->assertSame( '', Ground::dark() );
	}

	/**
	 * Unknown presets and values that aren't colors give nothing.
	 */
	public function test_unknown_values_give_nothing() {
		$this->use_background( 'var(--wp--preset--color--missing)' );
		$this->assertSame( '', Ground::color() );

		$this->use_background( 'linear-gradient(#000, #111)' );
		$this->assertSame( '', Ground::dark() );
	}
}
