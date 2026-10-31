<?php
/**
 * Puts each variation's colors on the product page.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\Color\Contrast;
use Purplish\Color\Resolver;
use Purplish\Color\Tone;
use Purplish\Options;
use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the page's color data for the script, and the default color on
 * `body` so the first paint already has it.
 */
final class PageData {

	const HANDLE = 'purplish-frontend';

	/**
	 * Options set by the block on this page, when a block theme renders it.
	 *
	 * @var array{parts: string[], transition: string}|null
	 */
	private static $block_options = null;

	/**
	 * Data for the current page, worked out once.
	 *
	 * @var array|null|false False until worked out.
	 */
	private $data = false;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Lets the block panel hand over the options of the block on this page.
	 *
	 * @param array $options Options as the block saved them.
	 * @return void
	 */
	public static function use_block_options( $options ) {
		self::$block_options = Options::clean( $options );
	}

	/**
	 * The options for this page: the block's when a block theme set them,
	 * otherwise the classic settings, otherwise the defaults.
	 *
	 * @return array{parts: string[], transition: string}
	 */
	public static function options() {
		if ( null !== self::$block_options ) {
			return self::$block_options;
		}

		/**
		 * Filters the options on pages without the block's own settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array{parts: string[], transition: string} $options Options.
		 */
		return Options::clean( apply_filters( 'purplish_page_options', Options::defaults() ) );
	}

	/**
	 * The color data for a variable product.
	 *
	 * @param \WC_Product                                $product Product.
	 * @param array{parts: string[], transition: string} $options Options.
	 * @return array|null Null when the product isn't variable or no variation has a color.
	 */
	public static function for_product( $product, $options ) {
		if ( ! $product instanceof WC_Product_Variable ) {
			return null;
		}

		$variations = array();
		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof WC_Product_Variation || 'publish' !== $variation->get_status() ) {
				continue;
			}

			$set = self::color_set( Resolver::accent( $variation ) );
			if ( null === $set ) {
				continue;
			}

			$variations[] = array( 'attributes' => $variation->get_variation_attributes() ) + $set;
		}

		if ( empty( $variations ) ) {
			return null;
		}

		$picked = array();
		foreach ( $product->get_default_attributes() as $name => $value ) {
			$picked[ 'attribute_' . sanitize_title( $name ) ] = (string) $value;
		}

		return array(
			'variations' => $variations,
			'default'    => self::match( $variations, $picked ),
			'options'    => $options,
		);
	}

	/**
	 * The colors for one accent: the accent, the text on it, and the tint.
	 *
	 * @param string $accent Hex color, or empty.
	 * @return array{color: string, on: string, tint: string}|null Null for no color.
	 */
	public static function color_set( $accent ) {
		$accent = sanitize_hex_color( (string) $accent );
		if ( ! $accent ) {
			return null;
		}
		return array(
			'color' => $accent,
			'on'    => Contrast::on( $accent ),
			'tint'  => Tone::tint( $accent ),
		);
	}

	/**
	 * The color set the picked values point to. The same rule as the
	 * script: keep the variations that fit every picked value (an empty
	 * value in a variation means any), and use their color when they all
	 * share one.
	 *
	 * @param array $variations Variations with `attributes` and their color set.
	 * @param array $picked     Picked values by `attribute_*` name; empty means not picked.
	 * @return array{color: string, on: string, tint: string}|null Null when nothing is picked or the colors differ.
	 */
	public static function match( $variations, $picked ) {
		$picked = array_filter( array_map( 'strval', $picked ), 'strlen' );
		if ( empty( $picked ) ) {
			return null;
		}

		$colors = array();
		foreach ( $variations as $variation ) {
			foreach ( $picked as $name => $value ) {
				$own = isset( $variation['attributes'][ $name ] ) ? (string) $variation['attributes'][ $name ] : '';
				if ( '' !== $own && $own !== $value ) {
					continue 2;
				}
			}
			$colors[ $variation['color'] ] = array(
				'color' => $variation['color'],
				'on'    => $variation['on'],
				'tint'  => $variation['tint'],
			);
		}

		return 1 === count( $colors ) ? reset( $colors ) : null;
	}

	/**
	 * The data for the page being shown, worked out once.
	 *
	 * @return array|null
	 */
	private function current() {
		if ( false === $this->data ) {
			$this->data = is_product() ? self::for_product( wc_get_product( get_queried_object_id() ), self::options() ) : null;
		}
		return $this->data;
	}

	/**
	 * Loads the script and styles, prints the data for the script, and puts
	 * the default colors on `body`.
	 *
	 * @return void
	 */
	public function enqueue() {
		$data = $this->current();
		if ( null === $data ) {
			return;
		}

		$asset = include PURPLISH_DIR . 'build/frontend.asset.php';
		wp_enqueue_script( self::HANDLE, PURPLISH_URL . 'build/frontend.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		wp_add_inline_script( self::HANDLE, 'window.purplishData = ' . wp_json_encode( $data ) . ';', 'before' );

		wp_enqueue_style( self::HANDLE, PURPLISH_URL . 'build/style-frontend.css', array(), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
		if ( null !== $data['default'] ) {
			wp_add_inline_style(
				self::HANDLE,
				sprintf(
					'body{--purplish-accent:%1$s;--purplish-on:%2$s;--purplish-tint:%3$s}',
					sanitize_hex_color( $data['default']['color'] ),
					sanitize_hex_color( $data['default']['on'] ),
					sanitize_hex_color( $data['default']['tint'] )
				)
			);
		}
	}

	/**
	 * Adds the part and transition classes, and `purplish-active` when the
	 * page starts with a color.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		$data = $this->current();
		if ( null === $data ) {
			return $classes;
		}

		$classes[] = 'purplish';
		if ( null !== $data['default'] ) {
			$classes[] = 'purplish-active';
		}
		foreach ( $data['options']['parts'] as $part ) {
			$classes[] = 'purplish-part-' . sanitize_html_class( $part );
		}
		$classes[] = 'purplish-transition-' . sanitize_html_class( $data['options']['transition'] );

		return $classes;
	}
}
