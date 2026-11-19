/**
 * Reads the effect stylesheets for tests: one part, or every file in the
 * order a page prints them, style.css first, then the parts as index.js
 * imports them.
 */
import { readFileSync } from 'fs';
import { join } from 'path';

const DIR = join( __dirname, '../../assets/src/effects' );

/**
 * One part's stylesheet.
 *
 * @param {string} name Part name, such as `cards`.
 * @return {string} The CSS.
 */
export const effectPart = ( name ) =>
	readFileSync( join( DIR, 'parts', `${ name }.css` ), 'utf8' );

/**
 * Every effect stylesheet, joined in print order.
 *
 * @return {string} The CSS.
 */
export const allEffects = () =>
	[
		...readFileSync( join( DIR, 'index.js' ), 'utf8' ).matchAll(
			/^import '\.\/(.+\.css)';$/gm
		),
	]
		.map( ( [ , file ] ) => readFileSync( join( DIR, file ), 'utf8' ) )
		.join( '\n' );
