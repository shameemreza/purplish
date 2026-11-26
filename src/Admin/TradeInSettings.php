<?php
/**
 * Trade-in settings: a section on WooCommerce's Products tab with the
 * device table.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\TradeIn;
use Purplish\TradeInCsv;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a Trade-in section to WooCommerce > Settings > Products. The notes
 * and terms save through WooCommerce's settings API, which checks the
 * nonce and capability first. The device table saves on the same submit:
 * the admin script posts the rows as one JSON field, so a long table never
 * runs into the server's input limit, and a CSV file uploaded with the
 * form replaces the table or adds to it. Export is its own link with its
 * own nonce. Products and categories get their setting in
 * TradeInOverride.
 *
 * @since 1.4.1
 */
final class TradeInSettings {

	const SECTION = 'purplish_trade_in';

	const EXPORT = 'purplish_trade_in_export';

	/**
	 * Posted fields: the table as JSON, the CSV file and how to import it.
	 */
	const JSON_FIELD   = 'purplish_trade_in_devices_json';
	const FILE_FIELD   = 'purplish_trade_in_csv';
	const IMPORT_FIELD = 'purplish_trade_in_import';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'settings' ), 10, 2 );
		add_action( 'woocommerce_admin_field_purplish_trade_in_devices', array( $this, 'devices_field' ) );
		add_filter( 'pre_update_option_' . TradeIn::OPTION, array( $this, 'sanitize' ) );
		add_action( 'woocommerce_update_options_products_' . self::SECTION, array( $this, 'save_devices' ) );
		add_action( 'admin_post_' . self::EXPORT, array( $this, 'export' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds the Trade-in section to the Products tab.
	 *
	 * @param array<string, string> $sections Sections.
	 * @return array<string, string>
	 */
	public function add_section( $sections ) {
		$sections[ self::SECTION ] = __( 'Trade-in', 'purplish' );
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
	 * The fields. Each saves into the `purplish_trade_in` option, except
	 * the device table, which has no ID so WooCommerce leaves it alone.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields() {
		$saved = TradeIn::settings();
		$key   = static function ( $name ) {
			return TradeIn::OPTION . '[' . $name . ']';
		};

		return array(
			array(
				'id'    => 'purplish_trade_in_section',
				'type'  => 'title',
				'title' => __( 'Trade-in', 'purplish' ),
				'desc'  => sprintf(
					/* translators: %s: "Trade-in", the name of the option on the Add to Cart + Options block. */
					__( 'Shoppers say whether they’re trading in an old device, pick it, and see your estimate. It shows where the Add to Cart + Options block has %s turned on in its Purplish panel. The device and the estimate go with the cart item and the order. Nothing comes off the price: you settle the value when you inspect the device.', 'purplish' ),
					'<strong>' . esc_html__( 'Trade-in', 'purplish' ) . '</strong>'
				),
			),
			array(
				'id'      => $key( 'scope' ),
				'type'    => 'select',
				'title'   => __( 'Products that take trade-in', 'purplish' ),
				'desc'    => __( 'A product or category can set its own in its Trade-in setting.', 'purplish' ),
				'default' => $saved['scope'],
				'options' => array(
					'all'    => __( 'All products', 'purplish' ),
					'chosen' => __( 'Only products and categories set to On', 'purplish' ),
				),
			),
			array(
				'type'  => 'purplish_trade_in_devices',
				'title' => __( 'Devices', 'purplish' ),
			),
			array(
				'id'      => $key( 'condition' ),
				'type'    => 'text',
				'title'   => __( 'Condition note', 'purplish' ),
				'desc'    => __( 'Shows under the estimate. Leave empty for none.', 'purplish' ),
				'default' => $saved['condition'],
				'css'     => 'width:24em',
			),
			array(
				'id'      => $key( 'inspection' ),
				'type'    => 'text',
				'title'   => __( 'Inspection note', 'purplish' ),
				'desc'    => __( 'Shows after the condition note. Leave empty for none.', 'purplish' ),
				'default' => $saved['inspection'],
				'css'     => 'width:24em',
			),
			array(
				'id'          => $key( 'link_url' ),
				'type'        => 'url',
				'title'       => __( '"How it works" link', 'purplish' ),
				'desc'        => __( 'A page about your trade-in. Leave empty for no link.', 'purplish' ),
				'desc_tip'    => true,
				'default'     => $saved['link_url'],
				'placeholder' => 'https://',
				'css'         => 'width:24em',
			),
			array(
				'id'      => $key( 'link_text' ),
				'type'    => 'text',
				'title'   => __( 'Link text', 'purplish' ),
				'default' => $saved['link_text'],
				'css'     => 'width:12em',
			),
			array(
				'id'      => $key( 'terms' ),
				'type'    => 'checkbox',
				'title'   => __( 'Terms', 'purplish' ),
				'desc'    => __( 'Shoppers who pick Yes tick a box to accept your trade-in terms before Add to Cart', 'purplish' ),
				'default' => $saved['terms'] ? 'yes' : 'no',
			),
			array(
				'id'      => $key( 'terms_label' ),
				'type'    => 'text',
				'title'   => __( 'Terms box label', 'purplish' ),
				'default' => $saved['terms_label'],
				'css'     => 'width:24em',
			),
			array(
				'id'          => $key( 'terms_url' ),
				'type'        => 'url',
				'title'       => __( 'Terms page', 'purplish' ),
				'desc'        => __( 'Adds a "Read the terms" link after the box. Leave empty for no link.', 'purplish' ),
				'desc_tip'    => true,
				'default'     => $saved['terms_url'],
				'placeholder' => 'https://',
				'css'         => 'width:24em',
			),
			array(
				'id'   => 'purplish_trade_in_section',
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * Cleans the whole option before it's saved.
	 *
	 * @param mixed $value The settings about to be saved.
	 * @return array<string, mixed>
	 */
	public function sanitize( $value ) {
		return TradeIn::clean( is_array( $value ) ? $value : array() );
	}

	/**
	 * Prints the device table row: the rows, a template for new ones, the
	 * JSON field the script fills on submit, the CSV import and export.
	 *
	 * @param array<string, mixed> $field The field.
	 * @return void
	 */
	public function devices_field( $field ) {
		$devices = TradeIn::devices();
		$rows    = '';
		foreach ( $devices as $device ) {
			$rows .= self::row( $device );
		}
		$export = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::EXPORT ), self::EXPORT );
		$count  = sprintf(
			/* translators: %s: number of devices. */
			_n( '%s device', '%s devices', count( $devices ), 'purplish' ),
			number_format_i18n( count( $devices ) )
		);

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Each part is escaped where it's built.
		echo '<tr><th scope="row" class="titledesc">' . esc_html( (string) $field['title'] ) . '</th><td class="forminp">'
			. '<div class="purplish-devices" data-purplish-devices>'
			. '<p class="purplish-devices-count">' . esc_html( $count ) . '</p>'
			. '<table class="widefat striped purplish-devices-table"><thead><tr>'
			. '<th scope="col">' . esc_html__( 'Brand', 'purplish' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Model', 'purplish' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Storage', 'purplish' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Low value', 'purplish' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'High value', 'purplish' ) . '</th>'
			. '<th scope="col"><span class="screen-reader-text">' . esc_html__( 'Remove', 'purplish' ) . '</span></th>'
			. '</tr></thead><tbody>' . $rows . '</tbody></table>'
			. '<template>' . self::row( null ) . '</template>'
			. '<p><button type="button" class="button purplish-device-add">' . esc_html__( 'Add device', 'purplish' ) . '</button></p>'
			. '<textarea name="' . esc_attr( self::JSON_FIELD ) . '" hidden>' . esc_textarea( (string) wp_json_encode( $devices ) ) . '</textarea>'
			. '<p class="description">' . esc_html__( 'A device needs a brand, a model and a high value. When low and high are the same, shoppers see one figure. Storage can stay empty. Rows without a brand, model or value are dropped on save.', 'purplish' ) . '</p>'
			. '</div>'
			. '<fieldset class="purplish-devices-import"><legend>' . esc_html__( 'Import a CSV file', 'purplish' ) . '</legend>'
			. '<p class="description">' . esc_html__( 'Columns: brand, model, storage, low, high. A first row that starts with "brand" is skipped. The file imports when you save.', 'purplish' ) . '</p>'
			. '<p><input type="file" name="' . esc_attr( self::FILE_FIELD ) . '" accept=".csv,text/csv" aria-label="' . esc_attr__( 'CSV file', 'purplish' ) . '"></p>'
			. '<p><label><input type="radio" name="' . esc_attr( self::IMPORT_FIELD ) . '" value="merge" checked> ' . esc_html__( 'Add to the table (a device already there takes the new values)', 'purplish' ) . '</label><br>'
			. '<label><input type="radio" name="' . esc_attr( self::IMPORT_FIELD ) . '" value="replace"> ' . esc_html__( 'Replace the table', 'purplish' ) . '</label></p>'
			. '</fieldset>'
			. ( $devices ? '<p><a class="button" href="' . esc_url( $export ) . '">' . esc_html__( 'Export CSV', 'purplish' ) . '</a></p>' : '' )
			. '</td></tr>';
		// phpcs:enable
	}

	/**
	 * One table row. The inputs have no names: the script posts the rows
	 * as JSON.
	 *
	 * @param array{brand: string, model: string, storage: string, low: float, high: float}|null $device Device, or null for the new row template.
	 * @return string
	 */
	private static function row( $device ) {
		$cell = static function ( $key, $label, $css_class = '' ) use ( $device ) {
			$value = '';
			if ( null !== $device ) {
				$value = in_array( $key, array( 'low', 'high' ), true ) ? wc_format_localized_price( (string) $device[ $key ] ) : $device[ $key ];
			}
			return '<td><input type="text" data-key="' . esc_attr( $key ) . '"' . ( $css_class ? ' class="' . esc_attr( $css_class ) . '"' : '' ) . ' value="' . esc_attr( $value ) . '" aria-label="' . esc_attr( $label ) . '"></td>';
		};
		return '<tr class="purplish-device">'
			. $cell( 'brand', __( 'Brand', 'purplish' ) )
			. $cell( 'model', __( 'Model', 'purplish' ) )
			. $cell( 'storage', __( 'Storage', 'purplish' ) )
			. $cell( 'low', __( 'Low value', 'purplish' ), 'wc_input_price' )
			. $cell( 'high', __( 'High value', 'purplish' ), 'wc_input_price' )
			. '<td><button type="button" class="button-link button-link-delete purplish-device-remove">' . esc_html__( 'Remove', 'purplish' ) . '</button></td>'
			. '</tr>';
	}

	/**
	 * Saves the device table, then imports an uploaded CSV file into it,
	 * and says what the import did. WooCommerce checks the settings nonce
	 * and the manage_woocommerce capability before this action runs.
	 *
	 * @return void
	 */
	public function save_devices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified its settings nonce before this action.
		if ( isset( $_POST[ self::JSON_FIELD ] ) && is_string( $_POST[ self::JSON_FIELD ] ) ) {
			// Each cell is cleaned in TradeIn::clean_devices().
			$rows = json_decode( wp_unslash( $_POST[ self::JSON_FIELD ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_array( $rows ) ) {
				TradeIn::save_devices( $rows );
			}
		}

		$file = isset( $_FILES[ self::FILE_FIELD ] ) && is_array( $_FILES[ self::FILE_FIELD ] ) ? $_FILES[ self::FILE_FIELD ] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only the temporary path and size are read, and checked below.
		$path = isset( $file['tmp_name'] ) && is_string( $file['tmp_name'] ) ? $file['tmp_name'] : '';
		if ( '' === $path ) {
			return;
		}
		$replace = isset( $_POST[ self::IMPORT_FIELD ] ) && 'replace' === sanitize_key( wp_unslash( $_POST[ self::IMPORT_FIELD ] ) );
		// phpcs:enable
		if ( ! is_uploaded_file( $path ) || ( isset( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) ) {
			\WC_Admin_Settings::add_error( __( 'The CSV file didn’t upload. Try again.', 'purplish' ) );
			return;
		}
		$this->import( (string) file_get_contents( $path, false, null, 0, TradeInCsv::MAX_BYTES + 1 ), $replace ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local upload.
	}

	/**
	 * Imports CSV text into the table and says what happened.
	 *
	 * @param string $csv     CSV text.
	 * @param bool   $replace Whether the import replaces the table.
	 * @return array{devices: array<int, array<string, mixed>>, skipped: int[]}|null The parsed import, or null when the file was too big.
	 */
	public function import( $csv, $replace ) {
		if ( strlen( $csv ) > TradeInCsv::MAX_BYTES ) {
			\WC_Admin_Settings::add_error( __( 'The CSV file is over 2 MB. Split it into smaller files and import them one by one.', 'purplish' ) );
			return null;
		}
		$parsed = TradeInCsv::parse( $csv );
		if ( ! $parsed['devices'] ) {
			\WC_Admin_Settings::add_error( __( 'No devices imported: no row had a brand, a model and a value. The table didn’t change.', 'purplish' ) );
			return $parsed;
		}
		$saved = TradeIn::save_devices( TradeIn::merge( TradeIn::devices(), $parsed['devices'], $replace ) );
		\WC_Admin_Settings::add_message(
			sprintf(
				/* translators: 1: number of devices imported. 2: number of devices in the table now. */
				_n( 'Imported %1$s device. The table has %2$s now.', 'Imported %1$s devices. The table has %2$s now.', count( $parsed['devices'] ), 'purplish' ),
				number_format_i18n( count( $parsed['devices'] ) ),
				number_format_i18n( count( $saved ) )
			)
		);
		if ( $parsed['skipped'] ) {
			$lines = array_slice( $parsed['skipped'], 0, 20 );
			\WC_Admin_Settings::add_error(
				sprintf(
					/* translators: 1: number of rows skipped. 2: their line numbers, like 4, 9, 12. */
					_n( 'Skipped %1$s row without a brand, a model or a value, on line %2$s.', 'Skipped %1$s rows without a brand, a model or a value, on lines %2$s.', count( $parsed['skipped'] ), 'purplish' ),
					number_format_i18n( count( $parsed['skipped'] ) ),
					implode( ', ', array_map( 'number_format_i18n', $lines ) ) . ( count( $parsed['skipped'] ) > count( $lines ) ? ' …' : '' )
				)
			);
		}
		return $parsed;
	}

	/**
	 * Sends the table as a CSV file.
	 *
	 * @return void
	 */
	public function export() {
		check_admin_referer( self::EXPORT );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you can’t export the trade-in devices.', 'purplish' ), 403 );
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=trade-in-devices-' . gmdate( 'Y-m-d' ) . '.csv' );
		echo TradeInCsv::build( TradeIn::devices() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A CSV download, not HTML; formula signs are guarded.
		exit;
	}

	/**
	 * Loads the table script and styles on the settings section.
	 *
	 * @return void
	 */
	public function enqueue() {
		$screen = get_current_screen();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks which screen loads the script.
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id || self::SECTION !== $section ) {
			return;
		}
		$asset = include PURPLISH_DIR . 'build/trade-in-admin.asset.php';
		wp_enqueue_script( 'purplish-trade-in-admin', PURPLISH_URL . 'build/trade-in-admin.js', $asset['dependencies'], $asset['version'], true );
		wp_register_style( 'purplish-trade-in-admin', false, array(), $asset['version'] );
		wp_enqueue_style( 'purplish-trade-in-admin' );
		wp_add_inline_style(
			'purplish-trade-in-admin',
			'table.purplish-devices-table{width:100%;max-width:52em;margin-bottom:4px}'
			. '.purplish-devices-table input{width:100%;min-width:0;box-sizing:border-box}'
			. 'table.widefat.purplish-devices-table th,table.widefat.purplish-devices-table td,'
			. '.woocommerce table.widefat.purplish-devices-table thead th,.woocommerce table.widefat.purplish-devices-table tbody td{width:auto;padding:6px 8px;vertical-align:middle;line-height:1.4;font-weight:400}'
			. 'table.widefat.purplish-devices-table td:nth-child(4),table.widefat.purplish-devices-table td:nth-child(5){width:7em}'
			. 'table.widefat.purplish-devices-table td:last-child{width:1%;white-space:nowrap}'
			. '.purplish-devices-count{margin-top:0}'
			. '.purplish-devices-import{margin-top:1.5em}.purplish-devices-import legend{font-weight:600}'
		);
	}
}
