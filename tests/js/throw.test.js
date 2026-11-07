/**
 * How a thrown Momentum carousel slows down.
 */
import { describe, expect, it } from '@jest/globals';
import { slowDown } from '../../assets/src/shared/throw';

/**
 * Glides at 60 frames a second until it stops.
 *
 * @param {number} speed Starting speed, pixels per millisecond.
 * @return {{distance: number, time: number}} How far and how long it slid.
 */
function glide( speed ) {
	let now = speed;
	let distance = 0;
	let time = 0;
	while ( now && time < 10000 ) {
		distance += now * 16;
		now = slowDown( now, 16 );
		time += 16;
	}
	return { distance, time };
}

describe( 'slowDown', () => {
	it( 'loses speed every frame, either way', () => {
		expect( slowDown( 2, 16 ) ).toBeLessThan( 2 );
		expect( slowDown( 2, 16 ) ).toBeGreaterThan( 1.8 );
		expect( slowDown( -2, 16 ) ).toBe( -slowDown( 2, 16 ) );
	} );

	it( 'loses more over a longer frame', () => {
		expect( slowDown( 2, 32 ) ).toBeCloseTo(
			slowDown( slowDown( 2, 16 ), 16 )
		);
	} );

	it( 'stops once too slow to see', () => {
		expect( slowDown( 0.01, 16 ) ).toBe( 0 );
		expect( slowDown( 0, 16 ) ).toBe( 0 );
	} );

	it( 'gives a fast throw a few cards of slide within about a second', () => {
		const { distance, time } = glide( 2 );
		expect( distance ).toBeGreaterThan( 300 );
		expect( distance ).toBeLessThan( 600 );
		expect( time ).toBeLessThan( 1500 );
	} );
} );
