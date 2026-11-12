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

	/**
	 * The filters that get a label button in Layout: Bar.
	 */
	const FILTERS = array( 'taxonomy', 'attribute', 'price', 'rating', 'status' );

	/**
	 * Tags with no closing tag.
	 */
	const VOID = array( 'AREA', 'BASE', 'BR', 'COL', 'EMBED', 'HR', 'IMG', 'INPUT', 'LINK', 'META', 'SOURCE', 'TRACK', 'WBR' );

	/**
	 * Turns the heading of each plain filter into a button that opens the
	 * rest of the filter, for Layout: Bar. A plain filter is one
	 * WooCommerce inserted as is: a heading, then its list. A filter in a
	 * core Accordion already has a button, so it has no heading of its own
	 * and stays as it is.
	 *
	 * @param string $html Rendered Product Filters block.
	 * @return string The block, unchanged when it has no plain filter or the markup doesn't close.
	 */
	public static function filter_toggles( $html ) {
		$tags    = new self( $html );
		$inserts = array();
		$depth   = 0;
		$filter  = null;
		$pattern = '#^woocommerce/product-filter-(' . implode( '|', self::FILTERS ) . ')$#';

		while ( $tags->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$name = $tags->get_tag();

			if ( $tags->is_tag_closer() ) {
				--$depth;
				if ( $filter && isset( $filter['heading'] ) && ! isset( $filter['panel'] ) && $depth === $filter['depth'] + 1 && $name === $filter['heading'] ) {
					$tags->set_bookmark( 'purplish' );
					$at                = $tags->bookmarks['purplish']->start;
					$inserts[ $at ][]  = '</button>';
					$after             = $at + $tags->bookmarks['purplish']->length;
					$inserts[ $after ] = array( '<div class="purplish-filters-bar-panel" id="' . esc_attr( $filter['id'] ) . '" hidden>' );
					$filter['panel']   = true;
				} elseif ( $filter && $depth === $filter['depth'] ) {
					if ( isset( $filter['panel'] ) ) {
						$tags->set_bookmark( 'purplish' );
						$inserts[ $tags->bookmarks['purplish']->start ][] = '</div>';
					}
					$filter = null;
				}
				continue;
			}

			if ( ! $filter && preg_match( $pattern, (string) $tags->get_attribute( 'data-block-name' ) ) ) {
				$filter = array(
					'depth' => $depth,
					'id'    => wp_unique_id( 'purplish-filter-' ),
				);
			} elseif ( $filter && ! isset( $filter['heading'] ) && $depth === $filter['depth'] + 1 && preg_match( '/^H[1-6]$/', $name ) ) {
				$tags->set_bookmark( 'purplish' );
				$end               = $tags->bookmarks['purplish']->start + $tags->bookmarks['purplish']->length;
				$inserts[ $end ]   = array( '<button type="button" class="purplish-filters-bar-toggle" aria-expanded="false" aria-controls="' . esc_attr( $filter['id'] ) . '">' );
				$filter['heading'] = $name;
			} elseif ( $filter && $depth === $filter['depth'] + 1 && ! isset( $filter['heading'] ) ) {
				// The first child isn't a heading, as in an Accordion.
				$filter['heading'] = '';
			}

			if ( ! in_array( $name, self::VOID, true ) && ! $tags->has_self_closing_flag() ) {
				++$depth;
			}
		}

		if ( 0 !== $depth || $filter ) {
			return $html;
		}

		krsort( $inserts );
		foreach ( $inserts as $at => $markup ) {
			$html = substr( $html, 0, $at ) . implode( '', $markup ) . substr( $html, $at );
		}
		return $html;
	}
}
