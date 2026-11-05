/**
 * The Scroll Lookbook's timeline, camera path, cards, clips and layout.
 */
import { describe, expect, it } from '@jest/globals';
import {
	END,
	INTRO,
	LENGTHS,
	REWIND,
	activeStop,
	cardAt,
	clipAt,
	ease,
	layout,
	openingStop,
	pathAt,
	runLength,
	side,
	stamp,
	stopAt,
	timeline,
} from '../../assets/src/blocks/scroll-lookbook/timing';

describe( 'runLength', () => {
	it( 'adds the opening, each stop, the rewind and the closing line', () => {
		expect( runLength( 3, 'medium' ) ).toBe(
			INTRO + 3 * LENGTHS.medium + REWIND + END
		);
		expect( runLength( 3, 'long' ) ).toBeGreaterThan(
			runLength( 3, 'short' )
		);
	} );

	it( 'falls back to medium for an unknown length', () => {
		expect( runLength( 2, 'endless' ) ).toBe( runLength( 2, 'medium' ) );
	} );
} );

describe( 'timeline', () => {
	const time = timeline( 3, 'medium' );

	it( 'has one hold per stop, in order, before the rewind', () => {
		expect( time.holds ).toHaveLength( 3 );
		time.holds.forEach( ( [ start, end ], index ) => {
			expect( end ).toBeGreaterThan( start );
			if ( index ) {
				expect( start ).toBeGreaterThan( time.holds[ index - 1 ][ 1 ] );
			}
		} );
		expect( time.forwardEnd ).toBeCloseTo( time.holds[ 2 ][ 1 ] );
		expect( time.forwardEnd ).toBeLessThan( 1 );
	} );

	it( 'ends the path at the opening shot again', () => {
		expect( time.beats[ 0 ] ).toEqual( [ 0, 0 ] );
		expect( time.beats[ time.beats.length - 1 ] ).toEqual( [ 1, 6 ] );
	} );

	it( 'brings the closing line in at the end', () => {
		expect( time.outroIn[ 0 ] ).toBeGreaterThan( time.forwardEnd );
		expect( time.outroIn[ 1 ] ).toBeLessThan( 1 );
	} );
} );

describe( 'pathAt', () => {
	const time = timeline( 3, 'medium' );

	it( 'stands on the opening shot until the scroll starts the first move', () => {
		expect( pathAt( 0, time.beats ) ).toBe( 0 );
		expect( pathAt( time.beats[ 1 ][ 0 ], time.beats ) ).toBe( 0 );
	} );

	it( 'holds still on each stop', () => {
		time.holds.forEach( ( [ start, end ], index ) => {
			expect( pathAt( start, time.beats ) ).toBeCloseTo( index + 1 );
			expect( pathAt( ( start + end ) / 2, time.beats ) ).toBeCloseTo(
				index + 1
			);
		} );
	} );

	it( 'moves forward, then rewinds to the opening shot', () => {
		let last = -1;
		for ( let u = 0; u <= time.forwardEnd; u += 0.01 ) {
			const p = pathAt( u, time.beats );
			expect( p ).toBeGreaterThanOrEqual( last - 1e-9 );
			last = p;
		}
		expect( pathAt( 1, time.beats ) ).toBe( 6 );
	} );
} );

describe( 'cardAt and activeStop', () => {
	const time = timeline( 3, 'medium' );

	it( 'is out before the lead and fully in through the middle of a hold', () => {
		const hold = time.holds[ 1 ];
		expect( cardAt( hold[ 0 ] - time.lead - 0.001, hold, time ) ).toBe( 0 );
		expect( cardAt( stopAt( 1, time ), hold, time ) ).toBe( 1 );
		expect( cardAt( hold[ 1 ], hold, time ) ).toBe( 0 );
	} );

	it( 'comes in as the camera lands and leaves before it moves on', () => {
		const hold = time.holds[ 0 ];
		const coming = cardAt( hold[ 0 ], hold, time );
		const going = cardAt( hold[ 1 ] - time.settle / 2, hold, time );
		expect( coming ).toBeGreaterThan( 0 );
		expect( coming ).toBeLessThan( 1 );
		expect( going ).toBeGreaterThan( 0 );
		expect( going ).toBeLessThan( 1 );
	} );

	it( 'names the stop whose card is in, and -1 between stops', () => {
		expect( activeStop( stopAt( 2, time ), time ) ).toBe( 2 );
		expect( activeStop( 0, time ) ).toBe( -1 );
		expect( activeStop( 0.999, time ) ).toBe( -1 );
	} );

	it( 'keeps stopAt inside the list', () => {
		expect( stopAt( 9, time ) ).toBe( stopAt( 2, time ) );
		expect( stopAt( -1, time ) ).toBe( stopAt( 0, time ) );
	} );
} );

describe( 'clipAt', () => {
	const time = timeline( 3, 'medium' );

	it( 'plays each stop clip forward from leaving the stop before', () => {
		const [ from, to ] = time.spans[ 1 ];
		expect( clipAt( 2, from, time ) ).toBe( 0 );
		expect( clipAt( 2, ( from + to ) / 2, time ) ).toBeCloseTo( 0.5 );
		expect( clipAt( 2, to, time ) ).toBe( 1 );
	} );

	it( 'carries the opening clip on from where its play stopped', () => {
		expect( clipAt( 0, 0, time, 0.4 ) ).toBe( 0.4 );
		expect( clipAt( 0, time.holds[ 0 ][ 0 ], time, 0.4 ) ).toBeCloseTo(
			0.4 + 0.6 * 0.6
		);
	} );

	it( 'runs the clips backwards through the rewind', () => {
		const late = 1 - END / runLength( 3, 'medium' ) / 2;
		expect( clipAt( 0, late, time ) ).toBe( 0 );
		expect( clipAt( 3, late, time ) ).toBe( 0 );
		const mid =
			( time.forwardEnd + time.beats[ time.beats.length - 2 ][ 0 ] ) / 2;
		const back = clipAt( 3, mid, time );
		expect( back ).toBeGreaterThanOrEqual( 0 );
		expect( back ).toBeLessThan( 1 );
	} );
} );

describe( 'openingStop', () => {
	it( 'plays once for the hold time at most', () => {
		expect( openingStop( 12, 5 ) ).toBe( 5 );
	} );

	it( 'stops just short of the end of a shorter clip, never looping', () => {
		expect( openingStop( 3, 5 ) ).toBeCloseTo( 2.95 );
	} );

	it( 'uses the hold when the length is unknown, and never goes below 0', () => {
		expect( openingStop( NaN, 5 ) ).toBe( 5 );
		expect( openingStop( 0, 5 ) ).toBe( 5 );
		expect( openingStop( 10, -2 ) ).toBe( 0 );
	} );
} );

describe( 'ease', () => {
	it( 'catches up at the same speed whatever the frame rate', () => {
		let fast = 0;
		for ( let frame = 0; frame < 4; frame++ ) {
			fast = ease( fast, 1, 1 / 120, 9 );
		}
		const slow = ease( 0, 1, 4 / 120, 9 );
		expect( fast ).toBeCloseTo( slow, 6 );
	} );

	it( 'snaps when close enough', () => {
		expect( ease( 0.99999, 1, 1 / 60, 9 ) ).toBe( 1 );
	} );
} );

describe( 'side', () => {
	it( 'puts the card away from the subject', () => {
		expect( side( 0.2 ) ).toBe( 'right' );
		expect( side( 0.7 ) ).toBe( 'left' );
		expect( side( 0.5 ) ).toBe( 'left' );
	} );
} );

describe( 'layout', () => {
	it( 'needs two shots to play', () => {
		expect( layout( { shots: 1, reduce: false, stick: true } ) ).toBe(
			'none'
		);
	} );

	it( 'stacks under reduced motion or where the block cannot stick', () => {
		expect( layout( { shots: 4, reduce: true, stick: true } ) ).toBe(
			'stacked'
		);
		expect( layout( { shots: 4, reduce: false, stick: false } ) ).toBe(
			'stacked'
		);
	} );

	it( 'plays the film otherwise', () => {
		expect( layout( { shots: 4, reduce: false, stick: true } ) ).toBe(
			'film'
		);
	} );
} );

describe( 'stamp', () => {
	it( 'shows minutes, seconds and frames', () => {
		expect( stamp( 0 ) ).toBe( '00:00:00' );
		expect( stamp( 1 ) ).toBe( '00:04:06' );
		expect( stamp( 20 ) ).toBe( '01:25:00' );
		expect( stamp( -1 ) ).toBe( '00:00:00' );
	} );
} );
