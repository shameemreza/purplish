<?php
/**
 * The Changing Room block.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use Purplish\Frontend\BlockEffects;

defined( 'ABSPATH' ) || exit;

/**
 * Prints a model between two rings of pieces, from the Piece blocks inside:
 * tops turn around his chest, bottoms around his legs. Each pair of a top
 * and a bottom has its own photo, picked in the block panel. The first top
 * and the first bottom are worn when the page loads, their photo prints as
 * the page's image, and every piece stands in its place, so without the
 * script the page still shows the outfit and the rings.
 *
 * @since 1.3.0
 */
final class ChangingRoom implements Block {

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'heading' => array( 'off', 'on' ),
		'hint'    => array( 'on', 'off' ),
		'tags'    => array( 'on', 'off' ),
		'outfit'  => array( 'on', 'off' ),
		'far'     => array( 'on', 'off' ),
		'drawer'  => array( 'on', 'off' ),
		'specs'   => array( 'on', 'off' ),
		'about'   => array( 'on', 'off' ),
		'wear'    => array( 'on', 'off' ),
		'drag'    => array( 'on', 'off' ),
		'wheel'   => array( 'on', 'off' ),
		'swipe'   => array( 'on', 'off' ),
		'notice'  => array( 'on', 'off' ),
		'fly'     => array( 'on', 'off' ),
		'look'    => array( 'theme', 'glow', 'sweep', 'glass', 'spectrum' ),
		'color'   => array( 'last', 'top', 'bottom', 'off' ),
		'area'    => array( 'accents', 'stage', 'page' ),
		'swap'    => array( 'fade', 'reveal', 'none' ),
	);

	/**
	 * The two rings, in the order they print.
	 *
	 * @var string[]
	 */
	const SLOTS = array( 'top', 'bottom' );

	/**
	 * Height of each ring on the figure, as a share of its height, and how
	 * far it bends. The same numbers as ring.js.
	 *
	 * @var array<string, float[]>
	 */
	const RISE = array(
		'top'    => array( 0.35, 0.09 ),
		'bottom' => array( 0.7, -0.075 ),
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'changing-room';
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
	 * The option classes, plus Purplish's button look classes when a look
	 * is picked, so the add buttons take it.
	 *
	 * @param array $attributes Block attributes.
	 * @return string[] Class names.
	 */
	public static function classes( $attributes ) {
		$options = self::options( $attributes );
		$classes = array();
		foreach ( $options as $option => $value ) {
			$classes[] = 'purplish-changing-room-' . $option . '-' . $value;
		}
		if ( 'theme' !== $options['look'] ) {
			$classes[] = 'purplish-button';
			$classes[] = 'purplish-button-' . $options['look'];
		}
		return $classes;
	}

	/**
	 * The name of each slot, as the store set it.
	 *
	 * @param array $attributes Block attributes.
	 * @return array{top: string, bottom: string}
	 */
	public static function labels( $attributes ) {
		$top    = isset( $attributes['topLabel'] ) ? trim( wp_strip_all_tags( (string) $attributes['topLabel'] ) ) : '';
		$bottom = isset( $attributes['bottomLabel'] ) ? trim( wp_strip_all_tags( (string) $attributes['bottomLabel'] ) ) : '';
		return array(
			'top'    => '' === $top ? __( 'Jacket', 'purplish' ) : $top,
			'bottom' => '' === $bottom ? __( 'Trousers', 'purplish' ) : $bottom,
		);
	}

	/**
	 * Where a piece stands in its ring. The same sums as ring.js, so the
	 * page shows the rings before the script runs.
	 *
	 * @param int    $steps Steps from the front, negative to the left.
	 * @param int    $count Pieces in the ring.
	 * @param string $slot  `top` or `bottom`.
	 * @param bool   $far   Whether pieces two steps out show, faded.
	 * @return array{x: float, y: float, s: float, r: float, o: float, z: int}
	 */
	public static function spot( $steps, $count, $slot, $far ) {
		$angle = M_PI / max( 1, $count );
		$near  = cos( $angle );
		$turn  = cos( $steps * $angle );
		$away  = abs( $steps );
		$o     = max( 0, min( 1, ( $away - 0.3 ) / 0.45 ) );
		if ( $far ) {
			if ( $away > 2 ) {
				$o *= 0.55 * max( 0, min( 1, ( 2.5 - $away ) / 0.5 ) );
			} elseif ( $away > 1 ) {
				$o *= 1 - 0.45 * ( $away - 1 );
			}
		} else {
			$o *= max( 0, min( 1, ( 1.5 - $away ) / 0.5 ) );
		}
		$rise = isset( self::RISE[ $slot ] ) ? self::RISE[ $slot ] : self::RISE['top'];
		$sin  = sin( $angle );
		return array(
			'x' => $sin ? sin( $steps * $angle ) / $sin : 0.0,
			'y' => $rise[0] + $rise[1] * ( $near - $turn ),
			's' => ( 1 - 0.487 * ( 1 - $turn ) ) / ( 1 - 0.487 * ( 1 - $near ) ),
			'r' => max( -7, min( 7, $steps * 3 ) ),
			'o' => $o,
			'z' => (int) round( 10 + $turn * 10 ),
		);
	}

	/**
	 * Steps from the front, the short way round.
	 *
	 * @param int $index The piece's place in its ring.
	 * @param int $front The piece at the front.
	 * @param int $count Pieces in the ring.
	 * @return int
	 */
	public static function offset( $index, $front, $count ) {
		$steps = ( ( $index - $front ) % $count + $count ) % $count;
		return $steps > $count / 2 ? $steps - $count : $steps;
	}

	/**
	 * The pieces in each ring, from the Piece blocks inside. A piece
	 * without a published product is skipped, and so is a second piece
	 * with the same product in the same ring.
	 *
	 * @param \WP_Block|null $block Block instance.
	 * @return array{top: array[], bottom: array[]} Each piece's product and flat image URL.
	 */
	public static function pieces( $block ) {
		$pieces = array(
			'top'    => array(),
			'bottom' => array(),
		);
		if ( ! $block || empty( $block->parsed_block['innerBlocks'] ) ) {
			return $pieces;
		}
		foreach ( $block->parsed_block['innerBlocks'] as $inner ) {
			if ( 'purplish/changing-room-piece' !== $inner['blockName'] ) {
				continue;
			}
			$attrs   = $inner['attrs'];
			$slot    = isset( $attrs['slot'] ) && 'bottom' === $attrs['slot'] ? 'bottom' : 'top';
			$id      = isset( $attrs['product'] ) ? (int) $attrs['product'] : 0;
			$product = $id ? wc_get_product( $id ) : null;
			if ( ! $product || 'publish' !== $product->get_status() || isset( $pieces[ $slot ][ $id ] ) ) {
				continue;
			}
			$image = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;
			$url   = $image && wp_attachment_is_image( $image ) ? wp_get_attachment_image_url( $image, 'large' ) : '';
			if ( ! $url && ! empty( $attrs['url'] ) ) {
				$url = esc_url_raw( (string) $attrs['url'], array( 'http', 'https' ) );
			}
			if ( ! $url && $product->get_image_id() ) {
				$url = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
			}
			$pieces[ $slot ][ $id ] = array(
				'product' => $product,
				'image'   => $url ? $url : wc_placeholder_img_src( 'large' ),
			);
		}
		$pieces['top']    = array_values( $pieces['top'] );
		$pieces['bottom'] = array_values( $pieces['bottom'] );
		return $pieces;
	}

	/**
	 * A pair's photo: the media library image, or the saved URL when it has
	 * none (a pattern can't carry an ID).
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $top        Top product ID.
	 * @param int   $bottom     Bottom product ID.
	 * @return array{id: int, url: string} Both empty when the pair has no photo.
	 */
	public static function photo( $attributes, $top, $bottom ) {
		$photos = isset( $attributes['photos'] ) && is_array( $attributes['photos'] ) ? $attributes['photos'] : array();
		$key    = $top . '|' . $bottom;
		$saved  = isset( $photos[ $key ] ) && is_array( $photos[ $key ] ) ? $photos[ $key ] : array();
		$id     = isset( $saved['id'] ) ? (int) $saved['id'] : 0;
		if ( $id && wp_attachment_is_image( $id ) ) {
			return array(
				'id'  => $id,
				'url' => (string) wp_get_attachment_image_url( $id, 'large' ),
			);
		}
		return array(
			'id'  => 0,
			'url' => isset( $saved['url'] ) ? esc_url_raw( (string) $saved['url'], array( 'http', 'https' ) ) : '',
		);
	}

	/**
	 * The hint line, naming only the moves that are on.
	 *
	 * @param array $options Block options.
	 * @return string
	 */
	public static function hint( $options ) {
		$parts = array();
		if ( 'on' === $options['wheel'] ) {
			$parts[] = __( 'scroll a row', 'purplish' );
		} elseif ( 'on' === $options['swipe'] ) {
			$parts[] = __( 'swipe a row', 'purplish' );
		}
		if ( 'on' === $options['drag'] ) {
			$parts[] = __( 'drag a piece onto the model', 'purplish' );
		}
		$parts[] = 'on' === $options['drawer'] ? __( 'tap one for details', 'purplish' ) : __( 'tap one to try it on', 'purplish' );
		if ( 3 === count( $parts ) ) {
			/* translators: 1: a way to use the block, like "scroll a row", 2: another, like "drag a piece onto the model", 3: the last, like "tap one for details". */
			$line = sprintf( __( '%1$s, %2$s or %3$s', 'purplish' ), $parts[0], $parts[1], $parts[2] );
		} elseif ( 2 === count( $parts ) ) {
			/* translators: 1: a way to use the block, like "scroll a row", 2: another, like "tap one for details". */
			$line = sprintf( __( '%1$s or %2$s', 'purplish' ), $parts[0], $parts[1] );
		} else {
			$line = $parts[0];
		}
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $line, 0, 1 ) ) . mb_substr( $line, 1 ) : ucfirst( $line );
	}

	/**
	 * The strings the view script shows, translated here.
	 *
	 * @return array<string, string>
	 */
	public static function strings() {
		return array(
			/* translators: 1: top piece, 2: bottom piece. */
			'wearing' => __( 'Wearing the %1$s and %2$s.', 'purplish' ),
			'adding'  => __( 'Adding', 'purplish' ),
			'added'   => __( 'Added', 'purplish' ),
			'add'     => __( 'Add to cart', 'purplish' ),
			'both'    => __( 'Add both', 'purplish' ),
			'one'     => __( 'Added to cart', 'purplish' ),
			'two'     => __( 'Added both to cart', 'purplish' ),
			/* translators: 1: top piece, 2: bottom piece. */
			'and'     => __( '%1$s and %2$s', 'purplish' ),
			'failed'  => __( 'Not added, try again', 'purplish' ),
			'pick'    => __( 'Pick a size first.', 'purplish' ),
			/* translators: %1$s: product name. */
			'pickFor' => __( 'Pick a size for the %1$s to add both.', 'purplish' ),
			'try'     => __( 'Try it on', 'purplish' ),
			'worn'    => __( 'Wearing it', 'purplish' ),
		);
	}

	/**
	 * Prints the room. With no top or no bottom, only an empty wrapper
	 * prints.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$options = self::options( $attributes );
		$classes = implode( ' ', self::classes( $attributes ) );
		$found   = self::pieces( $block );

		if ( empty( $found['top'] ) || empty( $found['bottom'] ) ) {
			return sprintf( '<div %s></div>', get_block_wrapper_attributes( array( 'class' => $classes ) ) );
		}

		if ( 'theme' !== $options['look'] ) {
			BlockEffects::enqueue();
		}

		$labels = self::labels( $attributes );
		$uid    = wp_unique_id( 'purplish-changing-room-' );
		$far    = 'on' === $options['far'];
		$drawer = 'on' === $options['drawer'];
		$data   = array(
			'top'    => array(),
			'bottom' => array(),
			'looks'  => array(),
		);
		$pieces = array();
		$rings  = '';
		foreach ( self::SLOTS as $slot ) {
			$count = count( $found[ $slot ] );
			$items = '';
			foreach ( $found[ $slot ] as $index => $found_piece ) {
				$piece                     = ChangingRoomPiece::piece( $found_piece );
				$pieces[ $slot ][ $index ] = $piece;
				$at                        = self::spot( self::offset( $index, 0, $count ), $count, $slot, $far );
				$opacity                   = 0 === $index ? 0 : $at['o'];
				$sheet                     = $drawer ? sprintf(
					'<template class="purplish-changing-room-sheet" data-kind="%1$s">%2$s</template>',
					/* translators: 1: slot name, like Jacket, 2: piece number, 3: pieces in the ring. */
					esc_attr( sprintf( __( '%1$s %2$s of %3$s', 'purplish' ), $labels[ $slot ], str_pad( number_format_i18n( $index + 1 ), 2, '0', STR_PAD_LEFT ), str_pad( number_format_i18n( $count ), 2, '0', STR_PAD_LEFT ) ) ),
					ChangingRoomPiece::sheet( $piece, $options, $uid )
				) : '';
				$items .= sprintf(
					'<li style="--x:%1$s;--y:%2$s;--s:%3$s;--r:%4$sdeg;opacity:%5$s;z-index:%6$d"%7$s><button type="button" class="purplish-changing-room-piece" aria-label="%8$s"><img src="%9$s" alt="" draggable="false" decoding="async"></button>%10$s</li>',
					esc_attr( number_format( $at['x'], 4, '.', '' ) ),
					esc_attr( number_format( $at['y'], 4, '.', '' ) ),
					esc_attr( number_format( $at['s'], 4, '.', '' ) ),
					esc_attr( number_format( $at['r'], 2, '.', '' ) ),
					esc_attr( number_format( $opacity, 3, '.', '' ) ),
					$at['z'],
					$opacity < 0.06 ? ' inert' : '',
					/* translators: 1: product name, 2: price. */
					esc_attr( sprintf( __( '%1$s, %2$s', 'purplish' ), $piece['name'], $piece['price'] ) ),
					esc_url( $piece['image'] ),
					$sheet
				);
				$data[ $slot ][] = array(
					'id'    => $piece['product']->get_id(),
					'name'  => $piece['name'],
					'price' => $piece['price'],
					'img'   => $piece['image'],
					'link'  => $piece['link'],
					'c'     => $piece['color'],
					't'     => $piece['stock'],
					'a'     => $piece['addable'],
					'sizes' => array_map(
						static function ( $size ) use ( $piece ) {
							return array(
								'id' => $size['id'],
								'l'  => $size['label'],
								/* translators: 1: product name, 2: size, like M. */
								'n'  => sprintf( __( '%1$s, %2$s', 'purplish' ), $piece['name'], $size['label'] ),
								't'  => ChangingRoomPiece::stock( $size['left'], $size['in'], $size['label'] ),
								'c'  => $size['color'],
							);
						},
						$piece['sizes']
					),
				);
			}
			$rings .= sprintf(
				'<ul class="purplish-changing-room-ring purplish-changing-room-ring-%1$s" aria-label="%2$s">%3$s</ul>',
				$slot,
				/* translators: %s: slot name, like Jacket. */
				esc_attr( sprintf( __( '%s pieces', 'purplish' ), $labels[ $slot ] ) ),
				$items
			);
		}

		foreach ( $pieces['top'] as $top ) {
			foreach ( $pieces['bottom'] as $bottom ) {
				$photo = self::photo( $attributes, $top['product']->get_id(), $bottom['product']->get_id() );
				$data['looks'][ $top['product']->get_id() . '|' . $bottom['product']->get_id() ] = array( $photo['url'], ChangingRoomPiece::money( $top['amount'] + $bottom['amount'] ) );
			}
		}
		$data['api'] = esc_url_raw( rest_url( 'wc/store/v1/' ) );
		$data['s']   = self::strings();

		$top    = $pieces['top'][0];
		$bottom = $pieces['bottom'][0];
		$start  = self::photo( $attributes, $top['product']->get_id(), $bottom['product']->get_id() );
		$alt    = sprintf(
			/* translators: 1: top piece, 2: bottom piece. */
			__( 'Wearing the %1$s and %2$s.', 'purplish' ),
			$top['name'],
			$bottom['name']
		);
		$photo_attrs = array(
			'class'         => 'purplish-changing-room-photo',
			'alt'           => $alt,
			'sizes'         => '(max-width: 700px) 80vw, 560px',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decoding'      => 'async',
			'draggable'     => 'false',
		);
		if ( $start['id'] ) {
			$photo = wp_get_attachment_image( $start['id'], 'large', false, $photo_attrs );
		} elseif ( '' !== $start['url'] ) {
			$photo = sprintf( '<img class="purplish-changing-room-photo" src="%1$s" alt="%2$s" fetchpriority="high" draggable="false">', esc_url( $start['url'] ), esc_attr( $alt ) );
		} else {
			$photo = '';
		}
		$figure = sprintf(
			'<div class="purplish-changing-room-figure">%1$s<p class="purplish-changing-room-missing"%2$s>%3$s</p></div>',
			$photo,
			'' === $photo ? '' : ' hidden',
			esc_html__( 'No photo of this pair yet', 'purplish' )
		);

		$head = '';
		if ( 'on' === $options['heading'] || 'on' === $options['hint'] ) {
			$text = isset( $attributes['headingText'] ) ? trim( wp_strip_all_tags( (string) $attributes['headingText'] ) ) : '';
			$head = sprintf(
				'<header class="purplish-changing-room-head">%1$s%2$s</header>',
				'on' === $options['heading'] ? '<h2 class="wp-block-heading purplish-changing-room-heading">' . esc_html( '' === $text ? __( 'Put a look together', 'purplish' ) : $text ) . '</h2>' : '',
				'on' === $options['hint'] ? '<p class="purplish-changing-room-hint">' . esc_html( self::hint( $options ) ) . '</p>' : ''
			);
		}

		$tag     = static function ( $slot, $piece ) use ( $labels ) {
			return sprintf(
				'<button type="button" class="purplish-changing-room-tag purplish-changing-room-tag-%1$s" style="--dot:%2$s"><span class="purplish-changing-room-dot" aria-hidden="true"></span><span class="screen-reader-text">%3$s</span><span class="purplish-changing-room-tag-name">%4$s</span><span class="purplish-changing-room-tag-price">%5$s</span></button>',
				$slot,
				esc_attr( $piece['color'] ? $piece['color']['color'] : '' ),
				/* translators: %s: slot name, like Jacket. */
				esc_html( sprintf( __( '%s:', 'purplish' ), $labels[ $slot ] ) ),
				esc_html( $piece['name'] ),
				esc_html( $piece['price'] )
			);
		};
		$wearing = '';
		if ( 'on' === $options['tags'] || 'on' === $options['outfit'] ) {
			$wearing = sprintf(
				'<div class="purplish-changing-room-wearing">%1$s%2$s%3$s</div>',
				'on' === $options['tags'] ? $tag( 'top', $top ) : '',
				'on' === $options['outfit'] ? sprintf(
					'<div class="wp-block-button purplish-changing-room-outfit"><button type="button" class="wp-block-button__link wp-element-button purplish-changing-room-outfit-add"><span class="purplish-changing-room-outfit-t">%1$s</span> <span class="purplish-changing-room-total">%2$s</span></button></div>',
					esc_html__( 'Add both', 'purplish' ),
					esc_html( ChangingRoomPiece::money( $top['amount'] + $bottom['amount'] ) )
				) : '',
				'on' === $options['tags'] ? $tag( 'bottom', $bottom ) : ''
			);
		}

		$dialog = $drawer ? sprintf(
			'<dialog class="purplish-changing-room-drawer" aria-labelledby="%1$s-name"><div class="purplish-changing-room-drawer-head"><p class="purplish-changing-room-kind"></p><button type="button" class="purplish-changing-room-close" aria-label="%2$s"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button></div><div class="purplish-changing-room-scroll"></div><div class="purplish-changing-room-foot">%3$s<div class="wp-block-button purplish-changing-room-buy"><button type="button" class="wp-block-button__link wp-element-button purplish-changing-room-add">%4$s</button></div></div></dialog>',
			esc_attr( $uid ),
			esc_attr__( 'Close', 'purplish' ),
			'on' === $options['wear'] ? '<button type="button" class="purplish-changing-room-wear">' . esc_html__( 'Try it on', 'purplish' ) . '</button>' : '',
			esc_html__( 'Add to cart', 'purplish' )
		) : '';

		$notice = 'on' === $options['notice'] ? sprintf(
			'<div class="purplish-changing-room-notice" popover="manual"><img class="purplish-changing-room-notice-img" alt=""><p class="purplish-changing-room-notice-text"><b></b><span></span></p><a class="purplish-changing-room-notice-link" href="%1$s">%2$s</a></div>',
			esc_url( wc_get_cart_url() ),
			esc_html__( 'View cart', 'purplish' )
		) : '';

		$wrapper = get_block_wrapper_attributes(
			array(
				'class'      => $classes,
				'aria-label' => __( 'Changing room', 'purplish' ),
			)
		);

		return sprintf(
			'<section %1$s><p class="screen-reader-text purplish-changing-room-live" aria-live="polite">%2$s</p>%3$s<div class="purplish-changing-room-stage">%4$s%5$s</div>%6$s%7$s%8$s<script type="application/json" class="purplish-changing-room-data">%9$s</script></section>',
			$wrapper,
			esc_html( $alt ),
			$head,
			$figure,
			$rings,
			$wearing,
			$dialog,
			$notice,
			wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}
}
