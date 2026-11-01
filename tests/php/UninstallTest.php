<?php
/**
 * Tests for uninstall.php.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\Jobs\DetectColors;
use Purplish\Meta;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * Uninstall removes every option, variation meta key and queued job.
 */
class UninstallTest extends WP_UnitTestCase {

	/**
	 * Fills every kind of Purplish data, runs uninstall.php, and checks it's gone.
	 */
	public function test_uninstall_removes_everything() {
		update_option( 'purplish_settings', array( 'button' => 'yes' ) );
		update_option( DetectColors::QUEUE_OPTION, 1 );

		$variations = Products::sweater();
		foreach ( $variations as $variation ) {
			$variation->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
			$variation->update_meta_data( Meta::CUSTOM_COLOR, '#7b2cbf' );
			$variation->update_meta_data( Meta::AUTO_COLOR, '#d94421' );
			$variation->update_meta_data( Meta::AUTO_SOURCE, '1:1' );
			$variation->save();
		}
		as_enqueue_async_action( DetectColors::HOOK_ONE, array( 'variation_id' => 1 ), DetectColors::GROUP );
		as_enqueue_async_action( DetectColors::HOOK_BATCH, array( 'offset' => 50 ), DetectColors::GROUP );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'purplish/purplish.php' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress's own constant.
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$this->assertFalse( get_option( 'purplish_settings' ) );
		$this->assertFalse( get_option( DetectColors::QUEUE_OPTION ) );
		foreach ( $variations as $variation ) {
			foreach ( array( Meta::MODE, Meta::CUSTOM_COLOR, Meta::AUTO_COLOR, Meta::AUTO_SOURCE ) as $key ) {
				$this->assertSame( '', get_post_meta( $variation->get_id(), $key, true ), $key );
			}
		}
		$this->assertFalse( as_has_scheduled_action( DetectColors::HOOK_ONE ) );
		$this->assertFalse( as_has_scheduled_action( DetectColors::HOOK_BATCH ) );
	}
}
