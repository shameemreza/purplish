/**
 * The scripts package's webpack config with the four 1.0 bundles and the
 * filters and grid scripts kept by name, plus the scripts each block lists in its
 * block.json. A new block is a new folder in assets/src/blocks and nothing
 * else here.
 */
const { existsSync, readdirSync } = require( 'fs' );
const { join } = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const blocksPath = join( __dirname, 'assets/src/blocks' );

/**
 * Whether any block folder has a block.json. With none, the default entry
 * lookup falls back to assets/src/index.js and warns that it's missing.
 *
 * @return {boolean} True when there is at least one block.
 */
const hasBlocks = () =>
	existsSync( blocksPath ) &&
	readdirSync( blocksPath ).some( ( slug ) =>
		existsSync( join( blocksPath, slug, 'block.json' ) )
	);

module.exports = {
	...defaultConfig,
	entry: () => ( {
		admin: './assets/src/admin/index.js',
		editor: './assets/src/editor/index.js',
		frontend: './assets/src/frontend/index.js',
		effects: './assets/src/effects/index.js',
		filters: './assets/src/filters/index.js',
		grid: './assets/src/grid/index.js',
		...( hasBlocks() ? defaultConfig.entry() : {} ),
	} ),
};
