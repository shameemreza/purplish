<?php
/**
 * Size charts: the table math, the chart patterns and which chart a
 * product shows.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * A chart is a synced pattern that holds a Size Chart block. A product
 * shows its own pick, then its category's chart. The math matches the
 * editor's (assets/src/blocks/size-chart/table.js).
 *
 * @since 1.4.0
 */
final class SizeCharts {

	/**
	 * Product meta: a chart's pattern ID, `none`, or empty for the category's.
	 */
	const META = '_purplish_size_chart';

	/**
	 * Product meta: the fit note.
	 */
	const FIT_META = '_purplish_fit_note';

	/**
	 * Product category meta: a chart's pattern ID.
	 */
	const TERM_META = 'purplish_size_chart';

	/**
	 * The pattern category every chart gets.
	 */
	const CATEGORY = 'purplish-size-charts';

	/**
	 * The tape lines a column can draw, each with the English words that
	 * pick it on its own.
	 *
	 * @var array<string, string>
	 */
	const LINES = array(
		'chest'  => '/chest|bust/i',
		'waist'  => '/waist/i',
		'hips'   => '/hip|seat/i',
		'sleeve' => '/sleeve|arm/i',
		'inseam' => '/inseam|inside leg|leg/i',
	);

	/**
	 * Context for the charts rendering now, innermost last.
	 *
	 * @var array[]
	 */
	private static $stack = array();

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'save_post_wp_block', array( self::class, 'tag_pattern' ), 10, 2 );
	}

	/**
	 * Tags a synced pattern that holds a Size Chart block with the "Size
	 * charts" pattern category, so it's easy to find under Patterns.
	 *
	 * @param int      $post_id Pattern ID.
	 * @param \WP_Post $post    Pattern.
	 * @return void
	 */
	public static function tag_pattern( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || ! has_block( 'purplish/size-chart', $post ) ) {
			return;
		}
		$term_id = self::category_term_id();
		if ( $term_id ) {
			wp_set_object_terms( $post_id, array( $term_id ), 'wp_pattern_category', true );
		}
	}

	/**
	 * The "Size charts" pattern category's term ID, made when it's missing.
	 *
	 * @return int 0 when it can't be made.
	 */
	public static function category_term_id() {
		if ( ! taxonomy_exists( 'wp_pattern_category' ) ) {
			return 0;
		}
		$term = get_term_by( 'slug', self::CATEGORY, 'wp_pattern_category' );
		if ( $term ) {
			return (int) $term->term_id;
		}
		$made = wp_insert_term( __( 'Size charts', 'purplish' ), 'wp_pattern_category', array( 'slug' => self::CATEGORY ) );
		return is_wp_error( $made ) ? 0 : (int) $made['term_id'];
	}

	/**
	 * The store's charts: published synced patterns in "Size charts".
	 *
	 * @return array<int, string> Pattern ID => title, by title.
	 */
	public static function charts() {
		$posts  = get_posts(
			array(
				'post_type'      => 'wp_block',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'wp_pattern_category',
						'field'    => 'slug',
						'terms'    => self::CATEGORY,
					),
				),
			)
		);
		$charts = array();
		foreach ( $posts as $post ) {
			$charts[ (int) $post->ID ] = '' === $post->post_title ? __( '(no title)', 'purplish' ) : $post->post_title;
		}
		return $charts;
	}

	/**
	 * Whether an ID is a published synced pattern.
	 *
	 * @param mixed $id Saved value.
	 * @return bool
	 */
	public static function is_chart( $id ) {
		if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
			return false;
		}
		$post = get_post( (int) $id );
		return $post && 'wp_block' === $post->post_type && 'publish' === $post->post_status;
	}

	/**
	 * The product that holds the chart settings: a variation's parent.
	 *
	 * @param \WC_Product $product Product.
	 * @return int Product ID.
	 */
	private static function owner( $product ) {
		return $product->is_type( 'variation' ) ? (int) $product->get_parent_id() : (int) $product->get_id();
	}

	/**
	 * The chart a product shows: its own pick, then the deepest category
	 * with a chart (ties go to the lower term order, then the older term).
	 * `none` hides it even when a category has one.
	 *
	 * @param \WC_Product $product Product.
	 * @return int Pattern ID, or 0 for no chart.
	 */
	public static function chart_id( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return 0;
		}
		$owner = self::owner( $product );
		$saved = (string) get_post_meta( $owner, self::META, true );
		$chart = 0;

		if ( 'none' !== $saved ) {
			$chart = self::is_chart( $saved ) ? (int) $saved : self::category_chart( $owner );
		}

		/**
		 * Filters the size chart a product shows.
		 *
		 * @since 1.4.0
		 *
		 * @param int         $chart   Synced pattern ID, or 0 for none.
		 * @param \WC_Product $product The product.
		 */
		$chart = (int) apply_filters( 'purplish_size_chart', $chart, $product );
		return self::is_chart( $chart ) ? $chart : 0;
	}

	/**
	 * The chart of a product's deepest category that has one.
	 *
	 * @param int $product_id Product ID.
	 * @return int Pattern ID, or 0.
	 */
	public static function category_chart( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! is_array( $terms ) ) {
			return 0;
		}
		$found = array();
		foreach ( $terms as $term ) {
			$chart = get_term_meta( $term->term_id, self::TERM_META, true );
			if ( self::is_chart( $chart ) ) {
				$found[] = array(
					'chart' => (int) $chart,
					'depth' => count( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ),
					'order' => (int) get_term_meta( $term->term_id, 'order', true ),
					'id'    => (int) $term->term_id,
				);
			}
		}
		if ( ! $found ) {
			return 0;
		}
		usort(
			$found,
			static function ( $a, $b ) {
				if ( $a['depth'] !== $b['depth'] ) {
					return $b['depth'] - $a['depth'];
				}
				return $a['order'] !== $b['order'] ? $a['order'] - $b['order'] : $a['id'] - $b['id'];
			}
		);
		return $found[0]['chart'];
	}

	/**
	 * A product's fit note.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public static function fit_note( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return '';
		}
		return trim( (string) get_post_meta( self::owner( $product ), self::FIT_META, true ) );
	}

	/**
	 * The variation attribute a product's sizes are in: the picked one when
	 * the product has it, else the first whose name or slug has "size" in
	 * it, like pa_size, shoe-size or Size.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $picked  Attribute slug picked on the block, or ''.
	 * @return string Attribute slug, like `pa_size`, or ''.
	 */
	public static function size_attribute( $product, $picked = '' ) {
		if ( ! $product instanceof \WC_Product ) {
			return '';
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product ) {
				return '';
			}
		}
		$found = '';
		foreach ( $product->get_attributes() as $slug => $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_variation() ) {
				continue;
			}
			$slug = (string) $slug;
			if ( '' !== $picked && $slug === $picked ) {
				$found = $slug;
				break;
			}
			if ( '' === $found && ( false !== stripos( $slug, 'size' ) || false !== stripos( wc_attribute_label( $attribute->get_name(), $product ), 'size' ) ) ) {
				$found = $slug;
			}
		}

		/**
		 * Filters the variation attribute that holds a product's sizes, for
		 * stores whose size attribute isn't named in English.
		 *
		 * @since 1.4.0
		 *
		 * @param string      $found   Attribute slug, like `pa_size`, or ''.
		 * @param \WC_Product $product The product.
		 */
		return (string) apply_filters( 'purplish_size_attribute', $found, $product );
	}

	/**
	 * The store's global attributes, for the editor's Beside choice. A
	 * product's own attributes match by name when Auto is picked.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function attribute_choices() {
		$choices = array();
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$choices[] = array(
				'value' => 'pa_' . $attribute->attribute_name,
				'label' => $attribute->attribute_label,
			);
		}
		return $choices;
	}

	/**
	 * The tops sample, the same as `SAMPLES.tops` in the editor.
	 *
	 * @return array{columns: array, rows: array}
	 */
	public static function sample() {
		return array(
			'columns' => array(
				array(
					'name' => __( 'Chest', 'purplish' ),
					'kind' => 'measure',
					'line' => 'auto',
					'note' => __( 'Around the fullest part of your chest, under your arms, with the tape level.', 'purplish' ),
				),
				array(
					'name' => __( 'Waist', 'purplish' ),
					'kind' => 'measure',
					'line' => 'auto',
					'note' => __( 'Around your natural waist, the narrowest part above your hips.', 'purplish' ),
				),
				array(
					'name' => __( 'Hips', 'purplish' ),
					'kind' => 'measure',
					'line' => 'auto',
					'note' => __( 'Around the fullest part of your hips, with your feet together.', 'purplish' ),
				),
			),
			'rows'    => array(
				array( 'XS', '80-85', '64-69', '86-91' ),
				array( 'S', '86-91', '70-75', '92-97' ),
				array( 'M', '92-97', '76-81', '98-103' ),
				array( 'L', '98-103', '82-87', '104-109' ),
				array( 'XL', '104-110', '88-94', '110-116' ),
			),
		);
	}

	/**
	 * The starter chart a new chart pattern opens with.
	 *
	 * @return string Block markup.
	 */
	public static function starter() {
		return '<!-- wp:purplish/size-chart ' . wp_json_encode( self::sample(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->';
	}

	/**
	 * A number, or a range like 86-91. Commas or dots read as decimals.
	 *
	 * @param string $value Typed value.
	 * @return array{min: float, max: float}|null
	 */
	public static function parse_value( $value ) {
		if ( ! preg_match( '/^\s*(\d+(?:[.,]\d+)?)\s*(?:[-\x{2013}\x{2014}]\s*(\d+(?:[.,]\d+)?))?\s*$/u', (string) $value, $match ) ) {
			return null;
		}
		$min = (float) str_replace( ',', '.', $match[1] );
		$max = isset( $match[2] ) && '' !== $match[2] ? (float) str_replace( ',', '.', $match[2] ) : $min;
		return array(
			'min' => $min,
			'max' => $max,
		);
	}

	/**
	 * Centimeters to inches, to the nearest half inch.
	 *
	 * @param float $cm Centimeters.
	 * @return float
	 */
	public static function inches( $cm ) {
		return round( (float) $cm / 2.54 * 2 ) / 2;
	}

	/**
	 * A number with at most one decimal, without a trailing zero.
	 *
	 * @param float $number Number.
	 * @return string
	 */
	public static function format( $number ) {
		return rtrim( rtrim( number_format( (float) $number, 1, '.', '' ), '0' ), '.' );
	}

	/**
	 * A measurement in both units.
	 *
	 * @param string $value Typed value.
	 * @return array{cm: string, in: string}|null Null for text.
	 */
	public static function cell( $value ) {
		$range = self::parse_value( $value );
		if ( null === $range ) {
			return null;
		}
		$cm = self::format( $range['min'] );
		$in = self::format( self::inches( $range['min'] ) );
		if ( $range['min'] !== $range['max'] ) {
			$cm .= '-' . self::format( $range['max'] );
			$in .= '-' . self::format( self::inches( $range['max'] ) );
		}
		return array(
			'cm' => $cm,
			'in' => $in,
		);
	}

	/**
	 * The tape line a column draws: its pick, or a guess from its name.
	 *
	 * @param array $column Column: name, kind, line.
	 * @return string A key of LINES, or `none`.
	 */
	public static function line_for( $column ) {
		if ( 'measure' !== ( isset( $column['kind'] ) ? $column['kind'] : 'measure' ) ) {
			return 'none';
		}
		$line = isset( $column['line'] ) && '' !== $column['line'] ? (string) $column['line'] : 'auto';
		if ( 'auto' !== $line ) {
			return isset( self::LINES[ $line ] ) ? $line : 'none';
		}
		$name = isset( $column['name'] ) ? (string) $column['name'] : '';
		foreach ( self::LINES as $key => $pattern ) {
			if ( preg_match( $pattern, $name ) ) {
				return $key;
			}
		}
		return 'none';
	}

	/**
	 * The figure a chart shows. Auto shows the bottom for an inseam, the
	 * top for any other line, and none when no column draws a line.
	 *
	 * @param string $figure  Saved figure.
	 * @param array  $columns Columns.
	 * @return string `top`, `bottom` or `none`.
	 */
	public static function figure_for( $figure, $columns ) {
		if ( in_array( $figure, array( 'top', 'bottom', 'none' ), true ) ) {
			return $figure;
		}
		if ( 'auto' !== $figure && '' !== (string) $figure ) {
			return 'none';
		}
		$lines = array_map( array( self::class, 'line_for' ), is_array( $columns ) ? $columns : array() );
		if ( in_array( 'inseam', $lines, true ) ) {
			return 'bottom';
		}
		return array_diff( $lines, array( 'none' ) ) ? 'top' : 'none';
	}

	/**
	 * Starts rendering charts for a product. Inner charts read this for
	 * their pick buttons and the product's own settings.
	 *
	 * @param array $context `product` (ID), `attribute` (size attribute slug)
	 *                       and `overrides` (unit, switch, pick).
	 * @return void
	 */
	public static function push( $context ) {
		self::$stack[] = wp_parse_args(
			$context,
			array(
				'product'   => 0,
				'attribute' => '',
				'overrides' => array(),
			)
		);
	}

	/**
	 * Ends the innermost context.
	 *
	 * @return void
	 */
	public static function pop() {
		array_pop( self::$stack );
	}

	/**
	 * The innermost context, or an empty array outside a product's chart.
	 *
	 * @return array
	 */
	public static function current() {
		return self::$stack ? end( self::$stack ) : array();
	}
}
