/*
 * Grid switch on Product Collection: the buttons move beside Sort by, and
 * a press switches every grid with the option and lasts for the visit.
 * A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it, jest } from '@jest/globals';
import {
	KEY,
	change,
	placeSwitch,
	remembered,
	setSize,
} from '../../assets/src/grid/switch';

const buttons = `<div class="purplish-grid-switch" hidden>
	<button type="button" class="purplish-grid-switch__button" data-size="own" aria-pressed="true"></button>
	<button type="button" class="purplish-grid-switch__button" data-size="other" aria-pressed="false"></button>
</div>`;
const grid = `<div class="wp-block-woocommerce-product-collection purplish-cards-grid-2" data-wp-router-region="x">
	<ul class="wc-block-product-template"><li>A</li><li>B</li></ul>
</div>`;

const pressed = () =>
	[ ...document.querySelectorAll( '[aria-pressed="true"]' ) ]
		.map( ( button ) => button.dataset.size )
		.join( ',' );

beforeEach( () => {
	window.sessionStorage.clear();
	delete document.documentElement.dataset.purplishGrid;
	delete document.startViewTransition;
} );

describe( 'placeSwitch', () => {
	it( 'goes just after Sort by in the row above the grid', () => {
		document.body.innerHTML = `<div id="wrap">
			<div class="row"><p>12 results</p><div class="group"><div class="wp-block-woocommerce-catalog-sorting"></div></div></div>
			${ buttons }${ grid }
		</div>`;
		const switcher = document.querySelector( '.purplish-grid-switch' );

		expect( placeSwitch( switcher ) ).toBe(
			document.querySelector( '.wp-block-woocommerce-catalog-sorting' )
		);
		expect( switcher.previousElementSibling.className ).toBe(
			'wp-block-woocommerce-catalog-sorting'
		);
		expect( switcher.classList.contains( 'is-beside' ) ).toBe( true );
	} );

	it( 'stays above the grid without a Sort by, or with one inside the grid', () => {
		document.body.innerHTML = `<div>${ buttons }${ grid.replace(
			'<ul',
			'<div class="wp-block-woocommerce-catalog-sorting"></div><ul'
		) }</div>`;
		const switcher = document.querySelector( '.purplish-grid-switch' );

		expect( placeSwitch( switcher ) ).toBe( null );
		expect( switcher.nextElementSibling.dataset.wpRouterRegion ).toBe(
			'x'
		);
		expect( switcher.classList.contains( 'is-beside' ) ).toBe( false );
	} );
} );

describe( 'setSize and remembered', () => {
	beforeEach( () => {
		document.body.innerHTML = buttons + grid + buttons;
	} );

	it( 'starts at the grid’s own size', () => {
		expect( remembered() ).toBe( 'own' );
	} );

	it( 'marks the page, presses the button in every switch and keeps the choice', () => {
		setSize( document, 'other' );
		expect( document.documentElement.dataset.purplishGrid ).toBe( 'other' );
		expect( pressed() ).toBe( 'other,other' );
		expect( window.sessionStorage.getItem( KEY ) ).toBe( 'other' );
		expect( remembered() ).toBe( 'other' );

		setSize( document, 'own' );
		expect( document.documentElement.dataset.purplishGrid ).toBe(
			undefined
		);
		expect( pressed() ).toBe( 'own,own' );
		expect( remembered() ).toBe( 'own' );
	} );
} );

describe( 'change', () => {
	beforeEach( () => {
		document.body.innerHTML = buttons + grid;
	} );

	it( 'switches at once without view transitions or with reduced motion', () => {
		change( document, 'other', false );
		expect( pressed() ).toBe( 'other' );

		const start = jest.fn();
		document.startViewTransition = start;
		change( document, 'own', true );
		expect( pressed() ).toBe( 'own' );
		expect( start ).not.toHaveBeenCalled();
	} );

	it( 'names each card for the move, then clears the names', async () => {
		let finish;
		document.startViewTransition = ( update ) => {
			const names = [ ...document.querySelectorAll( 'li' ) ].map(
				( card ) => card.style.viewTransitionName
			);
			expect( names ).toEqual( [ 'purplish-card-0', 'purplish-card-1' ] );
			expect(
				document.documentElement.classList.contains(
					'is-purplish-regrid'
				)
			).toBe( true );
			update();
			return {
				finished: new Promise( ( resolve ) => ( finish = resolve ) ),
			};
		};

		change( document, 'other', false );
		expect( pressed() ).toBe( 'other' );
		finish();
		await Promise.resolve();
		await Promise.resolve();

		expect( document.querySelector( 'li' ).style.viewTransitionName ).toBe(
			''
		);
		expect(
			document.documentElement.classList.contains( 'is-purplish-regrid' )
		).toBe( false );
	} );
} );
