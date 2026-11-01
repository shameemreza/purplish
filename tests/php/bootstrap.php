<?php
/**
 * PHPUnit bootstrap. Loads WooCommerce and Purplish into the WordPress
 * test suite, and installs WooCommerce's tables in the test database.
 *
 * @package Purplish
 */

$purplish_root = dirname( __DIR__, 2 );

require $purplish_root . '/vendor/autoload.php';

// The wp-phpunit package reads its config path from the environment.
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv

$purplish_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

require_once $purplish_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $purplish_root ) {
		require WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';

		// Load Purplish from the plugins folder, as WordPress would, so it has
		// its real plugin ID (purplish/purplish.php). CI links the checkout
		// there; register the link so the ID resolves through it.
		$purplish_installed = WP_PLUGIN_DIR . '/purplish/purplish.php';
		if ( file_exists( $purplish_installed ) ) {
			wp_register_plugin_realpath( $purplish_installed );
			require $purplish_installed;
		} else {
			require $purplish_root . '/purplish.php';
		}
	}
);

tests_add_filter(
	'setup_theme',
	static function () {
		WC_Install::install();

		// WooCommerce adds its roles during the install, so reload them.
		$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_roles();
	}
);

require $purplish_tests_dir . '/includes/bootstrap.php';
