<?php
// This file is generated. Do not modify it manually.
return array(
	'hanging-rail' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/hanging-rail',
		'version' => '1.1.0',
		'title' => 'Hanging Rail',
		'category' => 'purplish',
		'description' => 'Products hang from clips on a rope and slide along it as the page scrolls.',
		'keywords' => array(
			'products',
			'carousel',
			'clothesline',
			'scroll'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'clip' => array(
				'type' => 'string',
				'enum' => array(
					'peg',
					'binder',
					'tape'
				),
				'default' => 'peg'
			),
			'sag' => array(
				'type' => 'string',
				'enum' => array(
					'little',
					'more'
				),
				'default' => 'little'
			),
			'length' => array(
				'type' => 'string',
				'enum' => array(
					'short',
					'medium',
					'long'
				),
				'default' => 'medium'
			)
		),
		'allowedBlocks' => array(
			'woocommerce/product-collection',
			'core/heading',
			'core/paragraph'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	),
	'story-deck' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/story-deck',
		'version' => '1.1.0',
		'title' => 'Story Deck',
		'category' => 'purplish',
		'description' => 'Customer stories on a deck of cards that visitors flick through.',
		'keywords' => array(
			'testimonials',
			'reviews',
			'quotes',
			'cards'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'side' => array(
				'type' => 'string',
				'enum' => array(
					'left',
					'right'
				),
				'default' => 'left'
			),
			'shape' => array(
				'type' => 'string',
				'enum' => array(
					'rounded',
					'square'
				),
				'default' => 'rounded'
			),
			'fan' => array(
				'type' => 'string',
				'enum' => array(
					'fanned',
					'flat'
				),
				'default' => 'fanned'
			)
		),
		'allowedBlocks' => array(
			'core/group'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	)
);
