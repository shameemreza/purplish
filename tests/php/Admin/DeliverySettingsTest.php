<?php
/**
 * Tests for the Delivery dates settings.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Admin;

use Purplish\Admin\DeliverySettings;
use Purplish\Delivery;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * The section sits on WooCommerce's Shipping tab, its fields save into one
 * option that is cleaned as a whole, and the product field saves extra
 * processing days with the product.
 */
class DeliverySettingsTest extends WP_UnitTestCase {

	/**
	 * Puts the option and the posted data back after each test.
	 */
	public function tear_down() {
		delete_option( Delivery::OPTION );
		unset( $_POST[ Delivery::META ] );
		parent::tear_down();
	}

	/**
	 * The section is added, and its fields show only in it.
	 */
	public function test_adds_the_section_and_its_fields_only_there() {
		$settings = new DeliverySettings();
		$this->assertArrayHasKey( 'purplish_delivery', $settings->add_section( array() ) );
		$this->assertSame( array( 'other' ), $settings->settings( array( 'other' ), 'options' ) );

		$ids = wp_list_pluck( $settings->settings( array(), 'purplish_delivery' ), 'id' );
		foreach ( array( 'processing', 'cutoff', 'days', 'holidays', 'from', 'to', 'returns', 'free_returns' ) as $key ) {
			$this->assertContains( 'purplish_delivery[' . $key . ']', $ids );
		}
	}

	/**
	 * Saving through WooCommerce cleans the whole option.
	 */
	public function test_saving_through_woocommerce_cleans_the_whole_option() {
		( new DeliverySettings() )->register();
		$data = array(
			'purplish_delivery' => array(
				'processing'   => '2',
				'cutoff'       => '7:05',
				'days'         => array( '1', '3', '5' ),
				'holidays'     => "2026-12-25\nnope",
				'from'         => '5',
				'to'           => '1',
				'returns'      => '14',
				'free_returns' => '',
			),
		);
		\WC_Admin_Settings::save_fields( DeliverySettings::fields(), $data );

		$saved = get_option( Delivery::OPTION );
		$this->assertSame( 2, $saved['processing'] );
		$this->assertSame( '07:05', $saved['cutoff'] );
		$this->assertSame( array( '1', '3', '5' ), $saved['days'] );
		$this->assertSame( '2026-12-25', $saved['holidays'] );
		$this->assertSame( 5, $saved['to'] );
		$this->assertSame( 'no', $saved['free_returns'] );
		$this->assertSame( array( '2026-12-25' ), Delivery::settings()['holidays'] );
	}

	/**
	 * The product field saves extra days in range and clears when empty.
	 */
	public function test_product_field_saves_and_clears_extra_days() {
		$product                 = new WC_Product_Simple();
		$settings                = new DeliverySettings();
		$_POST[ Delivery::META ] = '99';
		$settings->save_product( $product );
		$product->save();
		$this->assertSame( '60', get_post_meta( $product->get_id(), Delivery::META, true ) );

		$_POST[ Delivery::META ] = '';
		$settings->save_product( $product );
		$product->save();
		$this->assertSame( '', get_post_meta( $product->get_id(), Delivery::META, true ) );
	}

	/**
	 * The product field prints with its saved value.
	 */
	public function test_product_field_prints_in_the_shipping_panel() {
		global $product_object;
		include_once WC()->plugin_path() . '/includes/admin/wc-meta-box-functions.php';
		$product_object = new WC_Product_Simple(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.
		$product_object->update_meta_data( Delivery::META, 4 );

		ob_start();
		( new DeliverySettings() )->product_field();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="' . Delivery::META . '"', $html );
		$this->assertStringContainsString( 'value="4"', $html );
		$product_object = null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.
	}
}
