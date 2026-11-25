<?php
/**
 * Instalment settings: a section on WooCommerce's Products tab, and the
 * product and category overrides.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\Instalments;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an Instalments section to WooCommerce > Settings > Products, saved by
 * WooCommerce's settings API (it checks the nonce and capability) into one
 * option, and an Instalments select to the product's General tab and the
 * product category screens: the store's bands, hide, or own bands.
 *
 * @since 1.4.1
 */
final class InstalmentSettings {

	const SECTION = 'purplish_instalments';

	const NONCE = 'purplish_instalments';

	/**
	 * The posted field on products and categories.
	 */
	const FIELD = 'purplish_instalments';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'settings' ), 10, 2 );
		add_action( 'woocommerce_admin_field_purplish_instalment_bands', array( $this, 'bands_field' ) );
		add_action( 'woocommerce_admin_field_purplish_instalment_logo', array( $this, 'logo_field' ) );
		add_filter( 'pre_update_option_' . Instalments::OPTION, array( $this, 'sanitize' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product' ) );
		add_action( 'product_cat_add_form_fields', array( $this, 'category_add_field' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'category_edit_field' ) );
		add_action( 'created_product_cat', array( $this, 'save_term' ) );
		add_action( 'edited_product_cat', array( $this, 'save_term' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds the Instalments section to the Products tab.
	 *
	 * @param array<string, string> $sections Sections.
	 * @return array<string, string>
	 */
	public function add_section( $sections ) {
		$sections[ self::SECTION ] = __( 'Instalments', 'purplish' );
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
	 * The fields. Each saves into the `purplish_instalments` option.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields() {
		$saved = Instalments::settings();
		$key   = static function ( $name ) {
			return Instalments::OPTION . '[' . $name . ']';
		};

		return array(
			array(
				'id'    => 'purplish_instalments',
				'type'  => 'title',
				'title' => __( 'Instalments', 'purplish' ),
				'desc'  => sprintf(
					/* translators: %s: "Instalment line", the name of the option on the Product Price block. */
					__( 'A line under the price with the monthly amount, like "or RM45.75/month for 12 months, 0%% interest". It shows where the Product Price block has %s turned on in its Purplish panel. It only shows the sum: checkout doesn’t change. You own the wording, so check what your country asks a store to say about payment plans.', 'purplish' ),
					'<strong>' . esc_html__( 'Instalment line', 'purplish' ) . '</strong>'
				),
			),
			array(
				'id'    => $key( 'bands' ),
				'type'  => 'purplish_instalment_bands',
				'title' => __( 'Price bands', 'purplish' ),
				'desc'  => __( 'A price uses the highest band it reaches. Prices under the first band show no line. A yearly rate above 0 always shows the total payable.', 'purplish' ),
				'value' => $saved['bands'],
			),
			array(
				'id'       => $key( 'minimum' ),
				'type'     => 'text',
				'class'    => 'wc_input_price',
				'title'    => __( 'Minimum price', 'purplish' ),
				'desc'     => __( 'Products under this price show no line.', 'purplish' ),
				'desc_tip' => true,
				'default'  => wc_format_localized_price( (string) $saved['minimum'] ),
				'css'      => 'width:8em',
			),
			array(
				'id'      => $key( 'wording' ),
				'type'    => 'text',
				'title'   => __( 'Wording', 'purplish' ),
				'desc'    => __( '{amount} is the monthly amount, {months} the months, {rate} the yearly rate and {total} the total payable.', 'purplish' ),
				'default' => $saved['wording'],
				'css'     => 'width:24em',
			),
			array(
				'id'      => $key( 'zero' ),
				'type'    => 'text',
				'title'   => __( 'Added when the rate is 0', 'purplish' ),
				'default' => $saved['zero'],
				'css'     => 'width:24em',
			),
			array(
				'id'      => $key( 'interest' ),
				'type'    => 'text',
				'title'   => __( 'Added when the rate is above 0', 'purplish' ),
				'desc'    => __( 'If neither line has {total}, the total payable is added at the end.', 'purplish' ),
				'default' => $saved['interest'],
				'css'     => 'width:24em',
			),
			array(
				'id'          => $key( 'link_url' ),
				'type'        => 'url',
				'title'       => __( 'Info link', 'purplish' ),
				'desc'        => __( 'A page about your payment plans. Leave empty for no link.', 'purplish' ),
				'desc_tip'    => true,
				'default'     => $saved['link_url'],
				'placeholder' => 'https://',
				'css'         => 'width:24em',
			),
			array(
				'id'      => $key( 'link_text' ),
				'type'    => 'text',
				'title'   => __( 'Info link text', 'purplish' ),
				'default' => $saved['link_text'],
				'css'     => 'width:12em',
			),
			array(
				'id'    => $key( 'logo' ),
				'type'  => 'purplish_instalment_logo',
				'title' => __( 'Logo', 'purplish' ),
				'value' => $saved['logo'],
			),
			array(
				'id'       => $key( 'logo_alt' ),
				'type'     => 'text',
				'title'    => __( 'Logo alt text', 'purplish' ),
				'desc'     => __( 'The name on the logo, like your payment provider’s.', 'purplish' ),
				'desc_tip' => true,
				'default'  => $saved['logo_alt'],
				'css'      => 'width:12em',
			),
			array(
				'id'   => 'purplish_instalments',
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
		return Instalments::clean( is_array( $value ) ? $value : array() );
	}

	/**
	 * Prints the price bands row on the settings screen.
	 *
	 * @param array<string, mixed> $field The field.
	 * @return void
	 */
	public function bands_field( $field ) {
		echo '<tr><th scope="row" class="titledesc">' . esc_html( (string) $field['title'] ) . '</th><td class="forminp">';
		self::bands_table( Instalments::OPTION . '[bands]', is_array( $field['value'] ) ? $field['value'] : array() );
		echo '<p class="description">' . esc_html( (string) $field['desc'] ) . '</p></td></tr>';
	}

	/**
	 * The repeater: a row per band, a template for new rows and an Add
	 * band button. The admin script adds and removes rows.
	 *
	 * @param string                                                   $name  Field name the rows post under.
	 * @param array<int, array{from: float, months: int, rate: float}> $bands Saved bands.
	 * @return void
	 */
	public static function bands_table( $name, $bands ) {
		$row = static function ( $index, $band ) use ( $name ) {
			$field = static function ( $key ) use ( $name, $index ) {
				return esc_attr( $name . '[' . $index . '][' . $key . ']' );
			};
			return '<tr class="purplish-band">'
				. '<td><input type="text" class="wc_input_price" name="' . $field( 'from' ) . '" value="' . esc_attr( null === $band ? '' : wc_format_localized_price( (string) $band['from'] ) ) . '" aria-label="' . esc_attr__( 'From price', 'purplish' ) . '"></td>'
				. '<td><input type="number" min="1" max="120" step="1" name="' . $field( 'months' ) . '" value="' . esc_attr( null === $band ? '12' : (string) $band['months'] ) . '" aria-label="' . esc_attr__( 'Months', 'purplish' ) . '"></td>'
				. '<td><input type="text" class="wc_input_decimal" name="' . $field( 'rate' ) . '" value="' . esc_attr( null === $band ? '0' : wc_format_localized_decimal( (string) $band['rate'] ) ) . '" aria-label="' . esc_attr__( 'Yearly rate %', 'purplish' ) . '"></td>'
				. '<td><button type="button" class="button-link button-link-delete purplish-band-remove">' . esc_html__( 'Remove', 'purplish' ) . '</button></td>'
				. '</tr>';
		};

		$rows = '';
		foreach ( array_values( $bands ) as $index => $band ) {
			$rows .= $row( $index, $band );
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Each part is escaped where it's built.
		echo '<div class="purplish-bands" data-purplish-bands>'
			. '<table class="widefat striped purplish-bands-table"><thead><tr>'
			. '<th scope="col">' . esc_html__( 'From price', 'purplish' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Months', 'purplish' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Yearly rate %', 'purplish' ) . '</th>'
			. '<th scope="col"><span class="screen-reader-text">' . esc_html__( 'Remove', 'purplish' ) . '</span></th>'
			. '</tr></thead><tbody>' . $rows . '</tbody></table>'
			. '<template>' . $row( '__i__', null ) . '</template>'
			. '<p><button type="button" class="button purplish-band-add">' . esc_html__( 'Add band', 'purplish' ) . '</button></p>'
			. '</div>';
		// phpcs:enable
	}

	/**
	 * Prints the logo row: a Media Library picker that saves the image ID.
	 *
	 * @param array<string, mixed> $field The field.
	 * @return void
	 */
	public function logo_field( $field ) {
		$id      = (int) $field['value'];
		$preview = $id ? wp_get_attachment_image( $id, 'thumbnail', false, array( 'style' => 'max-height:40px;width:auto' ) ) : '';
		echo '<tr><th scope="row" class="titledesc">' . esc_html( (string) $field['title'] ) . '</th><td class="forminp">';
		echo '<div class="purplish-logo-field" data-purplish-logo data-title="' . esc_attr__( 'Instalment logo', 'purplish' ) . '" data-button="' . esc_attr__( 'Use this image', 'purplish' ) . '">';
		echo '<input type="hidden" name="' . esc_attr( (string) $field['id'] ) . '" value="' . esc_attr( $id ? (string) $id : '' ) . '">';
		echo '<span class="purplish-logo-preview">' . wp_kses_post( (string) $preview ) . '</span> ';
		echo '<button type="button" class="button purplish-logo-pick">' . esc_html__( 'Choose image', 'purplish' ) . '</button> ';
		echo '<button type="button" class="button-link button-link-delete purplish-logo-clear"' . ( $id ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'purplish' ) . '</button>';
		echo '</div><p class="description">' . esc_html__( 'Shows before the line at the height of the text, like your payment provider’s mark.', 'purplish' ) . '</p></td></tr>';
	}

	/**
	 * The select and own bands, shared by products and categories.
	 *
	 * @param array{mode: string, bands: array<int, array{from: float, months: int, rate: float}>} $saved    Saved setting.
	 * @param bool                                                                                 $category Whether this is a category, which says "store" a little differently.
	 * @return void
	 */
	private static function override_fields( $saved, $category ) {
		$options = array(
			'store' => __( 'Store bands', 'purplish' ),
			'hide'  => __( 'Hide', 'purplish' ),
			'own'   => __( 'Own bands', 'purplish' ),
		);
		echo '<select name="' . esc_attr( self::FIELD . '[mode]' ) . '" id="purplish_instalments_mode" data-purplish-instalments-mode aria-describedby="purplish_instalments_help">';
		foreach ( $options as $value => $label ) {
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( $saved['mode'], $value, false ), esc_html( $label ) );
		}
		echo '</select>';
		echo '<p id="purplish_instalments_help" class="description">' . esc_html(
			$category
				? __( 'The instalment line for products in this category, unless a product sets its own.', 'purplish' )
				: __( 'The instalment line under the price. Store bands follows the product’s category first, then the store.', 'purplish' )
		) . '</p>';
		echo '<div class="purplish-own-bands"' . ( 'own' === $saved['mode'] ? '' : ' hidden' ) . '>';
		self::bands_table( self::FIELD . '[bands]', $saved['bands'] ? $saved['bands'] : Instalments::settings()['bands'] );
		echo '</div>';
	}

	/**
	 * The Instalments select in the product's General tab, for simple and
	 * variable products.
	 *
	 * @return void
	 */
	public function product_field() {
		global $product_object;

		$saved = Instalments::clean_override( $product_object instanceof \WC_Product ? $product_object->get_meta( Instalments::META ) : '' );
		echo '<div class="options_group show_if_simple show_if_variable purplish-instalments-field"><div class="form-field">';
		echo '<label for="purplish_instalments_mode">' . esc_html__( 'Instalments', 'purplish' ) . '</label>';
		self::override_fields( $saved, false );
		echo '</div></div>';
	}

	/**
	 * The posted product or category setting, cleaned. Store bands saves
	 * nothing, so the product or category follows the next one up.
	 *
	 * @return array{mode: string, bands: array<int, array{from: float, months: int, rate: float}>}
	 */
	private static function posted() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Callers check the nonce; values are cleaned below.
		$raw  = isset( $_POST[ self::FIELD ] ) && is_array( $_POST[ self::FIELD ] ) ? wc_clean( wp_unslash( $_POST[ self::FIELD ] ) ) : array();
		$mode = isset( $raw['mode'] ) ? (string) $raw['mode'] : 'store';
		return Instalments::clean_override(
			array(
				'mode'  => $mode,
				'bands' => isset( $raw['bands'] ) ? $raw['bands'] : array(),
			)
		);
	}

	/**
	 * Saves the product's setting. WooCommerce checks the nonce and
	 * capability before this action runs.
	 *
	 * @param \WC_Product $product The product being saved.
	 * @return void
	 */
	public function save_product( $product ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies its meta box nonce before this action.
		if ( ! isset( $_POST[ self::FIELD ] ) ) {
			return;
		}
		$saved = self::posted();
		if ( 'store' === $saved['mode'] ) {
			$product->delete_meta_data( Instalments::META );
		} else {
			$product->update_meta_data( Instalments::META, $saved );
		}
	}

	/**
	 * Prints the select on the Add new category form.
	 *
	 * @return void
	 */
	public function category_add_field() {
		echo '<div class="form-field term-purplish-instalments-wrap">';
		wp_nonce_field( self::NONCE, 'purplish_instalments_nonce' );
		echo '<label for="purplish_instalments_mode">' . esc_html__( 'Instalments', 'purplish' ) . '</label>';
		self::override_fields( Instalments::clean_override( '' ), true );
		echo '</div>';
	}

	/**
	 * Prints the select on the Edit category screen.
	 *
	 * @param \WP_Term $term Category.
	 * @return void
	 */
	public function category_edit_field( $term ) {
		echo '<tr class="form-field term-purplish-instalments-wrap"><th scope="row"><label for="purplish_instalments_mode">' . esc_html__( 'Instalments', 'purplish' ) . '</label></th><td>';
		wp_nonce_field( self::NONCE, 'purplish_instalments_nonce' );
		self::override_fields( Instalments::clean_override( get_term_meta( $term->term_id, Instalments::META, true ) ), true );
		echo '</td></tr>';
	}

	/**
	 * Saves the category's setting.
	 *
	 * @param int $term_id Category ID.
	 * @return void
	 */
	public function save_term( $term_id ) {
		$nonce = isset( $_POST['purplish_instalments_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['purplish_instalments_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		$saved = self::posted();
		if ( 'store' === $saved['mode'] ) {
			delete_term_meta( $term_id, Instalments::META );
		} else {
			update_term_meta( $term_id, Instalments::META, $saved );
		}
	}

	/**
	 * Loads the repeater script on the settings section, the product editor
	 * and the category screens, and the Media Library on the settings.
	 *
	 * @return void
	 */
	public function enqueue() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only picks which screen loads the script.
		$section  = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		$settings = 'woocommerce_page_wc-settings' === $screen->id && self::SECTION === $section;
		// phpcs:enable
		if ( ! $settings && 'product' !== $screen->id && 'edit-product_cat' !== $screen->id ) {
			return;
		}
		if ( $settings ) {
			wp_enqueue_media();
		}
		$asset = include PURPLISH_DIR . 'build/instalments-admin.asset.php';
		wp_enqueue_script( 'purplish-instalments-admin', PURPLISH_URL . 'build/instalments-admin.js', $asset['dependencies'], $asset['version'], true );
		wp_register_style( 'purplish-instalments-admin', false, array(), $asset['version'] );
		wp_enqueue_style( 'purplish-instalments-admin' );
		wp_add_inline_style(
			'purplish-instalments-admin',
			'table.purplish-bands-table{width:100%;max-width:32em;margin-bottom:4px}'
			. '.purplish-bands-table input{width:100%;max-width:8em;min-width:0;box-sizing:border-box}'
			// The settings page's .form-table th/td padding would reach these cells.
			. 'table.widefat.purplish-bands-table th,table.widefat.purplish-bands-table td,'
			. '.woocommerce table.widefat.purplish-bands-table thead th,.woocommerce table.widefat.purplish-bands-table tbody td{width:auto;padding:8px 10px;vertical-align:middle;line-height:1.4;font-weight:400}'
			. 'table.widefat.purplish-bands-table td:last-child{width:1%;white-space:nowrap}'
			// The General tab's own p.form-field layout, for a field that holds a table.
			. '.purplish-instalments-field .form-field{margin:9px 0;padding:5px 20px 5px 162px}'
			. '.purplish-instalments-field .form-field>label{float:left;width:150px;margin:0 0 0 -150px}'
			. '.purplish-instalments-field .purplish-bands{padding-top:8px}'
			. '.purplish-instalments-field table.widefat.purplish-bands-table th,.purplish-instalments-field table.widefat.purplish-bands-table td{padding:6px}'
			. '.purplish-logo-preview img{vertical-align:middle}'
		);
	}
}
