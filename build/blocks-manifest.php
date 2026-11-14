<?php
// This file is generated. Do not modify it manually.
return array(
	'changing-room' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/changing-room',
		'version' => '1.3.0',
		'title' => 'Changing Room',
		'category' => 'purplish',
		'description' => 'A model stands between two rings of pieces. Visitors turn the rings, drag a piece onto the model or open it, and add the outfit to the cart.',
		'keywords' => array(
			'try on',
			'outfit',
			'mix and match',
			'styling',
			'products'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			),
			'heading' => array(
				'type' => 'string',
				'enum' => array(
					'off',
					'on'
				),
				'default' => 'off'
			),
			'headingText' => array(
				'type' => 'string',
				'default' => ''
			),
			'hint' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'tags' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'outfit' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'far' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'drawer' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'specs' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'about' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'wear' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'drag' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'wheel' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'swipe' => array(
				'type' => 'string',
				'enum' => array(
					'on',
					'off'
				),
				'default' => 'on'
			),
			'notice' => array(
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
			),
			'look' => array(
				'type' => 'string',
				'enum' => array(
					'theme',
					'glow',
					'sweep',
					'glass',
					'spectrum'
				),
				'default' => 'theme'
			),
			'color' => array(
				'type' => 'string',
				'enum' => array(
					'last',
					'top',
					'bottom',
					'off'
				),
				'default' => 'last'
			),
			'area' => array(
				'type' => 'string',
				'enum' => array(
					'accents',
					'stage',
					'page'
				),
				'default' => 'accents'
			),
			'swap' => array(
				'type' => 'string',
				'enum' => array(
					'fade',
					'reveal',
					'none'
				),
				'default' => 'fade'
			),
			'topLabel' => array(
				'type' => 'string',
				'default' => ''
			),
			'bottomLabel' => array(
				'type' => 'string',
				'default' => ''
			),
			'photos' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		),
		'allowedBlocks' => array(
			'purplish/changing-room-piece'
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
	'changing-room-piece' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/changing-room-piece',
		'version' => '1.3.0',
		'title' => 'Piece',
		'category' => 'purplish',
		'parent' => array(
			'purplish/changing-room'
		),
		'description' => 'One piece in the Changing Room: a top or a bottom, its product and a flat cut-out image.',
		'textdomain' => 'purplish',
		'attributes' => array(
			'slot' => array(
				'type' => 'string',
				'enum' => array(
					'top',
					'bottom'
				),
				'default' => 'top'
			),
			'product' => array(
				'type' => 'number',
				'default' => 0
			),
			'id' => array(
				'type' => 'number',
				'default' => 0
			),
			'url' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false,
			'reusable' => false,
			'lock' => false
		)
	),
	'color-dots' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/color-dots',
		'version' => '1.3.0',
		'title' => 'Color Dots',
		'category' => 'purplish',
		'description' => 'Shows the colors a product comes in, as small dots or swatches on product cards. Colors come from the color attribute or the Purplish page color of each variation. A dot can swap the card photo to that color.',
		'keywords' => array(
			'swatches',
			'colors',
			'variations',
			'colorways'
		),
		'textdomain' => 'purplish',
		'ancestor' => array(
			'woocommerce/product-template',
			'core/post-template',
			'woocommerce/single-product'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'look' => array(
				'type' => 'string',
				'enum' => array(
					'dots',
					'swatches'
				),
				'default' => 'dots'
			),
			'limit' => array(
				'type' => 'number',
				'default' => 4
			),
			'single' => array(
				'type' => 'boolean',
				'default' => false
			),
			'swap' => array(
				'type' => 'string',
				'enum' => array(
					'off',
					'hover',
					'tap'
				),
				'default' => 'off'
			),
			'motion' => array(
				'type' => 'string',
				'enum' => array(
					'fade',
					'wipe',
					'slide'
				),
				'default' => 'fade'
			)
		),
		'supports' => array(
			'html' => false,
			'reusable' => false,
			'color' => array(
				'background' => false,
				'text' => true
			),
			'typography' => array(
				'fontSize' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'look' => 'dots'
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css'
	),
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
	'shelf-tag' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/shelf-tag',
		'version' => '1.3.0',
		'title' => 'Shelf Tag',
		'category' => 'purplish',
		'description' => 'A small tag on product cards, such as New arrival or Best seller. It shows only on the products its rule matches, and sits on the photo inside the Product Image block.',
		'keywords' => array(
			'badge',
			'label',
			'new',
			'best seller',
			'featured'
		),
		'textdomain' => 'purplish',
		'ancestor' => array(
			'woocommerce/product-template',
			'core/post-template',
			'woocommerce/single-product'
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'rule' => array(
				'type' => 'string',
				'enum' => array(
					'bestseller',
					'new',
					'featured'
				),
				'default' => 'bestseller'
			),
			'top' => array(
				'type' => 'number',
				'default' => 8
			),
			'days' => array(
				'type' => 'number',
				'default' => 30
			),
			'label' => array(
				'type' => 'string',
				'default' => ''
			),
			'look' => array(
				'type' => 'string',
				'enum' => array(
					'chip',
					'pill',
					'glass'
				),
				'default' => 'chip'
			),
			'corner' => array(
				'type' => 'string',
				'enum' => array(
					'top-right',
					'top-left',
					'bottom-left',
					'bottom-right'
				),
				'default' => 'top-right'
			),
			'shine' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'html' => false,
			'reusable' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalFontWeight' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextTransform' => true
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true
			),
			'shadow' => true,
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'rule' => 'bestseller'
			)
		),
		'editorScript' => 'file:./index.js',
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
	),
	'wardrobe' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'purplish/wardrobe',
		'version' => '1.3.0',
		'title' => 'Wardrobe',
		'category' => 'purplish',
		'parent' => array(
			'core/navigation'
		),
		'description' => 'A menu item that opens a big panel of blocks: category links, photo tiles, a look. Goes inside the Navigation block.',
		'keywords' => array(
			'mega menu',
			'dropdown',
			'menu panel',
			'submenu',
			'navigation'
		),
		'textdomain' => 'purplish',
		'attributes' => array(
			'label' => array(
				'type' => 'string',
				'role' => 'content',
				'default' => ''
			),
			'url' => array(
				'type' => 'string',
				'default' => ''
			),
			'width' => array(
				'type' => 'string',
				'enum' => array(
					'wide',
					'content',
					'full'
				),
				'default' => 'wide'
			),
			'look' => array(
				'type' => 'string',
				'enum' => array(
					'card',
					'flat'
				),
				'default' => 'card'
			)
		),
		'usesContext' => array(
			'submenuVisibility',
			'openSubmenusOnClick',
			'showSubmenuIcon'
		),
		'supports' => array(
			'html' => false,
			'reusable' => false
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	)
);
