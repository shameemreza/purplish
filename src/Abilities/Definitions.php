<?php
/**
 * Registration arguments for the Purplish abilities: labels, input and
 * output schemas, permissions and the meta the REST API and the MCP
 * Adapter read.
 *
 * @package Purplish
 */

namespace Purplish\Abilities;

use Purplish\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * The four abilities' arguments.
 */
class Definitions {

	/**
	 * Each ability's registration arguments, keyed by name.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		$target  = array(
			'post_id'       => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => __( 'ID of a page, post, or a template or template part saved in the Site Editor.', 'purplish' ),
			),
			'template'      => array(
				'type'        => 'string',
				'description' => __( 'A template or template part ID such as "purple//single-product", for one the Site Editor has not saved yet.', 'purplish' ),
			),
			'template_type' => array(
				'type'        => 'string',
				'enum'        => array( 'wp_template', 'wp_template_part' ),
				'default'     => 'wp_template',
				'description' => __( 'Whether `template` names a template or a template part.', 'purplish' ),
			),
		);
		$locator = array(
			'path'   => array(
				'type'        => 'string',
				'pattern'     => '^[0-9]+(\.[0-9]+)*$',
				'description' => __( 'The block\'s path from purplish/get-page-designs, such as "0.2.1".', 'purplish' ),
			),
			'anchor' => array(
				'type'        => 'string',
				'description' => __( 'The block\'s HTML anchor, instead of a path.', 'purplish' ),
			),
		);
		$found   = array(
			'type'       => 'object',
			'properties' => array(
				'path'    => array( 'type' => 'string' ),
				'name'    => array( 'type' => 'string' ),
				'title'   => array( 'type' => 'string' ),
				'options' => array( 'type' => 'object' ),
				'classes' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		);
		$mcp     = array(
			'public' => true,
			'type'   => 'tool',
		);

		return array(
			'purplish/get-design-catalog' => array(
				'label'               => __( 'Get the Purplish design catalog', 'purplish' ),
				'description'         => __( 'Lists what Purplish can do on this site: its own blocks, the options it adds to each WooCommerce and core block with the values each option accepts, and its patterns.', 'purplish' ),
				'category'            => Abilities::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'default'    => (object) array(),
					'properties' => array(
						'type' => array(
							'type'        => 'string',
							'enum'        => array( 'blocks', 'options', 'patterns' ),
							'description' => __( 'Only this part of the catalog. Leave it out for all three.', 'purplish' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'blocks'   => array( 'type' => 'array' ),
						'options'  => array( 'type' => 'array' ),
						'page'     => array( 'type' => 'object' ),
						'patterns' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( Abilities::class, 'get_design_catalog' ),
				'permission_callback' => array( Abilities::class, 'can_read_catalog' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => $mcp,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
			'purplish/get-page-designs'   => array(
				'label'               => __( 'Get the Purplish designs on a page', 'purplish' ),
				'description'         => __( 'Lists every block on a page, post, template or template part that can take Purplish options, with its path, its saved options and the classes they print.', 'purplish' ),
				'category'            => Abilities::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => $target,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'target' => array( 'type' => 'object' ),
						'blocks' => array(
							'type'  => 'array',
							'items' => $found,
						),
					),
				),
				'execute_callback'    => array( Abilities::class, 'get_page_designs' ),
				'permission_callback' => array( Abilities::class, 'can_edit_target' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => $mcp,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
			'purplish/set-block-design'   => array(
				'label'               => __( 'Set a block\'s Purplish design', 'purplish' ),
				'description'         => __( 'Gives one block Purplish options, or clears them with null. Every value is checked against the options in purplish/get-design-catalog. Only the block\'s Purplish options change, and the page saves a revision.', 'purplish' ),
				'category'            => Abilities::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'options' ),
					'properties' => $target + $locator + array(
						'options' => array(
							'type'                 => 'object',
							'additionalProperties' => true,
							'description'          => __( 'Option names and values, such as {"hover": "tilt"}. true turns a switch on, and null or false clears an option.', 'purplish' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'target'  => array( 'type' => 'object' ),
						'block'   => $found,
						'changed' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( Abilities::class, 'set_block_design' ),
				'permission_callback' => array( Abilities::class, 'can_edit_target' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => $mcp,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
			'purplish/insert-pattern'     => array(
				'label'               => __( 'Insert a Purplish pattern', 'purplish' ),
				'description'         => __( 'Adds a Purplish pattern\'s blocks at the start or end of a page, post, template or template part, or right after a block. The page saves a revision.', 'purplish' ),
				'category'            => Abilities::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'pattern' ),
					'properties' => $target + array(
						'pattern'  => array(
							'type'        => 'string',
							'description' => __( 'The pattern slug from purplish/get-design-catalog, such as "purplish/trust-row".', 'purplish' ),
						),
						'position' => array(
							'type'        => 'string',
							'enum'        => array( 'start', 'end', 'after' ),
							'default'     => 'end',
							'description' => __( 'Where the pattern goes. "after" needs `after`.', 'purplish' ),
						),
						'after'    => array(
							'type'        => 'string',
							'pattern'     => '^[0-9]+(\.[0-9]+)*$',
							'description' => __( 'The path of the block the pattern goes after.', 'purplish' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'target'   => array( 'type' => 'object' ),
						'pattern'  => array( 'type' => 'string' ),
						'path'     => array( 'type' => 'string' ),
						'inserted' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( Abilities::class, 'insert_pattern' ),
				'permission_callback' => array( Abilities::class, 'can_edit_target' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => $mcp,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			),
		);
	}
}
