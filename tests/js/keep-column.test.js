/*
 * Column, keep in view while the photos scroll. The column sticks only
 * where columns sit side by side, and the keep script tells the stylesheet
 * where the header ends and how tall the column is. A plain comment, since
 * Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it, jest } from '@jest/globals';
import { readFileSync } from 'fs';
import { join } from 'path';
import { keepVars, watchKeep } from '../../assets/src/keep/keep';

const CSS = readFileSync(
	join( __dirname, '../../assets/src/effects/parts/columns.css' ),
	'utf8'
);

describe( 'keep stylesheet', () => {
	const at = CSS.indexOf( '@media (min-width: 782px)' );

	it( 'sticks the column only from 782px', () => {
		expect( at ).toBeGreaterThan( -1 );
		expect( CSS.indexOf( 'purplish-columns-keep' ) ).toBeGreaterThan( at );
		expect( CSS.slice( 0, at ) ).not.toContain( 'position: sticky' );
	} );

	it( 'stops the stretch and tops out at the end of a tall column', () => {
		const rule = CSS.slice( at );
		expect( rule ).toContain( 'position: sticky;' );
		expect( rule ).toContain( 'align-self: flex-start;' );
		expect( rule ).toContain(
			'top: min(var(--purplish-keep-below), var(--purplish-keep-end));'
		);
		expect( rule ).toContain(
			'calc(100vh - var(--purplish-keep-height, 0px) - var(--purplish-keep-gap))'
		);
		expect( rule ).toContain( 'var(--wp-admin--admin-bar--height, 0px)' );
	} );
} );

describe( 'keepVars', () => {
	it( 'starts below the admin bar with no pinned header', () => {
		expect( keepVars( 0, 32, 640.4 ) ).toEqual( {
			'--purplish-keep-head': '32px',
			'--purplish-keep-height': '640px',
		} );
	} );

	it( 'starts below a pinned header', () => {
		expect( keepVars( 112, 32, 1500 ) ).toEqual( {
			'--purplish-keep-head': '112px',
			'--purplish-keep-height': '1500px',
		} );
		expect( keepVars( 80, 0, 300 )[ '--purplish-keep-head' ] ).toBe(
			'80px'
		);
	} );
} );

describe( 'watchKeep', () => {
	const setup = ( matches ) => {
		document.body.innerHTML =
			'<div class="wp-block-columns"><div class="wp-block-column">Photos</div><div class="wp-block-column purplish-columns purplish-columns-keep">Details</div></div>';
		const column = document.querySelector( '.purplish-columns-keep' );
		column.getBoundingClientRect = () => ( { height: 900 } );
		const wide = { matches, addEventListener: jest.fn() };
		watchKeep( document, window, wide );
		return column;
	};

	it( 'sets the head and height on a kept column', () => {
		const column = setup( true );
		expect( column.style.getPropertyValue( '--purplish-keep-head' ) ).toBe(
			'0px'
		);
		expect(
			column.style.getPropertyValue( '--purplish-keep-height' )
		).toBe( '900px' );
	} );

	it( 'leaves stacked columns alone', () => {
		const column = setup( false );
		expect( column.getAttribute( 'style' ) ).toBeNull();
	} );
} );
