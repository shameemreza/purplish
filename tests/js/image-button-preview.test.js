/*
 * The image hover light and the button looks work the same way in the
 * editor as on the front end. A plain comment, since Jest reads the
 * environment from it and ESLint's JSDoc rules don't know the tag.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it } from '@jest/globals';
import {
	EYES_BUTTONS,
	eyesIn,
	glassSelector,
} from '../../assets/src/editor/preview-classes';
import { fitImage, imageLight } from '../../assets/src/shared/hover';

/**
 * Gives an element the layout values jsdom leaves at zero.
 *
 * @param {HTMLElement} element The element.
 * @param {Object}      values  Layout properties, as offsetLeft.
 */
function layout( element, values ) {
	Object.entries( values ).forEach( ( [ name, value ] ) =>
		Object.defineProperty( element, name, { value, configurable: true } )
	);
}

describe( 'imageLight', () => {
	it( 'makes a hidden layer for the tilt, glare and loupe', () => {
		[ 'tilt', 'glare', 'loupe' ].forEach( ( style ) => {
			const light = imageLight( document, style );
			expect( light.className ).toBe( 'purplish-image-light' );
			expect( light.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		} );
	} );

	it( 'makes none for the styles that only move the photo', () => {
		[ 'zoom', 'lift', 'parallax', 'slowzoom', '' ].forEach( ( style ) =>
			expect( imageLight( document, style ) ).toBeNull()
		);
	} );
} );

describe( 'fitImage', () => {
	it( 'puts the light over the photo through a box between them', () => {
		const figure = document.createElement( 'figure' );
		const box = document.createElement( 'div' );
		const img = document.createElement( 'img' );
		img.style.borderTopLeftRadius = '12px';
		box.appendChild( img );
		figure.appendChild( box );
		document.body.appendChild( figure );
		layout( img, {
			offsetLeft: 4,
			offsetTop: 6,
			offsetWidth: 300,
			offsetHeight: 375,
			offsetParent: box,
		} );
		layout( box, { offsetLeft: 20, offsetTop: 10, offsetParent: figure } );

		const light = imageLight( document, 'glare' );
		fitImage( figure, img, light );

		expect( light.parentNode ).toBe( figure );
		expect( figure.style.getPropertyValue( '--purplish-radius' ) ).toBe(
			'12px'
		);
		expect( light.style.left ).toBe( '24px' );
		expect( light.style.top ).toBe( '16px' );
		expect( light.style.width ).toBe( '300px' );
		expect( light.style.height ).toBe( '375px' );
		expect( light.style.borderRadius ).toBe( '12px' );
	} );

	it( 'sets only the corner for styles without a light', () => {
		const figure = document.createElement( 'figure' );
		const img = document.createElement( 'img' );
		img.style.borderTopLeftRadius = '8px';
		figure.appendChild( img );
		fitImage( figure, img, null );
		expect( figure.children ).toHaveLength( 1 );
		expect( figure.style.getPropertyValue( '--purplish-radius' ) ).toBe(
			'8px'
		);
	} );
} );

describe( 'button looks', () => {
	it( 'names the button link when glass is on', () => {
		expect(
			glassSelector( [ 'purplish-button', 'purplish-button-glass' ] )
		).toBe( '.wp-block-button__link' );
		expect(
			glassSelector( [ 'purplish-button', 'purplish-button-glow' ] )
		).toBe( '' );
	} );

	it( 'puts the googly eyes in the button link', () => {
		expect( EYES_BUTTONS.button ).toBe( '.wp-block-button__link' );
		expect(
			eyesIn(
				[ 'purplish-button', 'purplish-button-eyes-two' ],
				'button'
			)
		).toBe( 2 );
	} );
} );
