<?php
/**
 * Tests for the plugin bootstrap.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Purplish\Plugin;
use WP_UnitTestCase;

/**
 * Purplish loads with WooCommerce and declares its compatibility.
 */
class PluginTest extends WP_UnitTestCase {

	/**
	 * The plugin hooks into WooCommerce's compatibility check.
	 */
	public function test_declares_compatibility_on_before_woocommerce_init() {
		$this->assertSame(
			10,
			has_action( 'before_woocommerce_init', array( Plugin::instance(), 'declare_compatibility' ) )
		);
	}

	/**
	 * WooCommerce lists Purplish as compatible with order storage and the
	 * cart and checkout blocks.
	 *
	 * @dataProvider provide_features
	 *
	 * @param string $feature WooCommerce feature ID.
	 */
	public function test_is_compatible_with( $feature ) {
		$compatible = FeaturesUtil::get_compatible_plugins_for_feature( $feature );

		$this->assertContains( plugin_basename( PURPLISH_FILE ), $compatible['compatible'] );
	}

	/**
	 * The features Purplish declares.
	 *
	 * @return array<string, array<string>>
	 */
	public function provide_features() {
		return array(
			'order storage'           => array( 'custom_order_tables' ),
			'cart and checkout block' => array( 'cart_checkout_blocks' ),
		);
	}
}
