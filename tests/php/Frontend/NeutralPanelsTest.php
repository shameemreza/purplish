<?php
/**
 * Tests for neutral panels.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Admin\Settings;
use Purplish\Frontend\NeutralPanels;
use WP_UnitTestCase;

/**
 * Panels take the page color unless the store turns the switch off.
 */
class NeutralPanelsTest extends WP_UnitTestCase {

	/**
	 * Clears the saved settings.
	 */
	public function tear_down() {
		delete_option( Settings::OPTION );
		parent::tear_down();
	}

	/**
	 * Unsaved and on add no class; off adds it.
	 */
	public function test_class_only_when_off() {
		$this->assertNotContains( NeutralPanels::BODY_CLASS, NeutralPanels::body_class( array() ) );

		update_option( Settings::OPTION, array( NeutralPanels::KEY => 'yes' ) );
		$this->assertNotContains( NeutralPanels::BODY_CLASS, NeutralPanels::body_class( array() ) );

		update_option( Settings::OPTION, array( NeutralPanels::KEY => 'no' ) );
		$this->assertSame( array( 'a', NeutralPanels::BODY_CLASS ), NeutralPanels::body_class( array( 'a' ) ) );
	}
}
