<?php
/**
 * Prints extra markup inside a rendered block.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * The HTML API can change attributes but not add elements, so this reads
 * where a tag ends from its bookmark, as core's own directive processor
 * does.
 */
final class Markup extends \WP_HTML_Tag_Processor {

	/**
	 * Puts markup right after the opening tag of the first element with a
	 * class, as its first child.
	 *
	 * @param string $html       Rendered block.
	 * @param string $class_name Class of the element to print into.
	 * @param string $markup     Markup to print. Trusted, never user input.
	 * @return string The block, unchanged when no element has the class.
	 */
	public static function prepend_inside( $html, $class_name, $markup ) {
		$tags = new self( $html );
		if ( ! $tags->next_tag( array( 'class_name' => $class_name ) ) || ! $tags->set_bookmark( 'purplish' ) ) {
			return $html;
		}

		$end = $tags->bookmarks['purplish']->start + $tags->bookmarks['purplish']->length;
		return substr( $html, 0, $end ) . $markup . substr( $html, $end );
	}
}
