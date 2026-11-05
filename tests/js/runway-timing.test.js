/**
 * The Runway's places, rail, easing and wheel nudge.
 */
import { describe, expect, it } from '@jest/globals';
import {
	NUDGE,
	PLACES,
	blend,
	ease,
	nearest,
	offset,
	railAt,
	settle,
	slot,
	wrap,
} from '../../assets/src/blocks/runway/timing';

describe( 'wrap', () => {
	it( 'leads from the last look back to the first, both ways', () => {
		expect( wrap( 6, 6 ) ).toBe( 0 );
		expect( wrap( 7.5, 6 ) ).toBe( 1.5 );
		expect( wrap( -1, 6 ) ).toBe( 5 );
		expect( wrap( -7, 6 ) ).toBe( 5 );
	} );

	it( 'is 0 with nothing to wrap', () => {
		expect( wrap( 3, 0 ) ).toBe( 0 );
	} );
} );

describe( 'offset', () => {
	it( 'puts the look at the position at the front and the rest behind it', () => {
		expect( offset( 2, 2, 6 ) ).toBe( 0 );
		expect( offset( 3, 2, 6 ) ).toBe( 1 );
		expect( offset( 1, 2, 6 ) ).toBe( 5 );
	} );

	it( 'wraps round, so the first look stands behind the last', () => {
		expect( offset( 0, 5, 6 ) ).toBe( 1 );
		expect( offset( 0, 11, 6 ) ).toBe( 1 );
		expect( offset( 5, -1, 6 ) ).toBe( 0 );
	} );

	it( 'counts the front look as leaving once the line moves past it', () => {
		expect( offset( 2, 2.25, 6 ) ).toBeCloseTo( -0.25 );
		expect( offset( 2, 2.9, 6 ) ).toBeCloseTo( -0.9 );
		expect( offset( 2, 3, 6 ) ).toBe( 5 );
	} );
} );

describe( 'blend', () => {
	it( 'gives each place its own values', () => {
		PLACES.forEach( ( place, index ) => {
			expect( blend( index - 1 ) ).toEqual( place );
		} );
	} );

	it( 'blends halfway between two places', () => {
		const half = blend( 0.5 );
		Object.keys( PLACES[ 1 ] ).forEach( ( key ) => {
			expect( half[ key ] ).toBeCloseTo(
				( PLACES[ 1 ][ key ] + PLACES[ 2 ][ key ] ) / 2
			);
		} );
	} );

	it( 'stays at the last place past the back, and the first past the front', () => {
		expect( blend( 9 ) ).toEqual( PLACES[ PLACES.length - 1 ] );
		expect( blend( -3 ) ).toEqual( PLACES[ 0 ] );
	} );

	it( 'grows smaller, blurrier and fainter toward the back', () => {
		for ( let index = 2; index < PLACES.length; index++ ) {
			expect( PLACES[ index ].height ).toBeLessThan(
				PLACES[ index - 1 ].height
			);
			expect( PLACES[ index ].blur ).toBeGreaterThanOrEqual(
				PLACES[ index - 1 ].blur
			);
			expect( PLACES[ index ].opacity ).toBeLessThanOrEqual(
				PLACES[ index - 1 ].opacity
			);
		}
		expect( PLACES[ 1 ].blur ).toBe( 0 );
		expect( PLACES[ PLACES.length - 1 ].opacity ).toBe( 0 );
	} );
} );

describe( 'railAt', () => {
	// Two products with three photos each, then one with a single photo.
	const cardOf = [ 0, 0, 0, 1, 1, 1, 2 ];

	it( 'keeps the rail still between looks of the same product', () => {
		expect( railAt( 0, cardOf, 3 ) ).toBe( 0 );
		expect( railAt( 0.5, cardOf, 3 ) ).toBe( 0 );
		expect( railAt( 1.7, cardOf, 3 ) ).toBe( 0 );
		expect( railAt( 4, cardOf, 3 ) ).toBe( 1 );
	} );

	it( 'moves to the next card on the way to its first look', () => {
		expect( railAt( 2.5, cardOf, 3 ) ).toBeCloseTo( 0.5 );
		expect( railAt( 3, cardOf, 3 ) ).toBe( 1 );
		expect( railAt( 5.25, cardOf, 3 ) ).toBeCloseTo( 1.25 );
	} );

	it( 'wraps forward from the last card to the first, and back', () => {
		expect( railAt( 6.5, cardOf, 3 ) ).toBeCloseTo( 2.5 );
		expect( railAt( 7, cardOf, 3 ) ).toBe( 3 );
		expect( railAt( 10, cardOf, 3 ) ).toBe( 4 );
		expect( railAt( -1, cardOf, 3 ) ).toBe( -1 );
		expect( railAt( -0.5, cardOf, 3 ) ).toBeCloseTo( -0.5 );
	} );

	it( 'is 0 with no looks', () => {
		expect( railAt( 2, [], 3 ) ).toBe( 0 );
	} );
} );

describe( 'slot', () => {
	it( 'puts the current card at the start and the next ones after it', () => {
		expect( slot( 2, 2, 200, 5 ) ).toBe( 0 );
		expect( slot( 3, 2, 200, 5 ) ).toBe( 200 );
		expect( slot( 4, 2, 200, 5 ) ).toBe( 400 );
	} );

	it( 'keeps a leaving card just before the start, then wraps it to the end', () => {
		expect( slot( 1, 1.5, 200, 5 ) ).toBe( -100 );
		expect( slot( 1, 2, 200, 5 ) ).toBe( 800 );
		expect( slot( 0, 7, 200, 5 ) ).toBe( 600 );
	} );
} );

describe( 'ease', () => {
	it( 'moves part of the way each frame and lands on the target', () => {
		let position = 0;
		let frames = 0;
		while ( position !== 1 && frames < 200 ) {
			const next = ease( position, 1, 16 );
			expect( next ).toBeGreaterThan( position );
			expect( next ).toBeLessThanOrEqual( 1 );
			position = next;
			frames++;
		}
		expect( position ).toBe( 1 );
		expect( frames ).toBeGreaterThan( 20 );
		expect( frames ).toBeLessThan( 120 );
	} );

	it( 'stays put at the target', () => {
		expect( ease( 3, 3, 16 ) ).toBe( 3 );
	} );
} );

describe( 'settle', () => {
	it( 'turns a short nudge into one look that way', () => {
		expect( settle( 2 + NUDGE * 2, 2 ) ).toBe( 3 );
		expect( settle( 2 - NUDGE * 2, 2 ) ).toBe( 1 );
	} );

	it( 'springs back from a tiny nudge', () => {
		expect( settle( 2 + NUDGE / 2, 2 ) ).toBe( 2 );
	} );

	it( 'lands on the nearest look after a longer move', () => {
		expect( settle( 4.3, 2 ) ).toBe( 4 );
		expect( settle( 4.6, 2 ) ).toBe( 5 );
		expect( settle( 0.4, 2 ) ).toBe( 0 );
	} );
} );

describe( 'nearest', () => {
	it( 'takes the short way round to a look', () => {
		expect( nearest( 3, 1, 6 ) ).toBe( 3 );
		expect( nearest( 5, 1, 6 ) ).toBe( -1 );
		expect( nearest( 0, 5, 6 ) ).toBe( 6 );
		expect( nearest( 2, 8, 6 ) ).toBe( 8 );
	} );
} );
