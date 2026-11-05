<?php
/**
 * Registers Purplish's block patterns. WordPress only loads a patterns/
 * folder for themes, so a plugin registers its own.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * One pattern category and one pattern per list entry, with the content
 * read from its file in patterns/.
 *
 * @since 1.1.0
 */
final class Patterns {

	const CATEGORY = 'purplish';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_patterns' ) );
	}

	/**
	 * The patterns Purplish ships, by slug. Each has a `title`, `categories`,
	 * `blockTypes` (the patterns show when one of these blocks is inserted)
	 * and the `file` that prints its content.
	 *
	 * @return array<string, array{title: string, categories: string[], blockTypes: string[], file: string}>
	 */
	public static function patterns() {
		return array(
			'hanging-rail' => array(
				'title'      => __( 'Hanging rail of new arrivals', 'purplish' ),
				'categories' => array(),
				'blockTypes' => array( 'purplish/hanging-rail' ),
				'file'       => PURPLISH_DIR . 'patterns/hanging-rail.php',
			),
		);
	}

	/**
	 * The patterns to register, after the `purplish_patterns` filter.
	 * Entries without a title or a readable file are dropped.
	 *
	 * @return array<string, array{title: string, categories: string[], blockTypes: string[], file: string}>
	 */
	public static function listed() {
		/**
		 * Filters the Purplish patterns to register, keyed by slug. Remove an
		 * entry to drop that pattern.
		 *
		 * @since 1.1.0
		 *
		 * @param array $patterns Patterns with `title`, `categories`, `blockTypes` and `file`.
		 */
		$patterns = apply_filters( 'purplish_patterns', self::patterns() );
		if ( ! is_array( $patterns ) ) {
			return array();
		}

		$listed = array();
		foreach ( $patterns as $slug => $pattern ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug || ! is_array( $pattern ) || empty( $pattern['title'] ) || empty( $pattern['file'] ) || ! is_readable( $pattern['file'] ) ) {
				continue;
			}

			$listed[ $slug ] = array(
				'title'      => (string) $pattern['title'],
				'categories' => isset( $pattern['categories'] ) ? array_map( 'strval', (array) $pattern['categories'] ) : array(),
				'blockTypes' => isset( $pattern['blockTypes'] ) ? array_map( 'strval', (array) $pattern['blockTypes'] ) : array(),
				'file'       => (string) $pattern['file'],
			);
		}
		return $listed;
	}

	/**
	 * Registers the category and every listed pattern. The category only
	 * registers when there is a pattern to put in it.
	 *
	 * @return void
	 */
	public function register_patterns() {
		$patterns = self::listed();
		if ( empty( $patterns ) ) {
			return;
		}

		register_block_pattern_category(
			self::CATEGORY,
			array( 'label' => __( 'Purplish', 'purplish' ) )
		);

		foreach ( $patterns as $slug => $pattern ) {
			register_block_pattern(
				'purplish/' . $slug,
				array(
					'title'      => $pattern['title'],
					'categories' => array_values( array_unique( array_merge( array( self::CATEGORY ), $pattern['categories'] ) ) ),
					'blockTypes' => $pattern['blockTypes'],
					'content'    => self::content( $pattern['file'] ),
				)
			);
		}
	}

	/**
	 * The markup a pattern file prints.
	 *
	 * @param string $file Pattern file.
	 * @return string
	 */
	public static function content( $file ) {
		ob_start();
		include $file;
		return (string) ob_get_clean();
	}
}
