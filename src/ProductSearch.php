<?php
/**
 * The instant search route: product matches for a few typed letters.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * `GET /wp-json/purplish/v1/search?q=…&n=6&c=1`, public and without a
 * nonce. A nonce printed into a cached page expires, and every search on
 * that page would then fail, so the route asks for none and reads no
 * cookie. Its answers are the same for every shopper, so it sends
 * `Cache-Control: public` and an edge cache can keep them. Not the Store
 * API, which some hosts block.
 *
 * SKU matches come first, variation SKUs included and shown as their
 * product, then title matches. Both follow catalog visibility and the
 * "Hide out of stock items" setting, as the shop page does.
 *
 * @since 1.4.1
 */
final class ProductSearch {

	const NAMESPACE = 'purplish/v1';
	const ROUTE     = '/search';

	const MIN_LENGTH = 2;
	const MAX_LENGTH = 64;
	const MIN_COUNT  = 3;
	const MAX_COUNT  = 10;
	const COUNT      = 6;
	const CATEGORIES = 3;

	/**
	 * Seconds a shopper's browser or an edge cache may keep an answer.
	 */
	const MAX_AGE = 300;

	/**
	 * Seconds an answer stays in the object cache.
	 */
	const CACHE_TTL = 600;

	const CACHE_GROUP = 'purplish';

	/**
	 * The `last changed` group. Changing it drops every kept answer.
	 */
	const VERSION_GROUP = 'purplish_search';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( self::class, 'register_route' ) );
		add_filter( 'rest_send_nocache_headers', array( self::class, 'keep_cacheable' ) );

		foreach ( array( 'save_post_product', 'save_post_product_variation', 'deleted_post', 'trashed_post', 'untrashed_post' ) as $hook ) {
			add_action( $hook, array( self::class, 'bump' ) );
		}
		// A save that changes only product data, like a price or a sale
		// starting, fires these and no save_post.
		foreach ( array( 'woocommerce_new_product', 'woocommerce_update_product', 'woocommerce_new_product_variation', 'woocommerce_update_product_variation' ) as $hook ) {
			add_action( $hook, array( self::class, 'bump' ) );
		}
		foreach ( array( 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status', 'woocommerce_product_set_visibility' ) as $hook ) {
			add_action( $hook, array( self::class, 'bump' ) );
		}
		foreach ( array( 'created_product_cat', 'edited_product_cat', 'delete_product_cat' ) as $hook ) {
			add_action( $hook, array( self::class, 'bump' ) );
		}
		add_action( 'update_option_woocommerce_hide_out_of_stock_items', array( self::class, 'bump' ) );
	}

	/**
	 * Registers the route.
	 *
	 * @return void
	 */
	public static function register_route() {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'respond' ),
				// Public on purpose: it shows what the shop page already shows to guests.
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => array( self::class, 'valid_query' ),
						'sanitize_callback' => array( self::class, 'normalize' ),
					),
					'n' => array(
						'type'              => 'integer',
						'default'           => self::COUNT,
						// A custom sanitizer drops WordPress's type check, so ask for it.
						'validate_callback' => 'rest_validate_request_arg',
						'sanitize_callback' => array( self::class, 'clamp_count' ),
					),
					'c' => array(
						'type'    => 'integer',
						'enum'    => array( 0, 1 ),
						'default' => 1,
					),
				),
			)
		);
	}

	/**
	 * A query as the route keys it: trimmed, lowercase, single spaces.
	 *
	 * @param mixed $value The raw query.
	 * @return string
	 */
	public static function normalize( $value ) {
		$value = is_scalar( $value ) ? wp_strip_all_tags( (string) $value ) : '';
		$value = trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
	}

	/**
	 * Whether a query is 2 to 64 characters once normalized.
	 *
	 * @param mixed $value The raw query.
	 * @return bool
	 */
	public static function valid_query( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}
		$length = self::length( self::normalize( $value ) );
		return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
	}

	/**
	 * A result count kept between 3 and 10.
	 *
	 * @param mixed $value The asked count, already checked as an integer.
	 * @return int
	 */
	public static function clamp_count( $value ) {
		return max( self::MIN_COUNT, min( self::MAX_COUNT, (int) $value ) );
	}

	/**
	 * Characters in a string.
	 *
	 * @param string $value String.
	 * @return int
	 */
	private static function length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}

	/**
	 * Answers a search.
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response
	 */
	public static function respond( $request ) {
		$query      = (string) $request['q'];
		$count      = self::clamp_count( $request['n'] );
		$categories = 1 === (int) $request['c'];

		$response = new \WP_REST_Response( self::results( $query, $count, $categories ) );
		$response->header( 'Cache-Control', 'public, max-age=' . self::MAX_AGE );
		return $response;
	}

	/**
	 * Keeps a search answer cacheable for a signed-in visitor too. The
	 * script sends no cookie, so this only matters when someone opens the
	 * route by hand; the answer is the guest one either way.
	 *
	 * @param bool $send Whether WordPress sends no-cache headers.
	 * @return bool
	 */
	public static function keep_cacheable( $send ) {
		if ( ! $send ) {
			return $send;
		}
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
		return '/' . self::NAMESPACE . self::ROUTE === untrailingslashit( $route ) ? false : $send;
	}

	/**
	 * The answer for a query, from the object cache when it's there.
	 *
	 * @param string $query      Normalized query.
	 * @param int    $count      Most products.
	 * @param bool   $categories Whether to add matching categories.
	 * @return array{cats: array<int, array{name: string, url: string, count: int}>, items: array<int, array{name: string, url: string, img: string, price: string, sold: bool, sku: string}>, all: string}
	 */
	public static function results( $query, $count, $categories ) {
		$key    = 'purplish_search:' . md5( wp_cache_get_last_changed( self::VERSION_GROUP ) . '|' . $query . '|' . $count . '|' . (int) $categories );
		$cached = wp_cache_get( $key, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$results = self::as_guest(
			static function () use ( $query, $count, $categories ) {
				return array(
					'cats'  => $categories ? self::categories( $query ) : array(),
					'items' => array_map( array( self::class, 'item' ), self::product_ids( $query, $count ) ),
					'all'   => self::all_url( $query ),
				);
			}
		);

		wp_cache_set( $key, $results, self::CACHE_GROUP, self::CACHE_TTL );
		return $results;
	}

	/**
	 * Runs a callback as a guest, so prices and visibility are the ones a
	 * shopper who isn't signed in sees, and kept answers are the same for
	 * everyone.
	 *
	 * @param callable $callback What to run.
	 * @return mixed What it returns.
	 */
	private static function as_guest( $callback ) {
		$user = get_current_user_id();
		if ( $user ) {
			wp_set_current_user( 0 );
		}
		try {
			return $callback();
		} finally {
			if ( $user ) {
				wp_set_current_user( $user );
			}
		}
	}

	/**
	 * Drops every kept answer.
	 *
	 * @return void
	 */
	public static function bump() {
		wp_cache_set_last_changed( self::VERSION_GROUP );
	}

	/**
	 * Matching product IDs: SKU matches first, then title matches, with no
	 * repeats, up to the count.
	 *
	 * @param string $query Normalized query.
	 * @param int    $count Most products.
	 * @return int[]
	 */
	public static function product_ids( $query, $count ) {
		$ids = self::visible( self::sku_ids( $query, $count ), $count );
		if ( count( $ids ) >= $count ) {
			return $ids;
		}

		// Relevance, then date, then newest ID, so products saved in the
		// same second keep one order.
		$tie_break = static function ( $orderby ) {
			global $wpdb;
			return $orderby ? $orderby . ", {$wpdb->posts}.ID DESC" : $orderby;
		};
		add_filter( 'posts_orderby', $tie_break );
		$titles = new \WP_Query(
			array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				's'                   => $query,
				'search_columns'      => array( 'post_title' ),
				'fields'              => 'ids',
				'posts_per_page'      => $count - count( $ids ),
				'post__not_in'        => $ids,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'tax_query'           => self::visibility_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- product_visibility is how WooCommerce hides products.
			)
		);
		remove_filter( 'posts_orderby', $tie_break );

		return array_merge( $ids, array_map( 'intval', $titles->posts ) );
	}

	/**
	 * Products whose SKU, or a variation's SKU, is the query or starts with
	 * it. Exact matches first. A variation counts as its product.
	 *
	 * @param string $query Normalized query.
	 * @param int    $count Most products.
	 * @return int[] Product IDs, maybe more than the count so hidden ones can drop.
	 */
	public static function sku_ids( $query, $count ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce's own SKU index; the whole answer is cached above.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_parent, p.post_type
				FROM {$wpdb->wc_product_meta_lookup} l
				INNER JOIN {$wpdb->posts} p ON p.ID = l.product_id
				WHERE l.sku LIKE %s
				AND p.post_type IN ( 'product', 'product_variation' )
				AND p.post_status = 'publish'
				ORDER BY ( l.sku = %s ) DESC, l.sku ASC
				LIMIT %d",
				$wpdb->esc_like( $query ) . '%',
				$query,
				$count * 3
			)
		);

		$ids = array();
		foreach ( (array) $rows as $row ) {
			$id = 'product_variation' === $row->post_type ? (int) $row->post_parent : (int) $row->ID;
			if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * The products the shop would show, in the order given. Products with a
	 * password stay out, as they do from a title search.
	 *
	 * @param int[] $ids   Product IDs.
	 * @param int   $count Most products.
	 * @return int[]
	 */
	private static function visible( $ids, $count ) {
		if ( ! $ids ) {
			return array();
		}
		$query = new \WP_Query(
			array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'post__in'            => $ids,
				'has_password'        => false,
				'orderby'             => 'post__in',
				'fields'              => 'ids',
				'posts_per_page'      => $count,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'tax_query'           => self::visibility_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- product_visibility is how WooCommerce hides products.
			)
		);
		return array_map( 'intval', $query->posts );
	}

	/**
	 * Leaves out products hidden from search and, when the store hides
	 * them, products out of stock.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function visibility_query() {
		// By slug, not wc_get_product_visibility_term_ids(): its static copy can hold zeros from before the terms existed.
		$exclude = array( 'exclude-from-search' );
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$exclude[] = 'outofstock';
		}
		return array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'slug',
				'terms'    => $exclude,
				'operator' => 'NOT IN',
			),
		);
	}

	/**
	 * Up to three product categories with the query in their name.
	 *
	 * @param string $query Normalized query.
	 * @return array<int, array{name: string, url: string, count: int}>
	 */
	public static function categories( $query ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name__like' => $query,
				'number'     => self::CATEGORIES,
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			$url = get_term_link( $term );
			if ( is_wp_error( $url ) ) {
				continue;
			}
			$out[] = array(
				'name'  => self::plain( $term->name ),
				'url'   => $url,
				'count' => (int) $term->count,
			);
		}
		return $out;
	}

	/**
	 * One result row.
	 *
	 * @param int $id Product ID.
	 * @return array{name: string, url: string, img: string, price: string, sold: bool, sku: string}
	 */
	public static function item( $id ) {
		$product = wc_get_product( $id );
		if ( ! $product ) {
			return array(
				'name'  => '',
				'url'   => '',
				'img'   => '',
				'price' => '',
				'sold'  => false,
				'sku'   => '',
			);
		}

		$image = $product->get_image_id() ? wp_get_attachment_image_url( (int) $product->get_image_id(), 'woocommerce_gallery_thumbnail' ) : '';

		return array(
			'name'  => self::plain( $product->get_name() ),
			'url'   => (string) $product->get_permalink(),
			'img'   => $image ? $image : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ),
			'price' => (string) $product->get_price_html(),
			'sold'  => ! $product->is_in_stock(),
			'sku'   => (string) $product->get_sku(),
		);
	}

	/**
	 * Plain text for a name the script sets as text.
	 *
	 * @param string $text Stored name, maybe with entities.
	 * @return string
	 */
	private static function plain( $text ) {
		return html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * WooCommerce's full results page for the query.
	 *
	 * @param string $query Normalized query.
	 * @return string
	 */
	public static function all_url( $query ) {
		return add_query_arg(
			array(
				's'         => rawurlencode( $query ),
				'post_type' => 'product',
			),
			home_url( '/' )
		);
	}
}
