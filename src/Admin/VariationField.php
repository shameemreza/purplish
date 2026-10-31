<?php
/**
 * The "Page color" field in the product editor's variation panel.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\Meta;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Prints and saves the page color of each variation.
 */
final class VariationField {

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render' ), 10, 3 );
		add_action( 'woocommerce_admin_process_variation_object', array( $this, 'save' ), 10, 2 );
		// After WooCommerce registers woocommerce_admin_styles at the default priority.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
	}

	/**
	 * Prints the field for one variation.
	 *
	 * @param int      $loop           Index of the variation in the panel.
	 * @param array    $variation_data Unused.
	 * @param \WP_Post $variation_post The variation's post.
	 * @return void
	 */
	public function render( $loop, $variation_data, $variation_post ) {
		$variation = wc_get_product( $variation_post->ID );
		if ( ! $variation instanceof WC_Product_Variation ) {
			return;
		}

		$loop   = absint( $loop );
		$mode   = Meta::mode( $variation );
		$auto   = sanitize_hex_color( (string) $variation->get_meta( Meta::AUTO_COLOR ) );
		$custom = sanitize_hex_color( (string) $variation->get_meta( Meta::CUSTOM_COLOR ) );

		echo '<div class="purplish-variation-color">';

		woocommerce_wp_select(
			array(
				'id'            => "purplish_mode{$loop}",
				'name'          => "purplish_mode[{$loop}]",
				'class'         => 'purplish-mode',
				'label'         => __( 'Page color', 'purplish' ),
				'value'         => $mode,
				'options'       => array(
					Meta::MODE_AUTO   => __( 'Automatic', 'purplish' ),
					Meta::MODE_CUSTOM => __( 'Custom', 'purplish' ),
					Meta::MODE_OFF    => __( 'Off', 'purplish' ),
				),
				'desc_tip'      => true,
				'description'   => __( 'The product page takes this color when a shopper picks this variation. Automatic reads it from the variation photo. Off keeps the theme colors.', 'purplish' ),
				'wrapper_class' => 'form-row form-row-first',
			)
		);

		$picker_attributes = array( 'data-purplish-color-picker' => '1' );
		if ( $auto ) {
			// The picker's Default button goes back to the photo color.
			$picker_attributes['data-default-color'] = $auto;
		}

		woocommerce_wp_text_input(
			array(
				'id'                => "purplish_custom_color{$loop}",
				'name'              => "purplish_custom_color[{$loop}]",
				'class'             => 'purplish-color-input',
				'label'             => __( 'Custom color', 'purplish' ),
				'value'             => $custom ? $custom : '',
				'placeholder'       => '#dc4a24',
				'desc_tip'          => true,
				'description'       => __( 'Pick a color, or type a hex code like #dc4a24.', 'purplish' ),
				'custom_attributes' => $picker_attributes,
				'wrapper_class'     => 'form-row form-row-last purplish-custom-color',
			)
		);

		echo '<p class="form-row form-row-full purplish-auto-color">';
		if ( $auto ) {
			printf(
				'<span class="purplish-swatch" style="background-color: %1$s;"></span> %2$s',
				esc_attr( $auto ),
				/* translators: %s: a hex color, like #dc4a24. */
				esc_html( sprintf( __( 'Color from the photo: %s', 'purplish' ), $auto ) )
			);
		} else {
			esc_html_e( 'Color from the photo: not read yet. Purplish reads it in the background after the variation has a photo and is saved.', 'purplish' );
		}
		echo '</p>';

		echo '</div>';
	}

	/**
	 * Sets the field values on the variation before WooCommerce saves it.
	 * WooCommerce checked the nonce and the edit_products capability before
	 * this action, and calls save() after it.
	 *
	 * @param WC_Product_Variation $variation Variation being saved.
	 * @param int                  $loop      Index of the variation in the form.
	 * @return void
	 */
	public function save( $variation, $loop ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified the save-variations nonce.
		$loop = absint( $loop );
		if ( ! isset( $_POST['purplish_mode'][ $loop ] ) ) {
			return;
		}

		$mode   = sanitize_key( wp_unslash( $_POST['purplish_mode'][ $loop ] ) );
		$custom = isset( $_POST['purplish_custom_color'][ $loop ] )
			? sanitize_text_field( wp_unslash( $_POST['purplish_custom_color'][ $loop ] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// apply() checks the color: empty removes it, anything not hex is ignored.
		self::apply( $variation, $mode, $custom );
	}

	/**
	 * Sets a mode and custom color on a variation, without saving it.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @param string               $mode      `auto`, `custom` or `off`. Anything else counts as `auto`.
	 * @param string               $custom    Hex color. Empty removes the saved one; anything else that isn't a hex color is ignored.
	 * @return void
	 */
	public static function apply( $variation, $mode, $custom ) {
		if ( ! in_array( $mode, Meta::modes(), true ) || Meta::MODE_AUTO === $mode ) {
			$variation->delete_meta_data( Meta::MODE );
		} else {
			$variation->update_meta_data( Meta::MODE, $mode );
		}

		if ( Meta::MODE_CUSTOM !== $mode ) {
			return;
		}

		if ( '' === trim( $custom ) ) {
			// Cleared in the picker: fall back to the term or photo color.
			$variation->delete_meta_data( Meta::CUSTOM_COLOR );
			return;
		}

		$custom = sanitize_hex_color( $custom );
		if ( $custom ) {
			$variation->update_meta_data( Meta::CUSTOM_COLOR, $custom );
		}
	}

	/**
	 * Loads WordPress's color picker and the field's script and styles on
	 * the product editor.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}

		$asset = include PURPLISH_DIR . 'build/admin.asset.php';
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'purplish-admin',
			PURPLISH_URL . 'build/admin.js',
			array_merge( $asset['dependencies'], array( 'jquery', 'wp-color-picker' ) ),
			$asset['version'],
			true
		);

		wp_add_inline_style(
			'woocommerce_admin_styles',
			'.purplish-variation-color { clear: both; }'
			. '.purplish-variation-color:not(:has(.purplish-mode option[value="custom"]:checked)) .purplish-custom-color { display: none; }'
			// The picker fills the column and matches the height WooCommerce gives selects in this panel.
			. '.purplish-custom-color .wp-picker-container { display: block; width: 100%; margin-top: 2px; }'
			. '.purplish-custom-color .wp-picker-container .wp-color-result.button { width: 100%; min-height: 40px; margin: 0; }'
			. '.purplish-custom-color .wp-picker-container .wp-color-result-text { line-height: 38px; height: 100%; }'
			. '.purplish-custom-color .wp-picker-input-wrap { margin-top: 6px; }'
			. '.purplish-custom-color .wp-picker-input-wrap input.purplish-color-input { width: 8em; }'
			. '.wc-wp-version-gte-70 #variable_product_options .purplish-custom-color .wp-color-result.button { height: 50px; }'
			. '.wc-wp-version-gte-70 #variable_product_options .purplish-custom-color .wp-color-result-text { line-height: 48px; }'
			. '.purplish-swatch { display: inline-block; width: 1.25em; height: 1.25em; border-radius: 50%; vertical-align: middle; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.15); }'
		);
	}
}
