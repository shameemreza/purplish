/**
 * Speed rule 2: a block's front script stays under 5 KB gzipped. Fails when
 * a `build/blocks/<block>/view.js`, or a script that loads only with one
 * option, is over the limit, and prints every front
 * script and stylesheet with its gzipped size so a pull request can quote
 * the numbers. Run after `npm run build`.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const zlib = require( 'zlib' );

const LIMIT = 5 * 1024;
const BUILD = path.join( __dirname, '..', 'build' );

const gzipped = ( file ) =>
	zlib.gzipSync( fs.readFileSync( file ), { level: 9 } ).length;

const blocks = fs.existsSync( path.join( BUILD, 'blocks' ) )
	? fs
			.readdirSync( path.join( BUILD, 'blocks' ) )
			.map( ( block ) => path.join( 'blocks', block, 'view.js' ) )
			.filter( ( file ) => fs.existsSync( path.join( BUILD, file ) ) )
	: [];
const options = [ 'filters.js', 'grid.js', 'more.js' ].filter( ( file ) =>
	fs.existsSync( path.join( BUILD, file ) )
);
const shared = [
	'effects.js',
	'frontend.js',
	'style-effects.css',
	'style-frontend.css',
].filter( ( file ) => fs.existsSync( path.join( BUILD, file ) ) );

if ( ! blocks.length && ! shared.length ) {
	process.stderr.write( 'No build files found. Run npm run build first.\n' );
	process.exit( 1 );
}

let over = 0;
const row = ( file, limited ) => {
	const size = gzipped( path.join( BUILD, file ) );
	const fails = limited && size > LIMIT;
	over += fails ? 1 : 0;
	const note = limited ? ` (limit ${ LIMIT })` : '';
	process.stdout.write(
		`${ fails ? 'OVER' : 'ok  ' }  ${ String( size ).padStart(
			6
		) } B  ${ file }${ note }\n`
	);
};

process.stdout.write( 'Block front scripts, gzipped:\n' );
blocks.forEach( ( file ) => row( file, true ) );
process.stdout.write( '\nOption front scripts, gzipped:\n' );
options.forEach( ( file ) => row( file, true ) );
process.stdout.write( '\nShared front files, gzipped:\n' );
shared.forEach( ( file ) => row( file, false ) );

if ( over ) {
	process.stderr.write(
		`\n${ over } front script(s) over 5 KB gzipped. Move motion to CSS or trim the script.\n`
	);
	process.exit( 1 );
}
