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
	'runway' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/runway',
		'version' => '1.1.0',
		'title' => 'Runway',
		'category' => 'purplish',
		'description' => 'Product photos stand in a line that fades into the distance, and visitors walk it look by look beside the product cards.',
		'keywords' => array(
			'products',
			'lookbook',
			'gallery',
			'collection'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'looks' => array(
				'type' => 'string',
				'enum' => array(
					'all',
					'featured'
				),
				'default' => 'all'
			),
			'shape' => array(
				'type' => 'string',
				'enum' => array(
					'framed',
					'cutout'
				),
				'default' => 'framed'
			),
			'number' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'fly' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
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
				)
			)
		),
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	),
	'scroll-lookbook' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/scroll-lookbook',
		'version' => '1.1.0',
		'title' => 'Scroll Lookbook',
		'category' => 'purplish',
		'description' => 'Videos or photos play as visitors scroll, and at each stop that product\'s card slides in with add to cart.',
		'keywords' => array(
			'products',
			'lookbook',
			'video',
			'cover',
			'film'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'chapters' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'timecode' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'off'
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
			'core/cover',
			'woocommerce/product-collection',
			'core/group'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'spacing' => array(
				'margin' => array(
					'top',
					'bottom'
				)
			)
		),
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	),
	'step-path' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/step-path',
		'version' => '1.2.0',
		'title' => 'Step Path',
		'category' => 'purplish',
		'description' => 'Numbered steps on a line that draws as the block scrolls into view.',
		'keywords' => array(
			'steps',
			'process',
			'timeline',
			'how it works'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'direction' => array(
				'type' => 'string',
				'enum' => array(
					'across',
					'down'
				),
				'default' => 'across'
			),
			'marker' => array(
				'type' => 'string',
				'enum' => array(
					'numbers',
					'dots'
				),
				'default' => 'numbers'
			),
			'line' => array(
				'type' => 'string',
				'enum' => array(
					'plain',
					'dashed'
				),
				'default' => 'plain'
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
