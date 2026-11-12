<?php
/**
 * The "Load more" option on Query Pagination inside Product Collection.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Swaps a Product Collection's page numbers for a Load more link.
 *
 * WooCommerce's client-side navigation replaces the grid with the next page,
 * and cards added to the grid by hand would miss WooCommerce's scripts, so
 * their Add to cart buttons would do nothing. So the link asks for the next
 * page with a flag, and with the flag the server draws every page so far in
 * one grid. WooCommerce's own navigation then puts that grid in place, every
 * card working, and the cards already there keep their place.
 *
 * The flag is `query-{id}-more` beside core's `query-{id}-page` on a
 * collection with its own query, and `query-more` beside `/page/N/` on a
 * collection that follows the archive's query.
 */
final class LoadMore {

	const COLLECTION = 'woocommerce/product-collection';
	const PAGINATION = 'core/query-pagination';
	const TEMPLATE   = 'woocommerce/product-template';
	const HANDLE     = 'purplish-more';

	/**
	 * The most products one grid draws. Past it the link stops and the page
	 * numbers come back, so a hand-made URL can't ask for the whole catalog.
	 */
	const MOST = 200;

	/**
	 * Query IDs of the collections on this page with Load more, each with
	 * whether WooCommerce's client-side navigation is on for it.
	 *
	 * @var array<int|string, bool>
	 */
	private $collections = array();

	/**
	 * Hooks in.
	 *
	 * @return void
	 */
	public function register() {
		// After WooCommerce's own render_block_data, which can turn client-side navigation off.
		add_filter( 'render_block_data', array( $this, 'note' ), 20 );
		// After WooCommerce builds the collection's query at 10.
		add_filter( 'query_loop_block_query_vars', array( $this, 'widen_query' ), 20, 3 );
		// After WooCommerce sets the archive's products per page at 10.
		add_action( 'pre_get_posts', array( $this, 'widen_main_query' ), 20 );
		add_filter( 'redirect_canonical', array( $this, 'keep_url' ) );
		// After core and WooCommerce finish the pagination at 10.
		add_filter( 'render_block_' . self::PAGINATION, array( $this, 'swap_pagination' ), 20, 3 );
		add_filter( 'render_block_' . self::TEMPLATE, array( $this, 'mark_new' ), 20, 3 );
	}

	/**
	 * Notes a Product Collection about to render that has Load more on its
	 * pagination, before its products query.
	 *
	 * @param array $parsed_block The block about to render.
	 * @return array
	 */
	public function note( $parsed_block ) {
		if ( ! isset( $parsed_block['blockName'] ) || self::COLLECTION !== $parsed_block['blockName'] ) {
			return $parsed_block;
		}
		$attrs = isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ? $parsed_block['attrs'] : array();
		$id    = isset( $attrs['queryId'] ) ? $attrs['queryId'] : 0;
		if ( self::has_more( isset( $parsed_block['innerBlocks'] ) ? $parsed_block['innerBlocks'] : array() ) ) {
			$this->collections[ $id ] = empty( $attrs['forcePageReload'] );
		} else {
			unset( $this->collections[ $id ] );
		}
		return $parsed_block;
	}

	/**
	 * Whether any of these blocks, or blocks inside them, is a pagination
	 * with Load more.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return bool
	 */
	private static function has_more( $blocks ) {
		foreach ( (array) $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( isset( $block['blockName'] ) && self::PAGINATION === $block['blockName'] && '' !== self::mode( isset( $block['attrs'] ) ? $block['attrs'] : array() ) ) {
				return true;
			}
			if ( ! empty( $block['innerBlocks'] ) && self::has_more( $block['innerBlocks'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * A pagination's Load more look, with the same rules as its class.
	 *
	 * @param mixed $attrs The pagination's attributes.
	 * @return string `button`, `fade`, or empty when off.
	 */
	public static function mode( $attrs ) {
		$saved = is_array( $attrs ) && isset( $attrs['purplish'] ) ? $attrs['purplish'] : array();
		foreach ( BlockEffects::classes_for( self::PAGINATION, $saved ) as $class ) {
			if ( 0 === strpos( $class, 'purplish-pages-more-' ) ) {
				return substr( $class, 20 );
			}
		}
		return '';
	}

	/**
	 * Whether the request asks for every page so far.
	 *
	 * @param string $key The flag's name.
	 * @return bool
	 */
	private static function flagged( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag, like core's page number.
		return isset( $_GET[ $key ] ) && '1' === $_GET[ $key ];
	}

	/**
	 * With the flag, turns a collection's query for page N into one for
	 * pages 1 to N.
	 *
	 * @param array     $query The query arguments.
	 * @param \WP_Block $block The block asking, Product Template or pagination.
	 * @param int       $page  The page number.
	 * @return array
	 */
	public function widen_query( $query, $block, $page ) {
		$context = isset( $block->context ) && is_array( $block->context ) ? $block->context : array();
		$id      = isset( $context['queryId'] ) ? $context['queryId'] : 0;
		if ( empty( $context['query']['isProductCollectionBlock'] ) || ! empty( $context['query']['inherit'] ) || ! isset( $this->collections[ $id ] ) ) {
			return $query;
		}
		$page = (int) $page;
		$per  = isset( $query['posts_per_page'] ) ? (int) $query['posts_per_page'] : 0;
		if ( $page < 2 || $per < 1 || $per * $page > self::MOST || ! self::flagged( 'query-' . $id . '-more' ) ) {
			return $query;
		}

		$offset                  = isset( $query['offset'] ) ? (int) $query['offset'] : 0;
		$query['offset']         = max( 0, $offset - $per * ( $page - 1 ) );
		$query['posts_per_page'] = $per * $page;
		$query['paged']          = 1;
		return $query;
	}

	/**
	 * With the flag, turns the shop archive's query for page N into one for
	 * pages 1 to N, for a collection that follows the archive.
	 *
	 * @param \WP_Query $query The query about to run.
	 * @return void
	 */
	public function widen_main_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || 'product_query' !== $query->get( 'wc_query' ) || ! self::flagged( 'query-more' ) ) {
			return;
		}
		$page = max( 1, (int) $query->get( 'paged' ) );
		$per  = (int) $query->get( 'posts_per_page' );
		if ( $page < 2 || $per < 1 || $per * $page > self::MOST ) {
			return;
		}

		$query->set( 'purplish_more_per', $per );
		$query->set( 'purplish_more_page', $page );
		$query->set( 'posts_per_page', $per * $page );
		$query->set( 'paged', 1 );
	}

	/**
	 * Keeps `/page/N/` in the URL of a widened archive. Its query now says
	 * page 1, which core would otherwise redirect to.
	 *
	 * @param string|false $redirect_url Where core would redirect.
	 * @return string|false
	 */
	public function keep_url( $redirect_url ) {
		global $wp_query;
		return $wp_query instanceof \WP_Query && $wp_query->get( 'purplish_more_per' ) ? false : $redirect_url;
	}

	/**
	 * Swaps the page numbers for the Load more link, and drops them once
	 * every product shows.
	 *
	 * @param string    $html     Rendered pagination.
	 * @param array     $block    The parsed block.
	 * @param \WP_Block $instance The block instance.
	 * @return string
	 */
	public function swap_pagination( $html, $block, $instance ) {
		$mode    = self::mode( isset( $block['attrs'] ) ? $block['attrs'] : array() );
		$saved   = isset( $block['attrs']['purplish'] ) && is_array( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
		$context = isset( $instance->context ) && is_array( $instance->context ) ? $instance->context : array();
		$query   = isset( $context['query'] ) && is_array( $context['query'] ) ? $context['query'] : array();
		$id      = isset( $context['queryId'] ) ? $context['queryId'] : 0;
		if ( '' === $mode || '' === trim( (string) $html ) || empty( $query['isProductCollectionBlock'] ) || ! isset( $this->collections[ $id ] ) ) {
			return $html;
		}

		$state = empty( $query['inherit'] ) ? self::own_state( $instance, $query, $id ) : self::archive_state();
		if ( ! $state ) {
			return $html;
		}
		list( $page, $per, $left ) = $state;

		$most = isset( $query['pages'] ) ? (int) $query['pages'] : 0;
		if ( $left < 1 || ( $most && $page >= $most ) ) {
			return '';
		}
		if ( $per * ( $page + 1 ) > self::MOST ) {
			return $html;
		}

		$next = empty( $query['inherit'] )
			? add_query_arg(
				array(
					'query-' . $id . '-page' => $page + 1,
					'query-' . $id . '-more' => 1,
				)
			)
			: add_query_arg( 'query-more', 1, get_pagenum_link( $page + 1, false ) );
		// A full page load, when the scripts aren't ready, lands on the new cards.
		$next .= '#' . self::anchor( $id, $page + 1 );

		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() || 'NAV' !== $tags->get_tag() ) {
			return $html;
		}
		$tags->add_class( 'purplish-more' );
		$open = $tags->get_updated_html();
		$open = substr( $open, 0, strpos( $open, '>' ) + 1 );

		$button = self::button( $next, $saved, $this->collections[ $id ] );
		if ( $this->collections[ $id ] ) {
			$asset = include PURPLISH_DIR . 'build/more.asset.php';
			wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/more.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		}
		return $open . $button . '</nav>';
	}

	/**
	 * The Load more button, drawn as a real Button block so it takes the
	 * theme's button styles, its outline style and the Button block's
	 * Purplish looks.
	 *
	 * @param string $next     The next page's URL.
	 * @param array  $saved    The pagination's Purplish options.
	 * @param bool   $enhanced Whether WooCommerce's client-side navigation is on.
	 * @return string
	 */
	private static function button( $next, $saved, $enhanced ) {
		$outline = isset( $saved['outline'] ) && true === $saved['outline'];
		$looks   = isset( $saved['button'] ) && is_array( $saved['button'] ) ? $saved['button'] : array();
		$markup  = '<div class="wp-block-button' . ( $outline ? ' is-style-outline' : '' ) . '"><a class="wp-block-button__link wp-element-button">' . esc_html__( 'Load more', 'purplish' ) . '<span class="purplish-more__spin" aria-hidden="true"></span></a></div>';
		$attrs   = array();
		if ( $outline ) {
			$attrs['className'] = 'is-style-outline';
		}
		if ( $looks ) {
			$attrs['purplish'] = $looks;
		}
		$html = render_block(
			array(
				'blockName'    => 'core/button',
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => $markup,
				'innerContent' => array( $markup ),
			)
		);

		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag( array( 'class_name' => 'wp-block-button' ) ) ) {
			return $html;
		}
		$tags->add_class( 'purplish-more__wrap' );
		if ( ! $tags->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) ) {
			return $html;
		}
		$tags->add_class( 'purplish-more__button' );
		$tags->set_attribute( 'href', esc_url_raw( $next ) );
		$tags->set_attribute( 'data-purplish-more', true );
		if ( $enhanced ) {
			$tags->set_attribute( 'data-wp-key', 'purplish-more' );
			$tags->set_attribute( 'data-wp-on--click', 'woocommerce/product-collection::actions.navigate' );
			$tags->set_attribute( 'data-wp-on--mouseenter', 'woocommerce/product-collection::actions.prefetchOnHover' );
		}
		return $tags->get_updated_html();
	}

	/**
	 * Puts an ID on the first card of the newest page in a widened grid,
	 * which the Load more link's fragment points at.
	 *
	 * @param string    $html     Rendered Product Template.
	 * @param array     $block    The parsed block.
	 * @param \WP_Block $instance The block instance.
	 * @return string
	 */
	public function mark_new( $html, $block, $instance ) {
		$context = isset( $instance->context ) && is_array( $instance->context ) ? $instance->context : array();
		$query   = isset( $context['query'] ) && is_array( $context['query'] ) ? $context['query'] : array();
		$id      = isset( $context['queryId'] ) ? $context['queryId'] : 0;
		if ( empty( $query['isProductCollectionBlock'] ) || ! isset( $this->collections[ $id ] ) ) {
			return $html;
		}
		$shown = self::shown( $query, $id );
		if ( ! $shown ) {
			return $html;
		}
		list( $page, $per ) = $shown;

		$tags = new \WP_HTML_Tag_Processor( $html );
		$seen = 0;
		while ( $tags->next_tag( array( 'class_name' => 'wc-block-product' ) ) ) {
			if ( 'LI' !== $tags->get_tag() ) {
				continue;
			}
			++$seen;
			if ( $per * ( $page - 1 ) + 1 === $seen ) {
				$tags->set_attribute( 'id', self::anchor( $id, $page ) );
				return $tags->get_updated_html();
			}
		}
		return $html;
	}

	/**
	 * The ID of a page's first card.
	 *
	 * @param int|string $id   The collection's query ID.
	 * @param int        $page The page.
	 * @return string
	 */
	private static function anchor( $id, $page ) {
		return 'purplish-more-' . sanitize_html_class( (string) $id ) . '-' . (int) $page;
	}

	/**
	 * Page and products per page of a widened grid, past page 1.
	 *
	 * @param array      $query The collection's query settings.
	 * @param int|string $id    The collection's query ID.
	 * @return array{0: int, 1: int}|null Null when the grid isn't widened.
	 */
	private static function shown( $query, $id ) {
		if ( ! empty( $query['inherit'] ) ) {
			global $wp_query;
			$per = $wp_query instanceof \WP_Query ? (int) $wp_query->get( 'purplish_more_per' ) : 0;
			return $per ? array( (int) $wp_query->get( 'purplish_more_page' ), $per ) : null;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, like core's own pagination.
		$page = isset( $_GET[ 'query-' . $id . '-page' ] ) ? (int) $_GET[ 'query-' . $id . '-page' ] : 1;
		$per  = isset( $query['perPage'] ) && is_numeric( $query['perPage'] ) ? max( 1, (int) $query['perPage'] ) : 9;
		return $page > 1 && $per * $page <= self::MOST && self::flagged( 'query-' . $id . '-more' ) ? array( $page, $per ) : null;
	}

	/**
	 * Page, products per page and products after the grid, for a
	 * collection with its own query.
	 *
	 * @param \WP_Block  $instance The pagination.
	 * @param array      $query    The collection's query settings.
	 * @param int|string $id       The collection's query ID.
	 * @return array{0: int, 1: int, 2: int}|null Null when page N shows alone.
	 */
	private static function own_state( $instance, $query, $id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, like core's own pagination.
		$page = isset( $_GET[ 'query-' . $id . '-page' ] ) ? max( 1, (int) $_GET[ 'query-' . $id . '-page' ] ) : 1;
		$per  = isset( $query['perPage'] ) && is_numeric( $query['perPage'] ) ? max( 1, (int) $query['perPage'] ) : 9;
		if ( $page > 1 && ( ! self::flagged( 'query-' . $id . '-more' ) || $per * $page > self::MOST ) ) {
			return null;
		}

		$args                           = build_query_vars_from_query_block( $instance, $page );
		$args['fields']                 = 'ids';
		$args['no_found_rows']          = false;
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;
		$count                          = new \WP_Query( $args );
		$skip                           = isset( $query['offset'] ) && is_numeric( $query['offset'] ) ? max( 0, (int) $query['offset'] ) : 0;

		return array( $page, $per, (int) $count->found_posts - $skip - $per * $page );
	}

	/**
	 * Page, products per page and products after the grid, for a
	 * collection that follows the archive.
	 *
	 * @return array{0: int, 1: int, 2: int}|null Null when page N shows alone.
	 */
	private static function archive_state() {
		global $wp_query;
		if ( ! $wp_query instanceof \WP_Query ) {
			return null;
		}
		$widened = (int) $wp_query->get( 'purplish_more_per' );
		$page    = $widened ? (int) $wp_query->get( 'purplish_more_page' ) : max( 1, (int) $wp_query->get( 'paged' ) );
		$per     = $widened ? $widened : (int) $wp_query->get( 'posts_per_page' );
		if ( $per < 1 || ( $page > 1 && ! $widened ) ) {
			return null;
		}
		return array( $page, $per, (int) $wp_query->found_posts - $per * $page );
	}
}
