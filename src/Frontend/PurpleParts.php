<?php
/**
 * Purplish blocks in Purple's Add to Cart parts, through Block Hooks.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Purple keeps one Add to Cart part per product type. When the store turns
 * a feature on in the Purplish settings, its block goes at the end of the
 * simple, variable and grouped parts, under the part's own blocks. The
 * external part only links to another store, so it gets none.
 *
 * Core saves a hooked block into a part the store edits, and remembers one
 * the store removes. The hooked copy carries `purplishHooked`, so it prints
 * nothing once the switch is off again; a copy the store placed by hand
 * always prints.
 */
final class PurpleParts {

	/**
	 * The theme the parts come from.
	 */
	const THEME = 'purple';

	/**
	 * The parts that get the blocks.
	 */
	const PARTS = array(
		'simple-product-add-to-cart-with-options',
		'variable-product-add-to-cart-with-options',
		'grouped-product-add-to-cart-with-options',
	);

	/**
	 * Each switch's block, in the order they go into the part.
	 */
	const BLOCKS = array(
		'delivery' => 'purplish/delivery-line',
		'extras'   => 'purplish/matching-extras',
	);

	/**
	 * The attribute that marks a hooked copy.
	 */
	const MARK = 'purplishHooked';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'hooked_block_types', array( $this, 'hook' ), 10, 4 );
		foreach ( self::BLOCKS as $name ) {
			add_filter( 'hooked_block_' . $name, array( $this, 'mark' ), 10, 5 );
		}
	}

	/**
	 * Whether the active theme is Purple or a child of it.
	 *
	 * @return bool
	 */
	public static function is_purple() {
		return self::THEME === get_template();
	}

	/**
	 * Whether the store turned a feature on for Purple's parts.
	 *
	 * @param string $feature A key of BLOCKS.
	 * @return bool
	 */
	public static function on( $feature ) {
		$saved = get_option( Settings::OPTION, array() );
		return is_array( $saved ) && isset( $saved[ 'purple_' . $feature ] ) && 'yes' === $saved[ 'purple_' . $feature ];
	}

	/**
	 * Adds the turned-on blocks at the end of a Purple Add to Cart part.
	 * Core wraps a part in a template part block for this, so its last
	 * child is the end of the part, outside the part's own rows.
	 *
	 * @param string[]                          $types    Hooked block types so far.
	 * @param string                            $position Relative position.
	 * @param string                            $anchor   Anchor block type.
	 * @param \WP_Block_Template|\WP_Post|array $context Where the blocks are.
	 * @return string[]
	 */
	public function hook( $types, $position, $anchor, $context ) {
		if (
			'last_child' !== $position
			|| 'core/template-part' !== $anchor
			|| ! $context instanceof \WP_Block_Template
			|| 'wp_template_part' !== $context->type
			|| ! in_array( $context->slug, self::PARTS, true )
			|| ! self::is_purple()
		) {
			return $types;
		}

		$registry = \WP_Block_Type_Registry::get_instance();
		foreach ( self::BLOCKS as $feature => $name ) {
			// A part that already has the block keeps just that one.
			if ( self::on( $feature ) && $registry->is_registered( $name ) && ! has_block( $name, (string) $context->content ) ) {
				$types[] = $name;
			}
		}
		return $types;
	}

	/**
	 * Marks the copy this class hooks in.
	 *
	 * @param array|null                        $block    Parsed hooked block, or null when suppressed.
	 * @param string                            $type     Hooked block type.
	 * @param string                            $position Relative position.
	 * @param array                             $anchor   Parsed anchor block.
	 * @param \WP_Block_Template|\WP_Post|array $context Where the blocks are.
	 * @return array|null
	 */
	public function mark( $block, $type, $position, $anchor, $context ) {
		if ( ! is_array( $block ) || 'last_child' !== $position || ! $context instanceof \WP_Block_Template || ! in_array( $context->slug, self::PARTS, true ) ) {
			return $block;
		}
		$block['attrs']               = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$block['attrs'][ self::MARK ] = true;
		return $block;
	}

	/**
	 * Whether a hooked copy should print: its switch is still on and the
	 * page template doesn't hold the same block already.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $feature    A key of BLOCKS.
	 * @return bool True when the block prints as usual.
	 */
	public static function keeps( $attributes, $feature ) {
		if ( empty( $attributes[ self::MARK ] ) ) {
			return true;
		}
		if ( ! self::on( $feature ) || ! self::is_purple() ) {
			return false;
		}
		global $_wp_current_template_content;
		return ! ( is_string( $_wp_current_template_content ) && has_block( self::BLOCKS[ $feature ], $_wp_current_template_content ) );
	}
}
