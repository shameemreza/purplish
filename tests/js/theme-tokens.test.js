/*
 * A theme sets its corners and its shadow once, in theme.json under
 * settings.custom.purplish, and the checkout, drawer, cart page, card ring
 * and filter bar looks follow.
 */
import { describe, expect, it } from '@jest/globals';
import { readFileSync } from 'fs';
import { join } from 'path';

const RADIUS = '--wp--custom--purplish--radius';
const SHADOW = '--wp--custom--purplish--shadow';

const FOLLOWS =
	/purplish-checkoutpage|purplish-(?:drawer|cartpage)-(?:rows|photos)|purplish-cards-hovercard-ring|purplish-filters-layout-bar/;

// Shapes, not corners: a circle, a pill, a square cut or an inherited corner.
const SHAPES = /^(?:0|50%|999px|inherit)$/;

/**
 * Every rule in a stylesheet, with each declaration.
 *
 * @param {string} file Path from the plugin root.
 * @return {{selector: string, property: string, value: string}[]} Declarations.
 */
const declarations = ( file ) => {
	const css = readFileSync(
		join( __dirname, '../..', file ),
		'utf8'
	).replace( /\/\*[\s\S]*?\*\//g, '' );
	const found = [];
	for ( const [ , selector, body ] of css.matchAll(
		/([^{}]+)\{([^{}]*)\}/g
	) ) {
		for ( const line of body.split( ';' ) ) {
			const at = line.indexOf( ':' );
			if ( at > 0 ) {
				found.push( {
					selector: selector.trim(),
					property: line.slice( 0, at ).trim(),
					value: line
						.slice( at + 1 )
						.replace( '!important', '' )
						.trim(),
				} );
			}
		}
	}
	return found;
};

const all = [
	...declarations( 'assets/src/effects/style.css' ),
	...declarations( 'assets/src/effects/shop/style.css' ),
].filter( ( { selector } ) => FOLLOWS.test( selector ) );

describe( 'theme tokens', () => {
	it( 'reads every corner from the radius token', () => {
		const fixed = all.filter(
			( { property, value } ) =>
				/radius$/.test( property ) &&
				! value.includes( RADIUS ) &&
				! SHAPES.test( value )
		);

		expect( fixed ).toEqual( [] );
	} );

	it( 'keeps today’s corners as the fallback', () => {
		const ring = all.find(
			( { selector, property } ) =>
				selector ===
					'.purplish-cards-hovercard-ring .wc-block-product' &&
				property === 'border-radius'
		);

		expect( ring.value ).toBe( `var(${ RADIUS }, 12px)` );
	} );

	it( 'lets a theme turn the shadow off with none', () => {
		const shadows = all.filter( ( { value } ) => value.includes( SHADOW ) );

		expect( shadows.length ).toBeGreaterThanOrEqual( 6 );
		for ( const { value } of shadows ) {
			// The whole value, so `none` stays a valid box-shadow.
			expect( value.startsWith( `var(${ SHADOW },` ) ).toBe( true );
			expect( value.endsWith( ')' ) ).toBe( true );
		}
	} );

	it( 'rings the picked choice card without the shadow', () => {
		const picked = all.filter(
			( { selector, property } ) =>
				selector.includes( 'choices-cards' ) &&
				selector.includes( 'radio-control__option-checked' ) &&
				property === 'outline'
		);

		expect( picked.map( ( { value } ) => value ) ).toEqual( [
			'1px solid var(--purplish-color)',
		] );
	} );
} );
