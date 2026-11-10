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
	'showfloor' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/showfloor',
		'version' => '1.3.0',
		'title' => 'Showfloor',
		'category' => 'purplish',
		'description' => 'Cut-out looks stand on a turning floor. Visitors turn it, open a look and add its pieces to the cart.',
		'keywords' => array(
			'shop the look',
			'lookbook',
			'hero',
			'outfit',
			'products'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			),
			'shape' => array(
				'type' => 'string',
				'enum' => array(
					'oval',
					'flat'
				),
				'default' => 'oval'
			),
			'depth' => array(
				'type' => 'string',
				'enum' => array(
					'subtle',
					'strong',
					'off'
				),
				'default' => 'subtle'
			),
			'start' => array(
				'type' => 'string',
				'enum' => array(
					'floor',
					'grid'
				),
				'default' => 'floor'
			),
			'autoplay' => array(
				'type' => 'string',
				'enum' => array(
					'off',
					'on'
				),
				'default' => 'off'
			),
			'speed' => array(
				'type' => 'number',
				'default' => 4
			),
			'pause' => array(
				'type' => 'string',
				'enum' => array(
					'ring',
					'plain',
					'off'
				),
				'default' => 'ring'
			),
			'phone' => array(
				'type' => 'string',
				'enum' => array(
					'row',
					'ring'
				),
				'default' => 'row'
			),
			'toggle' => array(
				'type' => 'string',
				'enum' => array(
					'both',
					'icons',
					'text',
					'off'
				),
				'default' => 'both'
			),
			'arrows' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'cursor' => array(
				'type' => 'string',
				'enum' => array(
					'label',
					'ring',
					'off'
				),
				'default' => 'label'
			),
			'list' => array(
				'type' => 'string',
				'enum' => array(
					'both',
					'faces',
					'names',
					'numbers',
					'off'
				),
				'default' => 'both'
			),
			'shop' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'shopText' => array(
				'type' => 'string',
				'default' => ''
			),
			'add' => array(
				'type' => 'string',
				'enum' => array(
					'text',
					'icon'
				),
				'default' => 'text'
			),
			'total' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'whole' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'hover' => array(
				'type' => 'string',
				'enum' => array(
					'lift',
					'spotlight',
					'peek',
					'none'
				),
				'default' => 'lift'
			),
			'close' => array(
				'type' => 'string',
				'enum' => array(
					'both',
					'icon',
					'text'
				),
				'default' => 'both'
			),
			'marks' => array(
				'type' => 'string',
				'enum' => array(
					'highlight',
					'add'
				),
				'default' => 'highlight'
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
			'purplish/showfloor-look'
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
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	),
	'showfloor-look' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/showfloor-look',
		'version' => '1.3.0',
		'title' => 'Look',
		'category' => 'purplish',
		'parent' => array(
			'purplish/showfloor'
		),
		'description' => 'One look on the Showfloor: a cut-out image, a name, a short line and the products in it.',
		'textdomain' => 'purplish',
		'attributes' => array(
			'id' => array(
				'type' => 'number',
				'default' => 0
			),
			'url' => array(
				'type' => 'string',
				'default' => ''
			),
			'name' => array(
				'type' => 'string',
				'default' => ''
			),
			'line' => array(
				'type' => 'string',
				'default' => ''
			),
			'products' => array(
				'type' => 'array',
				'items' => array(
					'type' => 'number'
				),
				'default' => array(
					
				)
			),
			'marks' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'face' => array(
				'type' => 'object',
				'default' => array(
					'x' => 0.5,
					'y' => 0.07
				)
			)
		),
		'supports' => array(
			'html' => false,
			'reusable' => false,
			'lock' => false
		)
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
