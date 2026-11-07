/**
 * The editor preview works out the same classes and color as the server.
 */
import { describe, expect, it } from '@jest/globals';
import {
	classesFor,
	colorStyle,
	onColor,
} from '../../assets/src/editor/preview-classes';

const buyButton = {
	prefix: 'buybutton',
	options: { glow: true, sweep: true, glass: true, spectrum: true },
	legacy: {},
};

describe( 'classesFor', () => {
	it( 'adds the prefix and one class per switch that is on', () => {
		expect(
			classesFor( buyButton, { glow: true, glass: true, sweep: false } )
		).toEqual( [
			'purplish-buybutton',
			'purplish-buybutton-glow',
			'purplish-buybutton-glass',
		] );
	} );

	it( 'gives nothing when no option is on', () => {
		expect( classesFor( buyButton, { color: '#7b2cbf' } ) ).toEqual( [] );
		expect( classesFor( buyButton, {} ) ).toEqual( [] );
		expect( classesFor( buyButton, undefined ) ).toEqual( [] );
		expect( classesFor( undefined, { glow: true } ) ).toEqual( [] );
	} );

	it( 'ignores unknown values', () => {
		expect( classesFor( buyButton, { glow: 'yes', eyes: 'two' } ) ).toEqual(
			[]
		);
	} );

	it( 'takes listed values and reads legacy switches', () => {
		const gallery = {
			prefix: 'gallery',
			options: { hover: [ 'tilt', 'zoom' ] },
			legacy: { tilt: [ 'hover', 'tilt' ] },
		};
		expect( classesFor( gallery, { hover: 'zoom' } ) ).toEqual( [
			'purplish-gallery',
			'purplish-gallery-hover-zoom',
		] );
		expect( classesFor( gallery, { tilt: true } ) ).toEqual( [
			'purplish-gallery',
			'purplish-gallery-hover-tilt',
		] );
		expect( classesFor( gallery, { tilt: true, hover: 'zoom' } ) ).toEqual(
			[ 'purplish-gallery', 'purplish-gallery-hover-zoom' ]
		);
		expect( classesFor( gallery, { hover: 'spin' } ) ).toEqual( [] );
	} );
} );

describe( 'onColor', () => {
	it( 'matches the server: white on dark colors, black on light ones', () => {
		expect( onColor( '#7b2cbf' ) ).toBe( '#ffffff' );
		expect( onColor( '#ffeb3b' ) ).toBe( '#000000' );
		expect( onColor( '#000' ) ).toBe( '#ffffff' );
		expect( onColor( '#FFF' ) ).toBe( '#000000' );
		expect( onColor( 'purple' ) ).toBe( '#000000' );
	} );
} );

describe( 'colorStyle', () => {
	it( 'gives both custom properties for a valid color only', () => {
		expect( colorStyle( { color: '#7b2cbf' } ) ).toEqual( {
			'--purplish-effect': '#7b2cbf',
			'--purplish-effect-on': '#ffffff',
		} );
		expect( colorStyle( { color: 'red;background:x' } ) ).toEqual( {} );
		expect( colorStyle( {} ) ).toEqual( {} );
	} );
} );
