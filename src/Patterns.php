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
	 * `blockTypes` (the patterns show when one of these blocks is inserted),
	 * optional `postTypes` (the post types it shows for) and the `file` that
	 * prints its content.
	 *
	 * @return array<string, array{title: string, categories: string[], blockTypes: string[], postTypes?: string[], file: string}>
	 */
	public static function patterns() {
		return array(
			'hanging-rail'          => array(
				'title'      => __( 'Hanging rail of new arrivals', 'purplish' ),
				'categories' => array(),
				'blockTypes' => array( 'purplish/hanging-rail' ),
				'file'       => PURPLISH_DIR . 'patterns/hanging-rail.php',
			),
			'story-deck'            => array(
				'title'      => __( 'Customer stories deck', 'purplish' ),
				'categories' => array( 'testimonials' ),
				'blockTypes' => array( 'purplish/story-deck' ),
				'file'       => PURPLISH_DIR . 'patterns/story-deck.php',
			),
			'runway'                => array(
				'title'      => __( 'Runway lookbook', 'purplish' ),
				'categories' => array(),
				'blockTypes' => array( 'purplish/runway' ),
				'file'       => PURPLISH_DIR . 'patterns/runway.php',
			),
			'scroll-lookbook'       => array(
				'title'      => __( 'Scroll lookbook storefront', 'purplish' ),
				'categories' => array(),
				'blockTypes' => array( 'purplish/scroll-lookbook' ),
				'file'       => PURPLISH_DIR . 'patterns/scroll-lookbook.php',
			),
			'flagship-header'       => array(
				'title'      => __( 'Flagship header', 'purplish' ),
				'categories' => array( 'header' ),
				'blockTypes' => array( 'core/template-part/header' ),
				'file'       => PURPLISH_DIR . 'patterns/flagship-header.php',
			),
			'flagship-phone-menu'   => array(
				'title'      => __( 'Flagship phone menu', 'purplish' ),
				'categories' => array( 'navigation' ),
				'blockTypes' => array( 'core/template-part/navigation-overlay' ),
				'file'       => PURPLISH_DIR . 'patterns/flagship-phone-menu.php',
			),
			'flagship-footer'       => array(
				'title'      => __( 'Flagship footer', 'purplish' ),
				'categories' => array( 'footer' ),
				'blockTypes' => array( 'core/template-part/footer' ),
				'file'       => PURPLISH_DIR . 'patterns/flagship-footer.php',
			),
			'flagship-home'         => array(
				'title'      => __( 'Flagship home', 'purplish' ),
				'categories' => array(),
				'blockTypes' => array( 'core/post-content' ),
				'postTypes'  => array( 'page' ),
				'file'       => PURPLISH_DIR . 'patterns/flagship-home.php',
			),
			'flagship-floor'        => array(
				'title'      => __( 'Flagship floor', 'purplish' ),
				'categories' => array( 'banner' ),
				'blockTypes' => array( 'purplish/showfloor' ),
				'file'       => PURPLISH_DIR . 'patterns/flagship-floor.php',
			),
			'flagship-categories'   => array(
				'title'      => __( 'Shop by category', 'purplish' ),
				'categories' => array( 'gallery' ),
				'blockTypes' => array(),
				'file'       => PURPLISH_DIR . 'patterns/flagship-categories.php',
			),
			'flagship-just-in'      => array(
				'title'      => __( 'Just in', 'purplish' ),
				'categories' => array( 'woo-commerce' ),
				'blockTypes' => array(),
				'file'       => PURPLISH_DIR . 'patterns/flagship-just-in.php',
			),
			'flagship-brand-band'   => array(
				'title'      => __( 'Brand band', 'purplish' ),
				'categories' => array( 'banner' ),
				'blockTypes' => array(),
				'file'       => PURPLISH_DIR . 'patterns/flagship-brand-band.php',
			),
			'flagship-best-sellers' => array(
				'title'      => __( 'Best sellers', 'purplish' ),
				'categories' => array( 'woo-commerce' ),
				'blockTypes' => array(),
				'file'       => PURPLISH_DIR . 'patterns/flagship-best-sellers.php',
			),
			'trust-row'             => array(
				'title'      => __( 'Trust row', 'purplish' ),
				'categories' => array( 'text' ),
				'blockTypes' => array(),
				'file'       => PURPLISH_DIR . 'patterns/trust-row.php',
			),
			'flagship-newsletter'   => array(
				'title'      => __( 'Newsletter', 'purplish' ),
				'categories' => array( 'call-to-action' ),
				'blockTypes' => array(),
				'file'       => PURPLISH_DIR . 'patterns/flagship-newsletter.php',
			),
		);
	}

	/**
	 * The patterns to register, after the `purplish_patterns` filter.
	 * Entries without a title or a readable file are dropped.
	 *
	 * @return array<string, array{title: string, categories: string[], blockTypes: string[], postTypes?: string[], file: string}>
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
			if ( ! empty( $pattern['postTypes'] ) ) {
				$listed[ $slug ]['postTypes'] = array_map( 'strval', (array) $pattern['postTypes'] );
			}
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
			$properties = array(
				'title'      => $pattern['title'],
				'categories' => array_values( array_unique( array_merge( array( self::CATEGORY ), $pattern['categories'] ) ) ),
				'blockTypes' => $pattern['blockTypes'],
				'content'    => self::content( $pattern['file'] ),
			);
			if ( ! empty( $pattern['postTypes'] ) ) {
				$properties['postTypes'] = $pattern['postTypes'];
			}
			register_block_pattern( 'purplish/' . $slug, $properties );
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
