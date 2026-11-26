/**
 * The scripts package's webpack config with the four 1.0 bundles and the
 * filters, grid, free shipping meter, load more, photo swap, sticky Add to Cart bar, checkout bar total, matching extras, picked color name, stock dot, kept column, instalment, trade-in and instant search scripts kept by name, plus the scripts each block lists in its
 * block.json. A new block is a new folder in assets/src/blocks and nothing
 * else here. Each file in assets/src/effects/parts builds to its own
 * stylesheet, `style-effects-<part>.css`, so a page loads only the effects
 * its blocks use.
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
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
			cacheGroups: {
				...defaultConfig.optimization.splitChunks.cacheGroups,
				effectParts: {
					type: 'css/mini-extract',
					test: /[\\/]effects[\\/]parts[\\/][a-z]+\.css$/,
					chunks: 'all',
					enforce: true,
					priority: 10,
					// An extracted CSS module has no `resource`; its identifier
					// ends with the source file's path.
					name: ( module ) =>
						`style-effects-${
							/[\\/]parts[\\/]([a-z]+)\.css/.exec(
								module.identifier()
							)[ 1 ]
						}`,
				},
			},
		},
	},
	entry: () => ( {
		admin: './assets/src/admin/index.js',
		bar: './assets/src/bar/index.js',
		editor: './assets/src/editor/index.js',
		frontend: './assets/src/frontend/index.js',
		effects: './assets/src/effects/index.js',
		filters: './assets/src/filters/index.js',
		grid: './assets/src/grid/index.js',
		meter: './assets/src/meter/index.js',
		more: './assets/src/more/index.js',
		results: './assets/src/results/index.js',
		swap: './assets/src/swap/index.js',
		total: './assets/src/total/index.js',
		extras: './assets/src/extras/index.js',
		picked: './assets/src/picked/index.js',
		stock: './assets/src/stock/index.js',
		keep: './assets/src/keep/index.js',
		instalments: './assets/src/instalments/index.js',
		'instalments-admin': './assets/src/instalments-admin/index.js',
		'trade-in': './assets/src/trade-in/index.js',
		'trade-in-admin': './assets/src/trade-in-admin/index.js',
		search: './assets/src/search/view.js',
		...( hasBlocks() ? defaultConfig.entry() : {} ),
	} ),
};
