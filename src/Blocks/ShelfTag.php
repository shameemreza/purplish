<?php
/**
 * The Shelf tag block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * A small tag on a product card, such as New arrival or Best seller. It
 * prints only on products its rule matches, so one tag in the product
 * template shows on the right cards and nowhere else. Inside the Product
 * Image block it sits on the photo, in the corner picked.
 *
 * @since 1.3.0
 */
final class ShelfTag implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'rule'   => array( 'bestseller', 'new', 'featured' ),
		'look'   => array( 'chip', 'pill', 'glass' ),
		'corner' => array( 'top-right', 'top-left', 'bottom-left', 'bottom-right' ),
	);

	/**
	 * Where the best seller ranks are kept between requests.
	 */
	const RANKS = 'purplish_best_sellers';

	/**
	 * How many best sellers get a rank. The block's own limit can't go past it.
	 */
	const MOST = 50;

	/**
	 * The ranks read on this request.
	 *
	 * @var int[]|null
	 */
	private static $ranks = null;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'woocommerce_recorded_sales', array( self::class, 'forget_ranks' ) );
		add_action( 'rest_api_init', array( self::class, 'add_rest_field' ) );
	}

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'shelf-tag';
	}

	/**
	 * A saved option, or its default when it's missing or unknown.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $option     Option name.
	 * @return string
	 */
	public static function option( $attributes, $option ) {
		$value = isset( $attributes[ $option ] ) ? $attributes[ $option ] : '';
		return in_array( $value, self::OPTIONS[ $option ], true ) ? $value : self::OPTIONS[ $option ][0];
	}

	/**
	 * The tag's words when the label is left empty.
	 *
	 * @param string $rule The tag's rule.
	 * @return string
	 */
	public static function default_label( $rule ) {
		$labels = array(
			'bestseller' => __( 'Best seller', 'purplish' ),
			'new'        => __( 'New arrival', 'purplish' ),
			'featured'   => __( 'Featured', 'purplish' ),
		);
		return $labels[ $rule ];
	}

	/**
	 * The store's best sellers, most sold first, as product IDs. Only
	 * products that have sold at least once count. Kept for six hours, and
	 * worked out again after an order records its sales.
	 *
	 * @return int[]
	 */
	public static function ranks() {
		if ( null !== self::$ranks ) {
			return self::$ranks;
		}

		$saved = get_transient( self::RANKS );
		if ( is_array( $saved ) ) {
			self::$ranks = array_map( 'intval', $saved );
			return self::$ranks;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => self::MOST,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_key'               => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'                => array(
					'meta_value_num' => 'DESC',
					'ID'             => 'DESC',
				),
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'total_sales',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		self::$ranks = array_map( 'intval', $query->posts );
		set_transient( self::RANKS, self::$ranks, 6 * HOUR_IN_SECONDS );
		return self::$ranks;
	}

	/**
	 * Drops the kept best sellers, so the next tag works them out again.
	 *
	 * @return void
	 */
	public static function forget_ranks() {
		self::$ranks = null;
		delete_transient( self::RANKS );
	}

	/**
	 * A product's place among the best sellers, from 1, or 0 when it isn't
	 * one of them.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	public static function rank( $product_id ) {
		$at = array_search( (int) $product_id, self::ranks(), true );
		return false === $at ? 0 : $at + 1;
	}

	/**
	 * Whether the tag's rule matches the product.
	 *
	 * @param \WC_Product $product    The card's product.
	 * @param array       $attributes Block attributes.
	 * @return bool
	 */
	public static function matches( $product, $attributes ) {
		$rule = self::option( $attributes, 'rule' );

		if ( 'featured' === $rule ) {
			$show = $product->is_featured();
		} elseif ( 'new' === $rule ) {
			$days    = isset( $attributes['days'] ) ? max( 1, min( 365, (int) $attributes['days'] ) ) : 30;
			$created = $product->get_date_created();
			$show    = $created && $created->getTimestamp() >= time() - $days * DAY_IN_SECONDS;
		} else {
			$top  = isset( $attributes['top'] ) ? max( 1, min( self::MOST, (int) $attributes['top'] ) ) : 8;
			$rank = self::rank( $product->get_id() );
			$show = $rank > 0 && $rank <= $top;
		}

		/**
		 * Filters whether a Shelf tag shows on a product card.
		 *
		 * @since 1.3.0
		 *
		 * @param bool        $show       Whether the rule matches.
		 * @param \WC_Product $product    The card's product.
		 * @param array       $attributes The tag's block attributes.
		 */
		return (bool) apply_filters( 'purplish_shelf_tag_show', (bool) $show, $product, $attributes );
	}

	/**
	 * The tag, or nothing when its rule doesn't match the card's product.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved content (none).
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$product_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		if ( ! $product && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
			$product = $GLOBALS['product'];
		}
		if ( ! $product instanceof \WC_Product || ! self::matches( $product, $attributes ) ) {
			return '';
		}

		$rule    = self::option( $attributes, 'rule' );
		$label   = isset( $attributes['label'] ) ? trim( wp_strip_all_tags( (string) $attributes['label'] ) ) : '';
		$classes = array(
			'purplish-shelf-tag-look-' . self::option( $attributes, 'look' ),
			'purplish-shelf-tag-corner-' . self::option( $attributes, 'corner' ),
			'purplish-shelf-tag-rule-' . $rule,
		);
		if ( ! empty( $attributes['shine'] ) ) {
			$classes[] = 'purplish-shelf-tag-shine';
		}

		return sprintf(
			'<div %1$s><span class="purplish-shelf-tag-text">%2$s</span></div>',
			get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) ),
			esc_html( '' === $label ? self::default_label( $rule ) : $label )
		);
	}

	/**
	 * Gives the editor each product's best seller rank and featured flag,
	 * so a tag in the editor shows which cards it lands on.
	 *
	 * @return void
	 */
	public static function add_rest_field() {
		register_rest_field(
			'product',
			'purplish_shelf',
			array(
				'get_callback' => array( self::class, 'rest_value' ),
				'schema'       => array(
					'description' => __( 'Where the product stands for the Purplish tags on product cards.', 'purplish' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'rank'     => array( 'type' => 'integer' ),
						'featured' => array( 'type' => 'boolean' ),
						'created'  => array( 'type' => 'integer' ),
						'stock'    => array(
							'type'       => 'object',
							'properties' => array(
								'text'  => array( 'type' => 'string' ),
								'class' => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * The field's value, for people who can edit the product.
	 *
	 * The stock is here because the Stock Indicator's editor view shows
	 * "In stock" on every card, and its sold-out looks need the real one.
	 *
	 * @param array $post The product as the REST API prepares it.
	 * @return array{rank: int, featured: bool, created: int, stock: array{text: string, class: string}}|null
	 */
	public static function rest_value( $post ) {
		$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			return null;
		}
		$product = wc_get_product( $id );
		if ( ! $product ) {
			return null;
		}
		$created = $product->get_date_created();
		$stock   = $product->get_availability();
		return array(
			'rank'     => self::rank( $id ),
			'featured' => $product->is_featured(),
			'created'  => $created ? $created->getTimestamp() : 0,
			'stock'    => array(
				'text'  => (string) $stock['availability'],
				'class' => (string) $stock['class'],
			),
		);
	}
}
