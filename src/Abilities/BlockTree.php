<?php
/**
 * Block tree helpers for the abilities: block paths, finding a block by
 * path or anchor, and changing or adding blocks in place.
 *
 * @package Purplish
 */

namespace Purplish\Abilities;

use WP_Error;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * Walks parsed blocks. A path such as "0.2.1" counts named blocks only,
 * so the blank space between top-level blocks has none.
 */
class BlockTree {

	/**
	 * Every named block with its path, in page order. A path counts only
	 * named blocks, so the blank space between top-level blocks has none.
	 *
	 * @param array  $blocks Parsed blocks.
	 * @param string $prefix Path of the parent.
	 * @return array<string, array> Blocks keyed by path.
	 */
	public static function paths( $blocks, $prefix = '' ) {
		$out = array();
		foreach ( array_values( self::named( $blocks ) ) as $index => $block ) {
			$path         = '' === $prefix ? (string) $index : $prefix . '.' . $index;
			$out[ $path ] = $block;
			$out         += self::paths( $block['innerBlocks'], $path );
		}
		return $out;
	}

	/**
	 * The named blocks in a list, keeping their keys.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	public static function named( $blocks ) {
		return array_filter(
			$blocks,
			static function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		);
	}

	/**
	 * The path of the block the input points at, by path or by anchor.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $input  Ability input.
	 * @return string|WP_Error
	 */
	public static function locate( $blocks, $input ) {
		$paths = self::paths( $blocks );

		if ( isset( $input['path'] ) && is_string( $input['path'] ) && '' !== $input['path'] ) {
			if ( isset( $paths[ $input['path'] ] ) ) {
				return $input['path'];
			}
			/* translators: %s: block path, such as 0.2.1. */
			return new WP_Error( 'purplish_invalid_path', sprintf( __( 'No block sits at path %s. purplish/get-page-designs lists the paths.', 'purplish' ), $input['path'] ) );
		}

		if ( isset( $input['anchor'] ) && is_string( $input['anchor'] ) && '' !== $input['anchor'] ) {
			foreach ( $paths as $path => $block ) {
				if ( self::anchor( $block ) === $input['anchor'] ) {
					return (string) $path;
				}
			}
			/* translators: %s: HTML anchor. */
			return new WP_Error( 'purplish_invalid_anchor', sprintf( __( 'No block has the anchor %s.', 'purplish' ), $input['anchor'] ) );
		}

		return new WP_Error( 'purplish_missing_path', __( 'Give the block\'s `path` or `anchor`.', 'purplish' ) );
	}

	/**
	 * A block's anchor: saved in its attributes, or as the ID on its first
	 * tag, where most core blocks keep it.
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	public static function anchor( $block ) {
		if ( isset( $block['attrs']['anchor'] ) && is_string( $block['attrs']['anchor'] ) ) {
			return $block['attrs']['anchor'];
		}
		$tags = new WP_HTML_Tag_Processor( (string) $block['innerHTML'] );
		return $tags->next_tag() ? (string) $tags->get_attribute( 'id' ) : '';
	}

	/**
	 * The block at a path, by reference, so it can be changed in place.
	 *
	 * @param array  $blocks Parsed blocks, changed in place.
	 * @param string $path   A path from locate().
	 * @return array
	 */
	public static function &at( &$blocks, $path ) {
		$list  = &$blocks;
		$steps = array_map( 'intval', explode( '.', $path ) );
		$last  = count( $steps ) - 1;
		foreach ( $steps as $depth => $step ) {
			$key = array_keys( self::named( $list ) )[ $step ];
			if ( $depth === $last ) {
				return $list[ $key ];
			}
			$list = &$list[ $key ]['innerBlocks'];
		}
		return $list;
	}

	/**
	 * Puts blocks right after the block at a path, as its siblings.
	 *
	 * @param array  $blocks Parsed blocks, changed in place.
	 * @param string $path   Path of the block to go after.
	 * @param array  $adding Parsed blocks to insert.
	 * @return string Path of the first inserted block.
	 */
	public static function insert_after( &$blocks, $path, $adding ) {
		$steps = explode( '.', $path );
		$index = (int) array_pop( $steps );
		$gap   = array(
			'blockName'    => null,
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => "\n\n",
			'innerContent' => array( "\n\n" ),
		);

		if ( ! $steps ) {
			$key   = array_keys( self::named( $blocks ) )[ $index ];
			$added = array();
			foreach ( $adding as $block ) {
				$added[] = $gap;
				$added[] = $block;
			}
			array_splice( $blocks, $key + 1, 0, $added );
			return (string) ( $index + 1 );
		}

		$parent = &self::at( $blocks, implode( '.', $steps ) );
		array_splice( $parent['innerBlocks'], $index + 1, 0, $adding );

		// Each inner block has a null in innerContent; the new ones go
		// right after the null of the block they follow.
		$seen = -1;
		foreach ( $parent['innerContent'] as $at => $chunk ) {
			if ( null === $chunk && ++$seen === $index ) {
				$chunks = array();
				foreach ( $adding as $block ) {
					$chunks[] = "\n\n";
					$chunks[] = null;
				}
				array_splice( $parent['innerContent'], $at + 1, 0, $chunks );
				break;
			}
		}
		unset( $parent );

		return implode( '.', $steps ) . '.' . ( $index + 1 );
	}
}
