/**
 * The instalment line's text for the picked options.
 */
import { describe, expect, it } from '@jest/globals';
import { pickKey } from '../../assets/src/instalments/pick';

const rows = [
	[ { attribute_color: 'Blue', attribute_size: 'S' }, '50' ],
	[ { attribute_color: 'Blue', attribute_size: 'M' }, '50' ],
	[ { attribute_color: 'Red', attribute_size: 'S' }, '120' ],
	[ { attribute_color: 'Red', attribute_size: 'M' }, '' ],
	[ { attribute_color: 'Green', attribute_size: '' }, '80' ],
];

describe( 'pickKey', () => {
	it( 'keeps the "from" text with nothing picked', () => {
		expect( pickKey( rows, {} ) ).toBeNull();
		expect(
			pickKey( rows, { attribute_color: '', attribute_size: '' } )
		).toBeNull();
	} );

	it( 'shows the text every fitting variation shares', () => {
		expect( pickKey( rows, { attribute_color: 'Blue' } ) ).toBe( '50' );
		expect(
			pickKey( rows, { attribute_color: 'Red', attribute_size: 'S' } )
		).toBe( '120' );
	} );

	it( 'keeps "from" while the fitting variations differ', () => {
		expect( pickKey( rows, { attribute_color: 'Red' } ) ).toBeNull();
		expect( pickKey( rows, { attribute_size: 'S' } ) ).toBeNull();
	} );

	it( 'hides the line for a variation without a text', () => {
		expect(
			pickKey( rows, { attribute_color: 'Red', attribute_size: 'M' } )
		).toBe( '' );
	} );

	it( 'treats an empty attribute as any', () => {
		expect(
			pickKey( rows, { attribute_color: 'Green', attribute_size: 'M' } )
		).toBe( '80' );
	} );

	it( 'hides the line when nothing fits', () => {
		expect( pickKey( rows, { attribute_color: 'Pink' } ) ).toBe( '' );
	} );
} );
