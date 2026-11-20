<?php
/**
 * Delivery dates settings, on WooCommerce's Shipping tab and the product's
 * Shipping panel.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a Delivery dates section to WooCommerce > Settings > Shipping, saved
 * by WooCommerce's settings API (it checks the nonce and capability) into
 * one option, and an Extra processing days field to the product's Shipping
 * panel, saved with the product.
 *
 * @since 1.4.0
 */
final class DeliverySettings {

	const SECTION = 'purplish_delivery';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_get_sections_shipping', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_shipping', array( $this, 'settings' ), 10, 2 );
		add_filter( 'pre_update_option_' . Delivery::OPTION, array( $this, 'sanitize' ) );
		add_action( 'woocommerce_product_options_shipping', array( $this, 'product_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product' ) );
	}

	/**
	 * Adds the Delivery dates section to the Shipping tab.
	 *
	 * @param array<string, string> $sections Sections.
	 * @return array<string, string>
	 */
	public function add_section( $sections ) {
		$sections[ self::SECTION ] = __( 'Delivery dates', 'purplish' );
		return $sections;
	}

	/**
	 * The section's fields.
	 *
	 * @param array  $settings        Settings of the current section.
	 * @param string $current_section Section ID.
	 * @return array
	 */
	public function settings( $settings, $current_section ) {
		return self::SECTION === $current_section ? self::fields() : $settings;
	}

	/**
	 * The fields. Each saves into the `purplish_delivery` option.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields() {
		$saved    = Delivery::settings();
		$key      = static function ( $name ) {
			return Delivery::OPTION . '[' . $name . ']';
		};
		$weekdays = array(
			'1' => __( 'Monday', 'purplish' ),
			'2' => __( 'Tuesday', 'purplish' ),
			'3' => __( 'Wednesday', 'purplish' ),
			'4' => __( 'Thursday', 'purplish' ),
			'5' => __( 'Friday', 'purplish' ),
			'6' => __( 'Saturday', 'purplish' ),
			'7' => __( 'Sunday', 'purplish' ),
		);

		return array(
			array(
				'id'    => 'purplish_delivery_dates',
				'type'  => 'title',
				'title' => __( 'Delivery dates', 'purplish' ),
				'desc'  => __( 'The Delivery Line block turns these into a date shoppers can read next to the buy button, like "Order within 3 hours, arrives by Thu, Nov 26". Days count only the working days below, skipping holidays, in the site’s time zone.', 'purplish' ),
			),
			array(
				'id'                => $key( 'processing' ),
				'type'              => 'number',
				'title'             => __( 'Processing days', 'purplish' ),
				'desc'              => __( 'Working days to pack an order. 0 ships it the same day when it comes in before the cut-off.', 'purplish' ),
				'desc_tip'          => true,
				'default'           => $saved['processing'],
				'css'               => 'width:6em',
				'custom_attributes' => array(
					'min'  => 0,
					'max'  => 60,
					'step' => 1,
				),
			),
			array(
				'id'       => $key( 'cutoff' ),
				'type'     => 'time',
				'title'    => __( 'Cut-off time', 'purplish' ),
				'desc'     => __( 'Orders after this time count from the next working day.', 'purplish' ),
				'desc_tip' => true,
				'default'  => $saved['cutoff'],
				'css'      => 'width:8em',
			),
			array(
				'id'      => $key( 'days' ),
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'title'   => __( 'Working days', 'purplish' ),
				'desc'    => __( 'The days orders are packed and carriers deliver.', 'purplish' ),
				'options' => $weekdays,
				'default' => $saved['days'],
			),
			array(
				'id'          => $key( 'holidays' ),
				'type'        => 'textarea',
				'title'       => __( 'Holidays', 'purplish' ),
				'desc'        => __( 'Days the store is closed, one per line, as 2026-12-25.', 'purplish' ),
				'default'     => implode( "\n", $saved['holidays'] ),
				'placeholder' => '2026-12-25',
				'css'         => 'width:12em;min-height:6em',
			),
			array(
				'id'                => $key( 'from' ),
				'type'              => 'number',
				'title'             => __( 'Delivery takes from', 'purplish' ),
				'desc'              => __( 'working days after it ships', 'purplish' ),
				'default'           => $saved['from'],
				'css'               => 'width:6em',
				'custom_attributes' => array(
					'min'  => 0,
					'max'  => 60,
					'step' => 1,
				),
			),
			array(
				'id'                => $key( 'to' ),
				'type'              => 'number',
				'title'             => __( 'Delivery takes up to', 'purplish' ),
				'desc'              => __( 'working days after it ships', 'purplish' ),
				'default'           => $saved['to'],
				'css'               => 'width:6em',
				'custom_attributes' => array(
					'min'  => 0,
					'max'  => 60,
					'step' => 1,
				),
			),
			array(
				'id'                => $key( 'returns' ),
				'type'              => 'number',
				'title'             => __( 'Returns window', 'purplish' ),
				'desc'              => __( 'days. 0 hides the returns line.', 'purplish' ),
				'default'           => $saved['returns'],
				'css'               => 'width:6em',
				'custom_attributes' => array(
					'min'  => 0,
					'max'  => 365,
					'step' => 1,
				),
			),
			array(
				'id'      => $key( 'free_returns' ),
				'type'    => 'checkbox',
				'title'   => __( 'Free returns', 'purplish' ),
				'desc'    => __( 'Say returns are free', 'purplish' ),
				'default' => $saved['free_returns'],
			),
			array(
				'id'   => 'purplish_delivery_dates',
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * Cleans the whole option before it's saved. WooCommerce sanitizes each
	 * field on its own, so ranges and the cut-off are checked here. Holidays
	 * stay text, one per line, so the textarea shows them again.
	 *
	 * @param mixed $value The settings about to be saved.
	 * @return array<string, mixed>
	 */
	public function sanitize( $value ) {
		$clean             = Delivery::clean( is_array( $value ) ? $value : array() );
		$clean['holidays'] = implode( "\n", $clean['holidays'] );
		return $clean;
	}

	/**
	 * The Extra processing days field in the product's Shipping panel.
	 *
	 * @return void
	 */
	public function product_field() {
		global $product_object;

		$value = $product_object instanceof \WC_Product ? Delivery::extra_days( $product_object->get_meta( Delivery::META ) ) : 0;

		woocommerce_wp_text_input(
			array(
				'id'                => Delivery::META,
				'type'              => 'number',
				'label'             => __( 'Extra processing days', 'purplish' ),
				'description'       => __( 'Working days this product adds before it ships, like a made-to-order item. Used by the Delivery Line block.', 'purplish' ),
				'desc_tip'          => true,
				'value'             => $value ? (string) $value : '',
				'placeholder'       => '0',
				'custom_attributes' => array(
					'min'  => 0,
					'max'  => 60,
					'step' => 1,
				),
			)
		);
	}

	/**
	 * Saves the field. WooCommerce checks the nonce and capability before
	 * this action runs.
	 *
	 * @param \WC_Product $product The product being saved.
	 * @return void
	 */
	public function save_product( $product ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies its meta box nonce before this action.
		$days = isset( $_POST[ Delivery::META ] ) ? Delivery::extra_days( sanitize_text_field( wp_unslash( $_POST[ Delivery::META ] ) ) ) : 0;
		if ( $days ) {
			$product->update_meta_data( Delivery::META, $days );
		} else {
			$product->delete_meta_data( Delivery::META );
		}
	}
}
