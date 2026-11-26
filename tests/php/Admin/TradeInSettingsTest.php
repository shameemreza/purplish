<?php
/**
 * Tests for the trade-in settings section and the product and category setting.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Admin;

use Purplish\Admin\TradeInOverride;
use Purplish\Admin\TradeInSettings;
use Purplish\TradeIn;
use WC_Admin_Settings;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * The notes save cleaned through WooCommerce's settings API, the table
 * saves from its JSON field for a shop manager only, a CSV import adds or
 * replaces and reports skipped lines, and products and categories save
 * their own setting.
 */
class TradeInSettingsTest extends WP_UnitTestCase {

	/**
	 * Clears posted data, messages, options and the user.
	 */
	public function tear_down() {
		$_POST = array();
		delete_option( TradeIn::OPTION );
		delete_option( TradeIn::DEVICES );
		$reset = static function () {
			self::$errors   = array();
			self::$messages = array();
		};
		\Closure::bind( $reset, null, WC_Admin_Settings::class )();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * WooCommerce's queued settings messages, or its errors.
	 *
	 * @param string $kind `messages` or `errors`.
	 * @return string[]
	 */
	private function notes( $kind ) {
		$read = static function () use ( $kind ) {
			return self::$$kind;
		};
		return \Closure::bind( $read, null, WC_Admin_Settings::class )();
	}

	/**
	 * Logs in a shop manager.
	 */
	private function manager() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
	}

	/**
	 * The section is on the Products tab and saves into one option.
	 */
	public function test_section_and_fields_save() {
		$this->assertSame( 'Trade-in', ( new TradeInSettings() )->add_section( array() )[ TradeInSettings::SECTION ] );
		( new TradeInSettings() )->register();
		WC_Admin_Settings::save_fields(
			TradeInSettings::fields(),
			array(
				TradeIn::OPTION => array(
					'scope'       => 'chosen',
					'condition'   => 'Good <b>condition</b>',
					'terms'       => 'yes',
					'terms_label' => 'I agree',
					'link_url'    => 'not a url',
				),
			)
		);
		$saved = TradeIn::settings();
		$this->assertSame( 'chosen', $saved['scope'] );
		$this->assertSame( 'Good condition', $saved['condition'] );
		$this->assertTrue( $saved['terms'] );
		$this->assertSame( 'I agree', $saved['terms_label'] );
		$this->assertSame( '', $saved['link_url'] );
		$this->assertArrayNotHasKey( 'devices', get_option( TradeIn::OPTION ), 'the table isn’t part of the option' );
	}

	/**
	 * The table saves from its JSON field, cleaned, for a shop manager.
	 */
	public function test_save_devices_from_json() {
		$_POST[ TradeInSettings::JSON_FIELD ] = wp_slash(
			wp_json_encode(
				array(
					array(
						'brand'   => 'Apple',
						'model'   => 'iPhone 13',
						'storage' => '128GB',
						'low'     => '600',
						'high'    => '680',
					),
					array(
						'brand' => '',
						'model' => 'dropped',
						'high'  => '5',
					),
				)
			)
		);
		( new TradeInSettings() )->save_devices();
		$this->assertSame( array(), TradeIn::devices(), 'a visitor can’t save' );

		$this->manager();
		( new TradeInSettings() )->save_devices();
		$this->assertCount( 1, TradeIn::devices() );

		$_POST[ TradeInSettings::JSON_FIELD ] = '[]';
		( new TradeInSettings() )->save_devices();
		$this->assertSame( array(), TradeIn::devices(), 'removing every row empties the table' );
	}

	/**
	 * A CSV import merges or replaces and reports what it skipped.
	 */
	public function test_import() {
		TradeIn::save_devices( array( array( 'Apple', 'iPhone 12', '', 400, 450 ) ) );
		$settings = new TradeInSettings();

		$parsed = $settings->import( "brand,model,storage,low,high\nApple,iPhone 13,128GB,600,680\nApple,,,1,2\n", false );
		$this->assertSame( array( 3 ), $parsed['skipped'] );
		$this->assertCount( 2, TradeIn::devices() );
		$this->assertSame( array( 'Imported 1 device. The table has 2 now.' ), $this->notes( 'messages' ) );
		$this->assertSame( array( 'Skipped 1 row without a brand, a model or a value, on line 3.' ), $this->notes( 'errors' ) );

		$settings->import( "Samsung,Galaxy S23,,900,1000\n", true );
		$this->assertSame( array( 'Samsung Galaxy S23' ), array_map( array( TradeIn::class, 'name' ), TradeIn::devices() ) );

		$settings->import( "nothing,useful\n", true );
		$this->assertCount( 1, TradeIn::devices(), 'an import with no good rows leaves the table alone' );

		$this->assertNull( $settings->import( str_repeat( 'a', \Purplish\TradeInCsv::MAX_BYTES + 1 ), true ) );
	}

	/**
	 * A product saves On, Off, or back to the store setting.
	 */
	public function test_product_setting() {
		$product = new WC_Product_Simple();
		$product->save();
		$override = new TradeInOverride();

		$_POST[ TradeInOverride::FIELD ] = 'on';
		$override->save_product( $product );
		$product->save();
		$this->assertSame( 'on', get_post_meta( $product->get_id(), TradeIn::META, true ) );

		$_POST[ TradeInOverride::FIELD ] = 'bogus';
		$override->save_product( $product );
		$product->save();
		$this->assertSame( '', get_post_meta( $product->get_id(), TradeIn::META, true ) );

		ob_start();
		$GLOBALS['product_object'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's product editor global.
		$override->product_field();
		unset( $GLOBALS['product_object'] );
		$this->assertStringContainsString( 'name="purplish_trade_in_mode"', (string) ob_get_clean() );
	}

	/**
	 * A category saves only with its nonce and the capability.
	 */
	public function test_category_setting() {
		$term     = wp_insert_term( 'Phones', 'product_cat' );
		$override = new TradeInOverride();

		$_POST = array(
			TradeInOverride::FIELD    => 'off',
			'purplish_trade_in_nonce' => wp_create_nonce( TradeInOverride::NONCE ),
		);
		$override->save_term( $term['term_id'] );
		$this->assertSame( '', get_term_meta( $term['term_id'], TradeIn::META, true ), 'no capability' );

		$this->manager();
		$_POST['purplish_trade_in_nonce'] = wp_create_nonce( TradeInOverride::NONCE );
		$override->save_term( $term['term_id'] );
		$this->assertSame( 'off', get_term_meta( $term['term_id'], TradeIn::META, true ) );

		$_POST['purplish_trade_in_nonce'] = 'bad';
		$_POST[ TradeInOverride::FIELD ]  = '';
		$override->save_term( $term['term_id'] );
		$this->assertSame( 'off', get_term_meta( $term['term_id'], TradeIn::META, true ), 'a bad nonce changes nothing' );
	}
}
