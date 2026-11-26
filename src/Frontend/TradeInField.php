<?php
/**
 * The trade-in choice inside the Add to Cart + Options block.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\TradeIn;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the trade-in choice in the block's form, before the quantity and
 * the Add to Cart button, when the block's Trade-in option is on and the
 * product takes trade-in. Without JavaScript it's a Yes/No pair and one
 * device select grouped by brand, which posts with the form. The script
 * turns the select into Brand, Model and Storage, shows the estimate, and
 * sends the choice along with the block's Store API add.
 *
 * The markup goes in after the block renders, so WooCommerce never sees
 * form fields in its add to cart hooks and keeps its AJAX add to cart.
 *
 * @since 1.4.1
 */
final class TradeInField {

	const BLOCK = 'woocommerce/add-to-cart-with-options';

	const HANDLE = 'purplish-trade-in';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_' . self::BLOCK, array( self::class, 'render' ), 20, 3 );
	}

	/**
	 * Puts the choice in the block's form.
	 *
	 * @param string         $html     Rendered block.
	 * @param array          $block    Parsed block.
	 * @param \WP_Block|null $instance Block instance.
	 * @return string
	 */
	public static function render( $html, $block, $instance = null ) {
		$saved = isset( $block['attrs']['purplish'] ) && is_array( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
		if ( empty( $saved['tradeIn'] ) || true !== $saved['tradeIn'] || false === stripos( $html, '<form' ) || false !== strpos( $html, 'class="purplish-trade-in"' ) ) {
			return $html;
		}

		$id      = $instance instanceof \WP_Block && isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : (int) get_the_ID();
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product instanceof \WC_Product || ! $product->is_type( array( 'simple', 'variable' ) ) || ! $product->is_purchasable() || ! TradeIn::accepts( $product ) ) {
			return $html;
		}
		$devices = TradeIn::devices();
		if ( ! $devices ) {
			return $html;
		}

		self::enqueue();
		return self::insert( $html, self::markup( $product, $devices, TradeIn::settings(), ! empty( $saved['tradeInNet'] ) && true === $saved['tradeInNet'] ) );
	}

	/**
	 * Puts markup before the quantity and button row, or before the form
	 * ends when the block has neither.
	 *
	 * @param string $html   The block's form.
	 * @param string $markup The trade-in markup.
	 * @return string
	 */
	public static function insert( $html, $markup ) {
		$marker = false;
		foreach ( array( 'woocommerce/add-to-cart-with-options-quantity-selector', 'woocommerce/product-button' ) as $name ) {
			$at = strpos( $html, 'data-block-name="' . $name . '"' );
			if ( false !== $at && ( false === $marker || $at < $marker ) ) {
				$marker = $at;
			}
		}
		if ( false === $marker ) {
			$end = strripos( $html, '</form>' );
			return false === $end ? $html : substr_replace( $html, $markup, $end, 0 );
		}
		$tag = strrpos( substr( $html, 0, $marker ), '<' );
		// The default parts wrap the quantity and button in a row group:
		// go before the group, unless another block sits between the two.
		$group = strrpos( substr( $html, 0, $marker ), '<div class="wp-block-group' );
		if ( false !== $group && false === strpos( substr( $html, $group, $marker - $group ), 'data-block-name=' ) ) {
			$tag = $group;
		}
		return false === $tag ? $html : substr_replace( $html, $markup, $tag, 0 );
	}

	/**
	 * The choice's markup.
	 *
	 * @param \WC_Product                                                                               $product  Product.
	 * @param array<int, array{brand: string, model: string, storage: string, low: float, high: float}> $devices  Devices.
	 * @param array<string, mixed>                                                                      $settings Store settings.
	 * @param bool                                                                                      $net      Whether to show the price after trade-in.
	 * @return string
	 */
	public static function markup( $product, $devices, $settings, $net ) {
		$id = wp_unique_id( 'purplish-trade-in-' );

		$groups = array();
		foreach ( $devices as $device ) {
			$label = '' === $device['storage']
				? $device['model']
				/* translators: 1: device model, like iPhone 13. 2: storage, like 128GB. */
				: sprintf( __( '%1$s, %2$s', 'purplish' ), $device['model'], $device['storage'] );

			$groups[ $device['brand'] ][] = sprintf(
				'<option value="%1$s" data-model="%2$s" data-storage="%3$s" data-low="%4$s" data-high="%5$s">%6$s</option>',
				esc_attr( TradeIn::key( $device ) ),
				esc_attr( $device['model'] ),
				esc_attr( $device['storage'] ),
				esc_attr( wc_format_decimal( $device['low'] ) ),
				esc_attr( wc_format_decimal( $device['high'] ) ),
				esc_html( $label )
			);
		}
		$options = '';
		foreach ( $groups as $brand => $rows ) {
			$options .= '<optgroup label="' . esc_attr( (string) $brand ) . '">' . implode( '', $rows ) . '</optgroup>';
		}

		$pick = static function ( $key, $label, $placeholder ) use ( $id ) {
			return '<p class="purplish-trade-in__field"><label for="' . esc_attr( $id . '-' . $key ) . '">' . esc_html( $label ) . '</label>'
				. '<select id="' . esc_attr( $id . '-' . $key ) . '" data-pick="' . esc_attr( $key ) . '"' . ( 'brand' === $key ? '' : ' disabled' ) . '><option value="">' . esc_html( $placeholder ) . '</option></select></p>';
		};

		$notes = array_filter( array( $settings['condition'], $settings['inspection'] ), 'strlen' );
		$link  = '' !== $settings['link_url'] && '' !== trim( $settings['link_text'] )
			? ' <a href="' . esc_url( $settings['link_url'] ) . '">' . esc_html( $settings['link_text'] ) . '</a>'
			: '';
		$terms = '';
		if ( $settings['terms'] ) {
			$terms = '<p class="purplish-trade-in__terms"><label class="purplish-trade-in__check"><input type="checkbox" name="' . esc_attr( TradeIn::TERMS_FIELD ) . '" value="1"> <span>' . esc_html( $settings['terms_label'] ) . '</span></label>'
				. ( '' !== $settings['terms_url'] ? ' <a href="' . esc_url( $settings['terms_url'] ) . '">' . esc_html__( 'Read the terms', 'purplish' ) . '</a>' : '' )
				. '</p>';
		}

		$radio = static function ( $value, $label, $checked ) {
			return '<label class="purplish-trade-in__option"><input type="radio" name="' . esc_attr( TradeIn::FIELD ) . '" value="' . esc_attr( $value ) . '"' . ( $checked ? ' checked' : '' ) . '> <span>' . esc_html( $label ) . '</span></label>';
		};

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Returned, not printed; each part is escaped where it's built.
		return '<fieldset class="purplish-trade-in" data-purplish-trade-in="' . esc_attr( (string) wp_json_encode( self::data( $product, $net ) ) ) . '">'
			. '<legend class="purplish-trade-in__legend">' . esc_html__( 'Trade in an old device?', 'purplish' ) . '</legend>'
			. '<div class="purplish-trade-in__choice">' . $radio( 'no', __( 'No', 'purplish' ), true ) . $radio( 'yes', __( 'Yes', 'purplish' ), false ) . '</div>'
			. '<div class="purplish-trade-in__panel">'
			. '<p class="purplish-trade-in__field purplish-trade-in__device"><label for="' . esc_attr( $id . '-device' ) . '">' . esc_html__( 'Your device', 'purplish' ) . '</label>'
			. '<select id="' . esc_attr( $id . '-device' ) . '" name="' . esc_attr( TradeIn::DEVICE_FIELD ) . '"><option value="">' . esc_html__( 'Choose your device', 'purplish' ) . '</option>' . $options . '</select></p>'
			. '<template><div class="purplish-trade-in__picks">' . $pick( 'brand', __( 'Brand', 'purplish' ), __( 'Choose a brand', 'purplish' ) ) . $pick( 'model', __( 'Model', 'purplish' ), __( 'Choose a model', 'purplish' ) ) . $pick( 'storage', __( 'Storage', 'purplish' ), __( 'Choose storage', 'purplish' ) ) . '</div></template>'
			. '<p class="purplish-trade-in__error" id="' . esc_attr( $id . '-error' ) . '" hidden></p>'
			. '<div class="purplish-trade-in__status" role="status"><p class="purplish-trade-in__estimate"></p>' . ( $net ? '<p class="purplish-trade-in__net"></p>' : '' ) . '</div>'
			. ( $notes || $link ? '<p class="purplish-trade-in__notes">' . esc_html( implode( ' ', $notes ) ) . $link . '</p>' : '' )
			. $terms
			. '</div></fieldset>';
		// phpcs:enable
	}

	/**
	 * What the script needs: the product and variation IDs its Store API
	 * add may use, the store's money format and the words it shows. With
	 * the line after trade-in on, the lowest price, whether prices differ,
	 * and each variation's attributes and price, so the line follows the
	 * picks.
	 *
	 * @param \WC_Product $product Product.
	 * @param bool        $net     Whether the price after trade-in shows.
	 * @return array<string, mixed>
	 */
	public static function data( $product, $net ) {
		$ids    = array( $product->get_id() );
		$prices = array();
		$price  = (float) wc_get_price_to_display( $product );
		if ( $product instanceof \WC_Product_Variable ) {
			$ids   = array_merge( $ids, array_map( 'intval', $product->get_children() ) );
			$price = (float) $product->get_variation_price( 'min', true );
			if ( $net ) {
				foreach ( $product->get_variation_prices( true )['price'] as $variation => $amount ) {
					$child = wc_get_product( $variation );
					if ( $child instanceof \WC_Product_Variation ) {
						$prices[] = array( (object) $child->get_variation_attributes(), (float) $amount );
					}
				}
			}
		}

		$data = array(
			'i' => $ids,
			'c' => array(
				's'  => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'p'  => (string) get_option( 'woocommerce_currency_pos', 'left' ),
				'd'  => wc_get_price_decimals(),
				'ds' => wc_get_price_decimal_separator(),
				'ts' => wc_get_price_thousand_separator(),
			),
			't' => array(
				/* translators: %s: the trade-in value, like RM680.00. */
				'one'    => __( 'Estimated trade-in value: %s', 'purplish' ),
				/* translators: 1: lowest trade-in value. 2: highest trade-in value. */
				'range'  => __( 'Estimated trade-in value: %1$s to %2$s', 'purplish' ),
				'device' => TradeIn::device_message(),
				'terms'  => TradeIn::terms_message(),
			),
		);
		if ( $net ) {
			$data['p'] = $price;
			$data['v'] = $prices;
			$data['f'] = $product instanceof \WC_Product_Variable && $product->get_variation_price( 'min', true ) !== $product->get_variation_price( 'max', true );
			/* translators: %s: the product price minus the trade-in value, like RM3,919.00. */
			$data['t']['net'] = __( 'Price after trade-in: %s', 'purplish' );
			/* translators: %s: the lowest product price minus the highest trade-in value, like RM3,919.00. */
			$data['t']['netup'] = __( 'Price after trade-in: from %s', 'purplish' );
		}
		return $data;
	}

	/**
	 * Loads the styles and the script.
	 *
	 * @return void
	 */
	public static function enqueue() {
		$asset = include PURPLISH_DIR . 'build/trade-in.asset.php';
		wp_enqueue_style( self::HANDLE, PURPLISH_URL . 'build/style-trade-in.css', array(), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/trade-in.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
	}
}
