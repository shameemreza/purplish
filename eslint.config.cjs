/**
 * The scripts package's ESLint rules, plus one note: WordPress provides
 * the block editor and blocks packages at runtime and the build maps them
 * to the wp-block-editor and wp-blocks scripts, so they aren't installed
 * here.
 */
const wordpress = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpress,
	{
		settings: {
			'import/core-modules': [
				'@wordpress/block-editor',
				'@wordpress/blocks',
				'@wordpress/data',
				'@wordpress/element',
			],
		},
	},
];
