/*
 * A section style sets --purplish-theme-ink, -paper and -accent again on
 * its band, so every block's own surface, text and accent colors have to
 * come from those tokens to follow it. The Changing Room's stage stays
 * light in a dark band, so its colors come from the page tokens instead.
 */
import { describe, expect, it } from '@jest/globals';
import { readdirSync, readFileSync, existsSync } from 'fs';
import { join } from 'path';

const DIR = join( __dirname, '../../assets/src/blocks' );

// A block's color tokens: --purplish-<block>-ink, -paper, -base, -accent.
const TOKEN =
	/^\s*(--purplish-[a-z-]+-(?:ink|paper|base|accent)):\s*([^;]+);/gm;

const blocks = readdirSync( DIR ).filter( ( name ) =>
	existsSync( join( DIR, name, 'style.css' ) )
);

/**
 * Each color token a block's stylesheet declares.
 *
 * @param {string} name Block folder.
 * @return {{name: string, value: string}[]} Tokens.
 */
const tokens = ( name ) =>
	[
		...readFileSync( join( DIR, name, 'style.css' ), 'utf8' ).matchAll(
			TOKEN
		),
	].map( ( [ , token, value ] ) => ( { name: token, value: value.trim() } ) );

describe( 'section colors', () => {
	it( 'finds the blocks with their own colors', () => {
		const found = blocks.filter( ( name ) => tokens( name ).length );
		expect( found ).toEqual(
			expect.arrayContaining( [
				'changing-room',
				'runway',
				'scroll-lookbook',
				'showfloor',
				'size-chart',
				'story-deck',
				'wardrobe',
			] )
		);
	} );

	it.each( blocks.filter( ( name ) => 'changing-room' !== name ) )(
		'%s takes its colors from the theme tokens',
		( name ) => {
			for ( const token of tokens( name ) ) {
				expect( token.value ).toMatch(
					/^var\((?:--purplish-effect, var\()?--purplish-theme-(?:ink|paper|accent),/
				);
			}
		}
	);

	it( 'keeps the Changing Room on the page colors', () => {
		const found = tokens( 'changing-room' );
		expect( found.length ).toBeGreaterThan( 0 );
		for ( const token of found ) {
			expect( token.value ).toMatch(
				/^var\(--purplish-page-(?:ink|paper),/
			);
		}
	} );

	it( 'leaves the theme tokens to the theme and its sections', () => {
		for ( const name of blocks ) {
			expect(
				readFileSync( join( DIR, name, 'style.css' ), 'utf8' )
			).not.toMatch( /^\s*--purplish-(?:theme|page)-[a-z]+\s*:/m );
		}
	} );
} );
