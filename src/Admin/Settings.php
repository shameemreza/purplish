<?php
/**
 * Purplish settings for classic themes, on WooCommerce's Products tab.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a Purplish section to WooCommerce > Settings > Products. WooCommerce's
 * settings API saves it and checks the nonce and capability. Block themes
 * get a pointer to the block panel instead of the fields.
 */
final class Settings {

	const SECTION = 'purplish';
	const OPTION  = 'purplish_settings';

	/**
	 * Adds the hooks. The options filter runs on the front end too.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'settings' ), 10, 2 );
		add_filter( 'purplish_page_options', array( $this, 'page_options' ) );
	}

	/**
	 * Adds the Purplish section to the Products tab.
	 *
	 * @param array<string, string> $sections Sections.
	 * @return array<string, string>
	 */
	public function add_section( $sections ) {
		$sections[ self::SECTION ] = __( 'Purplish', 'purplish' );
		return $sections;
	}

	/**
	 * The fields of the Purplish section.
	 *
	 * @param array  $settings        Settings of the current section.
	 * @param string $current_section Section ID.
	 * @return array
	 */
	public function settings( $settings, $current_section ) {
		if ( self::SECTION !== $current_section ) {
			return $settings;
		}
		if ( wp_is_block_theme() ) {
			// Nothing to save on block themes, so WooCommerce's own flag hides the button.
			$GLOBALS['hide_save_button'] = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce reads this flag.
		}
		return self::fields( wp_is_block_theme() );
	}

	/**
	 * The fields, or the pointer to the block panel on block themes.
	 *
	 * @param bool $block_theme Whether the active theme is a block theme.
	 * @return array
	 */
	public static function fields( $block_theme ) {
		$title = array(
			'id'    => 'purplish_options',
			'type'  => 'title',
			'title' => __( 'Purplish', 'purplish' ),
		);
		$end   = array(
			'id'   => 'purplish_options',
			'type' => 'sectionend',
		);

		if ( $block_theme ) {
			$title['desc'] = sprintf(
				/* translators: %s: link to the Single Product template in the Site Editor. */
				__( 'Your theme uses blocks, so these settings live on the Add to Cart + Options block. Open the %s, select the block, and find the Purplish panel. Each template keeps its own settings.', 'purplish' ),
				'<a href="' . esc_url( admin_url( 'site-editor.php?p=%2Fwp_template%2F' . rawurlencode( get_stylesheet() ) . '%2F%2Fsingle-product&canvas=edit' ) ) . '">' . esc_html__( 'Single Product template', 'purplish' ) . '</a>'
			);
			return array( $title, $end );
		}

		$title['desc'] = __( 'When a shopper picks a color on a variable product, these parts of the product page take that color.', 'purplish' );

		$labels = array(
			Options::PART_BUTTON => __( 'Add to cart button', 'purplish' ),
			Options::PART_BADGE  => __( 'Sale badge', 'purplish' ),
			Options::PART_PRICE  => __( 'Sale price', 'purplish' ),
			Options::PART_TINT   => __( 'Page background tint', 'purplish' ),
		);

		$fields = array( $title );
		$first  = true;
		foreach ( $labels as $part => $label ) {
			$fields[] = array(
				'id'            => self::OPTION . '[' . $part . ']',
				'type'          => 'checkbox',
				'title'         => $first ? __( 'Parts that take the color', 'purplish' ) : '',
				'desc'          => $label,
				'default'       => Options::part_defaults()[ $part ] ? 'yes' : 'no',
				'checkboxgroup' => $first ? 'start' : ( Options::PART_TINT === $part ? 'end' : '' ),
			);
			$first    = false;
		}

		$gallery = array(
			'gallery_nothumbs' => __( 'Hide thumbnails (one big photo)', 'purplish' ),
			'gallery_rounded'  => __( 'Rounded corners', 'purplish' ),
			'gallery_tilt'     => __( '3D tilt on hover (replaces WooCommerce\'s hover zoom)', 'purplish' ),
		);
		$first   = true;
		foreach ( $gallery as $key => $label ) {
			$fields[] = array(
				'id'            => self::OPTION . '[' . $key . ']',
				'type'          => 'checkbox',
				'title'         => $first ? __( 'Product gallery', 'purplish' ) : '',
				'desc'          => $label,
				'default'       => 'no',
				'checkboxgroup' => $first ? 'start' : ( 'gallery_tilt' === $key ? 'end' : '' ),
			);
			$first    = false;
		}

		$fields[] = array(
			'id'       => self::OPTION . '[transition]',
			'type'     => 'select',
			'title'    => __( 'Photo transition', 'purplish' ),
			'desc'     => __( 'Plays when the photo changes with the color. Visitors who ask for reduced motion see no effect.', 'purplish' ),
			'desc_tip' => true,
			'default'  => Options::defaults()['transition'],
			'options'  => array(
				'none' => __( 'None', 'purplish' ),
				'fade' => __( 'Fade', 'purplish' ),
				'blur' => __( 'Blur', 'purplish' ),
			),
		);
		$fields[] = array(
			'id'      => self::OPTION . '[rise]',
			'type'    => 'checkbox',
			'title'   => __( 'Title and price', 'purplish' ),
			'desc'    => __( 'Rise in after the photo on each color change', 'purplish' ),
			'default' => 'no',
		);
		$fields[] = $end;

		return $fields;
	}

	/**
	 * The page options from the saved settings, on classic themes. Block
	 * themes use the block panel, so the saved settings don't apply there.
	 *
	 * @param array $options Options so far.
	 * @return array
	 */
	public function page_options( $options ) {
		if ( wp_is_block_theme() ) {
			return $options;
		}
		return self::options_from( get_option( self::OPTION, array() ) );
	}

	/**
	 * Page options from the saved settings array: `yes` per part, and the
	 * transition. Parts never saved take their defaults.
	 *
	 * @param mixed $saved The saved option.
	 * @return array{parts: string[], transition: string}
	 */
	public static function options_from( $saved ) {
		$saved = is_array( $saved ) ? $saved : array();

		$parts = array();
		foreach ( Options::part_defaults() as $part => $default ) {
			// Classic forms use a select, so there is no chip to color.
			if ( Options::PART_CHIP === $part ) {
				continue;
			}
			$on = isset( $saved[ $part ] ) ? 'yes' === $saved[ $part ] : $default;
			if ( $on ) {
				$parts[] = $part;
			}
		}

		return Options::clean(
			array(
				'parts'      => $parts,
				'transition' => isset( $saved['transition'] ) ? $saved['transition'] : Options::defaults()['transition'],
				'rise'       => isset( $saved['rise'] ) && 'yes' === $saved['rise'],
			)
		);
	}
}
