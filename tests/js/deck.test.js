/**
 * The Story Deck's order, throws and tosses.
 */
import { describe, expect, it } from '@jest/globals';
import {
	FLICK_DISTANCE,
	FLICK_SPEED,
	MAX_LEAN,
	THROW_SHARE,
	depthOf,
	isThrow,
	lean,
	speedOf,
	toss,
	wrap,
} from '../../assets/src/blocks/story-deck/deck';

describe( 'wrap', () => {
	it( 'leads from the last card back to the first, both ways', () => {
		expect( wrap( 5, 5 ) ).toBe( 0 );
		expect( wrap( 6, 5 ) ).toBe( 1 );
		expect( wrap( -1, 5 ) ).toBe( 4 );
		expect( wrap( -6, 5 ) ).toBe( 4 );
		expect( wrap( 2, 5 ) ).toBe( 2 );
	} );

	it( 'is 0 for an empty deck', () => {
		expect( wrap( 3, 0 ) ).toBe( 0 );
	} );
} );

describe( 'next and previous', () => {
	it( 'walks the whole deck and comes back to the start', () => {
		let top = 0;
		const seen = [];
		for ( let i = 0; i < 5; i++ ) {
			top = wrap( top + 1, 5 );
			seen.push( top );
		}
		expect( seen ).toEqual( [ 1, 2, 3, 4, 0 ] );

		const back = [];
		for ( let i = 0; i < 5; i++ ) {
			top = wrap( top - 1, 5 );
			back.push( top );
		}
		expect( back ).toEqual( [ 4, 3, 2, 1, 0 ] );
	} );
} );

describe( 'depthOf', () => {
	it( 'puts the top card first and the one before it last', () => {
		expect( [ 0, 1, 2, 3, 4 ].map( ( i ) => depthOf( i, 0, 5 ) ) ).toEqual(
			[ 0, 1, 2, 3, 4 ]
		);
		expect( [ 0, 1, 2, 3, 4 ].map( ( i ) => depthOf( i, 3, 5 ) ) ).toEqual(
			[ 2, 3, 4, 0, 1 ]
		);
	} );

	it( 'gives every card its own depth', () => {
		const depths = [ 0, 1, 2, 3 ].map( ( i ) => depthOf( i, 2, 4 ) );
		expect( new Set( depths ).size ).toBe( 4 );
	} );
} );

describe( 'isThrow', () => {
	const width = 300;

	it( 'throws a card dragged far enough either way', () => {
		expect( isThrow( width * THROW_SHARE, 0, width ) ).toBe( true );
		expect( isThrow( -width * THROW_SHARE - 1, 0, width ) ).toBe( true );
	} );

	it( 'snaps back after a short, slow drag', () => {
		expect( isThrow( 40, 0.1, width ) ).toBe( false );
		expect( isThrow( 0, 0, width ) ).toBe( false );
	} );

	it( 'throws on a quick flick in the drag direction', () => {
		expect( isThrow( FLICK_DISTANCE, FLICK_SPEED, width ) ).toBe( true );
		expect( isThrow( -30, -1.2, width ) ).toBe( true );
	} );

	it( 'snaps back on a flick against the drag, or a tap', () => {
		expect( isThrow( 30, -1.2, width ) ).toBe( false );
		expect( isThrow( FLICK_DISTANCE - 1, 2, width ) ).toBe( false );
	} );
} );

describe( 'lean', () => {
	it( 'leans with the drag and stops at the most', () => {
		expect( lean( 0 ) ).toBe( 0 );
		expect( lean( 100 ) ).toBeGreaterThan( 0 );
		expect( lean( -100 ) ).toBeLessThan( 0 );
		expect( lean( 1e5 ) ).toBe( MAX_LEAN );
		expect( lean( -1e5 ) ).toBe( -MAX_LEAN );
	} );
} );

describe( 'speedOf', () => {
	it( 'reads the speed over the last moves', () => {
		expect(
			speedOf( [
				{ x: 0, t: 0 },
				{ x: 50, t: 200 },
				{ x: 100, t: 250 },
				{ x: 180, t: 290 },
			] )
		).toBeCloseTo( 130 / 90 );
	} );

	it( 'is 0 without movement over time', () => {
		expect( speedOf( [] ) ).toBe( 0 );
		expect( speedOf( [ { x: 10, t: 5 } ] ) ).toBe( 0 );
		expect(
			speedOf( [
				{ x: 10, t: 5 },
				{ x: 40, t: 5 },
			] )
		).toBe( 0 );
	} );
} );

describe( 'toss', () => {
	it( 'flies further the faster it was thrown, toward the throw', () => {
		const slow = toss( { x: -100, y: 0 }, -1, 2000, 0 );
		const fast = toss( { x: -100, y: 0 }, -1, 2000, 2 );
		expect( slow.x ).toBeLessThan( -100 );
		expect( fast.x ).toBeLessThan( slow.x );
		expect( slow.turn ).toBe( -MAX_LEAN );
		expect( toss( { x: 0, y: 0 }, 1, 2000 ).turn ).toBe( MAX_LEAN );
	} );

	it( 'never flies past the room on that side', () => {
		expect( toss( { x: 0, y: 0 }, 1, 120, 5 ).x ).toBe( 120 );
		expect( toss( { x: 0, y: 0 }, -1, -30, 5 ).x ).toBe( -0 );
	} );

	it( 'never pulls a card back toward the deck', () => {
		expect( toss( { x: 200, y: 10 }, 1, 150 ).x ).toBe( 200 );
	} );

	it( 'drops a little as it goes', () => {
		expect( toss( { x: 0, y: 10 }, 1, 500 ).y ).toBeGreaterThan( 10 );
	} );
} );
