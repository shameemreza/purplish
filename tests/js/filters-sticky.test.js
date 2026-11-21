/*
 * A sticky Product Filters block stays below a theme header that stays at
 * the top of the window. A plain comment, since Jest reads the environment
 * from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it } from '@jest/globals';
import { headerBottom } from '../../assets/src/shared/header';

const view = { width: 1440, height: 900 };
const box = ( over ) => ( {
	position: 'sticky',
	top: 0,
	rectTop: 0,
	height: 80,
	width: 1440,
	...over,
} );

describe( 'headerBottom', () => {
	it( 'is 0 with no pinned header', () => {
		expect( headerBottom( [], view ) ).toBe( 0 );
		expect( headerBottom( [ box( { position: 'static' } ) ], view ) ).toBe(
			0
		);
	} );

	it( 'ends below a sticky or fixed header at the top', () => {
		expect( headerBottom( [ box() ], view ) ).toBe( 80 );
		expect(
			headerBottom( [ box( { position: 'fixed', rectTop: 32 } ) ], view )
		).toBe( 112 );
	} );

	it( 'counts a sticky header from its CSS top before it gets there', () => {
		expect( headerBottom( [ box( { top: 0, rectTop: 40 } ) ], view ) ).toBe(
			80
		);
	} );

	it( 'lets go of a header that slid out of view', () => {
		expect(
			headerBottom( [ box( { position: 'fixed', rectTop: -80 } ) ], view )
		).toBe( 0 );
		expect( headerBottom( [ box( { rectTop: -50 } ) ], view ) ).toBe( 30 );
	} );

	it( 'takes the lowest of a stacked top bar and header', () => {
		expect(
			headerBottom(
				[
					box( { height: 40 } ),
					box( { position: 'fixed', rectTop: 40, height: 70 } ),
				],
				view
			)
		).toBe( 110 );
	} );

	it( 'skips narrow, empty, low and full-screen boxes', () => {
		expect(
			headerBottom(
				[
					box( { width: 300 } ),
					box( { height: 0 } ),
					box( { position: 'fixed', rectTop: 820 } ),
					box( { position: 'fixed', height: 900 } ),
				],
				view
			)
		).toBe( 0 );
	} );
} );
