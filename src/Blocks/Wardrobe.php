<?php
/**
 * The Wardrobe block, a menu item with a big panel.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Prints a Navigation menu item: a link and a button with the same label,
 * and the panel of inner blocks after them. The link is what phones, the
 * Navigation overlay and pages without scripts get; the view script swaps
 * in the button, which opens the panel.
 *
 * @since 1.3.0
 */
final class Wardrobe implements Block {

	const NAME = 'purplish/wardrobe';

	/**
	 * Each option's values, the default first.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONS = array(
		'width' => array( 'wide', 'content', 'full' ),
		'look'  => array( 'card', 'flat' ),
	);

	/**
	 * The block's folder name.
	 *
	 * @return string
	 */
	public function slug() {
		return 'wardrobe';
	}

	/**
	 * Adds the hooks that let the Navigation block hold Wardrobe. They have
	 * to be in place before init, when WordPress registers Navigation.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'block_type_metadata', array( self::class, 'allow_in_navigation' ) );
	}

	/**
	 * Adds Wardrobe to the Navigation block's child blocks, unless the
	 * `purplish_blocks` filter turned it off.
	 *
	 * @param array $metadata Block metadata from block.json.
	 * @return array
	 */
	public static function allow_in_navigation( $metadata ) {
		if (
			isset( $metadata['name'], $metadata['allowedBlocks'] )
			&& 'core/navigation' === $metadata['name']
			&& is_array( $metadata['allowedBlocks'] )
			&& ! in_array( self::NAME, $metadata['allowedBlocks'], true )
			&& in_array( self::class, Registry::listed(), true )
		) {
			$metadata['allowedBlocks'][] = self::NAME;
		}
		return $metadata;
	}

	/**
	 * Extra block settings, in the editor only: the starting layout, which
	 * is also the inserter preview. Links point at the shop page and the
	 * photos are Purplish's drawn figures.
	 *
	 * @return array
	 */
	public function settings() {
		if ( ! is_admin() ) {
			return array();
		}

		$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$links = array();
		foreach ( array( __( 'New in', 'purplish' ), __( 'Coats', 'purplish' ), __( 'Jackets', 'purplish' ), __( 'Knitwear', 'purplish' ), __( 'Denim', 'purplish' ) ) as $name ) {
			$links[] = self::block( 'core/list-item', array( 'content' => sprintf( '<a href="%s">%s</a>', esc_url( $shop ), esc_html( $name ) ) ) );
		}

		$tiles = array();
		foreach (
			array(
				'long-coat'   => __( 'Coats', 'purplish' ),
				'field-parka' => __( 'Jackets', 'purplish' ),
				'denim-set'   => __( 'Denim', 'purplish' ),
			) as $figure => $name
		) {
			$tiles[] = self::block(
				'core/image',
				array(
					'url'             => Showfloor::figure( $figure ),
					'alt'             => '',
					'caption'         => esc_html( $name ),
					'href'            => $shop,
					'linkDestination' => 'custom',
					'aspectRatio'     => '3/4',
					'scale'           => 'contain',
					'className'       => 'purplish-wardrobe-tile',
				)
			);
		}

		$columns = array(
			self::block(
				'core/column',
				array( 'width' => '20%' ),
				array(
					self::block( 'core/list', array( 'className' => 'purplish-wardrobe-links' ), $links ),
					self::block(
						'core/paragraph',
						array(
							'className' => 'purplish-wardrobe-all',
							'content'   => sprintf( '<a href="%s">%s</a>', esc_url( $shop ), esc_html__( 'All clothing', 'purplish' ) ),
						)
					),
				)
			),
			self::block(
				'core/column',
				array(),
				array(
					self::block(
						'core/group',
						array(
							'layout' => array(
								'type'        => 'grid',
								'columnCount' => 3,
							),
							'style'  => array( 'spacing' => array( 'blockGap' => '12px' ) ),
						),
						$tiles
					),
				)
			),
			self::block(
				'core/column',
				array( 'width' => '26%' ),
				array(
					self::block(
						'core/group',
						array(
							'className' => 'purplish-wardrobe-look',
							'layout'    => array(
								'type'           => 'flex',
								'orientation'    => 'vertical',
								'justifyContent' => 'stretch',
							),
							'style'     => array( 'spacing' => array( 'blockGap' => '0' ) ),
						),
						array(
							self::block(
								'core/image',
								array(
									'url'         => Showfloor::figure( 'violet-coat' ),
									'alt'         => '',
									'aspectRatio' => '3/4',
									'scale'       => 'contain',
								)
							),
							self::block(
								'core/paragraph',
								array(
									'className' => 'purplish-wardrobe-look-name',
									'content'   => esc_html__( 'New season', 'purplish' ),
								)
							),
							self::block( 'core/paragraph', array( 'content' => esc_html__( 'Long coats and soft layers.', 'purplish' ) ) ),
							self::block(
								'core/paragraph',
								array(
									'className' => 'purplish-wardrobe-look-go',
									'content'   => sprintf( '<a href="%s">%s</a>', esc_url( $shop ), esc_html__( 'Shop new in', 'purplish' ) ),
								)
							),
						)
					),
				)
			),
		);

		return array(
			'example' => array(
				'viewportWidth' => 1100,
				'attributes'    => array( 'label' => __( 'Shop', 'purplish' ) ),
				'innerBlocks'   => array( self::block( 'core/columns', array(), $columns ) ),
			),
		);
	}

	/**
	 * One block for the example.
	 *
	 * @param string  $name        Block name.
	 * @param array   $attributes  Attributes.
	 * @param array[] $inner_blocks Inner blocks.
	 * @return array
	 */
	private static function block( $name, $attributes = array(), $inner_blocks = array() ) {
		return array(
			'name'        => $name,
			'attributes'  => $attributes,
			'innerBlocks' => $inner_blocks,
		);
	}

	/**
	 * The option classes, with the default for anything missing or unknown.
	 *
	 * @param array $attributes Block attributes.
	 * @return string[] Class names.
	 */
	public static function classes( $attributes ) {
		$classes = array();
		foreach ( self::OPTIONS as $option => $values ) {
			$value     = isset( $attributes[ $option ] ) ? $attributes[ $option ] : '';
			$classes[] = 'purplish-wardrobe-' . $option . '-' . ( in_array( $value, $values, true ) ? $value : $values[0] );
		}
		return $classes;
	}

	/**
	 * How the panel opens, from the Navigation block's "Submenus open on"
	 * setting, including menus saved before WordPress 6.9 renamed it. A
	 * panel that's always open would cover the page, so "always" opens on
	 * click.
	 *
	 * @param array $context Block context from the Navigation block.
	 * @return string hover or click.
	 */
	public static function opens_on( $context ) {
		if ( isset( $context['openSubmenusOnClick'] ) && null !== $context['openSubmenusOnClick'] ) {
			return $context['openSubmenusOnClick'] ? 'click' : 'hover';
		}
		return isset( $context['submenuVisibility'] ) && in_array( $context['submenuVisibility'], array( 'click', 'always' ), true ) ? 'click' : 'hover';
	}

	/**
	 * The menu item. With no label it prints nothing, like a Navigation
	 * link; with an empty panel it's the link alone.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered inner blocks.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$label = isset( $attributes['label'] ) && is_string( $attributes['label'] ) ? trim( wp_kses( $attributes['label'], array() ) ) : '';
		if ( '' === $label ) {
			return '';
		}

		$url = isset( $attributes['url'] ) && is_string( $attributes['url'] ) ? trim( $attributes['url'] ) : '';
		if ( '' === $url ) {
			$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		}

		$link = sprintf(
			'<a class="wp-block-navigation-item__content purplish-wardrobe-link" href="%1$s"><span class="wp-block-navigation-item__label">%2$s</span></a>',
			esc_url( $url ),
			$label
		);

		$context = $block instanceof \WP_Block ? $block->context : array();
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'wp-block-navigation-item ' . implode( ' ', self::classes( $attributes ) ) ) );

		if ( '' === trim( $content ) ) {
			return sprintf( '<li %1$s>%2$s</li>', $wrapper, $link );
		}

		$id   = wp_unique_id( 'purplish-wardrobe-' );
		$icon = isset( $context['showSubmenuIcon'] ) && false === $context['showSubmenuIcon']
			? ''
			: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 9.5l6 6 6-6"/></svg>';

		return sprintf(
			'<li %1$s data-open="%2$s">%3$s<button type="button" class="wp-block-navigation-item__content purplish-wardrobe-toggle" aria-expanded="false" aria-controls="%4$s"><span class="wp-block-navigation-item__label">%5$s</span>%6$s</button><div class="purplish-wardrobe-panel" id="%4$s" hidden><div class="purplish-wardrobe-inner">%7$s</div></div></li>',
			$wrapper,
			esc_attr( self::opens_on( $context ) ),
			$link,
			esc_attr( $id ),
			$label,
			$icon,
			$content
		);
	}
}
