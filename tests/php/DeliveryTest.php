<?php
/**
 * Tests for the delivery dates settings and date math.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Purplish\Delivery;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * Settings are cleaned on read, days count only working days that aren't
 * holidays, the cut-off decides the order day, a page carries the states
 * for the next days, and pickup and returns read WooCommerce's own options.
 */
class DeliveryTest extends WP_UnitTestCase {

	/**
	 * Puts options and filters back after each test.
	 */
	public function tear_down() {
		delete_option( Delivery::OPTION );
		delete_option( 'woocommerce_pickup_location_settings' );
		delete_option( 'pickup_location_pickup_locations' );
		delete_option( 'woocommerce_refund_returns_page_id' );
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 0 );
		remove_all_filters( 'purplish_delivery_settings' );
		remove_all_filters( 'purplish_delivery_pickup' );
		parent::tear_down();
	}

	/**
	 * A moment in New York.
	 *
	 * @param string $when Like 2026-11-20 10:00.
	 * @return DateTimeImmutable
	 */
	private function moment( $when ) {
		return new DateTimeImmutable( $when, new DateTimeZone( 'America/New_York' ) );
	}

	/**
	 * Settings with changes.
	 *
	 * @param array $changes Changes to the defaults.
	 * @return array
	 */
	private function settings( $changes = array() ) {
		return Delivery::clean( array_merge( Delivery::defaults(), $changes ) );
	}

	/**
	 * With nothing saved, the settings are the defaults.
	 */
	public function test_defaults_when_nothing_is_saved() {
		$this->assertSame( Delivery::defaults(), Delivery::settings() );
	}

	/**
	 * Numbers stay in range, and the latest day is never before the earliest.
	 */
	public function test_clean_keeps_numbers_in_range_and_to_at_least_from() {
		$clean = Delivery::clean(
			array(
				'processing' => '-3',
				'from'       => '7',
				'to'         => '2',
				'returns'    => '900',
			)
		);
		$this->assertSame( 0, $clean['processing'] );
		$this->assertSame( 7, $clean['from'] );
		$this->assertSame( 7, $clean['to'] );
		$this->assertSame( 365, $clean['returns'] );
	}

	/**
	 * A loose cut-off gets two digits, a bad one the default, and at least one day stays open.
	 */
	public function test_clean_fixes_the_cut_off_and_keeps_a_working_day() {
		$this->assertSame( '09:30', Delivery::clean( array( 'cutoff' => '9:30' ) )['cutoff'] );
		$this->assertSame( '14:00', Delivery::clean( array( 'cutoff' => '25:00' ) )['cutoff'] );
		$this->assertSame( array( '1', '2', '3', '4', '5' ), Delivery::clean( array( 'days' => array() ) )['days'] );
		$this->assertSame( array( '6', '7' ), Delivery::clean( array( 'days' => array( 7, '6', '9' ) ) )['days'] );
	}

	/**
	 * Free returns is yes unless it says no.
	 */
	public function test_clean_reads_free_returns() {
		$this->assertSame( 'no', Delivery::clean( array( 'free_returns' => 'no' ) )['free_returns'] );
		$this->assertSame( 'yes', Delivery::clean( array() )['free_returns'] );
	}

	/**
	 * Holidays keep real dates only, once each, in order.
	 */
	public function test_holidays_keep_real_dates_once_and_sorted() {
		$this->assertSame(
			array( '2026-12-25', '2026-12-31' ),
			Delivery::holidays( "2026-12-31\n2026-12-25, 2026-02-30\nlater\n2026-12-25" )
		);
	}

	/**
	 * Before the cut-off, the order counts from today.
	 */
	public function test_before_the_cut_off_the_order_counts_from_today() {
		// Friday, November 20, 2026, 10:00. One processing day, 2 to 4 days.
		$at = Delivery::at( $this->moment( '2026-11-20 10:00' ), $this->settings() );
		$this->assertSame( '2026-11-23', $at['ship']->format( 'Y-m-d' ) );
		$this->assertSame( '2026-11-25', $at['from']->format( 'Y-m-d' ) );
		$this->assertSame( '2026-11-27', $at['to']->format( 'Y-m-d' ) );
		$this->assertSame( $this->moment( '2026-11-20 14:00' )->getTimestamp(), $at['until'] );
	}

	/**
	 * After the cut-off, the order counts from the next working day.
	 */
	public function test_after_the_cut_off_the_order_counts_from_the_next_working_day() {
		$at = Delivery::at( $this->moment( '2026-11-20 14:00' ), $this->settings() );
		$this->assertSame( 0, $at['until'] );
		$this->assertSame( '2026-11-24', $at['ship']->format( 'Y-m-d' ) );
		$this->assertSame( '2026-11-30', $at['to']->format( 'Y-m-d' ) );
	}

	/**
	 * Weekends and holidays are not working days.
	 */
	public function test_weekends_and_holidays_are_skipped() {
		$settings = $this->settings( array( 'holidays' => "2026-11-26\n2026-11-27" ) );
		$at       = Delivery::at( $this->moment( '2026-11-21 09:00' ), $settings );
		// Saturday: the order counts from Monday, ships Tuesday, Thanksgiving and Friday are closed.
		$this->assertSame( 0, $at['until'] );
		$this->assertSame( '2026-11-24', $at['ship']->format( 'Y-m-d' ) );
		$this->assertSame( '2026-11-30', $at['from']->format( 'Y-m-d' ) );
		$this->assertSame( '2026-12-02', $at['to']->format( 'Y-m-d' ) );
	}

	/**
	 * With no processing days, the order ships the day it counts from.
	 */
	public function test_zero_processing_days_ships_the_same_day() {
		$at = Delivery::at( $this->moment( '2026-11-18 08:00' ), $this->settings( array( 'processing' => 0 ) ) );
		$this->assertSame( '2026-11-18', $at['ship']->format( 'Y-m-d' ) );
	}

	/**
	 * States change at each cut-off and midnight, and days that change nothing merge.
	 */
	public function test_states_change_at_each_cut_off_and_merge_closed_days() {
		$data   = Delivery::states( $this->moment( '2026-11-20 10:00' ), $this->settings(), 'short' );
		$states = $data['states'];

		$this->assertSame( $this->moment( '2026-11-20 10:00' )->getTimestamp(), $states[0]['t'] );
		$this->assertSame( 'Fri, Nov 27', $states[0]['b'] );
		$this->assertSame( $this->moment( '2026-11-20 14:00' )->getTimestamp(), $states[1]['t'] );
		$this->assertSame( 0, $states[1]['c'] );
		$this->assertSame( 'Mon, Nov 30', $states[1]['b'] );
		// The weekend changes nothing, so the next state is Monday's midnight.
		$this->assertSame( $this->moment( '2026-11-23 00:00' )->getTimestamp(), $states[2]['t'] );
		$this->assertSame( $this->moment( '2026-11-28 00:00' )->getTimestamp(), $data['end'] );

		$times = wp_list_pluck( $states, 't' );
		$this->assertSame( $times, array_values( array_unique( $times ) ) );
	}

	/**
	 * Dates read short or in the site's date format.
	 */
	public function test_format_follows_the_site_date_format() {
		update_option( 'date_format', 'j F Y' );
		$this->assertSame( '27 November 2026', Delivery::format( $this->moment( '2026-11-27' ), 'site' ) );
		$this->assertSame( 'Fri, Nov 27', Delivery::format( $this->moment( '2026-11-27' ), 'short' ) );
	}

	/**
	 * The time left reads in hours and minutes, at least a minute.
	 */
	public function test_time_left_reads_hours_and_minutes() {
		$this->assertSame( '3 hours 20 minutes', Delivery::time_left( 3 * HOUR_IN_SECONDS + 20 * MINUTE_IN_SECONDS + 59 ) );
		$this->assertSame( '1 hour', Delivery::time_left( HOUR_IN_SECONDS ) );
		$this->assertSame( '45 minutes', Delivery::time_left( 45 * MINUTE_IN_SECONDS ) );
		$this->assertSame( '1 minute', Delivery::time_left( 5 ) );
	}

	/**
	 * A product adds its extra processing days, and the filter can change the settings.
	 */
	public function test_a_product_adds_its_extra_days_and_the_filter_can_change_them() {
		$product = new WC_Product_Simple();
		$product->save();
		update_post_meta( $product->get_id(), Delivery::META, '3' );

		$this->assertSame( 4, Delivery::settings_for( $product )['processing'] );
		$this->assertSame( 1, Delivery::settings_for( null )['processing'] );

		add_filter(
			'purplish_delivery_settings',
			static function ( $settings ) {
				$settings['to'] = 9;
				return $settings;
			}
		);
		$this->assertSame( 9, Delivery::settings_for( $product )['to'] );
	}

	/**
	 * Only shipped products in stock get a date.
	 */
	public function test_only_shipped_products_in_stock_get_a_date() {
		$product = new WC_Product_Simple();
		$product->set_stock_status( 'instock' );
		$this->assertTrue( Delivery::dated( $product ) );
		$this->assertTrue( Delivery::dated( null ) );

		$product->set_stock_status( 'onbackorder' );
		$this->assertFalse( Delivery::dated( $product ) );

		$product->set_stock_status( 'outofstock' );
		$this->assertFalse( Delivery::dated( $product ) );

		$product->set_stock_status( 'instock' );
		$product->set_virtual( true );
		$this->assertFalse( Delivery::dated( $product ) );
	}

	/**
	 * Pickup needs Local pickup on with an enabled location, and says when it costs.
	 */
	public function test_pickup_needs_local_pickup_on_with_an_enabled_location() {
		$this->assertNull( Delivery::pickup() );

		update_option(
			'woocommerce_pickup_location_settings',
			array(
				'enabled' => 'yes',
				'cost'    => '',
			)
		);
		$this->assertNull( Delivery::pickup() );

		update_option(
			'pickup_location_pickup_locations',
			array(
				array(
					'name'    => 'Downtown',
					'enabled' => true,
				),
				array(
					'name'    => 'Harbor',
					'enabled' => false,
				),
			)
		);
		$this->assertSame(
			array(
				'free' => true,
				'name' => 'Downtown',
			),
			Delivery::pickup()
		);

		update_option(
			'woocommerce_pickup_location_settings',
			array(
				'enabled' => 'yes',
				'cost'    => '5',
			)
		);
		$this->assertFalse( Delivery::pickup()['free'] );

		update_option(
			'woocommerce_pickup_location_settings',
			array( 'enabled' => 'no' )
		);
		$this->assertNull( Delivery::pickup() );
	}

	/**
	 * The returns link needs a published refund page.
	 */
	public function test_returns_url_needs_a_published_page() {
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		update_option( 'woocommerce_refund_returns_page_id', $page );
		$this->assertSame( '', Delivery::returns_url() );

		wp_publish_post( $page );
		$this->assertSame( get_permalink( $page ), Delivery::returns_url() );
	}
}
