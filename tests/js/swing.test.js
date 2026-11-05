/**
 * The Hanging Rail's scroll length and the spring its cards swing on.
 */
import { describe, expect, it } from '@jest/globals';
import {
	MAX_LEAN,
	lean,
	progress,
	runLength,
	step,
	stiffness,
} from '../../assets/src/blocks/hanging-rail/swing';

/**
 * Runs a swing at 60 frames a second until it stops or the time runs out.
 *
 * @param {{angle: number, speed: number}} swing   Starting swing.
 * @param {number}                         target  Angle to pull toward.
 * @param {number}                         seconds Most time to run.
 * @return {{swing: Object, frames: number, peak: number}} Where it ended.
 */
function run( swing, target, seconds ) {
	let now = { ...swing, still: false };
	let frames = 0;
	let peak = 0;
	while ( ! now.still && frames < seconds * 60 ) {
		now = step( now, target, 1 / 60 );
		peak = Math.max( peak, Math.abs( now.angle - target ) );
		frames++;
	}
	return { swing: now, frames, peak };
}

describe( 'runLength', () => {
	it( 'is the hidden width times the scroll length', () => {
		expect( runLength( 1000, 'short', 500 ) ).toBe( 1000 );
		expect( runLength( 1000, 'medium', 500 ) ).toBe( 1500 );
		expect( runLength( 1000, 'long', 500 ) ).toBe( 2000 );
	} );

	it( 'never goes under the floor', () => {
		expect( runLength( 200, 'short', 800 ) ).toBe( 800 );
	} );

	it( 'is nothing when the whole row shows', () => {
		expect( runLength( 0, 'long', 800 ) ).toBe( 0 );
		expect( runLength( -40, 'long', 800 ) ).toBe( 0 );
	} );

	it( 'reads an unknown length as medium', () => {
		expect( runLength( 1000, 'huge', 0 ) ).toBe( 1500 );
	} );
} );

describe( 'progress', () => {
	it( 'runs from 0 to 1 across the rail and holds at both ends', () => {
		expect( progress( -50, 1000 ) ).toBe( 0 );
		expect( progress( 250, 1000 ) ).toBe( 0.25 );
		expect( progress( 1400, 1000 ) ).toBe( 1 );
		expect( progress( 300, 0 ) ).toBe( 0 );
	} );
} );

describe( 'lean', () => {
	it( 'trails the row and stops at the most a card leans', () => {
		expect( lean( 0 ) ).toBe( 0 );
		expect( lean( -500 ) ).toBeLessThan( 0 );
		expect( lean( 500 ) ).toBeGreaterThan( 0 );
		expect( lean( 1e6 ) ).toBe( MAX_LEAN );
		expect( lean( -1e6 ) ).toBe( -MAX_LEAN );
	} );
} );

describe( 'step', () => {
	it( 'swings past the middle and settles when the row stops', () => {
		const { swing, frames, peak } = run(
			{ angle: MAX_LEAN, speed: 0 },
			0,
			10
		);

		expect( swing.still ).toBe( true );
		expect( swing.angle ).toBe( 0 );
		expect( swing.speed ).toBe( 0 );
		expect( frames ).toBeLessThan( 4 * 60 );
		expect( peak ).toBeLessThanOrEqual( MAX_LEAN );
	} );

	it( 'overshoots, so the card reads as swinging', () => {
		let now = { angle: MAX_LEAN, speed: 0 };
		let lowest = MAX_LEAN;
		for ( let frame = 0; frame < 60; frame++ ) {
			now = step( now, 0, 1 / 60 );
			lowest = Math.min( lowest, now.angle );
		}
		expect( lowest ).toBeLessThan( -1 );
	} );

	it( 'stays put at rest', () => {
		expect( step( { angle: 0, speed: 0 }, 0, 1 / 60 ) ).toEqual( {
			angle: 0,
			speed: 0,
			still: true,
		} );
	} );

	it( 'leans toward a moving row and settles there', () => {
		const { swing } = run( { angle: 0, speed: 0 }, -6, 10 );
		expect( swing ).toEqual( { angle: -6, speed: 0, still: true } );
	} );

	it( 'takes a long gap between frames as one short step', () => {
		const jump = step( { angle: MAX_LEAN, speed: 0 }, 0, 2 );
		expect( Math.abs( jump.angle ) ).toBeLessThanOrEqual( MAX_LEAN );
	} );
} );

describe( 'stiffness', () => {
	it( 'differs a little between neighbors', () => {
		const first = stiffness( 0 );
		const second = stiffness( 1 );
		expect( first ).not.toBe( second );
		expect( Math.abs( first - second ) / first ).toBeLessThan( 0.3 );
	} );
} );
