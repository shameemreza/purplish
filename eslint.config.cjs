/**
 * The scripts package's ESLint rules, plus one note: WordPress provides
 * the block editor package at runtime and the build maps it to the
 * wp-block-editor script, so it isn't installed here.
 */
const wordpress = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpress,
	{
		settings: {
			'import/core-modules': [ '@wordpress/block-editor' ],
		},
	},
];
