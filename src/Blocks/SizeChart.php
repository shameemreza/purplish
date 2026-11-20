<?php
/**
 * The Size Chart block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use Purplish\SizeCharts;

defined( 'ABSPATH' ) || exit;

/**
 * A size chart. In a chart pattern it prints its own rows; in the Single
 * Product template it prints the product's chart and fit note. Each
 * measurement cell holds both units, and CSS shows the one picked, so the
 * switch works without the script.
 *
 * @since 1.4.0
 */
final class SizeChart implements Block {

	/**
	 * The drawn figures, their crop, and where each tape line runs on them.
	 * `from` and `to` are the line's ends; `ends` draws ticks across a
	 * straight line or dots on a bent one; `label` is x, y and anchor.
	 *
	 * @var array<string, array{file: string, view: string, tapes: array<string, array<string, mixed>>}>
	 */
	const FIGURES = array(
		'top'    => array(
			'file'  => 'puffer.svg',
			'view'  => '30 16 290 430',
			'tapes' => array(
				'chest'  => array(
					'd'     => 'M84 196 H216',
					'from'  => array( 84, 196 ),
					'to'    => array( 216, 196 ),
					'ends'  => 'ticks',
					'label' => array( 150, 184, 'middle' ),
				),
				'waist'  => array(
					'd'     => 'M90 334 H210',
					'from'  => array( 90, 334 ),
					'to'    => array( 210, 334 ),
					'ends'  => 'ticks',
					'label' => array( 150, 322, 'middle' ),
				),
				'hips'   => array(
					'd'     => 'M92 400 H208',
					'from'  => array( 92, 400 ),
					'to'    => array( 208, 400 ),
					'ends'  => 'ticks',
					'label' => array( 150, 388, 'middle' ),
				),
				'sleeve' => array(
					'd'     => 'M150 120 L214 150 L238 392',
					'from'  => array( 150, 120 ),
					'to'    => array( 238, 392 ),
					'ends'  => 'dots',
					'label' => array( 252, 276, 'start' ),
				),
			),
		),
		'bottom' => array(
			'file'  => 'denim-set.svg',
			'view'  => '40 110 270 650',
			'tapes' => array(
				'chest'  => array(
					'd'     => 'M100 196 H200',
					'from'  => array( 100, 196 ),
					'to'    => array( 200, 196 ),
					'ends'  => 'ticks',
					'label' => array( 150, 184, 'middle' ),
				),
				'waist'  => array(
					'd'     => 'M118 296 H182',
					'from'  => array( 118, 296 ),
					'to'    => array( 182, 296 ),
					'ends'  => 'ticks',
					'label' => array( 150, 284, 'middle' ),
				),
				'hips'   => array(
					'd'     => 'M112 380 H188',
					'from'  => array( 112, 380 ),
					'to'    => array( 188, 380 ),
					'ends'  => 'ticks',
					'label' => array( 150, 368, 'middle' ),
				),
				'sleeve' => array(
					'd'     => 'M150 132 L204 150 L226 398',
					'from'  => array( 150, 132 ),
					'to'    => array( 226, 398 ),
					'ends'  => 'dots',
					'label' => array( 238, 276, 'start' ),
				),
				'inseam' => array(
					'd'     => 'M146 330 V735',
					'from'  => array( 146, 330 ),
					'to'    => array( 146, 735 ),
					'ends'  => 'dots',
					'label' => array( 156, 620, 'start' ),
				),
			),
		),
	);

	/**
	 * Charts being printed in product mode, so a chart that holds a
	 * product-mode block doesn't print itself again.
	 *
	 * @var array<int, bool>
	 */
	private static $rendering = array();

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public static function hooks() {}

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'size-chart';
	}

	/**
	 * The chart, or nothing when it has no rows.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved content (none).
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		if ( isset( $attributes['source'] ) && 'product' === $attributes['source'] ) {
			return self::render_product( $attributes, $block );
		}
		return self::render_own( $attributes );
	}

	/**
	 * The product's chart and fit note. Filled in with the resolver.
	 *
	 * @param array     $attributes Block attributes.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public static function render_product( $attributes, $block ) {
		$product = self::product( $block );
		$chart   = $product ? SizeCharts::chart_id( $product ) : 0;
		if ( ! $chart || isset( self::$rendering[ $chart ] ) ) {
			return '';
		}

		$saved     = $block instanceof \WP_Block && isset( $block->parsed_block['attrs'] ) ? (array) $block->parsed_block['attrs'] : array();
		$overrides = array_intersect_key( $saved, array_flip( array( 'unit', 'switch', 'pick' ) ) );
		$wrapper   = get_block_wrapper_attributes( array( 'class' => 'purplish-size-chart--product' ) );
		$note      = ! isset( $attributes['fitnote'] ) || $attributes['fitnote'] ? SizeCharts::fit_note( $product ) : '';

		self::$rendering[ $chart ] = true;
		SizeCharts::push(
			array(
				'product'   => $product->get_id(),
				'attribute' => SizeCharts::size_attribute( $product ),
				'overrides' => $overrides,
			)
		);
		$table = do_blocks( '<!-- wp:block {"ref":' . (int) $chart . '} /-->' );
		SizeCharts::pop();
		unset( self::$rendering[ $chart ] );

		if ( '' === trim( $table ) ) {
			return '';
		}
		return sprintf(
			'<div %1$s>%2$s%3$s</div>',
			$wrapper,
			'' === $note ? '' : self::fit( $note ),
			$table
		);
	}

	/**
	 * The fit note, like "Runs small, size up".
	 *
	 * @param string $note Fit note.
	 * @return string
	 */
	public static function fit( $note ) {
		return sprintf(
			'<p class="purplish-size-chart__fit"><span class="purplish-size-chart__fit-label">%1$s</span> %2$s</p>',
			esc_html__( 'Fit', 'purplish' ),
			esc_html( $note )
		);
	}

	/**
	 * The product a product-mode block is on: the context's, the template's
	 * post, or the global product.
	 *
	 * @param \WP_Block|null $block Block.
	 * @return \WC_Product|null
	 */
	private static function product( $block ) {
		$current = SizeCharts::current();
		$id      = ! empty( $current['product'] ) ? (int) $current['product'] : 0;
		if ( ! $id && $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
			$id = (int) $block->context['postId'];
		}
		if ( ! $id && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
			return $GLOBALS['product'];
		}
		$product = $id ? wc_get_product( $id ) : null;
		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * The block's columns, cleaned: a name, a kind, a line and a note each.
	 *
	 * @param array $attributes Block attributes.
	 * @return array[]
	 */
	public static function columns( $attributes ) {
		$columns = isset( $attributes['columns'] ) && is_array( $attributes['columns'] ) ? $attributes['columns'] : array();
		$clean   = array();
		foreach ( array_values( $columns ) as $column ) {
			$column  = is_array( $column ) ? $column : array();
			$clean[] = array(
				'name' => isset( $column['name'] ) ? trim( wp_strip_all_tags( (string) $column['name'] ) ) : '',
				'kind' => isset( $column['kind'] ) && 'label' === $column['kind'] ? 'label' : 'measure',
				'line' => isset( $column['line'] ) ? (string) $column['line'] : 'auto',
				'note' => isset( $column['note'] ) ? trim( wp_strip_all_tags( (string) $column['note'] ) ) : '',
			);
		}
		return $clean;
	}

	/**
	 * The block's rows, with one value per column after the size. Rows
	 * without a size are dropped.
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $width      Number of columns after the size.
	 * @return string[][]
	 */
	public static function rows( $attributes, $width ) {
		$rows  = isset( $attributes['rows'] ) && is_array( $attributes['rows'] ) ? $attributes['rows'] : array();
		$clean = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$row = array_values( $row );
			$out = array();
			for ( $i = 0; $i <= $width; $i++ ) {
				$out[] = isset( $row[ $i ] ) && is_scalar( $row[ $i ] ) ? trim( wp_strip_all_tags( (string) $row[ $i ] ) ) : '';
			}
			if ( '' !== $out[0] ) {
				$clean[] = $out;
			}
		}
		return $clean;
	}

	/**
	 * A setting, with the product's override when the chart shows for one.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $name       Setting: unit, switch or pick.
	 * @return mixed
	 */
	private static function setting( $attributes, $name ) {
		$defaults = array(
			'unit'   => 'cm',
			'switch' => true,
			'pick'   => true,
		);
		$context  = SizeCharts::current();
		$value    = isset( $attributes[ $name ] ) ? $attributes[ $name ] : $defaults[ $name ];
		if ( isset( $context['overrides'][ $name ] ) ) {
			$value = $context['overrides'][ $name ];
		}
		if ( 'unit' === $name ) {
			return 'in' === $value ? 'in' : 'cm';
		}
		return (bool) $value;
	}

	/**
	 * The chart's own table, unit switch and How to measure part.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_own( $attributes ) {
		$columns = self::columns( $attributes );
		$rows    = self::rows( $attributes, count( $columns ) );
		if ( empty( $rows ) ) {
			return '';
		}

		$context = SizeCharts::current();
		$unit    = self::setting( $attributes, 'unit' );
		$pick    = ! empty( $context['product'] ) && self::setting( $attributes, 'pick' );
		$figure  = SizeCharts::figure_for( isset( $attributes['figure'] ) ? $attributes['figure'] : 'auto', $columns );
		$id      = wp_unique_id( 'purplish-size-chart-' );
		$product = ! empty( $context['product'] ) ? wc_get_product( (int) $context['product'] ) : null;

		$wrapper = array(
			'class'     => 'purplish-size-chart--figure-' . $figure,
			'data-unit' => $unit,
		);
		if ( $pick && ! empty( $context['attribute'] ) ) {
			$wrapper['data-chips'] = wc_variation_attribute_name( $context['attribute'] );
		}

		$html  = '<div class="purplish-size-chart__bar"><p class="purplish-size-chart__caption" id="' . esc_attr( $id ) . '-caption">' . esc_html__( 'Body measurements', 'purplish' ) . '</p>';
		$html .= self::setting( $attributes, 'switch' ) ? self::units( $id, $unit ) : '';
		$html .= '</div>';
		$html .= self::table( $columns, $rows, $id, $pick, $product ? $product->get_name() : '' );
		$html .= self::measure( $columns, $figure );

		return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( $wrapper ), $html );
	}

	/**
	 * The cm/in switch: two radios, so it works without the script.
	 *
	 * @param string $id   The chart's ID.
	 * @param string $unit The unit shown first.
	 * @return string
	 */
	private static function units( $id, $unit ) {
		$html = '<fieldset class="purplish-size-chart__units"><legend class="screen-reader-text">' . esc_html__( 'Units', 'purplish' ) . '</legend>';
		foreach ( array( 'cm', 'in' ) as $value ) {
			$html .= sprintf(
				'<label class="purplish-size-chart__unit"><input type="radio" name="%1$s" value="%2$s"%3$s><span>%2$s</span></label>',
				esc_attr( $id . '-unit' ),
				esc_attr( $value ),
				$value === $unit ? ' checked' : ''
			);
		}
		return $html . '</fieldset>';
	}

	/**
	 * The table, both units in each measurement cell.
	 *
	 * @param array[]    $columns Columns.
	 * @param string[][] $rows    Rows.
	 * @param string     $id      The chart's ID.
	 * @param bool       $pick    Whether sizes are pick buttons.
	 * @param string     $name    The product's name, for the caption.
	 * @return string
	 */
	private static function table( $columns, $rows, $id, $pick, $name ) {
		/* translators: %s: product name. */
		$caption = '' === $name ? __( 'Size chart, body measurements', 'purplish' ) : sprintf( __( '%s size chart, body measurements', 'purplish' ), $name );
		$units   = '<span class="purplish-size-chart__u"><span class="purplish-size-chart__cm">cm</span><span class="purplish-size-chart__in">in</span></span>';

		$html  = '<div class="purplish-size-chart__scroll" role="region" tabindex="0" aria-labelledby="' . esc_attr( $id ) . '-caption">';
		$html .= '<table class="purplish-size-chart__table"><caption class="screen-reader-text">' . esc_html( $caption ) . '</caption>';
		$html .= '<thead><tr><th scope="col">' . esc_html__( 'Size', 'purplish' ) . '</th>';
		foreach ( $columns as $i => $column ) {
			$html .= '<th scope="col" data-m="c' . ( $i + 1 ) . '">' . esc_html( $column['name'] ) . ( 'measure' === $column['kind'] ? $units : '' ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$size  = $row[0];
			$label = $pick ? sprintf(
				'<button type="button" class="purplish-size-chart__pick" data-size="%1$s"><span class="screen-reader-text">%2$s </span>%3$s</button>',
				esc_attr( $size ),
				esc_html__( 'Pick size', 'purplish' ),
				esc_html( $size )
			) : esc_html( $size );
			$html .= '<tr data-size="' . esc_attr( $size ) . '"><th scope="row">' . $label . '</th>';
			foreach ( $columns as $i => $column ) {
				$value = $row[ $i + 1 ];
				$key   = 'c' . ( $i + 1 );
				$both  = 'measure' === $column['kind'] ? SizeCharts::cell( $value ) : null;
				if ( null === $both ) {
					$html .= '<td data-m="' . $key . '">' . esc_html( $value ) . '</td>';
					continue;
				}
				$html .= sprintf(
					'<td data-m="%1$s" data-cm="%2$s" data-in="%3$s"><span class="purplish-size-chart__cm">%2$s</span><span class="purplish-size-chart__in">%3$s</span></td>',
					$key,
					esc_attr( $both['cm'] ),
					esc_attr( $both['in'] )
				);
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table></div>';
	}

	/**
	 * How to measure: the figure with a tape line per column that draws
	 * one, and a note per measurement that has one or a line.
	 *
	 * @param array[] $columns Columns.
	 * @param string  $figure  `top`, `bottom` or `none`.
	 * @return string
	 */
	private static function measure( $columns, $figure ) {
		$tapes = 'none' === $figure ? array() : self::FIGURES[ $figure ]['tapes'];
		$items = '';
		$lines = array();
		foreach ( $columns as $i => $column ) {
			if ( 'measure' !== $column['kind'] ) {
				continue;
			}
			$key  = 'c' . ( $i + 1 );
			$line = SizeCharts::line_for( $column );
			if ( isset( $tapes[ $line ] ) && ! isset( $lines[ $line ] ) ) {
				$lines[ $line ] = array( $key, $column['name'] );
			}
			if ( '' === $column['note'] && ! isset( $lines[ $line ] ) ) {
				continue;
			}
			$items .= '<div class="purplish-size-chart__note" data-m="' . $key . '"><dt>' . esc_html( $column['name'] ) . '<span class="purplish-size-chart__value"></span></dt>';
			$items .= '' === $column['note'] ? '' : '<dd>' . esc_html( $column['note'] ) . '</dd>';
			$items .= '</div>';
		}

		if ( '' === $items && 'none' === $figure ) {
			return '';
		}

		$html = '<div class="purplish-size-chart__measure">';
		if ( 'none' !== $figure ) {
			$html .= '<figure class="purplish-size-chart__figure">' . self::figure( $figure, $lines ) . '</figure>';
		}
		$html .= '<div class="purplish-size-chart__measure-text"><h3 class="purplish-size-chart__measure-title">' . esc_html__( 'How to measure', 'purplish' ) . '</h3>';
		$html .= '' === $items ? '' : '<dl class="purplish-size-chart__notes">' . $items . '</dl>';
		return $html . '</div></div>';
	}

	/**
	 * A drawn figure with its tape lines.
	 *
	 * @param string $figure `top` or `bottom`.
	 * @param array  $lines  Line => [ column key, column name ].
	 * @return string
	 */
	private static function figure( $figure, $lines ) {
		$spec = self::FIGURES[ $figure ];
		$file = PURPLISH_DIR . 'assets/figures/' . $spec['file'];
		$svg  = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file shipped with the plugin.
		$svg  = (string) preg_replace( '#^\s*<svg[^>]*>(<title>[^<]*</title>)?#', '', $svg );
		$svg  = (string) preg_replace( '#</svg>\s*$#', '', $svg );

		$tapes = '';
		$names = array();
		foreach ( $lines as $line => $column ) {
			$tape    = $spec['tapes'][ $line ];
			$ends    = '';
			$names[] = $column[1];
			if ( 'ticks' === $tape['ends'] ) {
				$ends = sprintf(
					'<path class="purplish-size-chart__tape-end" d="M%1$d %2$d V%3$d M%4$d %5$d V%6$d"/>',
					$tape['from'][0],
					$tape['from'][1] - 7,
					$tape['from'][1] + 7,
					$tape['to'][0],
					$tape['to'][1] - 7,
					$tape['to'][1] + 7
				);
			} else {
				foreach ( array( $tape['from'], $tape['to'] ) as $point ) {
					$ends .= sprintf( '<circle class="purplish-size-chart__tape-dot" cx="%1$d" cy="%2$d" r="3.5"/>', $point[0], $point[1] );
				}
			}
			$tapes .= sprintf(
				'<g class="purplish-size-chart__tape" data-m="%1$s"><path class="purplish-size-chart__tape-line" d="%2$s"/>%3$s<text class="purplish-size-chart__tape-label" x="%4$d" y="%5$d" text-anchor="%6$s">%7$s</text></g>',
				esc_attr( $column[0] ),
				esc_attr( $tape['d'] ),
				$ends,
				$tape['label'][0],
				$tape['label'][1],
				esc_attr( $tape['label'][2] ),
				esc_html( $column[1] )
			);
		}

		/* translators: %s: measurement names, like "Chest, Waist". */
		$label = $names ? sprintf( __( 'Where to measure: %s', 'purplish' ), implode( ', ', $names ) ) : __( 'A drawn figure', 'purplish' );
		return sprintf(
			'<svg class="purplish-size-chart__figure-svg" viewBox="%1$s" role="img" aria-label="%2$s">%3$s%4$s</svg>',
			esc_attr( $spec['view'] ),
			esc_attr( $label ),
			$svg,
			$tapes
		);
	}
}
