<?php
/**
 * The Piece block, only inside Changing Room.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use Purplish\Frontend\PageData;

defined( 'ABSPATH' ) || exit;

/**
 * Holds one piece's slot (top or bottom), product and flat cut-out
 * image. It prints nothing: Changing Room reads its attributes and prints
 * every piece in its ring, with the product data and panel built here.
 *
 * @since 1.3.0
 */
final class ChangingRoomPiece implements Block {

	/**
	 * Stock at or under this shows as "Only N left".
	 */
	const LOW_STOCK = 3;

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'changing-room-piece';
	}

	/**
	 * Nothing, since Changing Room prints the piece.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		return '';
	}

	/**
	 * A price as plain text, the way the store prints money.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function money( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	}

	/**
	 * Stock as a short line.
	 *
	 * @param int|null $left  Items left, null when the store doesn't count them.
	 * @param bool     $in    Whether it's in stock.
	 * @param string   $label The size, empty for the product as a whole.
	 * @return string
	 */
	public static function stock( $left, $in, $label = '' ) {
		if ( ! $in ) {
			return __( 'Sold out', 'purplish' );
		}
		if ( null === $left ) {
			return __( 'In stock', 'purplish' );
		}
		if ( $left <= self::LOW_STOCK ) {
			return '' === $label
				/* translators: %s: items left. */
				? sprintf( __( 'Only %s left', 'purplish' ), number_format_i18n( $left ) )
				/* translators: 1: items left, 2: size, like M. */
				: sprintf( __( 'Only %1$s left in %2$s', 'purplish' ), number_format_i18n( $left ), $label );
		}
		/* translators: %s: items in stock. */
		return sprintf( __( '%s in stock', 'purplish' ), number_format_i18n( $left ) );
	}

	/**
	 * A product's variations a visitor can pick: published, purchasable and
	 * with every attribute set, so the Store API can add each by its ID.
	 * Sold out ones stay, crossed out.
	 *
	 * @param \WC_Product $product Variable product.
	 * @return array[] Each with `id`, `label`, `left`, `in` and the color set.
	 */
	public static function sizes( $product ) {
		$sizes = array();
		if ( ! $product->is_type( 'variable' ) ) {
			return $sizes;
		}
		foreach ( $product->get_children() as $child ) {
			$variation = wc_get_product( $child );
			if ( ! $variation || 'publish' !== $variation->get_status() || ! $variation->is_purchasable() ) {
				continue;
			}
			if ( in_array( '', array_map( 'strval', $variation->get_attributes() ), true ) ) {
				continue;
			}
			$sizes[] = array(
				'id'    => $variation->get_id(),
				'label' => wp_strip_all_tags( wc_get_formatted_variation( $variation, true, false ) ),
				'left'  => $variation->managing_stock() ? max( 0, (int) $variation->get_stock_quantity() ) : null,
				'in'    => $variation->is_in_stock(),
				'color' => PageData::product_set( $variation ),
			);
		}
		return $sizes;
	}

	/**
	 * The product's attributes shown on its page, as label and value rows.
	 *
	 * @param \WC_Product $product Product.
	 * @return array<int, string[]>
	 */
	public static function specs( $product ) {
		$rows = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() || $attribute->get_variation() ) {
				continue;
			}
			$values = $attribute->is_taxonomy()
				? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
				: $attribute->get_options();
			if ( $values ) {
				$rows[] = array( wc_attribute_label( $attribute->get_name(), $product ), implode( ', ', $values ) );
			}
		}
		return $rows;
	}

	/**
	 * What the script needs about a piece, and the parts the template and
	 * the rings print.
	 *
	 * @param array $piece A piece from pieces().
	 * @return array
	 */
	public static function piece( $piece ) {
		$product = $piece['product'];
		$sizes   = self::sizes( $product );
		$color   = PageData::product_set( $product );
		foreach ( $sizes as $size ) {
			if ( ! $color && $size['color'] ) {
				$color = $size['color'];
			}
		}
		$variable = $product->is_type( 'variable' );
		$in_stock = false;
		$counted  = true;
		$left     = 0;
		foreach ( $sizes as $size ) {
			$in_stock = $in_stock || $size['in'];
			$counted  = $counted && null !== $size['left'];
			$left    += (int) $size['left'];
		}
		if ( ! $variable ) {
			$in_stock = $product->is_in_stock();
			$counted  = $product->managing_stock();
			$left     = $counted ? max( 0, (int) $product->get_stock_quantity() ) : 0;
		}
		$addable = $variable ? $in_stock : $product->is_type( 'simple' ) && $product->is_purchasable() && $in_stock;
		$name    = wp_strip_all_tags( $product->get_name() );
		$price   = (float) wc_get_price_to_display( $product );

		return array(
			'product' => $product,
			'name'    => $name,
			'amount'  => $price,
			'price'   => self::money( $price ),
			'image'   => $piece['image'],
			'link'    => $product->get_permalink(),
			'color'   => $color,
			'sizes'   => $sizes,
			'stock'   => self::stock( $counted ? $left : null, $in_stock ),
			'addable' => $addable && ( ! $variable || $sizes ),
		);
	}

	/**
	 * The panel's body for one piece, in a template the script copies in
	 * when the piece opens.
	 *
	 * @param array  $piece   A piece from piece().
	 * @param array  $options Block options.
	 * @param string $id      ID prefix, unique on the page.
	 * @return string
	 */
	public static function sheet( $piece, $options, $id ) {
		$product = $piece['product'];
		$chips   = '';
		foreach ( $piece['sizes'] as $n => $size ) {
			$chips .= sprintf(
				'<button type="button" class="purplish-changing-room-chip" data-size="%1$d" aria-pressed="false" aria-label="%2$s"%3$s>%4$s</button>',
				$n,
				esc_attr(
					$size['in']
						/* translators: 1: size, like M, 2: stock line, like "9 in stock". */
						? sprintf( __( '%1$s, %2$s', 'purplish' ), $size['label'], self::stock( $size['left'], true ) )
						/* translators: %s: size, like M. */
						: sprintf( __( '%s, sold out', 'purplish' ), $size['label'] )
				),
				$size['in'] ? '' : ' disabled',
				esc_html( $size['label'] )
			);
		}
		$sizes = '';
		if ( $chips ) {
			$names = array_map(
				static function ( $attribute ) use ( $product ) {
					return wc_attribute_label( $attribute, $product );
				},
				array_keys( $product->get_variation_attributes() )
			);
			$sizes = sprintf(
				'<fieldset class="purplish-changing-room-sizes"><legend>%1$s <span class="purplish-changing-room-picked"></span></legend><div class="purplish-changing-room-chips">%2$s</div></fieldset>',
				esc_html( implode( ', ', $names ) ),
				$chips
			);
		}

		$specs = '';
		foreach ( self::specs( $product ) as $row ) {
			$specs .= sprintf( '<div><dt>%1$s</dt><dd>%2$s</dd></div>', esc_html( $row[0] ), esc_html( $row[1] ) );
		}
		$desc      = trim( (string) $product->get_description() );
		$show_spec = 'on' === $options['specs'] && '' !== $specs;
		$show_desc = 'on' === $options['about'];
		$both      = $show_spec && $show_desc;
		// Two radio buttons switch the tabs in CSS, so the panel needs no script for them.
		$tabs = $both ? sprintf(
			'<div class="purplish-changing-room-tabs" role="radiogroup" aria-label="%1$s"><label><input type="radio" name="%2$s-tab" value="specs" checked>%3$s</label><label><input type="radio" name="%2$s-tab" value="about">%4$s</label></div>',
			esc_attr__( 'Product information', 'purplish' ),
			esc_attr( $id ),
			esc_html__( 'Details', 'purplish' ),
			esc_html__( 'About', 'purplish' )
		) : '';

		$html = sprintf(
			'<div class="purplish-changing-room-media"><img class="purplish-changing-room-sheet-img" src="%1$s" alt="" decoding="async"></div><h3 class="purplish-changing-room-name" id="%2$s-name">%3$s</h3><p class="purplish-changing-room-price"><span class="purplish-changing-room-amount">%4$s</span><span class="purplish-changing-room-stock">%5$s</span></p>%6$s%7$s<p class="purplish-changing-room-note" aria-live="polite"></p>%8$s',
			esc_url( $piece['image'] ),
			esc_attr( $id ),
			esc_html( $piece['name'] ),
			wp_kses_post( $product->get_price_html() ),
			esc_html( $piece['stock'] ),
			$product->get_short_description() ? '<p class="purplish-changing-room-short">' . esc_html( wp_strip_all_tags( $product->get_short_description() ) ) . '</p>' : '',
			$sizes,
			$tabs
		);
		if ( $show_spec ) {
			$html .= '<dl class="purplish-changing-room-specs">' . $specs . '</dl>';
		}
		if ( $show_desc ) {
			$html .= sprintf(
				'<div class="purplish-changing-room-about">%1$s<a class="purplish-changing-room-link" href="%2$s">%3$s</a></div>',
				'' === $desc ? '' : '<div class="purplish-changing-room-desc">' . wp_kses_post( wpautop( $desc ) ) . '</div>',
				esc_url( $piece['link'] ),
				esc_html__( 'Open the product page', 'purplish' )
			);
		}
		return $html;
	}
}
