<?php
/**
 * Abilities: what Purplish can do, read and change through the WordPress
 * Abilities API, so the REST API and the MCP Adapter can offer it to agents.
 *
 * @package Purplish
 */

namespace Purplish;

use Purplish\Abilities\BlockTree;
use Purplish\Abilities\Definitions;
use Purplish\Frontend\BlockEffects;
use WP_Block_Patterns_Registry;
use WP_Block_Type_Registry;
use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Purplish category and its four abilities. The options map
 * in BlockEffects is the one source for the editor panels, the front end
 * and these abilities, so they can't drift apart.
 */
class Abilities {

	const CATEGORY = 'purplish';

	/**
	 * The block whose `purplish` attribute also holds the page options.
	 */
	const PAGE_BLOCK = 'woocommerce/add-to-cart-with-options';

	/**
	 * Hooks into the Abilities API, when the site has it.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Registers the Purplish category.
	 *
	 * @return void
	 */
	public function register_category() {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Purplish', 'purplish' ),
				'description' => __( 'Design options and patterns for WooCommerce blocks.', 'purplish' ),
			)
		);
	}

	/**
	 * Registers the four abilities.
	 *
	 * @return void
	 */
	public function register_abilities() {
		foreach ( Definitions::all() as $name => $args ) {
			wp_register_ability( $name, $args );
		}
	}

	/**
	 * Anyone who can edit posts can read the catalog.
	 *
	 * @return bool
	 */
	public static function can_read_catalog() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Whether the user can edit the page, post or template the input names:
	 * `edit_post` for a post, `edit_theme_options` for a template.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_edit_target( $input = null ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		// A missing or unknown target fails in the ability itself, with an
		// error that says what to give instead of a permission error.
		$target = self::target( $input );
		if ( is_wp_error( $target ) ) {
			return true;
		}
		return 'template' === $target['kind'] || in_array( $target['post']->post_type, array( 'wp_template', 'wp_template_part' ), true )
			? current_user_can( 'edit_theme_options' )
			: current_user_can( 'edit_post', $target['post']->ID );
	}

	/**
	 * The catalog: Purplish's own blocks, the options per block, the page
	 * options and the patterns.
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>
	 */
	public static function get_design_catalog( $input = null ) {
		$type = is_array( $input ) && isset( $input['type'] ) ? (string) $input['type'] : '';
		$out  = array();

		if ( '' === $type || 'blocks' === $type ) {
			$out['blocks'] = array();
			foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block ) {
				if ( 0 === strpos( $name, 'purplish/' ) ) {
					$out['blocks'][] = array(
						'name'        => $name,
						'title'       => (string) $block->title,
						'description' => (string) $block->description,
						'parent'      => array_values( array_merge( (array) $block->parent, (array) $block->ancestor ) ),
					);
				}
			}
		}

		if ( '' === $type || 'options' === $type ) {
			$out['options'] = array();
			foreach ( BlockEffects::blocks() as $name => $block ) {
				$out['options'][] = array(
					'name'    => $name,
					'title'   => self::title( $name ),
					'options' => array_map(
						static function ( $allowed ) {
							return true === $allowed ? 'switch' : array_values( $allowed );
						},
						$block['options']
					) + array( 'color' => 'hex' ),
				);
			}
			$out['page'] = array(
				'block'      => self::PAGE_BLOCK,
				'parts'      => array_keys( Options::part_defaults() ),
				'transition' => Options::TRANSITIONS,
				'switches'   => Options::SWITCHES,
			);
		}

		if ( '' === $type || 'patterns' === $type ) {
			$out['patterns'] = array();
			foreach ( Patterns::listed() as $slug => $pattern ) {
				$out['patterns'][] = array(
					'name'       => 'purplish/' . $slug,
					'title'      => $pattern['title'],
					'categories' => $pattern['categories'],
					'blockTypes' => $pattern['blockTypes'],
				);
			}
		}

		return $out;
	}

	/**
	 * Every block on the target that can take Purplish options.
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get_page_designs( $input = null ) {
		$target = self::target( $input );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$found = array();
		foreach ( BlockTree::paths( parse_blocks( $target['content'] ) ) as $path => $block ) {
			if ( isset( BlockEffects::blocks()[ $block['blockName'] ] ) ) {
				$found[] = self::describe( (string) $path, $block );
			}
		}

		return array(
			'target' => self::summary( $target ),
			'blocks' => $found,
		);
	}

	/**
	 * Merges options into one block's `purplish` attribute and saves.
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function set_block_design( $input = null ) {
		$target = self::target( $input );
		if ( is_wp_error( $target ) ) {
			return $target;
		}
		if ( ! isset( $input['options'] ) || ! is_array( $input['options'] ) ) {
			return new WP_Error( 'purplish_missing_options', __( 'Give the options to set, such as {"hover": "tilt"}.', 'purplish' ) );
		}

		$blocks = parse_blocks( $target['content'] );
		$path   = BlockTree::locate( $blocks, $input );
		if ( is_wp_error( $path ) ) {
			return $path;
		}

		$block = &BlockTree::at( $blocks, $path );
		$name  = (string) $block['blockName'];
		if ( ! isset( BlockEffects::blocks()[ $name ] ) ) {
			/* translators: %s: block name, such as core/paragraph. */
			return new WP_Error( 'purplish_invalid_block', sprintf( __( 'Purplish has no options for %s.', 'purplish' ), $name ) );
		}

		$saved  = isset( $block['attrs']['purplish'] ) && is_array( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
		$merged = self::merge_options( $name, $saved, $input['options'] );
		if ( is_wp_error( $merged ) ) {
			return $merged;
		}

		$changed = $merged !== $saved;
		if ( $changed ) {
			if ( $merged ) {
				$block['attrs']['purplish'] = $merged;
			} else {
				unset( $block['attrs']['purplish'] );
			}
			$described = self::describe( $path, $block );
			unset( $block );

			$saved_to = self::save( $target, serialize_blocks( $blocks ) );
			if ( is_wp_error( $saved_to ) ) {
				return $saved_to;
			}
			$target = $saved_to;
		} else {
			$described = self::describe( $path, $block );
			unset( $block );
		}

		return array(
			'target'  => self::summary( $target ),
			'block'   => $described,
			'changed' => $changed,
		);
	}

	/**
	 * Inserts a Purplish pattern's blocks and saves.
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function insert_pattern( $input = null ) {
		$target = self::target( $input );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$slug = isset( $input['pattern'] ) && is_string( $input['pattern'] ) ? preg_replace( '#^purplish/#', '', $input['pattern'] ) : '';
		$name = 'purplish/' . $slug;
		if ( '' === $slug || ! isset( Patterns::listed()[ $slug ] ) || ! WP_Block_Patterns_Registry::get_instance()->is_registered( $name ) ) {
			/* translators: %s: pattern name. */
			return new WP_Error( 'purplish_invalid_pattern', sprintf( __( 'There is no Purplish pattern named %s. purplish/get-design-catalog lists them.', 'purplish' ), $name ) );
		}

		$pattern  = WP_Block_Patterns_Registry::get_instance()->get_registered( $name )['content'];
		$adding   = array_values( BlockTree::named( parse_blocks( $pattern ) ) );
		$blocks   = parse_blocks( $target['content'] );
		$position = isset( $input['position'] ) ? (string) $input['position'] : 'end';

		if ( 'start' === $position ) {
			$content = trim( $pattern ) . "\n\n" . $target['content'];
			$path    = '0';
		} elseif ( 'end' === $position ) {
			$content = rtrim( $target['content'] ) . "\n\n" . trim( $pattern );
			$path    = (string) count( BlockTree::named( $blocks ) );
		} elseif ( 'after' === $position ) {
			$after = BlockTree::locate( $blocks, array( 'path' => isset( $input['after'] ) ? $input['after'] : '' ) );
			if ( is_wp_error( $after ) ) {
				return new WP_Error( 'purplish_invalid_after', __( 'Give `after`: the path of a block on the page, from purplish/get-page-designs.', 'purplish' ) );
			}
			$path    = BlockTree::insert_after( $blocks, $after, $adding );
			$content = serialize_blocks( $blocks );
		} else {
			return new WP_Error( 'purplish_invalid_position', __( 'The position is start, end or after.', 'purplish' ) );
		}

		$saved_to = self::save( $target, $content );
		if ( is_wp_error( $saved_to ) ) {
			return $saved_to;
		}

		return array(
			'target'   => self::summary( $saved_to ),
			'pattern'  => $name,
			'path'     => $path,
			'inserted' => count( $adding ),
		);
	}

	/**
	 * The page, post or template the input names, with its block content.
	 *
	 * @param mixed $input Ability input.
	 * @return array{kind: string, content: string, post?: WP_Post, template?: \WP_Block_Template}|WP_Error
	 */
	public static function target( $input ) {
		$input = is_array( $input ) ? $input : array();
		$id    = isset( $input['post_id'] ) && is_int( $input['post_id'] ) ? $input['post_id'] : 0;
		$slug  = isset( $input['template'] ) && is_string( $input['template'] ) ? $input['template'] : '';

		if ( $id && '' === $slug ) {
			$post = get_post( $id );
			if ( ! $post instanceof WP_Post || 'revision' === $post->post_type ) {
				/* translators: %d: post ID. */
				return new WP_Error( 'purplish_invalid_post_id', sprintf( __( 'There is no post with ID %d.', 'purplish' ), $id ), array( 'status' => 404 ) );
			}
			return array(
				'kind'    => 'post',
				'post'    => $post,
				'content' => (string) $post->post_content,
			);
		}

		if ( '' !== $slug && ! $id ) {
			$type     = isset( $input['template_type'] ) && 'wp_template_part' === $input['template_type'] ? 'wp_template_part' : 'wp_template';
			$template = get_block_template( $slug, $type );
			if ( ! $template ) {
				/* translators: %s: template ID, such as purple//single-product. */
				return new WP_Error( 'purplish_invalid_template', sprintf( __( 'There is no template named %s.', 'purplish' ), $slug ), array( 'status' => 404 ) );
			}
			return array(
				'kind'     => 'template',
				'template' => $template,
				'content'  => (string) $template->content,
			);
		}

		return new WP_Error( 'purplish_missing_target', __( 'Give either `post_id` or `template`.', 'purplish' ) );
	}

	/**
	 * The options a block ends up with: the saved ones with the changes on
	 * top. null or false clears an option. Anything the block doesn't
	 * accept stops the whole change.
	 *
	 * @param string $name    Block name.
	 * @param array  $saved   The block's saved options.
	 * @param array  $changes Options to set or clear.
	 * @return array|WP_Error
	 */
	public static function merge_options( $name, $saved, $changes ) {
		$map    = BlockEffects::blocks()[ $name ]['options'];
		$page   = self::PAGE_BLOCK === $name;
		$merged = $saved;

		foreach ( $changes as $option => $value ) {
			$option = (string) $option;
			if ( null === $value || false === $value ) {
				unset( $merged[ $option ] );
				continue;
			}

			$allowed = null;
			if ( isset( $map[ $option ] ) ) {
				$allowed = $map[ $option ];
			} elseif ( $page && in_array( $option, Options::SWITCHES, true ) ) {
				$allowed = true;
			} elseif ( $page && 'transition' === $option ) {
				$allowed = Options::TRANSITIONS;
			}

			if ( true === $allowed || is_array( $allowed ) ) {
				if ( true === $allowed ? true !== $value : ! ( is_string( $value ) && in_array( $value, $allowed, true ) ) ) {
					return self::invalid_value( $option, $allowed );
				}
				$merged[ $option ] = $value;
			} elseif ( 'color' === $option ) {
				if ( ! is_string( $value ) || ! sanitize_hex_color( $value ) ) {
					return self::invalid_value( $option, null );
				}
				$merged[ $option ] = sanitize_hex_color( $value );
			} elseif ( $page && 'parts' === $option ) {
				if ( ! is_array( $value ) || array_diff( $value, array_keys( Options::part_defaults() ) ) ) {
					return self::invalid_value( $option, null );
				}
				$merged[ $option ] = array_values( array_unique( $value ) );
			} else {
				return new WP_Error(
					'purplish_invalid_option',
					/* translators: 1: block name, 2: option name, 3: option names. */
					sprintf( __( '%1$s has no option named %2$s. It has: %3$s.', 'purplish' ), $name, $option, implode( ', ', self::option_names( $name ) ) )
				);
			}
		}

		return $merged;
	}

	/**
	 * Saves new content to the target, through the posts API for a post and
	 * the templates REST route for a template, so a template the Site
	 * Editor hasn't saved yet gets its own copy. Both keep a revision.
	 *
	 * @param array  $target  Target from target().
	 * @param string $content New block content.
	 * @return array|WP_Error The target, read again.
	 */
	private static function save( $target, $content ) {
		if ( 'post' === $target['kind'] ) {
			$saved = wp_update_post(
				array(
					'ID'           => $target['post']->ID,
					'post_content' => wp_slash( $content ),
				),
				true
			);
			if ( is_wp_error( $saved ) ) {
				return $saved;
			}
			return self::target( array( 'post_id' => $target['post']->ID ) );
		}

		$type    = $target['template']->type;
		$base    = 'wp_template_part' === $type ? 'template-parts' : 'templates';
		$request = new \WP_REST_Request( 'POST', '/wp/v2/' . $base . '/' . $target['template']->id );
		$request->set_param( 'content', $content );
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return $response->as_error();
		}
		return self::target(
			array(
				'template'      => $target['template']->id,
				'template_type' => $type,
			)
		);
	}

	/**
	 * A short, readable description of the target.
	 *
	 * @param array $target Target from target().
	 * @return array<string, mixed>
	 */
	private static function summary( $target ) {
		if ( 'post' === $target['kind'] ) {
			return array(
				'post_id'   => $target['post']->ID,
				'post_type' => $target['post']->post_type,
				'title'     => get_the_title( $target['post'] ),
			);
		}
		return array(
			'template'      => $target['template']->id,
			'template_type' => $target['template']->type,
			'title'         => $target['template']->title,
		);
	}

	/**
	 * A block's path, name, saved options and the classes they print.
	 *
	 * @param string $path  Block path.
	 * @param array  $block Parsed block.
	 * @return array<string, mixed>
	 */
	private static function describe( $path, $block ) {
		$saved = isset( $block['attrs']['purplish'] ) && is_array( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
		return array(
			'path'    => $path,
			'name'    => $block['blockName'],
			'title'   => self::title( $block['blockName'] ),
			'options' => $saved ? $saved : (object) array(),
			'classes' => BlockEffects::classes_for( $block['blockName'], $saved ),
		);
	}

	/**
	 * A block's title from the registry, or its name.
	 *
	 * @param string $name Block name.
	 * @return string
	 */
	private static function title( $name ) {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( $name );
		return $type && $type->title ? (string) $type->title : $name;
	}

	/**
	 * Every option name a block takes, for error messages.
	 *
	 * @param string $name Block name.
	 * @return string[]
	 */
	private static function option_names( $name ) {
		$names   = array_keys( BlockEffects::blocks()[ $name ]['options'] );
		$names[] = 'color';
		if ( self::PAGE_BLOCK === $name ) {
			$names = array_merge( $names, array( 'parts', 'transition' ), Options::SWITCHES );
		}
		return $names;
	}

	/**
	 * The error for a value an option doesn't take, saying what it takes.
	 *
	 * @param string     $option  Option name.
	 * @param mixed|null $allowed The option's allowed values from the map.
	 * @return WP_Error
	 */
	private static function invalid_value( $option, $allowed ) {
		if ( 'color' === $option ) {
			$takes = __( 'a hex color such as #8f43ee', 'purplish' );
		} elseif ( 'parts' === $option ) {
			$takes = __( 'a list from: ', 'purplish' ) . implode( ', ', array_keys( Options::part_defaults() ) );
		} else {
			$takes = true === $allowed ? 'true, false, null' : implode( ', ', (array) $allowed ) . ', null';
		}
		/* translators: 1: option name, 2: the values it takes. */
		return new WP_Error( 'purplish_invalid_value', sprintf( __( '%1$s takes %2$s.', 'purplish' ), $option, $takes ) );
	}
}
