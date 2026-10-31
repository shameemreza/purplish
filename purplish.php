<?php
/**
 * Plugin Name:          Purplish - Product Page Design for WooCommerce
 * Plugin URI:           https://github.com/shameemreza/purplish
 * Description:          Design options for the WooCommerce product page, added to WooCommerce's own blocks. Works with block and classic themes.
 * Version:              1.0.0
 * Author:               Shameem Reza
 * Author URI:           https://shameem.dev/
 * License:              GPL v2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          purplish
 * Domain Path:          /languages
 * Requires at least:    7.0
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * WC requires at least: 11.0
 * WC tested up to:      11.2
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

// The test bootstrap defines these first, so each one is guarded.
defined( 'PURPLISH_VERSION' ) || define( 'PURPLISH_VERSION', '1.0.0' );
defined( 'PURPLISH_FILE' ) || define( 'PURPLISH_FILE', __FILE__ );
defined( 'PURPLISH_DIR' ) || define( 'PURPLISH_DIR', plugin_dir_path( __FILE__ ) );
defined( 'PURPLISH_URL' ) || define( 'PURPLISH_URL', plugin_dir_url( __FILE__ ) );

require_once PURPLISH_DIR . 'src/Autoloader.php';
\Purplish\Autoloader::register();

register_activation_hook( __FILE__, array( \Purplish\Jobs\DetectColors::class, 'activate' ) );

\Purplish\Plugin::instance()->register();
