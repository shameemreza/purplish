<?php
/**
 * Registers Purplish's own blocks and their inserter category.
 *
 * @package Purplish
 */

namespace Purplish\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Reads every block's metadata from the blocks manifest the build writes,
 * then registers each listed block class with its render callback.
 *
 * @since 1.1.0
 */
final class Registry {

	const CATEGORY = 'purplish';

	/**
	 * The build folder that holds blocks-manifest.php and blocks/<slug>/.
	 *
	 * @var string
	 */
	private $build_dir;

	/**
	 * Sets the build folder.
	 *
	 * @param string $build_dir Build folder; defaults to the plugin's build/.
	 */
	public function __construct( $build_dir = '' ) {
		$this->build_dir = trailingslashit( '' === $build_dir ? PURPLISH_DIR . 'build' : $build_dir );
	}

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'add_category' ) );
		Wardrobe::hooks();
		ShelfTag::hooks();
	}

	/**
	 * The block classes Purplish ships.
	 *
	 * @return string[] Class names.
	 */
	public static function blocks() {
		return array(
			HangingRail::class,
			StoryDeck::class,
			Runway::class,
			ScrollLookbook::class,
			Showfloor::class,
			ShowfloorLook::class,
			ChangingRoom::class,
			ChangingRoomPiece::class,
			Wardrobe::class,
			ShelfTag::class,
		);
	}

	/**
	 * The block classes to register, after the `purplish_blocks` filter.
	 * Anything that isn't a Block class is dropped.
	 *
	 * @return string[] Class names.
	 */
	public static function listed() {
		/**
		 * Filters the Purplish blocks to register. Remove a class to turn
		 * that block off; its scripts and styles go with it.
		 *
		 * @since 1.1.0
		 *
		 * @param string[] $blocks Block class names.
		 */
		$blocks = apply_filters( 'purplish_blocks', self::blocks() );
		if ( ! is_array( $blocks ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter(
					$blocks,
					static function ( $class_name ) {
						return is_string( $class_name ) && is_subclass_of( $class_name, Block::class );
					}
				)
			)
		);
	}

	/**
	 * Registers the metadata collection and every listed block.
	 *
	 * @return void
	 */
	public function register_blocks() {
		$blocks = self::listed();
		if ( empty( $blocks ) ) {
			return;
		}

		// The build only writes the manifest when there is a block.json.
		$manifest = $this->build_dir . 'blocks-manifest.php';
		if ( file_exists( $manifest ) ) {
			wp_register_block_metadata_collection( $this->build_dir, $manifest );
		}

		foreach ( $blocks as $class_name ) {
			$block = new $class_name();
			$slug  = sanitize_key( $block->slug() );
			if ( '' === $slug ) {
				continue;
			}

			$args = array( 'render_callback' => array( $block, 'render' ) );
			// A block can add settings PHP has to fill, like an inserter
			// preview with URLs.
			if ( method_exists( $block, 'settings' ) ) {
				$args = array_merge( $block->settings(), $args );
			}
			register_block_type( $this->build_dir . 'blocks/' . $slug, $args );
		}
	}

	/**
	 * Adds the Purplish category at the end of the inserter list, only when
	 * there is a block to put in it.
	 *
	 * @param array[] $categories Block categories.
	 * @return array[]
	 */
	public function add_category( $categories ) {
		if ( empty( self::listed() ) || in_array( self::CATEGORY, wp_list_pluck( $categories, 'slug' ), true ) ) {
			return $categories;
		}

		$categories[] = array(
			'slug'  => self::CATEGORY,
			'title' => __( 'Purplish', 'purplish' ),
			'icon'  => null,
		);
		return $categories;
	}
}
