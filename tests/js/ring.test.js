/**
 * Where a Changing Room piece stands in its ring.
 */
import { describe, expect, it } from '@jest/globals';
import { offset, spot, vars } from '../../assets/src/blocks/changing-room/ring';

describe( 'offset', () => {
	it( 'counts steps from the front the short way round', () => {
		expect( offset( 0, 0, 6 ) ).toBe( 0 );
		expect( offset( 1, 0, 6 ) ).toBe( 1 );
		expect( offset( 5, 0, 6 ) ).toBe( -1 );
		expect( offset( 0, 4, 6 ) ).toBe( 2 );
	} );

	it( 'follows a ring part way between pieces', () => {
		expect( offset( 1, 0.5, 6 ) ).toBeCloseTo( 0.5 );
		expect( offset( 5, 0.5, 6 ) ).toBeCloseTo( -1.5 );
	} );
} );

describe( 'spot', () => {
	it( 'hides the worn piece on the model', () => {
		const at = spot( 0, 6, 'top', true );
		expect( at.o ).toBe( 0 );
		expect( at.x ).toBe( 0 );
		expect( at.y ).toBeCloseTo(
			0.35 + 0.09 * ( Math.cos( Math.PI / 6 ) - 1 )
		);
		expect( at.y ).toBeLessThan( spot( 1, 6, 'top', true ).y );
		expect( at.z ).toBe( 20 );
	} );

	it( 'stands the neighbours one step out at full size', () => {
		const right = spot( 1, 6, 'top', true );
		const left = spot( -1, 6, 'bottom', true );
		expect( right.x ).toBeCloseTo( 1 );
		expect( right.s ).toBeCloseTo( 1 );
		expect( right.o ).toBe( 1 );
		expect( right.r ).toBe( 3 );
		expect( left.x ).toBeCloseTo( -1 );
		expect( left.y ).toBeCloseTo( 0.7 );
		expect( left.r ).toBe( -3 );
	} );

	it( 'fades far pieces, and hides them when far pieces are off', () => {
		expect( spot( 2, 6, 'top', true ).o ).toBeCloseTo( 0.55 );
		expect( spot( 2, 6, 'top', true ).s ).toBeLessThan( 1 );
		expect( spot( 3, 6, 'top', true ).o ).toBe( 0 );
		expect( spot( 2, 6, 'top', false ).o ).toBe( 0 );
	} );
} );

describe( 'vars', () => {
	it( 'writes the custom properties the stylesheet reads', () => {
		expect( vars( spot( 1, 6, 'top', true ) ) ).toEqual( {
			'--x': '1.0000',
			'--y': '0.3500',
			'--s': '1.0000',
			'--r': '3.00deg',
		} );
	} );
} );
