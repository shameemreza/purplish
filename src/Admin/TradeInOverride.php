<?php
/**
 * The Trade-in setting on products and categories.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\TradeIn;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a Trade-in select to the product's General tab and to product
 * categories: follow the store, on or off. A product's setting wins over
 * its category's, and a category's over the store's.
 *
 * @since 1.4.1
 */
final class TradeInOverride {

	const NONCE = 'purplish_trade_in';

	const FIELD = 'purplish_trade_in_mode';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product' ) );
		add_action( 'product_cat_add_form_fields', array( $this, 'category_add_field' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'category_edit_field' ) );
		add_action( 'created_product_cat', array( $this, 'save_term' ) );
		add_action( 'edited_product_cat', array( $this, 'save_term' ) );
	}

	/**
	 * The select, shared by products and categories.
	 *
	 * @param string $saved    Saved setting: '', `on` or `off`.
	 * @param bool   $category Whether this is a category.
	 * @return void
	 */
	private static function select( $saved, $category ) {
		$options = array(
			''    => __( 'Store setting', 'purplish' ),
			'on'  => __( 'On', 'purplish' ),
			'off' => __( 'Off', 'purplish' ),
		);
		echo '<select name="' . esc_attr( self::FIELD ) . '" id="purplish_trade_in_mode" aria-describedby="purplish_trade_in_help">';
		foreach ( $options as $value => $label ) {
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( $saved, $value, false ), esc_html( $label ) );
		}
		echo '</select>';
		echo '<p id="purplish_trade_in_help" class="description">' . esc_html(
			$category
				? __( 'Whether products in this category take trade-in, unless a product sets its own.', 'purplish' )
				: __( 'Whether this product takes trade-in. Store setting follows the product’s category first, then the store.', 'purplish' )
		) . '</p>';
	}

	/**
	 * The Trade-in select in the product's General tab, for simple and
	 * variable products.
	 *
	 * @return void
	 */
	public function product_field() {
		global $product_object;

		$saved = TradeIn::clean_override( $product_object instanceof \WC_Product ? $product_object->get_meta( TradeIn::META ) : '' );
		echo '<div class="options_group show_if_simple show_if_variable"><p class="form-field purplish_trade_in_mode_field">';
		echo '<label for="purplish_trade_in_mode">' . esc_html__( 'Trade-in', 'purplish' ) . '</label>';
		self::select( $saved, false );
		echo '</p></div>';
	}

	/**
	 * The posted product or category setting.
	 *
	 * @return string '', `on` or `off`.
	 */
	private static function posted() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Callers check the nonce.
		return TradeIn::clean_override( isset( $_POST[ self::FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD ] ) ) : '' );
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
		if ( '' === $saved ) {
			$product->delete_meta_data( TradeIn::META );
		} else {
			$product->update_meta_data( TradeIn::META, $saved );
		}
	}

	/**
	 * Prints the select on the Add new category form.
	 *
	 * @return void
	 */
	public function category_add_field() {
		echo '<div class="form-field term-purplish-trade-in-wrap">';
		wp_nonce_field( self::NONCE, 'purplish_trade_in_nonce' );
		echo '<label for="purplish_trade_in_mode">' . esc_html__( 'Trade-in', 'purplish' ) . '</label>';
		self::select( '', true );
		echo '</div>';
	}

	/**
	 * Prints the select on the Edit category screen.
	 *
	 * @param \WP_Term $term Category.
	 * @return void
	 */
	public function category_edit_field( $term ) {
		echo '<tr class="form-field term-purplish-trade-in-wrap"><th scope="row"><label for="purplish_trade_in_mode">' . esc_html__( 'Trade-in', 'purplish' ) . '</label></th><td>';
		wp_nonce_field( self::NONCE, 'purplish_trade_in_nonce' );
		self::select( TradeIn::clean_override( get_term_meta( $term->term_id, TradeIn::META, true ) ), true );
		echo '</td></tr>';
	}

	/**
	 * Saves the category's setting.
	 *
	 * @param int $term_id Category ID.
	 * @return void
	 */
	public function save_term( $term_id ) {
		$nonce = isset( $_POST['purplish_trade_in_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['purplish_trade_in_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		$saved = self::posted();
		if ( '' === $saved ) {
			delete_term_meta( $term_id, TradeIn::META );
		} else {
			update_term_meta( $term_id, TradeIn::META, $saved );
		}
	}
}
