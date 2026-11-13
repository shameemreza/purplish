/*
 * Results change on Product Collection: which cards stay, where they came
 * from, and what each look does with them.
 * A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it } from '@jest/globals';
import {
	MOST_STEPS,
	isAppend,
	keyOf,
	lookOf,
	motionFor,
	plan,
	same,
} from '../../assets/src/results/motion';

const element = ( html ) => {
	const box = document.createElement( 'div' );
	box.innerHTML = html;
	return box.firstElementChild;
};

describe( 'keyOf', () => {
	it( 'reads the router key first', () => {
		expect(
			keyOf(
				element(
					'<li data-wp-key="product-item-12" class="post-99"></li>'
				)
			)
		).toBe( 'product-item-12' );
	} );

	it( 'falls back to the post class, then nothing', () => {
		expect(
			keyOf(
				element( '<li class="wc-block-product post-41 product"></li>' )
			)
		).toBe( 'post-41' );
		expect( keyOf( element( '<li class="wc-block-product"></li>' ) ) ).toBe(
			''
		);
	} );
} );

describe( 'same and isAppend', () => {
	it( 'tells an unchanged list apart', () => {
		expect( same( [ 'a', 'b' ], [ 'a', 'b' ] ) ).toBe( true );
		expect( same( [ 'a', 'b' ], [ 'b', 'a' ] ) ).toBe( false );
		expect( same( [ 'a' ], [ 'a', 'b' ] ) ).toBe( false );
	} );

	it( 'spots Load more adding cards at the end', () => {
		expect( isAppend( [ 'a', 'b' ], [ 'a', 'b', 'c' ] ) ).toBe( true );
		expect( isAppend( [ 'a', 'b' ], [ 'a', 'c', 'd' ] ) ).toBe( false );
		expect( isAppend( [ 'a', 'b' ], [ 'a', 'b' ] ) ).toBe( false );
		expect( isAppend( [], [ 'a' ] ) ).toBe( false );
	} );
} );

describe( 'plan', () => {
	it( 'gives a kept card its offset and marks a new one', () => {
		const before = new Map( [
			[ 'a', { x: 0, y: 0 } ],
			[ 'b', { x: 300, y: 0 } ],
		] );
		expect(
			plan( before, [
				{ key: 'b', x: 0, y: 0 },
				{ key: 'c', x: 300, y: 0 },
			] )
		).toEqual( [
			{ key: 'b', dx: 300, dy: 0, fresh: false },
			{ key: 'c', dx: 0, dy: 0, fresh: true },
		] );
	} );

	it( 'rounds part pixels', () => {
		expect(
			plan( new Map( [ [ 'a', { x: 10.6, y: 4.2 } ] ] ), [
				{ key: 'a', x: 0, y: 0 },
			] )[ 0 ]
		).toEqual( { key: 'a', dx: 11, dy: 4, fresh: false } );
	} );
} );

describe( 'motionFor', () => {
	it( 'fades every card the same', () => {
		const motion = motionFor( 'fade', null, 7 );
		expect( motion.frames[ 0 ] ).toEqual( { opacity: 0 } );
		expect( motion.timing.delay ).toBeUndefined();
	} );

	it( 'staggers Rise and caps the wait', () => {
		expect( motionFor( 'rise', null, 2 ).timing.delay ).toBe( 90 );
		expect( motionFor( 'rise', null, 40 ).timing.delay ).toBe(
			MOST_STEPS * 45
		);
		expect( motionFor( 'rise', null, 0 ).timing.fill ).toBe( 'backwards' );
	} );

	it( 'slides a kept card from its old place in Move', () => {
		const motion = motionFor(
			'move',
			{ key: 'a', dx: -300, dy: 20, fresh: false },
			0
		);
		expect( motion.frames ).toEqual( [
			{ transform: 'translate(-300px, 20px)' },
			{ transform: 'none' },
		] );
	} );

	it( 'brings a new card in after the others start moving', () => {
		const motion = motionFor(
			'move',
			{ key: 'c', dx: 0, dy: 0, fresh: true },
			3
		);
		expect( motion.frames[ 0 ].opacity ).toBe( 0 );
		expect( motion.timing.delay ).toBe( 140 );
	} );

	it( 'leaves still cards and unknown looks alone', () => {
		expect(
			motionFor( 'move', { key: 'a', dx: 0, dy: 0, fresh: false }, 0 )
		).toBeNull();
		expect( motionFor( 'move', undefined, 0 ) ).toBeNull();
		expect( motionFor( 'spin', null, 0 ) ).toBeNull();
	} );
} );

describe( 'lookOf', () => {
	it( 'reads the look from the class', () => {
		expect(
			lookOf(
				element(
					'<div class="wp-block-woocommerce-product-collection purplish-cards-results-rise"></div>'
				)
			)
		).toBe( 'rise' );
		expect(
			lookOf(
				element( '<div class="purplish-cards-results-spin"></div>' )
			)
		).toBe( '' );
	} );
} );
