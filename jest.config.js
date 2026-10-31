/**
 * Jest for the front end's matching rule. The scripts package no longer
 * supplies Jest defaults, so they live here.
 */
module.exports = {
	roots: [ '<rootDir>/tests/js' ],
	testEnvironment: 'node',
	transform: {
		'^.+\\.js$': [
			'babel-jest',
			{ presets: [ '@wordpress/babel-preset-default' ] },
		],
	},
};
