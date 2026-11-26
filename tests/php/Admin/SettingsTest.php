<?php
/**
 * Tests for the classic theme settings.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Admin;

use Purplish\Admin\Settings;
use Purplish\Options;
use WP_UnitTestCase;

/**
 * The section shows fields on classic themes and a pointer on block themes,
 * and the saved settings become the page options.
 */
class SettingsTest extends WP_UnitTestCase {

	/**
	 * Nothing saved gives the defaults, without the chip.
	 */
	public function test_nothing_saved_gives_defaults() {
		$options = Settings::options_from( false );

		$this->assertSame( array( 'button', 'badge' ), $options['parts'] );
		$this->assertSame( 'blur', $options['transition'] );
	}

	/**
	 * Saved checkboxes and the transition become the page options.
	 */
	public function test_saved_settings() {
		$options = Settings::options_from(
			array(
				'button'     => 'no',
				'badge'      => 'yes',
				'price'      => 'yes',
				'tint'       => 'yes',
				'transition' => 'fade',
			)
		);

		$this->assertSame( array( 'badge', 'price', 'tint' ), $options['parts'] );
		$this->assertSame( 'fade', $options['transition'] );
		$this->assertFalse( $options['rise'] );
		$this->assertTrue( Settings::options_from( array( 'rise' => 'yes' ) )['rise'] );
	}

	/**
	 * A tampered transition falls back to the default.
	 */
	public function test_unknown_transition() {
		$this->assertSame( 'blur', Settings::options_from( array( 'transition' => 'spin' ) )['transition'] );
	}

	/**
	 * Classic themes get a checkbox per part and the transition select.
	 */
	public function test_classic_fields() {
		$ids = wp_list_pluck( Settings::fields( false ), 'id' );

		foreach ( array( 'button', 'badge', 'price', 'tint', 'transition' ) as $key ) {
			$this->assertContains( 'purplish_settings[' . $key . ']', $ids );
		}
		$this->assertNotContains( 'purplish_settings[chip]', $ids );
		foreach ( array( 'gallery_nothumbs', 'gallery_rounded', 'gallery_hover', 'rise' ) as $key ) {
			$this->assertContains( 'purplish_settings[' . $key . ']', $ids );
		}
		$this->assertNotContains( 'purplish_settings[gallery_tilt]', $ids );
	}

	/**
	 * The hover style select starts on the tilt when the old checkbox was
	 * saved, and on None otherwise.
	 */
	public function test_hover_select_default() {
		$default = function () {
			foreach ( Settings::fields( false ) as $field ) {
				if ( 'purplish_settings[gallery_hover]' === $field['id'] ) {
					return $field['default'];
				}
			}
			return null;
		};

		delete_option( Settings::OPTION );
		$this->assertSame( 'none', $default() );

		update_option( Settings::OPTION, array( 'gallery_tilt' => 'yes' ) );
		$this->assertSame( 'tilt', $default() );

		update_option(
			Settings::OPTION,
			array(
				'gallery_tilt'  => 'yes',
				'gallery_hover' => 'loupe',
			)
		);
		$this->assertSame( 'loupe', $default() );
	}

	/**
	 * Block themes get one line pointing to the block panel, and the panel
	 * tint switch, which works on every theme.
	 */
	public function test_block_theme_pointer() {
		$fields = Settings::fields( true );

		$this->assertCount( 17, $fields );
		$this->assertSame( Settings::OPTION . '[tint_panels]', $fields[1]['id'] );
		$this->assertSame( 'yes', $fields[1]['default'] );
		$this->assertStringContainsString( 'Add to Cart + Options block', $fields[0]['desc'] );
		$this->assertStringContainsString( 'site-editor.php', $fields[0]['desc'] );
	}

	/**
	 * On Purple, the pointer comes with the switches for Purple's Add to
	 * Cart parts, off by default.
	 */
	public function test_purple_parts_switches() {
		$fields = Settings::fields( true, true );
		$ids    = wp_list_pluck( $fields, 'id' );

		$this->assertCount( 21, $fields );
		$this->assertContains( Settings::OPTION . '[purple_delivery]', $ids );
		$this->assertContains( Settings::OPTION . '[purple_extras]', $ids );
		foreach ( $fields as $field ) {
			if ( 'checkbox' === $field['type'] && Settings::OPTION . '[tint_panels]' !== $field['id'] ) {
				$this->assertSame( 'no', $field['default'] );
			}
		}
	}

	/**
	 * The section is added to the Products tab.
	 */
	public function test_section() {
		$sections = ( new Settings() )->add_section( array( '' => 'General' ) );

		$this->assertArrayHasKey( Settings::SECTION, $sections );
	}

	/**
	 * Options keep the allowlist even when the option holds junk.
	 */
	public function test_junk_option() {
		$this->assertSame( Options::clean( array( 'parts' => array( 'button', 'badge' ) ) ), Settings::options_from( 'junk' ) );
	}
}
