<?php
/**
 * Block styles for store blocks, in the theme's colors.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Purple ships block styles for buttons, post terms and sections, but none
 * for store blocks. These add one-click looks to product cards, the product
 * image, the product button, the sale badge and the Mini-Cart drawer.
 *
 * Every color is one of Purple's palette slugs, so the styles follow the
 * color variation a store picks, dark ones included. On a theme without
 * those slugs, ThemeColors points each slug at that theme's own text,
 * background and accent colors. Corners and rules read the tokens
 * PurpleLook fills in (square on Purple), with Purplish's usual fallbacks.
 */
final class BlockStyles {

	/**
	 * Purple's palette, by slug.
	 */
	const BASE     = 'var(--wp--preset--color--theme-1)';
	const CONTRAST = 'var(--wp--preset--color--theme-2)';
	const BASE_ALT = 'var(--wp--preset--color--theme-5)';
	const BORDER   = 'var(--wp--preset--color--theme-6)';

	/**
	 * Card padding and corner, from the theme where it has them.
	 */
	const PAD    = 'var(--wp--preset--spacing--20, 1rem)';
	const RADIUS = 'var(--wp--custom--purplish--radius, 12px)';

	/**
	 * Purple's Contrast button hover: 5% of the text color mixed in.
	 */
	const CONTRAST_HOVER = 'color-mix(in srgb, var(--wp--preset--color--theme-1) 95%, var(--wp--preset--color--theme-2))';

	/**
	 * Each card in a Product Collection is a list item the Product Template
	 * prints, which theme.json properties can't reach, so the card looks
	 * style it through the variation's own CSS.
	 */
	const CARD = '& > .wc-block-product';

	/**
	 * Adds the hook.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_styles' ) );
	}

	/**
	 * Registers every style.
	 *
	 * @return void
	 */
	public function register_styles() {
		if ( ! function_exists( 'register_block_style' ) ) {
			return;
		}
		foreach ( self::styles() as $style ) {
			register_block_style(
				$style['block'],
				array(
					'name'       => $style['name'],
					'label'      => $style['label'],
					'style_data' => $style['style_data'],
				)
			);
		}
	}

	/**
	 * The styles, as block name, style name, label and theme.json styles.
	 *
	 * @return array<int, array{block: string, name: string, label: string, style_data: array}>
	 */
	public static function styles() {
		return array(
			array(
				'block'      => 'woocommerce/product-template',
				'name'       => 'purplish-panel',
				'label'      => __( 'Panel', 'purplish' ),
				'style_data' => self::on_base_alt(
					array(),
					self::CARD . '{background-color:' . self::BASE_ALT . ';padding:' . self::PAD . ';border-radius:' . self::RADIUS . '}'
				),
			),
			array(
				'block'      => 'woocommerce/product-template',
				'name'       => 'purplish-outline',
				'label'      => __( 'Outline', 'purplish' ),
				'style_data' => array(
					'css' => self::CARD . '{border:1px solid var(--wp--custom--purplish--rule, ' . self::BORDER . ');padding:' . self::PAD . ';border-radius:' . self::RADIUS . ';transition:border-color .2s ease-out}'
						. self::CARD . ':hover{border-color:currentColor}'
						. self::CARD . ':focus-within{border-color:currentColor}',
				),
			),
			array(
				// Purple's Section Style 1. Styles 2 and 3 need per-palette
				// text overrides on dark variations; Style 1 reads on all.
				'block'      => 'woocommerce/product-template',
				'name'       => 'purplish-section',
				'label'      => __( 'Section', 'purplish' ),
				'style_data' => array(
					'color'    => array( 'text' => self::BASE_ALT ),
					'elements' => array(
						'link'    => array( 'color' => array( 'text' => self::BASE_ALT ) ),
						'heading' => array( 'color' => array( 'text' => self::BASE_ALT ) ),
						'button'  => array(
							'color'  => array(
								'background' => self::BASE,
								'text'       => self::CONTRAST,
							),
							':hover' => array( 'color' => array( 'background' => self::CONTRAST_HOVER ) ),
						),
					),
					'blocks'   => array(
						'woocommerce/product-price' => array( 'color' => array( 'text' => 'inherit' ) ),
					),
					'css'      => self::CARD . '{background-color:' . self::CONTRAST . ';padding:' . self::PAD . ';border-radius:' . self::RADIUS . '}',
				),
			),
			array(
				'block'      => 'woocommerce/product-image',
				'name'       => 'purplish-plate',
				'label'      => __( 'Plate', 'purplish' ),
				'style_data' => array(
					'css' => 'background-color:' . self::BASE_ALT . ';padding:' . self::PAD . ';border-radius:' . self::RADIUS,
				),
			),
			array(
				// Purple's own Contrast button, with a hairline edge drawn
				// inside so it still reads on a white card.
				'block'      => 'woocommerce/product-button',
				'name'       => 'purplish-contrast',
				'label'      => __( 'Contrast', 'purplish' ),
				'style_data' => array(
					'color'  => array(
						'background' => self::BASE,
						'text'       => self::CONTRAST,
					),
					'shadow' => 'inset 0 0 0 1px ' . self::CONTRAST,
					'css'    => '&:hover{background-color:' . self::CONTRAST_HOVER . '}',
				),
			),
			array(
				'block'      => 'woocommerce/product-sale-badge',
				'name'       => 'purplish-contrast',
				'label'      => __( 'Contrast', 'purplish' ),
				'style_data' => array(
					'color'  => array(
						'background' => self::BASE,
						'text'       => self::CONTRAST,
					),
					'border' => array(
						'color' => self::CONTRAST,
						'style' => 'solid',
						'width' => '1px',
					),
				),
			),
			array(
				// WooCommerce's Mini-Cart script copies the page background
				// onto the drawer as an inline style when it opens, so the
				// drawer's fill has to outrank it. The drawer's inner blocks
				// inherit it from there.
				'block'      => 'woocommerce/mini-cart-contents',
				'name'       => 'purplish-panel',
				'label'      => __( 'Panel', 'purplish' ),
				'style_data' => self::on_base_alt( array(), 'background-color:' . self::BASE_ALT . ' !important' ),
			),
		);
	}

	/**
	 * Styles for a surface on Base Alt. Some palettes set body text to
	 * Contrast Alt, which falls under 4.5:1 on Base Alt (Electric Kiwi), so
	 * text there takes Contrast, as Purple's own badge on Base Alt does.
	 *
	 * @param array  $color Extra color values for the block itself.
	 * @param string $css   The variation's own CSS.
	 * @return array
	 */
	private static function on_base_alt( $color, $css = '' ) {
		$styles = array(
			'color'  => array_merge( array( 'text' => self::CONTRAST ), $color ),
			'blocks' => array(
				'woocommerce/product-price' => array( 'color' => array( 'text' => 'inherit' ) ),
			),
		);
		if ( '' !== $css ) {
			$styles['css'] = $css;
		}
		return $styles;
	}
}
