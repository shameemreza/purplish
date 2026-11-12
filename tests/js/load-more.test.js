/*
 * Load more on Product Collection: after WooCommerce's navigation swaps in
 * the wider grid, the page stays where it was, the new cards wait a moment
 * and come in, and focus moves to the first new card. A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	jest,
} from '@jest/globals';
import { SHORTEST, cards, track } from '../../assets/src/more/track';

const card = ( n ) =>
	`<li class="wc-block-product"><a href="/p/${ n }">Product ${ n }</a></li>`;
const grid = ( count ) =>
	`<div data-wp-router-region="wc-product-collection-0">
		<ul class="wc-block-product-template">${ Array.from(
			{ length: count },
			( _, i ) => card( i + 1 )
		).join( '' ) }</ul>
		<nav class="purplish-more"><a href="?query-0-page=2&query-0-more=1" data-purplish-more>Load more</a></nav>
	</div>`;

let doc;
let win;
let clock;
let events;

const start = ( reduced = false ) => {
	jest.useFakeTimers();
	clock = 0;
	doc = document.implementation.createHTMLDocument( 'test' );
	doc.body.innerHTML = grid( 2 );
	events = new window.EventTarget();
	win = {
		addEventListener: events.addEventListener.bind( events ),
		Element: window.Element,
		scrollX: 0,
		scrollY: 640,
		innerHeight: 900,
		performance: { now: () => clock },
		scrollTo: jest.fn(),
		scrollBy: jest.fn(),
		setTimeout: ( fn, ms ) => setTimeout( fn, ms ),
		clearTimeout: jest.fn( ( id ) => clearTimeout( id ) ),
	};
	track( doc, win, reduced );
};

const link = () => doc.querySelector( 'a[data-purplish-more]' );
const click = ( options = {} ) =>
	link().dispatchEvent(
		new window.MouseEvent( 'click', {
			bubbles: true,
			cancelable: true,
			button: 0,
			...options,
		} )
	);
const render = ( count ) => {
	const list = doc.querySelector( '.wc-block-product-template' );
	for ( let n = list.children.length + 1; n <= count; n++ ) {
		list.insertAdjacentHTML( 'beforeend', card( n ) );
	}
	doc.dispatchEvent( new window.Event( 'wc-blocks_product_list_rendered' ) );
};
const fresh = () => cards( doc.body ).slice( 2 );
const all = ( name ) =>
	fresh().every( ( item ) => item.classList.contains( name ) );

beforeEach( () => start() );
afterEach( () => jest.useRealTimers() );

describe( 'track', () => {
	it( 'marks the link busy on a plain click', () => {
		click();
		expect( link().classList.contains( 'is-loading' ) ).toBe( true );
		expect( link().getAttribute( 'aria-disabled' ) ).toBe( 'true' );
	} );

	it( 'leaves a modified click to the browser', () => {
		click( { metaKey: true } );
		expect( link().classList.contains( 'is-loading' ) ).toBe( false );
		render( 4 );
		expect( win.scrollTo ).not.toHaveBeenCalled();
	} );

	it( 'holds a fast answer until the wait ends, then brings the cards in', () => {
		click();
		win.scrollY = 0;
		clock = 200;
		const focus = jest.spyOn( window.HTMLElement.prototype, 'focus' );
		render( 4 );

		expect( win.scrollTo ).toHaveBeenCalledWith( 0, 640 );
		expect( all( 'is-purplish-wait' ) ).toBe( true );
		expect( link().classList.contains( 'is-loading' ) ).toBe( true );
		expect( focus ).not.toHaveBeenCalled();

		jest.advanceTimersByTime( SHORTEST - 200 );
		expect( all( 'is-purplish-wait' ) ).toBe( false );
		expect( all( 'is-purplish-new' ) ).toBe( true );
		expect( fresh()[ 1 ].style.getPropertyValue( '--purplish-i' ) ).toBe(
			'1'
		);
		expect(
			cards( doc.body )[ 0 ].classList.contains( 'is-purplish-new' )
		).toBe( false );
		expect( link().classList.contains( 'is-loading' ) ).toBe( false );
		expect( link().hasAttribute( 'aria-disabled' ) ).toBe( false );
		expect( focus.mock.contexts[ 0 ] ).toBe(
			fresh()[ 0 ].querySelector( 'a' )
		);
		expect( focus ).toHaveBeenCalledWith( { preventScroll: true } );
		focus.mockRestore();
	} );

	it( 'shows a slow answer at once and clears the entrance after it', () => {
		click();
		clock = SHORTEST + 100;
		render( 4 );
		expect( all( 'is-purplish-new' ) ).toBe( true );
		const item = fresh()[ 0 ];
		item.dispatchEvent( new window.Event( 'animationend' ) );
		expect( item.classList.contains( 'is-purplish-new' ) ).toBe( false );
		expect( item.style.getPropertyValue( '--purplish-i' ) ).toBe( '' );
	} );

	it( 'glides a low new row up into view', () => {
		click();
		clock = SHORTEST;
		const box = jest
			.spyOn( window.Element.prototype, 'getBoundingClientRect' )
			.mockReturnValue( { top: 800 } );
		render( 4 );
		expect( win.scrollBy ).toHaveBeenCalledWith( {
			top: 800 - 270,
			behavior: 'smooth',
		} );
		box.mockRestore();
	} );

	it( 'catches a page WooCommerce draws before the click bubbles up', () => {
		// A page fetched on hover is drawn inside WooCommerce's own handler.
		link().addEventListener( 'click', () => render( 4 ) );
		click();
		expect( win.scrollTo ).toHaveBeenCalledWith( 0, 640 );
		expect( all( 'is-purplish-wait' ) ).toBe( true );
		jest.advanceTimersByTime( SHORTEST );
		expect( all( 'is-purplish-new' ) ).toBe( true );
		expect( link().classList.contains( 'is-loading' ) ).toBe( false );
	} );

	it( 'frees the button when the load never comes', () => {
		click();
		jest.advanceTimersByTime( 12000 );
		expect( link().classList.contains( 'is-loading' ) ).toBe( false );
		expect( link().hasAttribute( 'aria-disabled' ) ).toBe( false );
		render( 4 );
		expect( win.scrollTo ).not.toHaveBeenCalled();
	} );

	it( 'frees the button on a page restored from the browser cache', () => {
		click();
		const show = new window.Event( 'pageshow' );
		Object.defineProperty( show, 'persisted', { value: true } );
		events.dispatchEvent( show );
		expect( link().classList.contains( 'is-loading' ) ).toBe( false );
	} );

	it( 'does nothing when the grid did not grow', () => {
		click();
		render( 2 );
		expect( win.scrollTo ).not.toHaveBeenCalled();
		expect( link().classList.contains( 'is-loading' ) ).toBe( true );
	} );

	it( 'stops once WooCommerce has moved focus', () => {
		click();
		Object.defineProperty( doc, 'activeElement', {
			configurable: true,
			get: () => cards( doc.body )[ 0 ].querySelector( 'a' ),
		} );
		render( 4 );
		expect( win.scrollTo ).toHaveBeenCalledTimes( 1 );
		render( 6 );
		expect( win.scrollTo ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'skips the wait, the entrance and the glide with reduced motion', () => {
		start( true );
		click();
		const focus = jest.spyOn( window.HTMLElement.prototype, 'focus' );
		render( 4 );
		expect(
			doc.querySelectorAll( '.is-purplish-new, .is-purplish-wait' )
		).toHaveLength( 0 );
		expect( link().classList.contains( 'is-loading' ) ).toBe( false );
		expect( focus.mock.contexts[ 0 ] ).toBe(
			fresh()[ 0 ].querySelector( 'a' )
		);
		expect( win.scrollBy ).not.toHaveBeenCalled();
		focus.mockRestore();
	} );
} );
