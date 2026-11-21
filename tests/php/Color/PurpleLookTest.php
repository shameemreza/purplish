<?php
/**
 * Tests for Purplish's values from the Purple theme.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\PurpleLook;
use WP_UnitTestCase;

/**
 * With Purple active, Purplish's corners, rules, washes and shadows follow
 * Purple, and the store's or the theme's own values still win.
 */
class PurpleLookTest extends WP_UnitTestCase {

	/**
	 * The active template the test pretends to have.
	 *
	 * @var string
	 */
	private $template = 'purple';

	/**
	 * Purplish values the theme sets itself.
	 *
	 * @var array
	 */
	private $theme_own = array();

	/**
	 * Data the store's saved Global Styles hold.
	 *
	 * @var array
	 */
	private $user = array();

	/**
	 * Fakes the template, the theme's palette and the store's styles.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'template', array( $this, 'filter_template' ) );
		add_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		add_filter( 'wp_theme_json_data_user', array( $this, 'filter_user_json' ) );
		wp_clean_theme_json_cache();
	}

	/**
	 * Removes the filters.
	 */
	public function tear_down() {
		remove_filter( 'template', array( $this, 'filter_template' ) );
		remove_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		remove_filter( 'wp_theme_json_data_user', array( $this, 'filter_user_json' ) );
		$this->template  = 'purple';
		$this->theme_own = array();
		$this->user      = array();
		wp_clean_theme_json_cache();
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
	 * A palette shaped like Purple's default, plus any theme values.
	 *
	 * @param \WP_Theme_JSON_Data $data Theme data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function filter_theme_json( $data ) {
		$settings = array(
			'color' => array(
				'palette' => self::palette(
					array(
						'theme-5' => '#f5f5f5',
						'theme-6' => '#d8d8d8',
					)
				),
			),
		);
		if ( $this->theme_own ) {
			$settings['custom'] = array( 'purplish' => $this->theme_own );
		}
		return $data->update_with(
			array(
				'version'  => 3,
				'settings' => $settings,
			)
		);
	}

	/**
	 * The store's saved Global Styles.
	 *
	 * @param \WP_Theme_JSON_Data $data User data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function filter_user_json( $data ) {
		return $this->user ? $data->update_with( array_merge( array( 'version' => 3 ), $this->user ) ) : $data;
	}

	/**
	 * A palette list from slug to color.
	 *
	 * @param array $colors Slug to color.
	 * @return array
	 */
	private static function palette( $colors ) {
		$out = array();
		foreach ( $colors as $slug => $color ) {
			$out[] = array(
				'slug'  => $slug,
				'name'  => $slug,
				'color' => $color,
			);
		}
		return $out;
	}

	/**
	 * Purplish's values after every origin merges.
	 *
	 * @return array
	 */
	private function merged() {
		wp_clean_theme_json_cache();
		// A missing path returns every setting, so read the whole tree.
		$settings = wp_get_global_settings();
		return isset( $settings['custom']['purplish'] ) && is_array( $settings['custom']['purplish'] ) ? $settings['custom']['purplish'] : array();
	}

	/**
	 * Purple gets square flat values from its palette.
	 */
	public function test_purple_gets_square_flat_values_from_its_palette() {
		$values = $this->merged();

		$this->assertSame( '0px', $values['radius'] );
		$this->assertSame( 'none', $values['shadow'] );
		$this->assertSame( 'var(--wp--preset--color--theme-6)', $values['rule'] );
		$this->assertSame( 'var(--wp--preset--color--theme-5)', $values['wash'] );
	}

	/**
	 * Other themes get nothing.
	 */
	public function test_other_themes_get_nothing() {
		$this->template = 'twentytwentyfive';

		$this->assertSame( array(), $this->merged() );
	}

	/**
	 * The theme's own values win.
	 */
	public function test_the_themes_own_values_win() {
		$this->theme_own = array(
			'radius' => '6px',
			'wash'   => '#fafafa',
		);
		$values          = $this->merged();

		$this->assertSame( '6px', $values['radius'] );
		$this->assertSame( '#fafafa', $values['wash'] );
		$this->assertSame( 'none', $values['shadow'] );
	}

	/**
	 * The store's Global Styles win.
	 */
	public function test_the_stores_global_styles_win() {
		$this->user = array( 'settings' => array( 'custom' => array( 'purplish' => array( 'radius' => '4px' ) ) ) );
		$values     = $this->merged();

		$this->assertSame( '4px', $values['radius'] );
		$this->assertSame( 'none', $values['shadow'] );
	}

	/**
	 * A dark variation drops the wash.
	 */
	public function test_a_dark_variation_drops_the_wash() {
		$this->user = array(
			'settings' => array(
				'color' => array(
					'palette' => self::palette(
						array(
							'theme-5' => '#3d2a1c',
							'theme-6' => '#5a4636',
						)
					),
				),
			),
		);
		$values     = $this->merged();

		$this->assertArrayNotHasKey( 'wash', $values );
		$this->assertSame( 'var(--wp--preset--color--theme-6)', $values['rule'] );
		$this->assertSame( '0px', $values['radius'] );
	}

	/**
	 * A missing slug skips its color.
	 */
	public function test_a_missing_slug_skips_its_color() {
		$values = PurpleLook::values( array( 'settings' => array( 'color' => array( 'palette' => self::palette( array( 'base' => '#ffffff' ) ) ) ) ) );

		$this->assertSame(
			array(
				'radius' => '0px',
				'shadow' => 'none',
			),
			$values
		);
	}

	/**
	 * A palette split by origin is read.
	 */
	public function test_a_palette_split_by_origin_is_read() {
		$values = PurpleLook::values(
			array(
				'settings' => array(
					'color' => array(
						'palette' => array(
							'theme' => self::palette(
								array(
									'theme-5' => '#f5f5f5',
									'theme-6' => '#d8d8d8',
								)
							),
						),
					),
				),
			)
		);

		$this->assertSame( 'var(--wp--preset--color--theme-6)', $values['rule'] );
		$this->assertSame( 'var(--wp--preset--color--theme-5)', $values['wash'] );
	}

	/**
	 * A non-hex wash counts as light.
	 */
	public function test_non_hex_wash_counts_as_light() {
		$values = PurpleLook::values( array(), array( 'theme-5' => 'oklch(97% 0 0)' ) );

		$this->assertSame( 'var(--wp--preset--color--theme-5)', $values['wash'] );
	}
}
