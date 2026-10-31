<?php
/**
 * Runs when Purplish is deleted from the Plugins screen. Removes its
 * settings, its variation data and its queued background jobs.
 *
 * Block settings saved in a template stay in that template's markup:
 * WordPress ignores an attribute no plugin registers, so they're harmless.
 *
 * @package Purplish
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'purplish_settings' );
delete_option( 'purplish_queue_batch' );

// Variation meta. Variations are posts, so this is the meta API, not SQL.
foreach ( array( '_purplish_mode', '_purplish_custom_color', '_purplish_auto_color', '_purplish_auto_source' ) as $purplish_meta_key ) {
	delete_metadata( 'post', 0, $purplish_meta_key, '', true );
}

// Queued jobs, when WooCommerce's Action Scheduler is loaded.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	// Hook name alone cancels every job for it, whatever its arguments.
	as_unschedule_all_actions( 'purplish_detect_variation_color' );
	as_unschedule_all_actions( 'purplish_detect_batch' );
}
