<?php
/**
 * Tests for the Instalments settings section.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Admin;

use Purplish\Admin\InstalmentSettings;
use Purplish\Instalments;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * Saving through WooCommerce keeps each field, and a save with every band
 * removed stores no bands, so no line prints.
 */
class InstalmentSettingsTest extends WP_UnitTestCase {

	/**
	 * Puts the option back after each test.
	 */
	public function tear_down() {
		delete_option( Instalments::OPTION );
		parent::tear_down();
	}

	/**
	 * Saves the section the way WooCommerce does on submit.
	 *
	 * @param array<string, mixed> $posted The posted purplish_instalments fields.
	 * @return void
	 */
	private function save( $posted ) {
		\WC_Admin_Settings::save_fields( InstalmentSettings::fields(), array( Instalments::OPTION => $posted ) );
	}

	/**
	 * Posted bands and text save cleaned.
	 */
	public function test_saves_bands_and_text() {
		( new InstalmentSettings() )->register();
		$this->save(
			array(
				'bands'   => array(
					array(
						'from'   => '100',
						'months' => '6',
						'rate'   => '0',
					),
				),
				'minimum' => '50',
				'wording' => 'or {amount}/month <b>now</b>',
			)
		);

		$saved = Instalments::settings();
		$this->assertCount( 1, $saved['bands'] );
		$this->assertSame( 6, $saved['bands'][0]['months'] );
		$this->assertSame( 50.0, $saved['minimum'] );
		$this->assertSame( 'or {amount}/month now', $saved['wording'] );
	}

	/**
	 * Removing every band row saves no bands, so no line shows, rather than
	 * the default "0% for 12 months" band.
	 */
	public function test_no_band_rows_save_no_bands() {
		( new InstalmentSettings() )->register();
		update_option(
			Instalments::OPTION,
			array(
				'bands' => array(
					array(
						'from'   => 100,
						'months' => 6,
						'rate'   => 0,
					),
				),
			)
		);

		$this->save( array( 'minimum' => '0' ) );

		$this->assertSame( array(), Instalments::settings()['bands'] );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '240' );
		$product->save();
		$this->assertSame( '', Instalments::text_for( wc_get_product( $product->get_id() ), Instalments::settings() ) );
	}

	/**
	 * The section's own rows don't save over the option.
	 */
	public function test_section_rows_have_their_own_id() {
		$ids = wp_list_pluck( InstalmentSettings::fields(), 'id' );
		$this->assertNotContains( Instalments::OPTION, $ids );
	}
}
