/**
 * Where Momentum, Helix and Reel carousels come to rest.
 */
import { describe, expect, it } from '@jest/globals';
import { middleOf, nearest } from '../../assets/src/shared/settle';

describe( 'middleOf', () => {
	it( 'puts the card in the middle of the strip', () => {
		const strip = { clientWidth: 350 };
		expect( middleOf( strip, { offsetLeft: 103, offsetWidth: 145 } ) ).toBe(
			0.5
		);
		expect( middleOf( strip, { offsetLeft: 568, offsetWidth: 145 } ) ).toBe(
			465.5
		);
	} );

	it( 'works right to left, where cards sit at negative offsets', () => {
		const strip = { clientWidth: 350 };
		expect(
			middleOf( strip, { offsetLeft: -362, offsetWidth: 145 } )
		).toBe( -464.5 );
	} );
} );

describe( 'nearest', () => {
	it( 'picks the closest card either way', () => {
		const places = [ 0, 155, 310, 465 ];
		expect( nearest( places, 70 ) ).toBe( 0 );
		expect( nearest( places, 80 ) ).toBe( 155 );
		expect( nearest( places, 1000 ) ).toBe( 465 );
		expect( nearest( [ 0, -155, -310 ], -200 ) ).toBe( -155 );
	} );

	it( 'has nothing to pick without cards', () => {
		expect( nearest( [], 40 ) ).toBeNull();
	} );
} );
