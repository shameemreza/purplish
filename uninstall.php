<?php
/**
 * Runs when Purplish is deleted from the Plugins screen. Removes its
 * settings, its variation data, its size chart picks and fit notes, its
 * delivery dates settings and extra processing days, its instalment
 * and trade-in settings with their product and category overrides, the
 * trade-in device table, and its queued background jobs. Size charts are
 * the store's own synced patterns, so they stay. The trade-in lines on
 * past orders are part of those orders' records, so they stay too.
 *
 * Block settings saved in a template stay in that template's markup:
 * WordPress ignores an attribute no plugin registers, so they're harmless.
 *
 * @package Purplish
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'purplish_settings' );
delete_option( 'purplish_queue_batch' );
delete_option( 'purplish_container_walk' );
delete_option( 'purplish_delivery' );
delete_option( 'purplish_instalments' );
delete_option( 'purplish_trade_in' );
delete_option( 'purplish_trade_in_devices' );

// Variation and product meta. Both are posts, so this is the meta API, not SQL.
foreach ( array( '_purplish_mode', '_purplish_custom_color', '_purplish_auto_color', '_purplish_auto_source', '_purplish_size_chart', '_purplish_fit_note', '_purplish_processing_days', '_purplish_instalments', '_purplish_trade_in' ) as $purplish_meta_key ) {
	delete_metadata( 'post', 0, $purplish_meta_key, '', true );
}
foreach ( array( 'purplish_size_chart', '_purplish_instalments', '_purplish_trade_in' ) as $purplish_meta_key ) {
	delete_metadata( 'term', 0, $purplish_meta_key, '', true );
}

// Queued jobs, when WooCommerce's Action Scheduler is loaded.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	// Hook name alone cancels every job for it, whatever its arguments.
	as_unschedule_all_actions( 'purplish_detect_variation_color' );
	as_unschedule_all_actions( 'purplish_detect_batch' );
}
