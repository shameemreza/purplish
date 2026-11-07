<?php
/**
 * Markup helpers shared by Purplish blocks.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

use WP_HTML_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * Small changes to rendered inner blocks that block supports can't make.
 *
 * @since 1.2.0
 */
final class Markup {

	/**
	 * Marks each top-level element in rendered inner blocks as a list item,
	 * so a wrapper with `role="list"` reads as a list with its item count.
	 *
	 * @param string $html Rendered inner blocks.
	 * @return string|null The marked markup, or null when there is nothing
	 *                     to mark or the HTML can't be parsed safely; the
	 *                     caller then leaves the list role out.
	 */
	public static function list_items( $html ) {
		$processor = WP_HTML_Processor::create_fragment( $html );
		if ( null === $processor ) {
			return null;
		}

		$top   = null;
		$items = 0;
		while ( $processor->next_tag() ) {
			$depth = $processor->get_current_depth();
			if ( null === $top ) {
				$top = $depth;
			}
			if ( $depth === $top ) {
				$processor->set_attribute( 'role', 'listitem' );
				++$items;
			}
		}

		if ( null !== $processor->get_last_error() || 0 === $items ) {
			return null;
		}
		return $processor->get_updated_html();
	}
}
