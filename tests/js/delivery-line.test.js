/*
 * The Delivery Line's script picks the state for the shopper's clock and
 * words the time left the way the server does.
 */
import { describe, expect, it } from '@jest/globals';
import {
	dateLine,
	parts,
	pick,
	split,
	timeLeft,
} from '../../assets/src/blocks/delivery-line/states';

const states = [
	{ t: 100, c: 500, a: 'Wed, Nov 25', b: 'Fri, Nov 27', p: 'Mon, Nov 23' },
	{ t: 500, c: 0, a: 'Thu, Nov 26', b: 'Mon, Nov 30', p: 'Tue, Nov 24' },
	{ t: 900, c: 1200, a: 'Thu, Nov 26', b: 'Mon, Nov 30', p: 'Tue, Nov 24' },
];

const data = {
	states,
	range: false,
	countdown: true,
	locale: 'en-US',
	texts: {
		by: 'Arrives by %1$s',
		by_left: 'Order within %1$s, arrives by %2$s',
		range: 'Arrives %1$s to %2$s',
		range_left: 'Order within %1$s, arrives %2$s to %3$s',
		hm: '%1$s %2$s',
	},
};

describe( 'pick', () => {
	it( 'takes the last state that has started', () => {
		expect( pick( states, 100 ).b ).toBe( 'Fri, Nov 27' );
		expect( pick( states, 499 ).b ).toBe( 'Fri, Nov 27' );
		expect( pick( states, 500 ).b ).toBe( 'Mon, Nov 30' );
		expect( pick( states, 5000 ).t ).toBe( 900 );
	} );

	it( 'takes the first when the clock is behind the server', () => {
		expect( pick( states, 10 ).t ).toBe( 100 );
		expect( pick( [], 10 ) ).toBeNull();
	} );
} );

describe( 'time left', () => {
	it( 'splits whole hours and minutes, at least a minute', () => {
		expect( split( 3 * 3600 + 20 * 60 + 59 ) ).toEqual( {
			hours: 3,
			minutes: 20,
		} );
		expect( split( 5 ) ).toEqual( { hours: 0, minutes: 1 } );
	} );

	it( 'reads like the server in English', () => {
		expect( timeLeft( 3 * 3600 + 20 * 60, 'en-US', '%1$s %2$s' ) ).toBe(
			'3 hours 20 minutes'
		);
		expect( timeLeft( 3600, 'en-US', '%1$s %2$s' ) ).toBe( '1 hour' );
		expect( timeLeft( 45 * 60, 'en-US', '%1$s %2$s' ) ).toBe(
			'45 minutes'
		);
	} );

	it( 'falls back to the browser language for a bad locale', () => {
		expect( timeLeft( 60, 'not a locale!', '%1$s %2$s' ) ).toMatch( /1/ );
	} );
} );

describe( 'parts', () => {
	it( 'puts values in strong unless told otherwise', () => {
		expect(
			parts(
				'Free pickup at %1$s from %2$s',
				[ 'Downtown', 'Mon' ],
				[ 1 ]
			)
		).toEqual( [
			{ text: 'Free pickup at ', strong: false },
			{ text: 'Downtown', strong: false },
			{ text: ' from ', strong: false },
			{ text: 'Mon', strong: true },
		] );
	} );

	it( 'follows the order a translation puts the placeholders in', () => {
		expect( parts( '%2$s: %1$s', [ 'a', 'b' ] ) ).toEqual( [
			{ text: 'b', strong: true },
			{ text: ': ', strong: false },
			{ text: 'a', strong: true },
		] );
	} );
} );

describe( 'dateLine', () => {
	it( 'shows the time left while the cut-off is ahead', () => {
		expect( dateLine( data, states[ 0 ], 500 - 2 * 3600 ) ).toEqual( {
			template: data.texts.by_left,
			values: [ '2 hours', 'Fri, Nov 27' ],
		} );
	} );

	it( 'drops it after the cut-off or when it is off', () => {
		expect( dateLine( data, states[ 1 ], 600 ) ).toEqual( {
			template: data.texts.by,
			values: [ 'Mon, Nov 30' ],
		} );
		expect(
			dateLine( { ...data, countdown: false }, states[ 0 ], 200 ).values
		).toEqual( [ 'Fri, Nov 27' ] );
	} );

	it( 'shows a range when the dates differ', () => {
		const range = { ...data, range: true, countdown: false };
		expect( dateLine( range, states[ 1 ], 600 ) ).toEqual( {
			template: data.texts.range,
			values: [ 'Thu, Nov 26', 'Mon, Nov 30' ],
		} );
		expect(
			dateLine( range, { ...states[ 1 ], a: 'Mon, Nov 30' }, 600 )
				.template
		).toBe( data.texts.by );
	} );
} );
