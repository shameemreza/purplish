/**
 * The scripts package's ESLint rules, plus one note: WordPress provides
 * the block editor, blocks, data and api-fetch packages at runtime and
 * the build maps them to their wp- scripts, so they aren't installed
 * here.
 */
const wordpress = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpress,
	{
		settings: {
			'import/core-modules': [
				'@wordpress/api-fetch',
				'@wordpress/block-editor',
				'@wordpress/blocks',
				'@wordpress/data',
				'@wordpress/element',
			],
		},
	},
];
