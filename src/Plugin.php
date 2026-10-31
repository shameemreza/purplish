<?php
/**
 * Wires Purplish into WordPress and WooCommerce.
 *
 * @package Purplish
 */

namespace Purplish;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every hook the plugin uses.
 */
final class Plugin {

	/**
	 * The one instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether register() already ran.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * The one instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Adds the hooks. Safe to call more than once.
	 *
	 * @return void
	 */
	public function register() {
		if ( $this->registered ) {
			return;
		}
		$this->registered = true;

		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		add_action( 'woocommerce_loaded', array( $this, 'boot' ) );
	}

	/**
	 * Declares support for WooCommerce order storage (HPOS) and the cart and
	 * checkout blocks. Purplish touches neither, so both are safe.
	 *
	 * @return void
	 */
	public function declare_compatibility() {
		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}
		FeaturesUtil::declare_compatibility( 'custom_order_tables', PURPLISH_FILE, true );
		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PURPLISH_FILE, true );
	}

	/**
	 * Starts the parts that need WooCommerce. Runs only when WooCommerce
	 * loaded, so nothing here has to check for it again.
	 *
	 * @return void
	 */
	public function boot() {
		( new Jobs\DetectColors() )->register();

		if ( is_admin() ) {
			( new Admin\VariationField() )->register();
		}
	}
}
