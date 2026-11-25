/*
 * Instant results under a Search block: typing waits, drops stale
 * requests, the arrow keys walk the rows, Enter opens one, Escape closes,
 * and the price HTML from the route is cut down to safe tags. A plain
 * comment, since Jest reads the environment from it.
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
import { cleanPrice, normalize, setup } from '../../assets/src/search/combobox';

const SETTINGS = {
	url: 'https://shop.test/wp-json/purplish/v1/search',
	n: 6,
	c: 1,
	p: 1,
	counts: [ 'No products found', '1 product found', '2 products found' ],
	all: 'See all results for “%s”',
	sold: 'Sold out',
	cats: 'Categories',
	items: 'Products',
};

const ANSWER = {
	cats: [ { name: 'TVs', url: 'https://shop.test/tvs/', count: 4 } ],
	items: [
		{
			name: 'Smart TV',
			url: 'https://shop.test/smart-tv/',
			img: 'https://shop.test/tv.jpg',
			price: '<span class="woocommerce-Price-amount amount"><bdi>$499</bdi></span>',
			sold: false,
			sku: 'TV-1',
		},
		{
			name: 'Old TV',
			url: 'https://shop.test/old-tv/',
			img: 'https://shop.test/old.jpg',
			price: '<span>$99</span>',
			sold: true,
			sku: 'TV-0',
		},
	],
	all: 'https://shop.test/?s=tv&post_type=product',
};

let form;
let input;
let env;
let requests;

const markup = () =>
	`<form role="search" method="get" action="/" data-purplish-search='${ JSON.stringify(
		SETTINGS
	) }'><div class="wp-block-search__inside-wrapper"><input type="search" name="s" required><input type="hidden" name="post_type" value="product"><button type="submit">Search</button></div><div class="purplish-search" hidden><div class="purplish-search__list" role="listbox" id="purplish-search-1"></div></div><span class="purplish-search__live" role="status"></span></form>`;

const type = ( value ) => {
	input.value = value;
	input.dispatchEvent( new window.Event( 'input' ) );
};

const key = ( name ) => {
	const event = new window.KeyboardEvent( 'keydown', {
		key: name,
		bubbles: true,
		cancelable: true,
	} );
	input.dispatchEvent( event );
	return event;
};

// Lets the fetch promise chain settle.
const settle = async () => {
	for ( let i = 0; i < 5; i++ ) {
		await Promise.resolve();
	}
};

beforeEach( () => {
	jest.useFakeTimers();
	document.body.innerHTML = markup();
	form = document.querySelector( 'form' );
	input = form.querySelector( 'input[type="search"]' );
	requests = [];
	env = {
		setTimeout,
		clearTimeout,
		AbortController: window.AbortController,
		fetch: jest.fn( ( url, init ) => {
			requests.push( { url: String( url ), init } );
			return Promise.resolve( {
				ok: true,
				json: () => Promise.resolve( ANSWER ),
			} );
		} ),
	};
	setup( form, env );
	input.focus();
} );

afterEach( () => {
	jest.useRealTimers();
} );

describe( 'instant search', () => {
	it( 'marks the field as a combobox and keeps the list hidden at first', () => {
		expect( input.getAttribute( 'role' ) ).toBe( 'combobox' );
		expect( input.getAttribute( 'aria-controls' ) ).toBe(
			'purplish-search-1'
		);
		expect( input.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( form.querySelector( '.purplish-search' ).hidden ).toBe( true );
	} );

	it( 'waits 250 ms, starts at two letters and sends no cookie', () => {
		type( 't' );
		jest.advanceTimersByTime( 300 );
		expect( env.fetch ).not.toHaveBeenCalled();

		type( 'tv' );
		jest.advanceTimersByTime( 249 );
		expect( env.fetch ).not.toHaveBeenCalled();
		jest.advanceTimersByTime( 1 );
		expect( env.fetch ).toHaveBeenCalledTimes( 1 );
		expect( requests[ 0 ].url ).toBe(
			'https://shop.test/wp-json/purplish/v1/search?q=tv&n=6&c=1'
		);
		expect( requests[ 0 ].init.credentials ).toBe( 'omit' );
	} );

	it( 'sends one request for fast typing and aborts the one in flight', () => {
		type( 'sm' );
		jest.advanceTimersByTime( 100 );
		type( 'sma' );
		jest.advanceTimersByTime( 100 );
		type( 'smar' );
		jest.advanceTimersByTime( 250 );
		expect( env.fetch ).toHaveBeenCalledTimes( 1 );
		expect( requests[ 0 ].url ).toContain( 'q=smar' );

		const first = requests[ 0 ].init.signal;
		type( '  Smart  ' );
		jest.advanceTimersByTime( 250 );
		expect( first.aborted ).toBe( true );
		expect( requests[ 1 ].url ).toContain( 'q=smart' );
		expect( requests[ 1 ].init.signal.aborted ).toBe( false );
	} );

	it( 'shows categories, rows, a sold chip, the count and the all link', async () => {
		type( 'tv' );
		jest.advanceTimersByTime( 250 );
		await settle();

		expect( form.querySelector( '.purplish-search' ).hidden ).toBe( false );
		expect( input.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		const options = form.querySelectorAll( '[role="option"]' );
		expect( options ).toHaveLength( 4 );
		expect( options[ 0 ].textContent ).toBe( 'TVs 4' );
		expect(
			options[ 1 ].querySelector( '.purplish-search__name' ).textContent
		).toBe( 'Smart TV' );
		expect(
			options[ 2 ].querySelector( '.purplish-search__sold' ).textContent
		).toBe( 'Sold out' );
		expect( options[ 3 ].textContent ).toBe( 'See all results for “tv”' );
		expect( options[ 3 ].getAttribute( 'href' ) ).toBe( ANSWER.all );
		expect(
			form.querySelector( '.purplish-search__live' ).textContent
		).toBe( '2 products found' );
	} );

	it( 'walks rows with the arrows, wraps, opens one with Enter', async () => {
		type( 'tv' );
		jest.advanceTimersByTime( 250 );
		await settle();
		const options = [ ...form.querySelectorAll( '[role="option"]' ) ];
		const clicked = jest.fn( ( event ) => event.preventDefault() );
		options[ 1 ].addEventListener( 'click', clicked );

		expect( key( 'ArrowDown' ).defaultPrevented ).toBe( true );
		expect( input.getAttribute( 'aria-activedescendant' ) ).toBe(
			options[ 0 ].id
		);
		key( 'ArrowDown' );
		expect( options[ 1 ].getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( options[ 0 ].getAttribute( 'aria-selected' ) ).toBe( 'false' );

		key( 'ArrowUp' );
		key( 'ArrowUp' );
		expect( input.getAttribute( 'aria-activedescendant' ) ).toBe(
			options[ 3 ].id
		);
		key( 'ArrowDown' );
		key( 'ArrowDown' );
		expect( input.getAttribute( 'aria-activedescendant' ) ).toBe(
			options[ 1 ].id
		);

		expect( key( 'Enter' ).defaultPrevented ).toBe( true );
		expect( clicked ).toHaveBeenCalledTimes( 1 );
		expect( document.activeElement ).toBe( input );
	} );

	it( 'closes with Escape without bubbling to the form', async () => {
		const formKeys = jest.fn();
		form.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key ) {
				formKeys();
			}
		} );
		type( 'tv' );
		jest.advanceTimersByTime( 250 );
		await settle();
		key( 'ArrowDown' );

		expect( key( 'Escape' ).defaultPrevented ).toBe( true );
		expect( form.querySelector( '.purplish-search' ).hidden ).toBe( true );
		expect( input.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( input.hasAttribute( 'aria-activedescendant' ) ).toBe( false );
		expect( formKeys ).not.toHaveBeenCalled();
		expect( document.activeElement ).toBe( input );

		// Closed, Escape is left to the page and the browser.
		expect( key( 'Escape' ).defaultPrevented ).toBe( false );
		expect( formKeys ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'leaves Enter to the form when no row is active', async () => {
		expect( key( 'Enter' ).defaultPrevented ).toBe( false );
		type( 'tv' );
		jest.advanceTimersByTime( 250 );
		await settle();
		expect( key( 'Enter' ).defaultPrevented ).toBe( false );
	} );

	it( 'reuses an answer it already has', async () => {
		type( 'tv' );
		jest.advanceTimersByTime( 250 );
		await settle();
		type( 'tvs' );
		jest.advanceTimersByTime( 250 );
		await settle();
		type( 'TV' );
		jest.advanceTimersByTime( 250 );
		await settle();
		expect( env.fetch ).toHaveBeenCalledTimes( 2 );
		expect( form.querySelectorAll( '[role="option"]' ) ).toHaveLength( 4 );
	} );

	it( 'closes when focus leaves the form', async () => {
		document.body.insertAdjacentHTML( 'beforeend', '<a href="#x">x</a>' );
		type( 'tv' );
		jest.advanceTimersByTime( 250 );
		await settle();
		document.querySelector( 'a[href="#x"]' ).focus();
		expect( form.querySelector( '.purplish-search' ).hidden ).toBe( true );
	} );
} );

describe( 'no script', () => {
	it( 'is a plain form that submits to the results page', () => {
		document.body.innerHTML = markup();
		const plain = document.querySelector( 'form' );
		expect( plain.getAttribute( 'action' ) ).toBe( '/' );
		expect( plain.querySelector( '.purplish-search' ).hidden ).toBe( true );
		expect(
			plain.querySelector( 'input[name="s"]' ).hasAttribute( 'role' )
		).toBe( false );
	} );
} );

describe( 'price HTML', () => {
	it( 'keeps amount tags and screen reader text, drops the rest', () => {
		const box = document.createElement( 'div' );
		box.append(
			cleanPrice(
				document,
				'<del aria-hidden="true"><span class="amount" style="x">$5</span></del> <span class="screen-reader-text">Was $5.</span><ins onclick="evil()"><a href="https://bad.test"><b>$4</b></a></ins><script>evil()</script><img src=x onerror="evil()">'
			)
		);
		expect( box.innerHTML ).toBe(
			'<del aria-hidden="true"><span>$5</span></del> <span class="screen-reader-text">Was $5.</span><ins>$4</ins>'
		);
	} );

	it( 'normalizes queries like the route', () => {
		expect( normalize( '  Smart \t TV ' ) ).toBe( 'smart tv' );
	} );
} );
