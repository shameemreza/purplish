<?php
/**
 * The Shelf tag block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use Purplish\Frontend\StockDot;

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
		'rule'   => array( 'bestseller', 'new', 'featured', 'tag', 'category', 'sale' ),
		'look'   => array( 'chip', 'pill', 'glass', 'ribbon', 'image' ),
		'corner' => array( 'top-right', 'top-left', 'bottom-left', 'bottom-right' ),
		'size'   => array( 'm', 's', 'l' ),
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
		add_filter( 'render_block_woocommerce/product-image', array( self::class, 'photo_radius' ), 10, 2 );
	}

	/**
	 * Hands a rounded Product Image's radius to the tags on it, so tags,
	 * sale badges and the sold-out tag sit far enough in to stay inside the
	 * round corner, and a ribbon is clipped to it.
	 *
	 * @param string $html  The rendered Product Image.
	 * @param array  $block The parsed block.
	 * @return string
	 */
	public static function photo_radius( $html, $block ) {
		$radius = isset( $block['attrs']['style']['border']['radius'] ) ? $block['attrs']['style']['border']['radius'] : '';
		$values = is_array( $radius ) ? array_values( $radius ) : array( $radius );
		$px     = 0.0;
		foreach ( $values as $value ) {
			// Presets and other units stay out: the inset only needs a rough size.
			if ( is_string( $value ) && preg_match( '/^(\d+(?:\.\d+)?)(px|rem|em)$/', trim( $value ), $match ) ) {
				$px = max( $px, (float) $match[1] * ( 'px' === $match[2] ? 1 : 16 ) );
			}
		}
		if ( $px <= 0 || false === strpos( $html, 'wc-block-components-product-image__inner-container' ) ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag( array( 'class_name' => 'wc-block-components-product-image' ) ) ) {
			return $html;
		}
		$style = trim( (string) $tags->get_attribute( 'style' ) );
		$tags->set_attribute( 'style', ( '' === $style ? '' : rtrim( $style, ';' ) . ';' ) . '--purplish-photo-radius:' . round( $px, 2 ) . 'px' );
		return $tags->get_updated_html();
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
	 * The tag's words when the label is left empty. Tag and category rules
	 * use the term's name.
	 *
	 * @param string $rule       The tag's rule.
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	public static function default_label( $rule, $attributes = array() ) {
		$taxonomy = self::taxonomy( $rule );
		if ( $taxonomy ) {
			$term = get_term( self::term( $attributes ), $taxonomy );
			return $term instanceof \WP_Term ? html_entity_decode( $term->name, ENT_QUOTES, get_bloginfo( 'charset' ) ) : '';
		}
		$labels = array(
			'bestseller' => __( 'Best seller', 'purplish' ),
			'new'        => __( 'New arrival', 'purplish' ),
			'featured'   => __( 'Featured', 'purplish' ),
			'sale'       => __( 'Deal', 'purplish' ),
		);
		return $labels[ $rule ];
	}

	/**
	 * The taxonomy a rule reads, or an empty string.
	 *
	 * @param string $rule The tag's rule.
	 * @return string
	 */
	public static function taxonomy( $rule ) {
		$taxonomies = array(
			'tag'      => 'product_tag',
			'category' => 'product_cat',
		);
		return isset( $taxonomies[ $rule ] ) ? $taxonomies[ $rule ] : '';
	}

	/**
	 * The term a tag or category rule picked, or 0.
	 *
	 * @param array $attributes Block attributes.
	 * @return int
	 */
	public static function term( $attributes ) {
		return isset( $attributes['term'] ) ? max( 0, (int) $attributes['term'] ) : 0;
	}

	/**
	 * Whether today, in the store's timezone, is inside the tag's dates.
	 * An empty or malformed date leaves that side open.
	 *
	 * @param array $attributes Block attributes.
	 * @return bool
	 */
	public static function in_dates( $attributes ) {
		$today = current_datetime()->format( 'Y-m-d' );
		$from  = isset( $attributes['from'] ) ? (string) $attributes['from'] : '';
		$until = isset( $attributes['until'] ) ? (string) $attributes['until'] : '';
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) && $today < $from ) {
			return false;
		}
		return ! ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $until ) && $today > $until );
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
		$rule     = self::option( $attributes, 'rule' );
		$taxonomy = self::taxonomy( $rule );

		if ( ! self::in_dates( $attributes ) ) {
			// Outside its dates a campaign is over, whatever a filter says.
			return false;
		}
		if ( $taxonomy ) {
			$show = self::has_term( $product, self::term( $attributes ), $taxonomy );
		} elseif ( 'sale' === $rule ) {
			$show = $product->is_on_sale();
		} elseif ( 'featured' === $rule ) {
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
	 * Whether the product has the term, or for categories one of its
	 * children. A variation reads its parent's terms.
	 *
	 * @param \WC_Product $product  The card's product.
	 * @param int         $term     Term ID.
	 * @param string      $taxonomy Taxonomy.
	 * @return bool
	 */
	public static function has_term( $product, $term, $taxonomy ) {
		if ( ! $term || ! term_exists( $term, $taxonomy ) ) {
			return false;
		}
		$terms = array( $term );
		if ( 'product_cat' === $taxonomy ) {
			$children = get_term_children( $term, $taxonomy );
			if ( is_array( $children ) ) {
				$terms = array_merge( $terms, array_map( 'intval', $children ) );
			}
		}
		$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		return has_term( $terms, $taxonomy, $id );
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

		$rule  = self::option( $attributes, 'rule' );
		$look  = self::option( $attributes, 'look' );
		$label = isset( $attributes['label'] ) ? trim( wp_strip_all_tags( (string) $attributes['label'] ) ) : '';
		$label = '' === $label ? self::default_label( $rule, $attributes ) : $label;
		$image = 'image' === $look ? self::image( $attributes, $label ) : '';
		if ( 'image' === $look && '' === $image ) {
			// No picture picked yet: the words keep the tag readable.
			$look = 'chip';
		}
		if ( '' === $label && '' === $image ) {
			return '';
		}

		$classes = array(
			'purplish-shelf-tag-look-' . $look,
			'purplish-shelf-tag-corner-' . self::option( $attributes, 'corner' ),
			'purplish-shelf-tag-rule-' . $rule,
		);
		if ( ! empty( $attributes['shine'] ) && 'image' !== $look ) {
			$classes[] = 'purplish-shelf-tag-shine';
		}

		if ( '' !== $image ) {
			// The picture is the tag: no colors, padding or border from Styles.
			$classes[] = 'wp-block-purplish-shelf-tag';
			$classes[] = 'purplish-shelf-tag-size-' . self::option( $attributes, 'size' );
			if ( ! empty( $attributes['className'] ) ) {
				$classes[] = $attributes['className'];
			}
			return sprintf( '<div class="%1$s">%2$s</div>', esc_attr( implode( ' ', $classes ) ), $image );
		}

		return sprintf(
			'<div %1$s><span class="purplish-shelf-tag-text">%2$s</span></div>',
			get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) ),
			esc_html( $label )
		);
	}

	/**
	 * The image look's picture, or an empty string when none is picked.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $label      The tag's words, the last fallback for alt text.
	 * @return string
	 */
	public static function image( $attributes, $label ) {
		$id = isset( $attributes['imageId'] ) ? (int) $attributes['imageId'] : 0;
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return '';
		}
		$alt = isset( $attributes['imageAlt'] ) ? trim( wp_strip_all_tags( (string) $attributes['imageAlt'] ) ) : '';
		if ( '' === $alt ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		$large = 'l' === self::option( $attributes, 'size' );
		return (string) wp_get_attachment_image(
			$id,
			$large ? 'medium' : 'thumbnail',
			false,
			array(
				'class'    => 'purplish-shelf-tag-image',
				'alt'      => '' === $alt ? $label : $alt,
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
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
						'sale'     => array( 'type' => 'boolean' ),
						'tags'     => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'cats'     => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'created'  => array( 'type' => 'integer' ),
						'stock'    => array(
							'type'       => 'object',
							'properties' => array(
								'text'  => array( 'type' => 'string' ),
								'class' => array( 'type' => 'string' ),
								'low'   => array( 'type' => 'boolean' ),
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
	 * "In stock" on every card, and its sold-out looks and dot need the
	 * real one.
	 *
	 * @param array $post The product as the REST API prepares it.
	 * @return array{rank: int, featured: bool, sale: bool, tags: int[], cats: int[], created: int, stock: array{text: string, class: string, low: bool}}|null
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
		$cats    = array();
		foreach ( wc_get_product_cat_ids( $id ) as $cat ) {
			$cats = array_merge( $cats, array( (int) $cat ), array_map( 'intval', get_ancestors( $cat, 'product_cat', 'taxonomy' ) ) );
		}
		return array(
			'rank'     => self::rank( $id ),
			'featured' => $product->is_featured(),
			'sale'     => $product->is_on_sale(),
			'tags'     => array_map( 'intval', wc_get_product_term_ids( $id, 'product_tag' ) ),
			'cats'     => array_values( array_unique( $cats ) ),
			'created'  => $created ? $created->getTimestamp() : 0,
			'stock'    => array(
				'text'  => (string) $stock['availability'],
				'class' => (string) $stock['class'],
				'low'   => StockDot::is_low( $product ),
			),
		);
	}
}
