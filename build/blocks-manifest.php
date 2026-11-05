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
	)
);
