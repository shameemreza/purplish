<?php
/**
 * Purplish settings for classic themes, on WooCommerce's Products tab.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\Frontend\AccountForms;
use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\NeutralPanels;
use Purplish\Frontend\PurpleParts;
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
		return self::fields( wp_is_block_theme(), PurpleParts::is_purple() );
	}

	/**
	 * The fields, or the pointer to the block panel on block themes. Purple
	 * also gets the switches for its Add to Cart parts.
	 *
	 * @param bool $block_theme Whether the active theme is a block theme.
	 * @param bool $purple      Whether the active theme is Purple.
	 * @return array
	 */
	public static function fields( $block_theme, $purple = false ) {
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
			$fields = array_merge( array( $title, self::panels_field(), $end ), self::account_fields() );
			return $purple ? array_merge( $fields, self::purple_fields() ) : $fields;
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
		$fields[] = self::panels_field();

		$gallery = array(
			'gallery_nothumbs' => __( 'Hide thumbnails (one big photo)', 'purplish' ),
			'gallery_rounded'  => __( 'Rounded corners', 'purplish' ),
		);
		$first   = true;
		foreach ( $gallery as $key => $label ) {
			$fields[] = array(
				'id'            => self::OPTION . '[' . $key . ']',
				'type'          => 'checkbox',
				'title'         => $first ? __( 'Product gallery', 'purplish' ) : '',
				'desc'          => $label,
				'default'       => 'no',
				'checkboxgroup' => $first ? 'start' : 'end',
			);
			$first    = false;
		}

		// A tilt saved as a checkbox before the select existed shows as the tilt.
		$hover    = BlockEffects::classic_hover( get_option( self::OPTION, array() ) );
		$fields[] = array(
			'id'       => self::OPTION . '[gallery_hover]',
			'type'     => 'select',
			'title'    => __( 'Gallery hover style', 'purplish' ),
			'desc'     => __( 'Plays when the mouse is over the photo, and replaces WooCommerce\'s hover zoom. Touch screens and visitors who ask for reduced motion see the plain photo.', 'purplish' ),
			'desc_tip' => true,
			'default'  => '' === $hover ? 'none' : $hover,
			'options'  => array(
				'none'     => __( 'None', 'purplish' ),
				'tilt'     => __( '3D tilt', 'purplish' ),
				'zoom'     => __( 'Zoom', 'purplish' ),
				'lift'     => __( 'Lift', 'purplish' ),
				'parallax' => __( 'Parallax', 'purplish' ),
				'glare'    => __( 'Glare', 'purplish' ),
				'loupe'    => __( 'Loupe', 'purplish' ),
				'slowzoom' => __( 'Slow zoom', 'purplish' ),
			),
		);

		$fields[] = array(
			'id'       => self::OPTION . '[transition]',
			'type'     => 'select',
			'title'    => __( 'Photo transition', 'purplish' ),
			'desc'     => __( 'Plays when the photo changes with the color. Visitors who ask for reduced motion see no effect.', 'purplish' ),
			'desc_tip' => true,
			'default'  => Options::defaults()['transition'],
			'options'  => array(
				'none'   => __( 'None', 'purplish' ),
				'fade'   => __( 'Fade', 'purplish' ),
				'blur'   => __( 'Blur', 'purplish' ),
				'zoom'   => __( 'Zoom', 'purplish' ),
				'slide'  => __( 'Slide', 'purplish' ),
				'flip'   => __( 'Flip', 'purplish' ),
				'wipe'   => __( 'Wipe', 'purplish' ),
				'circle' => __( 'Circle reveal', 'purplish' ),
				'wash'   => __( 'Color wash', 'purplish' ),
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

		return array_merge( $fields, self::account_fields() );
	}

	/**
	 * The login and register options, on every theme: they apply to My
	 * Account on a page where no block sets them, such as a classic theme
	 * or a page made in the classic editor.
	 *
	 * @since 1.4.2
	 * @return array
	 */
	public static function account_fields() {
		$options = AccountForms::config()['options'];
		$labels  = array(
			'fields' => array(
				'title'   => __( 'Fields', 'purplish' ),
				'default' => __( 'Theme default', 'purplish' ),
				'values'  => array(
					'soft'      => __( 'Soft', 'purplish' ),
					'filled'    => __( 'Filled', 'purplish' ),
					'underline' => __( 'Underline', 'purplish' ),
					'draw'      => __( 'Draw', 'purplish' ),
					'fill'      => __( 'Fill', 'purplish' ),
					'corners'   => __( 'Corners', 'purplish' ),
				),
			),
			'focus'  => array(
				'title' => __( 'Focus ring', 'purplish' ),
				'desc'  => __( 'A ring in your theme\'s accent color around the field, box or link that has keyboard focus', 'purplish' ),
			),
			'labels' => array(
				'title'   => __( 'Labels', 'purplish' ),
				'default' => __( 'Above', 'purplish' ),
				'values'  => array(
					'inside'   => __( 'Inside', 'purplish' ),
					'floating' => __( 'Floating', 'purplish' ),
					'flip'     => __( 'Flip', 'purplish' ),
					'wave'     => __( 'Wave', 'purplish' ),
				),
			),
			'switch' => array(
				'title'   => __( 'Log in and register', 'purplish' ),
				'default' => __( 'Side by side', 'purplish' ),
				'values'  => array(
					'tabs'  => __( 'Tabs', 'purplish' ),
					'flip'  => __( 'Flip', 'purplish' ),
					'slide' => __( 'Slide', 'purplish' ),
				),
			),
			'tabs'   => array(
				'title'   => __( 'Tab look', 'purplish' ),
				'default' => __( 'Pill', 'purplish' ),
				'desc'    => __( 'With Tabs or Slide.', 'purplish' ),
				'values'  => array(
					'line' => __( 'Line', 'purplish' ),
				),
			),
			'slide'  => array(
				'title'   => __( 'Slide motion', 'purplish' ),
				'default' => __( 'Push', 'purplish' ),
				'desc'    => __( 'With Slide.', 'purplish' ),
				'values'  => array(
					'stretch' => __( 'Stretch', 'purplish' ),
					'flood'   => __( 'Flood', 'purplish' ),
					'blinds'  => __( 'Blinds', 'purplish' ),
					'page'    => __( 'Page', 'purplish' ),
					'shear'   => __( 'Shear', 'purplish' ),
					'shutter' => __( 'Shutter', 'purplish' ),
					'liquid'  => __( 'Liquid', 'purplish' ),
					'depth'   => __( 'Depth', 'purplish' ),
					'elastic' => __( 'Elastic', 'purplish' ),
					'mosaic'  => __( 'Mosaic', 'purplish' ),
					'fold'    => __( 'Fold', 'purplish' ),
					'iris'    => __( 'Iris', 'purplish' ),
				),
			),
			'button' => array(
				'title'   => __( 'Button', 'purplish' ),
				'default' => __( 'Theme button', 'purplish' ),
				'values'  => array(
					'bloom'    => __( 'Bloom', 'purplish' ),
					'ripple'   => __( 'Ripple', 'purplish' ),
					'sweep'    => __( 'Sweep', 'purplish' ),
					'spectrum' => __( 'Spectrum', 'purplish' ),
					'trace'    => __( 'Trace', 'purplish' ),
					'roll'     => __( 'Roll', 'purplish' ),
					'split'    => __( 'Split', 'purplish' ),
					'glass'    => __( 'Glass', 'purplish' ),
				),
			),
		);

		$fields = array(
			array(
				'id'    => 'purplish_account',
				'type'  => 'title',
				'title' => __( 'My Account login and register', 'purplish' ),
				'desc'  => __( 'How the log in and register forms look to visitors who aren\'t signed in. These apply where the page sets nothing itself. On a block page, select the Account Group or the Shortcode block that holds My Account and use its Login and register panel instead.', 'purplish' ),
			),
		);
		foreach ( $options as $option => $allowed ) {
			$label = $labels[ $option ];
			if ( true === $allowed ) {
				$fields[] = array(
					'id'      => self::OPTION . '[account_' . $option . ']',
					'type'    => 'checkbox',
					'title'   => $label['title'],
					'desc'    => $label['desc'],
					'default' => 'no',
				);
				continue;
			}
			$fields[] = array(
				'id'      => self::OPTION . '[account_' . $option . ']',
				'type'    => 'select',
				'title'   => $label['title'],
				'desc'    => isset( $label['desc'] ) ? $label['desc'] : '',
				'default' => '',
				'options' => array_merge( array( '' => $label['default'] ), array_intersect_key( $label['values'], array_flip( $allowed ) ) ),
			);
		}
		$fields[] = array(
			'id'   => 'purplish_account',
			'type' => 'sectionend',
		);
		return $fields;
	}

	/**
	 * The switch for panel tints, on every theme: soft panels show on
	 * pages without a page color too, like the cart.
	 *
	 * @since 1.4.1
	 * @return array
	 */
	public static function panels_field() {
		return array(
			'id'       => self::OPTION . '[' . NeutralPanels::KEY . ']',
			'type'     => 'checkbox',
			'title'    => __( 'Panels', 'purplish' ),
			'desc'     => __( 'Tint panels with the page color', 'purplish' ),
			'desc_tip' => __( 'Soft panels, like accordions and the cart drawer, take a light mix of the page color. Turn this off to keep them on a neutral fill from your theme\'s text color.', 'purplish' ),
			'default'  => 'yes',
		);
	}

	/**
	 * The switches for Purple's Add to Cart parts.
	 *
	 * @return array
	 */
	public static function purple_fields() {
		return array(
			array(
				'id'    => 'purplish_purple_parts',
				'type'  => 'title',
				'title' => __( 'Purple\'s Add to Cart parts', 'purplish' ),
				'desc'  => __( 'Purple has an Add to Cart part for each product type. What you turn on here goes at the end of the simple, variable and grouped parts, under the buy button. To move it, change its settings or take it out of one part, open that part in the Site Editor.', 'purplish' ),
			),
			array(
				'id'            => self::OPTION . '[purple_delivery]',
				'type'          => 'checkbox',
				'title'         => __( 'Add under the button', 'purplish' ),
				'desc'          => __( 'Delivery Line: the delivery date, returns and pickup. Set your dates in Shipping > Delivery dates first.', 'purplish' ),
				'default'       => 'no',
				'checkboxgroup' => 'start',
			),
			array(
				'id'            => self::OPTION . '[purple_extras]',
				'type'          => 'checkbox',
				'desc'          => __( 'Matching Extras: other products to tick, added to the cart on the same click.', 'purplish' ),
				'default'       => 'no',
				'checkboxgroup' => 'end',
			),
			array(
				'id'   => 'purplish_purple_parts',
				'type' => 'sectionend',
			),
		);
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
