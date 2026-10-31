<?php
/**
 * The "Page color" field in the product editor: on each variation, and
 * on the General tab of simple products.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\Meta;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Prints and saves the page color of each variation and simple product.
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
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_product' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product' ) );
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

		$loop = absint( $loop );

		echo '<div class="purplish-variation-color">';
		self::print_fields(
			$variation,
			array(
				'mode_id'     => "purplish_mode{$loop}",
				'mode_name'   => "purplish_mode[{$loop}]",
				'custom_id'   => "purplish_custom_color{$loop}",
				'custom_name' => "purplish_custom_color[{$loop}]",
				'mode_help'   => __( 'The product page takes this color when a shopper picks this variation. Automatic reads it from the variation photo. Off keeps the theme colors.', 'purplish' ),
				'empty_auto'  => __( 'Color from the photo: not read yet. Purplish reads it in the background after the variation has a photo and is saved.', 'purplish' ),
				'mode_class'  => 'form-row form-row-first',
				'color_class' => 'form-row form-row-last',
				'auto_class'  => 'form-row form-row-full',
			)
		);
		echo '</div>';
	}

	/**
	 * Prints the field on the General tab of a simple product. WooCommerce
	 * shows it only while the product type is Simple.
	 *
	 * @return void
	 */
	public function render_product() {
		global $product_object;
		if ( ! $product_object instanceof \WC_Product ) {
			return;
		}

		echo '<div class="options_group show_if_simple purplish-variation-color purplish-product-color">';
		self::print_fields(
			$product_object,
			array(
				'mode_id'      => 'purplish_product_mode',
				'mode_name'    => 'purplish_product_mode',
				'custom_id'    => 'purplish_product_custom_color',
				'custom_name'  => 'purplish_product_custom_color',
				'mode_help'    => __( 'The product page takes this color. Automatic reads it from the product image. Off keeps the theme colors.', 'purplish' ),
				'empty_auto'   => __( 'Color from the photo: not read yet. Purplish reads it in the background after the product has an image and is saved.', 'purplish' ),
				'mode_class'   => '',
				'color_class'  => '',
				'auto_class'   => 'form-field',
				// WooCommerce's half-width field on the General tab.
				'select_class' => 'select short',
			)
		);
		echo '</div>';
	}

	/**
	 * Prints the Page color select, the custom color field and the photo
	 * color for a variation or a simple product.
	 *
	 * @param \WC_Product $product Variation or simple product.
	 * @param array       $args    IDs, names, help texts and wrapper classes.
	 * @return void
	 */
	private static function print_fields( $product, $args ) {
		$mode   = Meta::mode( $product );
		$auto   = sanitize_hex_color( (string) $product->get_meta( Meta::AUTO_COLOR ) );
		$custom = sanitize_hex_color( (string) $product->get_meta( Meta::CUSTOM_COLOR ) );

		woocommerce_wp_select(
			array(
				'id'            => $args['mode_id'],
				'name'          => $args['mode_name'],
				'class'         => trim( 'purplish-mode ' . ( isset( $args['select_class'] ) ? $args['select_class'] : '' ) ),
				'label'         => __( 'Page color', 'purplish' ),
				'value'         => $mode,
				'options'       => array(
					Meta::MODE_AUTO   => __( 'Automatic', 'purplish' ),
					Meta::MODE_CUSTOM => __( 'Custom', 'purplish' ),
					Meta::MODE_OFF    => __( 'Off', 'purplish' ),
				),
				'desc_tip'      => true,
				'description'   => $args['mode_help'],
				'wrapper_class' => $args['mode_class'],
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => $args['custom_id'],
				'name'              => $args['custom_name'],
				'class'             => 'purplish-color-input',
				'label'             => __( 'Custom color', 'purplish' ),
				'value'             => $custom ? $custom : '',
				// Shows the photo color as a hint until a custom color is set.
				'placeholder'       => $auto ? $auto : '',
				'desc_tip'          => true,
				'description'       => __( 'Click the field to pick a color, or type a hex code like #dc4a24.', 'purplish' ),
				'custom_attributes' => array(
					'data-purplish-color-picker' => '1',
					'autocomplete'               => 'off',
					'spellcheck'                 => 'false',
					'maxlength'                  => '7',
				),
				'wrapper_class'     => trim( $args['color_class'] . ' purplish-custom-color' ),
			)
		);

		echo '<p class="' . esc_attr( trim( $args['auto_class'] . ' purplish-auto-color' ) ) . '">';
		if ( $auto ) {
			printf(
				'<span class="purplish-swatch" style="background-color: %1$s;"></span> %2$s',
				esc_attr( $auto ),
				/* translators: %s: a hex color, like #dc4a24. */
				esc_html( sprintf( __( 'Color from the photo: %s', 'purplish' ), $auto ) )
			);
		} else {
			echo esc_html( $args['empty_auto'] );
		}
		echo '</p>';
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
	 * Sets the field values on a simple product before WooCommerce saves
	 * it. WooCommerce checked the nonce and the edit_product capability
	 * before this action, and calls save() after it.
	 *
	 * @param \WC_Product $product Product being saved.
	 * @return void
	 */
	public function save_product( $product ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified the product edit nonce.
		if ( ! $product->is_type( 'simple' ) || ! isset( $_POST['purplish_product_mode'] ) ) {
			return;
		}

		$mode   = sanitize_key( wp_unslash( $_POST['purplish_product_mode'] ) );
		$custom = isset( $_POST['purplish_product_custom_color'] )
			? sanitize_text_field( wp_unslash( $_POST['purplish_product_custom_color'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		self::apply( $product, $mode, $custom );
	}

	/**
	 * Sets a mode and custom color on a variation or simple product,
	 * without saving it.
	 *
	 * @param \WC_Product $variation Variation or simple product.
	 * @param string      $mode      `auto`, `custom` or `off`. Anything else counts as `auto`.
	 * @param string      $custom    Hex color. Empty removes the saved one; anything else that isn't a hex color is ignored.
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
	 * Loads WordPress's Iris color picker and the field's script and styles
	 * on the product editor.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}

		$asset = include PURPLISH_DIR . 'build/admin.asset.php';
		wp_enqueue_script(
			'purplish-admin',
			PURPLISH_URL . 'build/admin.js',
			array_merge( $asset['dependencies'], array( 'jquery', 'iris' ) ),
			$asset['version'],
			true
		);

		// Same field as WooCommerce's own color settings: a hex text field with
		// a swatch, at the height WooCommerce gives selects in this panel.
		wp_add_inline_style(
			'woocommerce_admin_styles',
			'.purplish-variation-color { clear: both; }'
			. '.purplish-variation-color:not(:has(.purplish-mode option[value="custom"]:checked)) .purplish-custom-color { display: none; }'
			. '.purplish-color-field { position: relative; display: block; }'
			// The ID matches WooCommerce's own #variable_product_options input rule, so this padding wins.
			. '#variable_product_options .purplish-color-field input.purplish-color-input { width: 100%; padding-left: 48px; font-family: Consolas, Monaco, monospace; text-transform: lowercase; }'
			. '.wc-wp-version-gte-70 #variable_product_options .purplish-color-field .purplish-color-input { height: 50px; }'
			// The General tab floats its fields at half width; the field's wrapper follows.
			. '#woocommerce-product-data .purplish-product-color .purplish-color-field { float: left; width: 50%; }'
			. '.wc-wp-version-gte-70 #woocommerce-product-data .purplish-product-color .purplish-color-field { width: 55%; }'
			. '#woocommerce-product-data .purplish-product-color .purplish-color-field input.purplish-color-input { float: none; width: 100%; box-sizing: border-box; padding-left: 48px; font-family: Consolas, Monaco, monospace; }'
			. '.purplish-color-swatch { position: absolute; z-index: 1; left: 10px; top: calc(50% + 1px); width: 28px; height: 28px; transform: translateY(-50%); border-radius: 2px; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.2); cursor: pointer; }'
			. '.purplish-color-swatch.is-empty { background: linear-gradient(to top right, #fff calc(50% - 1px), #cc1818 50%, #fff calc(50% + 1px)); }'
			. '.purplish-color-field .iris-picker { position: absolute; z-index: 100; top: 100%; left: 0; margin-top: 4px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15); }'
			. '.purplish-swatch { display: inline-block; width: 1.25em; height: 1.25em; border-radius: 50%; vertical-align: middle; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.15); }'
		);
	}
}
