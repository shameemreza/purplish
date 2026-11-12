<?php
/**
 * "Mark the current term" on Terms Query.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * On a category, tag or other term archive, marks the archive's own term in
 * a Terms Query list: its item gets the `current-term` class and its links
 * get `aria-current="page"`. Listed parents of that term get
 * `current-term-ancestor`, so a list of top categories still shows where
 * the visitor is. Core's Term Template marks neither.
 */
final class CurrentTerm {

	const BLOCK = 'core/terms-query';

	/**
	 * Hooks in.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'render_block_' . self::BLOCK, array( $this, 'mark' ), 10, 2 );
	}

	/**
	 * Whether saved options ask for the mark. Both looks
	 * always mark it, since they draw the current term.
	 *
	 * @param mixed $saved The block's saved Purplish options.
	 * @return bool
	 */
	public static function wants( $saved ) {
		$classes = is_array( $saved ) ? BlockEffects::classes_for( self::BLOCK, $saved ) : array();
		return in_array( 'purplish-terms-current', $classes, true ) || (bool) preg_grep( '/^purplish-terms-look-/', $classes );
	}

	/**
	 * Marks the current term and its listed parents.
	 *
	 * @param string $block_content The Terms Query HTML.
	 * @param array  $block         The parsed block.
	 * @return string
	 */
	public function mark( $block_content, $block ) {
		$saved = isset( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
		if ( ! self::wants( $saved ) ) {
			return $block_content;
		}

		if ( in_array( 'purplish-terms-look-photos', BlockEffects::classes_for( self::BLOCK, $saved ), true ) ) {
			$block_content = self::photo_links( $block_content );
		}

		$term = ( is_category() || is_tag() || is_tax() ) ? get_queried_object() : null;
		if ( ! $term instanceof \WP_Term ) {
			return $block_content;
		}

		return self::mark_term( $block_content, $term );
	}

	/**
	 * Marks a term in rendered Terms Query HTML.
	 *
	 * @param string   $html The Terms Query HTML.
	 * @param \WP_Term $term The term to mark.
	 * @return string
	 */
	public static function mark_term( $html, $term ) {
		$ancestors = is_taxonomy_hierarchical( $term->taxonomy ) ? array_map( 'intval', get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) : array();
		$link      = get_term_link( $term );
		$link      = is_string( $link ) ? self::normalize( $link ) : '';

		$tags = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag() ) {
			$tag = $tags->get_tag();
			if ( 'A' === $tag ) {
				$href = $tags->get_attribute( 'href' );
				if ( '' !== $link && is_string( $href ) && self::normalize( $href ) === $link ) {
					$tags->set_attribute( 'aria-current', 'page' );
				}
				continue;
			}
			if ( 'LI' !== $tag || ! $tags->has_class( 'wp-block-term' ) || ! $tags->has_class( 'taxonomy-' . $term->taxonomy ) ) {
				continue;
			}
			$id = self::term_id( $tags );
			if ( $id === $term->term_id ) {
				$tags->add_class( 'current-term' );
			} elseif ( in_array( $id, $ancestors, true ) ) {
				$tags->add_class( 'current-term-ancestor' );
			}
		}

		return $tags->get_updated_html();
	}

	/**
	 * Puts a link to each term inside its Featured Category, over the photo,
	 * so the round photo opens the term while the Featured Category still
	 * gets the pointer for its own hover looks. The term's name stays the
	 * link for keyboards and screen readers.
	 *
	 * @param string $html The Terms Query HTML.
	 * @return string
	 */
	public static function photo_links( $html ) {
		$link = '';
		return (string) preg_replace_callback(
			'/<li\b[^>]*>|<div\b[^>]*\bclass="(?:[^"]*\s)?wc-block-featured-category(?=[\s"])[^"]*"[^>]*>/',
			static function ( $found ) use ( &$link ) {
				if ( 0 === strpos( $found[0], '<li' ) ) {
					$link = '';
					if ( preg_match( '/\bclass="[^"]*\bwp-block-term\b[^"]*"/', $found[0] ) && preg_match( '/\bterm-(\d+)\b/', $found[0], $id ) && preg_match( '/\btaxonomy-([\w-]+)/', $found[0], $tax ) ) {
						$url  = get_term_link( (int) $id[1], $tax[1] );
						$link = is_string( $url ) ? $url : '';
					}
					return $found[0];
				}
				if ( '' === $link ) {
					return $found[0];
				}
				$out  = $found[0] . '<a class="purplish-terms-photo" href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true"></a>';
				$link = '';
				return $out;
			},
			$html
		);
	}

	/**
	 * The term ID from a Term Template item's `term-{id}` class.
	 *
	 * @param \WP_HTML_Tag_Processor $tags Processor on the item.
	 * @return int 0 when there is none.
	 */
	private static function term_id( $tags ) {
		foreach ( $tags->class_list() as $class ) {
			if ( preg_match( '/^term-(\d+)$/', $class, $match ) ) {
				return (int) $match[1];
			}
		}
		return 0;
	}

	/**
	 * A link without its scheme or trailing slash, so http and https or a
	 * missing slash still match.
	 *
	 * @param string $url The link.
	 * @return string
	 */
	private static function normalize( $url ) {
		return untrailingslashit( (string) preg_replace( '#^https?:#i', '', html_entity_decode( $url ) ) );
	}
}
