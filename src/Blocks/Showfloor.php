<?php
/**
 * The Showfloor block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the floor of looks from the Look blocks inside, each with its
 * cut-out, name, line and products, plus the copy, the look list, the view
 * switch, the turn row, the panel and the dialog. The front look's copy and
 * products print in place, so without the script the page still shows one
 * look and its products. Every other look's products wait in a template.
 *
 * @since 1.3.0
 */
final class Showfloor implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'shape'    => array( 'oval', 'flat' ),
		'depth'    => array( 'subtle', 'strong', 'off' ),
		'start'    => array( 'floor', 'grid' ),
		'autoplay' => array( 'off', 'on' ),
		'pause'    => array( 'ring', 'plain', 'off' ),
		'phone'    => array( 'row', 'ring' ),
		'toggle'   => array( 'both', 'icons', 'text', 'off' ),
		'arrows'   => array( 'on', 'off' ),
		'cursor'   => array( 'label', 'ring', 'off' ),
		'list'     => array( 'both', 'faces', 'names', 'numbers', 'off' ),
		'shop'     => array( 'on', 'off' ),
		'add'      => array( 'text', 'icon' ),
		'total'    => array( 'on', 'off' ),
		'whole'    => array( 'on', 'off' ),
		'hover'    => array( 'lift', 'spotlight', 'peek', 'none' ),
		'close'    => array( 'both', 'icon', 'text' ),
		'marks'    => array( 'highlight', 'add' ),
		'fly'      => array( 'on', 'off' ),
	);

	/**
	 * Autoplay speed in seconds a look: the lowest, the highest and the
	 * default.
	 *
	 * @var int[]
	 */
	const SPEED = array( 3, 8, 4 );

	/**
	 * The drawn figures in assets/figures/, for patterns and the inserter
	 * preview. Original drawings, so they ship under the plugin's GPL.
	 *
	 * @var string[]
	 */
	const FIGURES = array( 'long-coat', 'field-parka', 'violet-coat', 'puffer', 'easy-sunday', 'trench', 'quilted-jacket', 'denim-set' );

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'showfloor';
	}

	/**
	 * A drawn figure's URL, for a pattern's Look.
	 *
	 * @param string $name A name from FIGURES.
	 * @return string Empty for an unknown name.
	 */
	public static function figure( $name ) {
		return in_array( $name, self::FIGURES, true ) ? plugins_url( 'assets/figures/' . $name . '.svg', PURPLISH_FILE ) : '';
	}

	/**
	 * Extra block settings: the inserter preview, a floor of drawn figures.
	 *
	 * @return array
	 */
	public function settings() {
		$looks = array(
			'violet-coat' => array( __( 'Gallery Night', 'purplish' ), __( 'One bright coat, everything else quiet.', 'purplish' ) ),
			'long-coat'   => array( __( 'Soft Grey', 'purplish' ), __( 'Quiet colors, sharp shape.', 'purplish' ) ),
			'puffer'      => array( __( 'Trail Mix', 'purplish' ), __( 'City paths, mountain jacket.', 'purplish' ) ),
			'trench'      => array( __( 'Late Train', 'purplish' ), __( 'Belted, buttoned, ready for rain.', 'purplish' ) ),
			'denim-set'   => array( __( 'Weekend Blue', 'purplish' ), __( 'Denim on denim, no rules.', 'purplish' ) ),
		);
		$inner = array();
		foreach ( $looks as $name => $copy ) {
			$inner[] = array(
				'name'       => 'purplish/showfloor-look',
				'attributes' => array(
					'url'  => self::figure( $name ),
					'name' => $copy[0],
					'line' => $copy[1],
				),
			);
		}
		return array(
			'example' => array(
				'viewportWidth' => 1200,
				'innerBlocks'   => $inner,
			),
		);
	}

	/**
	 * Each option's value, with the default for anything missing or unknown.
	 *
	 * @param array $attributes Block attributes.
	 * @return array<string, string>
	 */
	public static function options( $attributes ) {
		$options = array();
		foreach ( self::OPTIONS as $option => $values ) {
			$value              = isset( $attributes[ $option ] ) ? $attributes[ $option ] : '';
			$options[ $option ] = in_array( $value, $values, true ) ? $value : $values[0];
		}
		return $options;
	}

	/**
	 * The option classes.
	 *
	 * @param array $attributes Block attributes.
	 * @return string[] Class names.
	 */
	public static function classes( $attributes ) {
		$classes = array();
		foreach ( self::options( $attributes ) as $option => $value ) {
			$classes[] = 'purplish-showfloor-' . $option . '-' . $value;
		}
		return $classes;
	}

	/**
	 * Autoplay speed in seconds, kept between the lowest and the highest.
	 *
	 * @param array $attributes Block attributes.
	 * @return int
	 */
	public static function speed( $attributes ) {
		$speed = isset( $attributes['speed'] ) && is_numeric( $attributes['speed'] ) ? (int) $attributes['speed'] : self::SPEED[2];
		return max( self::SPEED[0], min( self::SPEED[1], $speed ) );
	}

	/**
	 * A share of the image, kept between 0 and 1.
	 *
	 * @param mixed $value    The saved value.
	 * @param float $fallback Used when the value isn't a number.
	 * @return float
	 */
	private static function share( $value, $fallback ) {
		return is_numeric( $value ) ? round( max( 0, min( 1, (float) $value ) ), 3 ) : $fallback;
	}

	/**
	 * The looks to print, from the Look blocks inside. A Look without an
	 * image ID or an image URL gives nothing; a product that's gone or not
	 * published is skipped.
	 *
	 * @param \WP_Block|null $block Block instance.
	 * @return array<int, array> Looks, in order.
	 */
	public static function looks( $block ) {
		$looks = array();
		if ( ! $block || empty( $block->parsed_block['innerBlocks'] ) ) {
			return $looks;
		}
		foreach ( $block->parsed_block['innerBlocks'] as $inner ) {
			if ( 'purplish/showfloor-look' !== $inner['blockName'] ) {
				continue;
			}
			$attrs = $inner['attrs'];
			$image = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;
			$url   = isset( $attrs['url'] ) ? esc_url_raw( (string) $attrs['url'], array( 'http', 'https' ) ) : '';
			// A media library image wins. A pattern can't carry one, so a Look
			// without an ID uses its URL, like the figures in assets/figures/.
			if ( $image ? ! wp_attachment_is_image( $image ) : '' === $url ) {
				continue;
			}
			$marks    = isset( $attrs['marks'] ) && is_array( $attrs['marks'] ) ? $attrs['marks'] : array();
			$face     = isset( $attrs['face'] ) && is_array( $attrs['face'] ) ? $attrs['face'] : array();
			$products = array();
			$ids      = isset( $attrs['products'] ) && is_array( $attrs['products'] ) ? array_unique( array_map( 'intval', $attrs['products'] ) ) : array();
			foreach ( $ids as $id ) {
				$product = $id ? wc_get_product( $id ) : null;
				if ( ! $product || 'publish' !== $product->get_status() ) {
					continue;
				}
				$mark       = isset( $marks[ $id ] ) && is_array( $marks[ $id ] ) ? $marks[ $id ] : array();
				$products[] = array(
					'product' => $product,
					'x'       => self::share( isset( $mark['x'] ) ? $mark['x'] : null, 0.5 ),
					'y'       => isset( $mark['y'] ) ? self::share( $mark['y'], 0.5 ) : null,
				);
			}
			// Pieces without a marker spot stand down the middle, in order.
			foreach ( $products as $n => $piece ) {
				if ( null === $piece['y'] ) {
					$products[ $n ]['y'] = round( ( $n + 1 ) / ( count( $products ) + 1 ), 3 );
				}
			}
			$looks[] = array(
				'image'    => $image,
				'url'      => $image ? '' : $url,
				'name'     => isset( $attrs['name'] ) ? trim( wp_strip_all_tags( (string) $attrs['name'] ) ) : '',
				'line'     => isset( $attrs['line'] ) ? trim( wp_strip_all_tags( (string) $attrs['line'] ) ) : '',
				'face'     => array( self::share( isset( $face['x'] ) ? $face['x'] : null, 0.5 ), self::share( isset( $face['y'] ) ? $face['y'] : null, 0.07 ) ),
				'products' => $products,
			);
		}
		return $looks;
	}

	/**
	 * A look's image: the media library image at this size, or an img tag
	 * for its URL when it has no ID.
	 *
	 * @param array  $look  A look from looks().
	 * @param string $size  Image size for a media library image.
	 * @param array  $attrs Image attributes.
	 * @return string
	 */
	private static function image( $look, $size, $attrs ) {
		if ( $look['image'] ) {
			return wp_get_attachment_image( $look['image'], $size, false, $attrs );
		}
		unset( $attrs['sizes'] );
		$html = '<img src="' . esc_url( $look['url'] ) . '"';
		foreach ( $attrs as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		return $html . '>';
	}

	/**
	 * Whether a visitor can add the product from the block: a simple
	 * product that can be bought and is in stock. Anything else links to
	 * its page.
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	public static function addable( $product ) {
		return $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
	}

	/**
	 * One product row: photo, name, price and the add button, or a link to
	 * the product when it can't be added here.
	 *
	 * @param \WC_Product $product Product.
	 * @param int         $number  Piece number, from 1.
	 * @return string
	 */
	private static function row( $product, $number ) {
		$name = wp_strip_all_tags( $product->get_name() );
		$sr   = sprintf( '<span class="screen-reader-text">%s</span>', esc_html( $name ) );
		if ( self::addable( $product ) ) {
			$button = sprintf(
				'<button type="button" class="purplish-showfloor-add" data-product="%1$d"><span class="purplish-showfloor-add-t">%2$s</span> %3$s</button>',
				$product->get_id(),
				esc_html__( 'Add to cart', 'purplish' ),
				$sr
			);
		} else {
			$button = sprintf(
				'<a class="purplish-showfloor-add is-link" href="%1$s"><span class="purplish-showfloor-add-t">%2$s</span> %3$s</a>',
				esc_url( $product->get_permalink() ),
				esc_html( $product->add_to_cart_text() ),
				$sr
			);
		}
		return sprintf(
			'<li class="purplish-showfloor-piece" data-piece="%1$d">%2$s<span class="purplish-showfloor-piece-name"><a href="%3$s">%4$s</a><span class="purplish-showfloor-piece-price">%5$s</span></span>%6$s</li>',
			$number,
			$product->get_image(
				'thumbnail',
				array(
					'alt'     => '',
					'loading' => 'lazy',
				)
			),
			esc_url( $product->get_permalink() ),
			esc_html( $name ),
			wp_kses_post( $product->get_price_html() ),
			$button
		);
	}

	/**
	 * The look's numbers: piece count line, total and whole-look label.
	 * The whole-look label is empty when a piece can't be added here.
	 *
	 * @param array $look       A look from looks().
	 * @param bool  $with_total Whether the piece count line ends with the total.
	 * @return array{meta: string, total: string, whole: string}
	 */
	public static function sums( $look, $with_total = true ) {
		$sum   = 0;
		$whole = true;
		foreach ( $look['products'] as $piece ) {
			$sum  += (float) wc_get_price_to_display( $piece['product'] );
			$whole = $whole && self::addable( $piece['product'] );
		}
		$many  = count( $look['products'] );
		$total = $many ? html_entity_decode( wp_strip_all_tags( wc_price( $sum ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ) : '';
		/* translators: %s: number of pieces. */
		$pieces = sprintf( _n( '%s piece', '%s pieces', $many, 'purplish' ), number_format_i18n( $many ) );
		return array(
			/* translators: 1: number of pieces, like "3 pieces", 2: look total. */
			'meta'  => $many && $with_total ? sprintf( __( '%1$s, %2$s', 'purplish' ), $pieces, $total ) : ( $many ? $pieces : '' ),
			'total' => $total,
			/* translators: %s: number of pieces. */
			'whole' => $many > 1 && $whole ? sprintf( _n( 'Add %s piece', 'Add all %s pieces', $many, 'purplish' ), number_format_i18n( $many ) ) : '',
		);
	}

	/**
	 * The whole-look button.
	 *
	 * @param string $label Button label, empty to hide it.
	 * @param string $total Look total.
	 * @return string
	 */
	private static function whole( $label, $total ) {
		return sprintf(
			'<button type="button" class="purplish-showfloor-whole"%1$s><span class="purplish-showfloor-whole-t">%2$s</span><span class="purplish-showfloor-whole-p">%3$s</span></button>',
			'' === $label ? ' hidden' : '',
			esc_html( $label ),
			esc_html( $total )
		);
	}

	/**
	 * The strings the view script shows, translated here.
	 *
	 * @return array<string, string>
	 */
	public static function strings() {
		return array(
			/* translators: 1: look number, 2: number of looks. */
			'count'  => __( 'Look %1$s of %2$s', 'purplish' ),
			/* translators: %s: look name. */
			'open'   => __( 'Open %s', 'purplish' ),
			/* translators: %s: look name. */
			'turn'   => __( 'Turn to %s', 'purplish' ),
			/* translators: %s: product name. */
			'mark'   => __( 'Add %s to cart', 'purplish' ),
			'adding' => __( 'Adding', 'purplish' ),
			'added'  => __( 'Added', 'purplish' ),
			'again'  => __( 'Try again', 'purplish' ),
			'done'   => __( 'Look added', 'purplish' ),
			'failed' => __( 'Some pieces failed, try again', 'purplish' ),
			'pause'  => __( 'Pause', 'purplish' ),
			'play'   => __( 'Play', 'purplish' ),
			'drag'   => __( 'Drag', 'purplish' ),
			'enter'  => __( 'Open', 'purplish' ),
			'spin'   => __( 'Turn', 'purplish' ),
		);
	}

	/**
	 * The Look blocks print nothing themselves; this prints them all.
	 * Without a look that has an image, only an empty wrapper prints.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$options = self::options( $attributes );
		$looks   = self::looks( $block );
		$classes = implode( ' ', self::classes( $attributes ) );

		if ( empty( $looks ) ) {
			return sprintf( '<div %s></div>', get_block_wrapper_attributes( array( 'class' => $classes ) ) );
		}

		$count   = count( $looks );
		$figures = '';
		$names   = '';
		foreach ( $looks as $index => $look ) {
			$sums  = self::sums( $look, 'on' === $options['total'] );
			$rows  = '';
			$marks = '';
			$peek  = '';
			foreach ( $look['products'] as $n => $piece ) {
				$product = $piece['product'];
				$name    = wp_strip_all_tags( $product->get_name() );
				$rows   .= self::row( $product, $n + 1 );
				$peek   .= $product->get_image(
					'thumbnail',
					array(
						'alt'     => '',
						'loading' => 'lazy',
					)
				);
				$marks  .= sprintf(
					'<button type="button" class="purplish-showfloor-mark" style="--x:%1$s;--y:%2$s" data-piece="%3$d" data-name="%4$s" aria-label="%4$s">%3$d</button>',
					esc_attr( (string) $piece['x'] ),
					esc_attr( (string) $piece['y'] ),
					$n + 1,
					esc_attr( $name )
				);
			}
			$image    = self::image(
				$look,
				'large',
				array(
					/* translators: %s: look name. */
					'alt'           => '' === $look['name'] ? '' : sprintf( __( '%s look', 'purplish' ), $look['name'] ),
					'sizes'         => '(max-width: 781px) 60vw, 420px',
					'loading'       => 0 === $index ? 'eager' : 'lazy',
					'fetchpriority' => 0 === $index ? 'high' : 'auto',
					'decoding'      => 'async',
					'draggable'     => 'false',
				)
			);
			$figures .= sprintf(
				'<li class="purplish-showfloor-look" data-name="%1$s" data-line="%2$s" data-meta="%3$s" data-total="%4$s" data-whole="%5$s"><button type="button" class="purplish-showfloor-figure" aria-label="%1$s">%6$s</button><span class="purplish-showfloor-shadow" aria-hidden="true"></span><span class="purplish-showfloor-grid-label"><strong>%7$s</strong> %8$s</span>%9$s<template class="purplish-showfloor-marks">%10$s</template><template class="purplish-showfloor-rows">%11$s</template></li>',
				esc_attr( $look['name'] ),
				esc_attr( $look['line'] ),
				esc_attr( $sums['meta'] ),
				esc_attr( $sums['total'] ),
				esc_attr( $sums['whole'] ),
				$image,
				esc_html( $look['name'] ),
				esc_html( $sums['meta'] ),
				'peek' === $options['hover'] && '' !== $peek ? '<span class="purplish-showfloor-grid-peek" aria-hidden="true">' . $peek . '</span>' : '',
				$marks,
				$rows
			);
			$names   .= sprintf(
				'<li><button type="button" class="purplish-showfloor-name" data-index="%1$d"%2$s><span class="purplish-showfloor-name-face" aria-hidden="true" style="--fx:%3$s;--fy:%4$s">%5$s</span><span class="purplish-showfloor-name-n">%6$s</span><span class="purplish-showfloor-name-t">%7$s</span></button></li>',
				$index,
				0 === $index ? ' aria-current="true"' : '',
				esc_attr( (string) $look['face'][0] ),
				esc_attr( (string) $look['face'][1] ),
				'faces' === $options['list'] ? self::image(
					$look,
					'medium',
					array(
						'alt'     => '',
						'loading' => 'lazy',
					)
				) : '',
				esc_html( str_pad( number_format_i18n( $index + 1 ), 2, '0', STR_PAD_LEFT ) ),
				esc_html( $look['name'] )
			);
		}

		$first = $looks[0];
		$sums  = self::sums( $first, 'on' === $options['total'] );
		// A look with no products shows its figure and name only.
		if ( empty( $first['products'] ) ) {
			$classes .= ' is-bare';
		}
		$rows = '';
		foreach ( $first['products'] as $n => $piece ) {
			$rows .= self::row( $piece['product'], $n + 1 );
		}

		$shop = trim( isset( $attributes['shopText'] ) ? wp_strip_all_tags( (string) $attributes['shopText'] ) : '' );
		$copy = sprintf(
			'<div class="purplish-showfloor-copy"><p class="purplish-showfloor-count" aria-live="polite">%1$s</p><h2 class="wp-block-heading purplish-showfloor-title">%2$s</h2><p class="purplish-showfloor-meta">%3$s</p><p class="purplish-showfloor-line">%4$s</p>%5$s</div>',
			/* translators: 1: look number, 2: number of looks. */
			esc_html( sprintf( __( 'Look %1$s of %2$s', 'purplish' ), number_format_i18n( 1 ), number_format_i18n( $count ) ) ),
			esc_html( $first['name'] ),
			esc_html( $sums['meta'] ),
			esc_html( $first['line'] ),
			'on' === $options['shop'] ? sprintf( '<button type="button" class="wp-element-button purplish-showfloor-open">%s</button>', esc_html( '' === $shop ? __( 'Shop this look', 'purplish' ) : $shop ) ) : ''
		);

		$view = sprintf(
			'<div class="purplish-showfloor-view" role="group" aria-label="%1$s"><button type="button" class="purplish-showfloor-view-ring" aria-pressed="true" aria-label="%2$s"><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><ellipse cx="8" cy="12.5" rx="6.5" ry="2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M4.5 11V6M8 12V3M11.5 11V6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span class="purplish-showfloor-label">%2$s</span></button><button type="button" class="purplish-showfloor-view-grid" aria-pressed="false" aria-label="%3$s"><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><rect x="2" y="2" width="5" height="5" rx="1.2" fill="currentColor"/><rect x="9" y="2" width="5" height="5" rx="1.2" fill="currentColor"/><rect x="2" y="9" width="5" height="5" rx="1.2" fill="currentColor"/><rect x="9" y="9" width="5" height="5" rx="1.2" fill="currentColor"/></svg><span class="purplish-showfloor-label">%3$s</span></button></div>',
			esc_attr__( 'View', 'purplish' ),
			esc_html__( 'Floor', 'purplish' ),
			esc_html__( 'Grid', 'purplish' )
		);

		$turn = sprintf(
			'<div class="purplish-showfloor-turn">%1$s<button type="button" class="purplish-showfloor-prev" aria-label="%2$s"></button><span class="purplish-showfloor-hint">%3$s</span><button type="button" class="purplish-showfloor-next" aria-label="%4$s"></button></div>',
			'on' === $options['autoplay'] ? sprintf( '<button type="button" class="purplish-showfloor-play" aria-label="%s"><svg viewBox="0 0 44 44" width="44" height="44" aria-hidden="true" focusable="false"><circle class="purplish-showfloor-play-ring" cx="22" cy="22" r="20.5" pathLength="100"/><path class="purplish-showfloor-play-pause" d="M18.5 16.5v11M25.5 16.5v11"/><path class="purplish-showfloor-play-go" d="M18 15.5 29 22l-11 6.5z"/></svg></button>', esc_attr__( 'Pause', 'purplish' ) ) : '',
			esc_attr__( 'Previous look', 'purplish' ),
			esc_html__( 'Drag to turn', 'purplish' ),
			esc_attr__( 'Next look', 'purplish' )
		);

		$panel = sprintf(
			'<aside class="purplish-showfloor-panel" aria-label="%1$s"><p class="purplish-showfloor-panel-title"><span>%1$s</span><span class="purplish-showfloor-panel-total">%2$s</span></p><ul class="purplish-showfloor-pieces">%3$s</ul>%4$s</aside>',
			esc_attr__( 'In this look', 'purplish' ),
			esc_html( $sums['total'] ),
			$rows,
			self::whole( $sums['whole'], $sums['total'] )
		);

		$dialog = sprintf(
			'<dialog class="purplish-showfloor-dialog" aria-label="%1$s"><div class="purplish-showfloor-dialog-body"><div class="purplish-showfloor-solo"><img alt=""><div class="purplish-showfloor-solo-marks"></div></div><div class="purplish-showfloor-tag-wrap"><div class="purplish-showfloor-tag"><span class="purplish-showfloor-tag-hole" aria-hidden="true"></span><p class="purplish-showfloor-tag-brand">%2$s</p><h3 class="purplish-showfloor-tag-title"></h3><p class="purplish-showfloor-tag-line"></p><ol class="purplish-showfloor-tag-pieces"></ol><p class="purplish-showfloor-tag-total"><span>%3$s</span><strong></strong></p>%4$s</div></div><button type="button" class="purplish-showfloor-close" aria-label="%5$s"><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span class="purplish-showfloor-label">%6$s</span></button></div></dialog>',
			esc_attr__( 'Look', 'purplish' ),
			esc_html( wp_strip_all_tags( get_bloginfo( 'name' ) ) ),
			esc_html__( 'Look total', 'purplish' ),
			self::whole( '', '' ),
			esc_attr__( 'Close', 'purplish' ),
			esc_html__( 'Close', 'purplish' )
		);

		$wrapper = get_block_wrapper_attributes(
			array(
				'class'                => $classes,
				'aria-roledescription' => __( 'carousel', 'purplish' ),
				'aria-label'           => __( 'Shop the look', 'purplish' ),
				'data-api'             => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
				'data-speed'           => (string) ( self::speed( $attributes ) * 1000 ),
				'data-strings'         => wp_json_encode( self::strings() ),
			)
		);

		return sprintf(
			'<section %1$s><div class="purplish-showfloor-stage" role="group" tabindex="0" aria-label="%2$s"><div class="purplish-showfloor-floor" aria-hidden="true"></div><ul class="purplish-showfloor-ring">%3$s</ul><div class="purplish-showfloor-cursor" aria-hidden="true"><span>%4$s</span></div></div>%5$s%6$s%7$s%8$s%9$s%10$s</section>',
			$wrapper,
			esc_attr__( 'Looks. Use the arrow keys to turn.', 'purplish' ),
			$figures,
			esc_html__( 'Drag', 'purplish' ),
			$copy,
			'off' === $options['list'] ? '' : '<ul class="purplish-showfloor-names">' . $names . '</ul>',
			'off' === $options['toggle'] ? '' : $view,
			$turn,
			$panel,
			$dialog
		);
	}
}
