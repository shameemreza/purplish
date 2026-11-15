<?php
/**
 * The Escalator block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints photos or products as tiles in two or three columns that drift
 * in opposite directions. Each column holds its tiles twice, so the loop
 * closes without a jump; the second copy is hidden from screen readers and
 * the Tab key but still takes clicks, since it is often the copy on screen. The stylesheet moves the columns, and the view script only
 * runs the pause button.
 *
 * @since 1.3.2
 */
final class Escalator implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, array>
	 */
	const OPTIONS = array(
		'source'  => array( 'products', 'photos' ),
		'query'   => array( 'newest', 'best', 'sale', 'featured', 'category' ),
		'columns' => array( 3, 2 ),
		'speed'   => array( 'medium', 'slow', 'fast' ),
		'details' => array( 'on', 'off' ),
		'hover'   => array( 'none', 'ring', 'lift', 'sweep', 'push' ),
	);

	/**
	 * Seconds each tile takes to pass, by speed.
	 *
	 * @var array<string, float>
	 */
	const PACE = array(
		'slow'   => 12,
		'medium' => 8,
		'fast'   => 5,
	);

	/**
	 * Each column holds at least this many tiles, so a short column still
	 * fills the block's height before its loop starts again.
	 */
	const FILL = 4;

	/**
	 * The number of products, and the height in pixels: default, lowest,
	 * highest.
	 *
	 * @var array<string, int[]>
	 */
	const RANGES = array(
		'count'  => array( 9, 4, 18 ),
		'height' => array( 560, 320, 900 ),
	);

	/**
	 * The highest corner radius in pixels. Without one, tiles take the
	 * theme's Purplish radius.
	 */
	const MAX_RADIUS = 48;

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'escalator';
	}

	/**
	 * Each option's value, with the default for anything missing or unknown.
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	public static function options( $attributes ) {
		$options = array();
		foreach ( self::OPTIONS as $option => $values ) {
			$value              = isset( $attributes[ $option ] ) ? $attributes[ $option ] : null;
			$options[ $option ] = in_array( $value, $values, true ) ? $value : $values[0];
		}
		foreach ( self::RANGES as $option => $range ) {
			$value              = isset( $attributes[ $option ] ) && is_int( $attributes[ $option ] ) ? $attributes[ $option ] : $range[0];
			$options[ $option ] = max( $range[1], min( $range[2], $value ) );
		}
		$options['color']    = isset( $attributes['color'] ) && is_string( $attributes['color'] ) ? (string) sanitize_hex_color( $attributes['color'] ) : '';
		$options['radius']   = isset( $attributes['radius'] ) && is_int( $attributes['radius'] ) ? max( 0, min( self::MAX_RADIUS, $attributes['radius'] ) ) : null;
		$options['category'] = isset( $attributes['category'] ) ? absint( $attributes['category'] ) : 0;
		$options['images']   = isset( $attributes['images'] ) && is_array( $attributes['images'] ) ? array_values( array_unique( array_filter( array_map( 'absint', $attributes['images'] ) ) ) ) : array();
		return $options;
	}

	/**
	 * The block's markup.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved content, unused.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$options = self::options( $attributes );
		$tiles   = 'photos' === $options['source'] ? self::photo_tiles( $options['images'] ) : self::product_tiles( $options );
		if ( empty( $tiles ) ) {
			return '';
		}

		$columns = '';
		foreach ( self::columns( $tiles, $options['columns'] ) as $index => $column ) {
			$columns .= self::column( $column, $index, $options['speed'] );
		}

		$classes = 'purplish-escalator-columns-' . $options['columns'] . ' purplish-escalator-details-' . $options['details'];
		if ( 'none' !== $options['hover'] ) {
			$classes .= ' purplish-escalator-hover-' . $options['hover'];
		}
		$style = '--purplish-escalator-height:' . $options['height'] . 'px;';
		if ( '' !== $options['color'] ) {
			$style .= '--purplish-effect:' . $options['color'] . ';';
		}
		if ( null !== $options['radius'] ) {
			$style .= '--purplish-escalator-radius:' . $options['radius'] . 'px;';
		}
		$wrapper = get_block_wrapper_attributes(
			array(
				'class' => $classes,
				'style' => $style,
			)
		);

		return sprintf(
			'<div %1$s><div class="purplish-escalator-stage">%2$s</div><button type="button" class="purplish-escalator-pause" aria-pressed="false" hidden><span class="screen-reader-text">%3$s</span><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path class="purplish-escalator-pause-bars" d="M9 7v10M15 7v10"/><path class="purplish-escalator-pause-go" d="M9 6.5 18 12l-9 5.5z"/></svg></button></div>',
			$wrapper,
			$columns,
			esc_html__( 'Pause the moving tiles', 'purplish' )
		);
	}

	/**
	 * Deals the tiles into columns one at a time, then tops up any column
	 * shorter than FILL with tiles from the start of the list. The extra
	 * tiles are copies, so they carry `copy` and print hidden.
	 *
	 * @param array[] $tiles   Tiles, each with `html`.
	 * @param int     $columns Number of columns.
	 * @return array[] One list of tiles per column.
	 */
	public static function columns( $tiles, $columns ) {
		$dealt = array_fill( 0, $columns, array() );
		foreach ( array_values( $tiles ) as $index => $tile ) {
			$dealt[ $index % $columns ][] = $tile;
		}

		$tiles = array_values( $tiles );
		$total = count( $tiles );
		$next  = 0;
		foreach ( $dealt as $column => $list ) {
			for ( $have = count( $list ); $have < self::FILL; $have++ ) {
				$copy               = $tiles[ $next % $total ];
				$copy['copy']       = true;
				$dealt[ $column ][] = $copy;
				++$next;
			}
		}
		return $dealt;
	}

	/**
	 * One column: the tiles, then the same tiles again as the hidden copy
	 * that closes the loop. Copies stay out of screen readers and the Tab
	 * order but not out of reach of the mouse: half the time a copy is the
	 * tile on screen, so it has to take the click. Columns take turns going up and down, and each
	 * starts at a different point so the rows don't line up.
	 *
	 * @param array[] $tiles Tiles in this column.
	 * @param int     $index The column's place, from 0.
	 * @param string  $speed slow, medium or fast.
	 * @return string
	 */
	private static function column( $tiles, $index, $speed ) {
		$items  = '';
		$copies = '';
		foreach ( $tiles as $tile ) {
			$copy    = '<li class="purplish-escalator-tile" aria-hidden="true">' . self::untabbed( $tile['html'] ) . '</li>';
			$items  .= empty( $tile['copy'] ) ? '<li class="purplish-escalator-tile">' . $tile['html'] . '</li>' : $copy;
			$copies .= $copy;
		}
		$time = count( $tiles ) * self::PACE[ $speed ];

		return sprintf(
			'<div class="purplish-escalator-column purplish-escalator-%1$s" style="--purplish-escalator-time:%2$ss;--purplish-escalator-start:-%3$ss;"><div class="purplish-escalator-track"><ul class="purplish-escalator-set">%4$s</ul><ul class="purplish-escalator-set" aria-hidden="true">%5$s</ul></div></div>',
			0 === $index % 2 ? 'up' : 'down',
			esc_attr( (string) $time ),
			esc_attr( (string) round( $time * $index / 3, 2 ) ),
			$items,
			$copies
		);
	}

	/**
	 * A tile's markup with its link taken out of the Tab order.
	 *
	 * @param string $html Tile markup.
	 * @return string
	 */
	private static function untabbed( $html ) {
		$tags = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( 'a' ) ) {
			$tags->set_attribute( 'tabindex', '-1' );
		}
		return $tags->get_updated_html();
	}

	/**
	 * Image attributes every tile photo shares.
	 *
	 * @return array
	 */
	private static function image_attributes() {
		return array(
			'class' => 'purplish-escalator-photo',
			'sizes' => '(max-width: 600px) 45vw, 22vw',
		);
	}

	/**
	 * The photo in a frame that clips it to its corners, so a hover look
	 * can grow the photo or pass a light over it inside the frame.
	 *
	 * @param string $img Image markup.
	 * @return string
	 */
	private static function frame( $img ) {
		return '<span class="purplish-escalator-frame">' . $img . '</span>';
	}

	/**
	 * Tiles from picked Media Library photos, unlinked, with each photo's
	 * alt text. Anything that isn't an image is skipped.
	 *
	 * @param int[] $ids Attachment IDs.
	 * @return array[]
	 */
	public static function photo_tiles( $ids ) {
		$tiles = array();
		foreach ( $ids as $id ) {
			if ( ! wp_attachment_is_image( $id ) ) {
				continue;
			}
			$tiles[] = array( 'html' => self::frame( wp_get_attachment_image( $id, 'medium_large', false, self::image_attributes() ) ) );
		}
		return $tiles;
	}

	/**
	 * The product IDs for the chosen products, in order.
	 *
	 * @param array $options The block's options.
	 * @return int[]
	 */
	public static function product_ids( $options ) {
		$args = array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'limit'      => $options['count'],
			'orderby'    => 'date',
			'order'      => 'DESC',
			'return'     => 'ids',
		);
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$args['stock_status'] = 'instock';
		}

		switch ( $options['query'] ) {
			case 'best':
				$ranks = ShelfTag::ranks();
				if ( empty( $ranks ) ) {
					break;
				}
				$args['include'] = $ranks;
				$args['orderby'] = 'include';
				break;
			case 'sale':
				$args['include'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
				break;
			case 'featured':
				$args['featured'] = true;
				break;
			case 'category':
				$term = $options['category'] ? get_term( $options['category'], 'product_cat' ) : null;
				if ( ! $term instanceof \WP_Term ) {
					return array();
				}
				$args['category'] = array( $term->slug );
				break;
		}

		return array_map( 'intval', wc_get_products( $args ) );
	}

	/**
	 * Tiles from store products: the main photo linked to the product, with
	 * the name and price when Details is on. Products without a photo are
	 * skipped.
	 *
	 * @param array $options The block's options.
	 * @return array[]
	 */
	public static function product_tiles( $options ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$tiles = array();
		foreach ( self::product_ids( $options ) as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || ! $product->get_image_id() ) {
				continue;
			}
			$photo   = wp_get_attachment_image( $product->get_image_id(), 'medium_large', false, array_merge( self::image_attributes(), array( 'alt' => '' ) ) );
			$details = 'on' === $options['details'] ? sprintf(
				'<span class="purplish-escalator-name">%1$s</span><span class="purplish-escalator-price">%2$s</span>',
				esc_html( $product->get_name() ),
				wp_kses_post( $product->get_price_html() )
			) : sprintf( '<span class="screen-reader-text">%s</span>', esc_html( $product->get_name() ) );

			$tiles[] = array(
				'html' => sprintf( '<a class="purplish-escalator-link" href="%1$s">%2$s%3$s</a>', esc_url( $product->get_permalink() ), self::frame( $photo ), $details ),
			);
		}
		return $tiles;
	}
}
