<?php
/**
 * Matching extras under Add to Cart + Options.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * A short list of other products under the Add to Cart button, each with a
 * checkbox, that go into the cart on the same click. The list prints
 * inside the block's form. When WooCommerce posts the form, the server
 * adds the ticked ones after the main product. When the block adds
 * through its own store instead, the script adds them once the main
 * product is in.
 */
final class Extras {

	/**
	 * The class BlockEffects adds to the block when the switch is on.
	 */
	const CLASS_NAME = 'purplish-cart-extras';

	/**
	 * The script that adds ticked extras when the block adds without a
	 * page load.
	 */
	const HANDLE = 'purplish-extras';

	/**
	 * Where the products come from.
	 */
	const SOURCES = array( 'pick', 'crosssells', 'auto' );

	/**
	 * How the list looks.
	 */
	const STYLES = array( 'cards', 'compact', 'tiles' );

	/**
	 * The most products the list shows.
	 */
	const MAX = 6;

	/**
	 * The field the ticked boxes post.
	 */
	const FIELD = 'purplish_extras';

	/**
	 * Whether this request has added the posted extras, so they go in once.
	 *
	 * @var bool
	 */
	private static $added = false;

	/**
	 * Lists printed on this page, for unique checkbox IDs.
	 *
	 * @var int
	 */
	private static $lists = 0;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_add_to_cart', array( $this, 'add_posted' ), 10, 2 );
	}

	/**
	 * The block's extras settings, with defaults for what isn't saved.
	 *
	 * @param mixed $saved The block's `purplish` attribute.
	 * @return array{from: string, pick: int[], fallback: bool, title: string, notitle: bool, style: string, photo: bool, link: bool, desc: bool, count: int}
	 */
	public static function settings( $saved ) {
		$saved = is_array( $saved ) ? $saved : array();
		$value = static function ( $key, $allowed, $fallback ) use ( $saved ) {
			return isset( $saved[ $key ] ) && in_array( $saved[ $key ], $allowed, true ) ? $saved[ $key ] : $fallback;
		};
		$pick  = isset( $saved['extraspick'] ) && is_array( $saved['extraspick'] ) ? $saved['extraspick'] : array();
		$title = isset( $saved['extrastitle'] ) && is_string( $saved['extrastitle'] ) ? trim( $saved['extrastitle'] ) : '';
		$count = isset( $saved['extrascount'] ) && is_int( $saved['extrascount'] ) ? $saved['extrascount'] : 3;

		return array(
			'from'     => $value( 'extrasfrom', self::SOURCES, 'crosssells' ),
			'pick'     => array_values( array_unique( array_filter( array_map( 'absint', $pick ) ) ) ),
			'fallback' => false !== ( isset( $saved['extrasfallback'] ) ? $saved['extrasfallback'] : true ),
			'title'    => '' === $title ? __( 'Add matching extras?', 'purplish' ) : $title,
			'notitle'  => isset( $saved['extrasnotitle'] ) && true === $saved['extrasnotitle'],
			'style'    => $value( 'extrasstyle', self::STYLES, 'cards' ),
			'photo'    => ! isset( $saved['extrasphoto'] ) || false !== $saved['extrasphoto'],
			'link'     => ! isset( $saved['extraslink'] ) || false !== $saved['extraslink'],
			'desc'     => isset( $saved['extrasdesc'] ) && true === $saved['extrasdesc'],
			'count'    => max( 1, min( self::MAX, $count ) ),
		);
	}

	/**
	 * The products the list shows for a product. Pick them and Cross-sells
	 * fall back to Auto match when none of theirs can go in the cart.
	 *
	 * @param \WC_Product $product  The product on the page.
	 * @param array       $settings From `settings()`.
	 * @return \WC_Product[]
	 */
	public static function products( $product, $settings ) {
		$ids = array();
		if ( 'pick' === $settings['from'] ) {
			$ids = $settings['pick'];
		} elseif ( 'crosssells' === $settings['from'] ) {
			$ids = $product->get_cross_sell_ids();
		}

		$found = self::usable( $ids, $product->get_id(), $settings['count'] );
		if ( 'auto' === $settings['from'] || ( ! $found && $settings['fallback'] ) ) {
			$found = self::usable( self::matches( $product, $settings['count'] ), $product->get_id(), $settings['count'] );
		}
		return $found;
	}

	/**
	 * Simple products that are on sale, in stock and shown in the shop,
	 * other than the product on the page, in the given order.
	 *
	 * @param int[] $ids     Product IDs.
	 * @param int   $current The product on the page.
	 * @param int   $count   How many to keep.
	 * @return \WC_Product[]
	 */
	public static function usable( $ids, $current, $count ) {
		$found = array();
		foreach ( array_unique( array_map( 'absint', (array) $ids ) ) as $id ) {
			if ( count( $found ) >= $count ) {
				break;
			}
			$extra = $id && (int) $current !== $id ? wc_get_product( $id ) : null;
			if ( self::can_add( $extra ) ) {
				$found[] = $extra;
			}
		}
		return $found;
	}

	/**
	 * Whether a product can go in the cart as an extra, with no options to
	 * pick.
	 *
	 * @param mixed $extra A product, or anything else.
	 * @return bool
	 */
	public static function can_add( $extra ) {
		return $extra instanceof \WC_Product && $extra->is_type( 'simple' ) && 'publish' === $extra->get_status() && $extra->is_purchasable() && $extra->is_in_stock() && $extra->is_visible();
	}

	/**
	 * Products from the same categories, then the same tags, best sellers
	 * first.
	 *
	 * @param \WC_Product $product The product on the page.
	 * @param int         $count   How many it needs.
	 * @return int[]
	 */
	private static function matches( $product, $count ) {
		$ids        = array();
		$taxonomies = array(
			'category' => 'product_cat',
			'tag'      => 'product_tag',
		);
		foreach ( $taxonomies as $arg => $taxonomy ) {
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( count( $ids ) >= $count || ! is_array( $terms ) || ! $terms ) {
				continue;
			}
			$ids = array_merge(
				$ids,
				wc_get_products(
					array(
						'status'       => 'publish',
						'type'         => 'simple',
						'stock_status' => 'instock',
						'visibility'   => 'catalog',
						$arg           => wp_list_pluck( $terms, 'slug' ),
						'exclude'      => array_merge( array( $product->get_id() ), $ids ),
						// Room for ones that turn out not purchasable.
						'limit'        => $count * 2,
						'return'       => 'ids',
						'meta_key'     => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Best sellers first, as WooCommerce's own popularity sort.
						'orderby'      => array(
							'meta_value_num' => 'DESC',
							'ID'             => 'DESC',
						),
					)
				)
			);
		}
		return $ids;
	}

	/**
	 * Prints the list inside the block's form, under the button, and loads
	 * its styles and script. Nothing prints when no product fits.
	 *
	 * @param string $html  The rendered block.
	 * @param mixed  $saved The block's `purplish` attribute.
	 * @return string
	 */
	public static function render( $html, $saved ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
		$end     = strripos( $html, '</form>' );
		if ( ! $product || false === $end ) {
			return $html;
		}

		$settings = self::settings( $saved );
		$extras   = self::products( $product, $settings );
		if ( ! $extras ) {
			return $html;
		}

		BlockEffects::enqueue( array( 'extras' ) );
		$asset = include PURPLISH_DIR . 'build/extras.asset.php';
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/extras.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );

		return substr( $html, 0, $end ) . self::markup( $extras, $settings ) . substr( $html, $end );
	}

	/**
	 * The list's markup. Each card's label stretches over the card, so a
	 * tap anywhere ticks it; the details link sits above that.
	 *
	 * @param \WC_Product[] $extras   The products.
	 * @param array         $settings From `settings()`.
	 * @return string
	 */
	public static function markup( $extras, $settings ) {
		++self::$lists;
		$items = '';
		foreach ( $extras as $extra ) {
			$id    = 'purplish-extra-' . self::$lists . '-' . $extra->get_id();
			$photo = $settings['photo'] ? $extra->get_image(
				'woocommerce_thumbnail',
				array(
					'class'   => 'purplish-extra-photo',
					'alt'     => '',
					'loading' => 'lazy',
					'sizes'   => 'tiles' === $settings['style'] ? '(max-width: 600px) 45vw, 200px' : '56px',
				)
			) : '';
			$desc  = $settings['desc'] ? wp_trim_words( wp_strip_all_tags( $extra->get_short_description() ), 14 ) : '';
			$link  = '';
			if ( $settings['link'] ) {
				$link = sprintf(
					'<a class="purplish-extra-link" href="%1$s" target="_blank" rel="noopener">%2$s<span class="screen-reader-text"> %3$s</span></a>',
					esc_url( $extra->get_permalink() ),
					esc_html__( 'See more details', 'purplish' ),
					/* translators: %s: product name. */
					esc_html( sprintf( __( 'about %s (opens in a new tab)', 'purplish' ), $extra->get_name() ) )
				);
			}

			$items .= sprintf(
				'<div class="purplish-extra"><input class="purplish-extra-check" type="checkbox" id="%1$s" name="%2$s[]" value="%3$d" />%4$s<div class="purplish-extra-text"><label class="purplish-extra-name" for="%1$s">%5$s</label>%6$s%7$s</div><span class="purplish-extra-price">%8$s</span></div>',
				esc_attr( $id ),
				esc_attr( self::FIELD ),
				$extra->get_id(),
				$photo,
				esc_html( $extra->get_name() ),
				'' === $desc ? '' : '<p class="purplish-extra-desc">' . esc_html( $desc ) . '</p>',
				$link,
				/* translators: %s: the extra's price. */
				wp_kses_post( sprintf( __( '+ %s', 'purplish' ), wc_price( wc_get_price_to_display( $extra ) ) ) )
			);
		}

		return sprintf(
			'<fieldset class="purplish-extras purplish-extras-%1$s" data-api="%2$s"><legend class="purplish-extras-title%3$s">%4$s</legend><div class="purplish-extras-list">%5$s</div></fieldset>',
			esc_attr( $settings['style'] ),
			esc_url( rest_url( 'wc/store/v1/' ) ),
			$settings['notitle'] ? ' screen-reader-text' : '',
			esc_html( $settings['title'] ),
			$items
		);
	}

	/**
	 * Adds the ticked extras when WooCommerce posts the block's form, right
	 * after it adds the main product. Each must still be one the list can
	 * show; the store's own add to cart checks run on each.
	 *
	 * @param string $key        The main item's cart key.
	 * @param int    $product_id The main product.
	 * @return void
	 */
	public function add_posted( $key, $product_id ) {
		// WooCommerce's own form add carries no nonce either; this adds to
		// the shopper's own cart only.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( self::$added || empty( $_REQUEST[ self::FIELD ] ) || ! isset( $_REQUEST['add-to-cart'] ) || absint( wp_unslash( $_REQUEST['add-to-cart'] ) ) !== (int) $product_id ) {
			return;
		}
		$ids = array_map( 'absint', (array) wp_unslash( $_REQUEST[ self::FIELD ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		self::$added = true;
		foreach ( self::usable( $ids, $product_id, self::MAX ) as $extra ) {
			/** This filter is documented in WooCommerce's includes/class-wc-form-handler.php */
			if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $extra->get_id(), 1 ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own add to cart check.
				WC()->cart->add_to_cart( $extra->get_id(), 1 );
			}
		}
	}
}
