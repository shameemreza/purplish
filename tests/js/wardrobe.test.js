/*
 * Wardrobe's panel placement and how the menu item opens and closes. A
 * plain comment, since Jest reads the environment from it and ESLint's
 * JSDoc rules don't know the tag.
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
import { placement } from '../../assets/src/blocks/wardrobe/place';
import {
	CLOSE_DELAY,
	OPEN_DELAY,
	looks,
	wardrobe,
} from '../../assets/src/blocks/wardrobe/menu';

const box = ( left, top, width, height ) => ( {
	left,
	top,
	width,
	height,
	right: left + width,
	bottom: top + height,
} );

describe( 'placement', () => {
	const base = box( 100, 80, 60, 40 );
	const item = box( 100, 80, 60, 40 );

	it( 'centers the panel under the header, just below it', () => {
		const spot = placement( {
			base,
			bar: box( 0, 0, 1440, 120 ),
			item,
			width: 1200,
			viewport: 1440,
			gap: 8,
			height: 900,
		} );
		expect( spot.x ).toBe( 120 - 100 );
		expect( spot.y ).toBe( 128 - 80 );
		expect( spot.max ).toBe( 900 - 128 - 16 );
	} );

	it( 'keeps the panel 16px inside the page edges', () => {
		const left = placement( {
			base,
			bar: box( -400, 0, 800, 120 ),
			item,
			width: 1000,
			viewport: 1440,
			gap: 8,
			height: 900,
		} );
		expect( left.x + base.left ).toBe( 16 );

		const right = placement( {
			base,
			bar: box( 1200, 0, 800, 120 ),
			item,
			width: 1000,
			viewport: 1440,
			gap: 8,
			height: 900,
		} );
		expect( right.x + base.left ).toBe( 1440 - 1000 - 16 );
	} );

	it( 'lets a panel as wide as the page touch both edges', () => {
		const spot = placement( {
			base,
			bar: box( 0, 0, 1440, 120 ),
			item,
			width: 1440,
			viewport: 1440,
			gap: 0,
			height: 900,
		} );
		expect( spot.x + base.left ).toBe( 0 );
	} );

	it( 'sizes the hover bridge from the menu item to the panel', () => {
		const spot = placement( {
			base,
			bar: box( 0, 0, 1440, 160 ),
			item,
			width: 1200,
			viewport: 1440,
			gap: 8,
			height: 900,
		} );
		expect( spot.bridgeX ).toBe( 100 - 120 );
		expect( spot.bridgeW ).toBe( 60 );
		expect( spot.reach ).toBe( 168 - 120 );
	} );

	it( 'keeps some panel height on a short window', () => {
		const spot = placement( {
			base,
			bar: box( 0, 0, 1440, 120 ),
			item,
			width: 1200,
			viewport: 1440,
			gap: 8,
			height: 200,
		} );
		expect( spot.max ).toBe( 160 );
	} );
} );

/**
 * One Wardrobe item in the page, as the block prints it.
 *
 * @param {string} open hover or click.
 * @return {HTMLElement} The item.
 */
const addItem = ( open = 'hover' ) => {
	const list = document.createElement( 'ul' );
	list.innerHTML = `<li class="wp-block-purplish-wardrobe" data-open="${ open }">
		<a class="purplish-wardrobe-link" href="/shop/">Shop</a>
		<button type="button" class="purplish-wardrobe-toggle" aria-expanded="false">Shop</button>
		<div class="purplish-wardrobe-panel" hidden><div class="purplish-wardrobe-inner"><a href="/coats/">Coats</a></div></div>
	</li>`;
	document.body.append( list );
	return list.firstElementChild;
};

const pointer = ( target, type ) => {
	const event = new Event( type );
	event.pointerType = 'mouse';
	target.dispatchEvent( event );
};

describe( 'wardrobe', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		document
			.querySelectorAll( '.purplish-wardrobe-toggle' )
			.forEach( ( toggle ) => {
				if ( 'true' === toggle.getAttribute( 'aria-expanded' ) ) {
					toggle.click();
				}
			} );
		document.body.innerHTML = '';
		jest.useRealTimers();
	} );

	it( 'needs a button and a panel', () => {
		const item = document.createElement( 'li' );
		expect( wardrobe( item ) ).toBeNull();
	} );

	it( 'marks the item ready and opens and closes on click', () => {
		const item = addItem();
		const toggle = item.querySelector( 'button' );
		const { panel } = wardrobe( item );

		expect( item.classList.contains( 'is-ready' ) ).toBe( true );
		toggle.click();
		expect( panel.hidden ).toBe( false );
		expect( toggle.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( item.classList.contains( 'is-open' ) ).toBe( true );
		expect(
			panel.style.getPropertyValue( '--purplish-wardrobe-y' )
		).toMatch( /px$/ );

		toggle.click();
		expect( panel.hidden ).toBe( true );
		expect( toggle.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
	} );

	it( 'opens after a short rest on hover and closes after leaving', () => {
		const item = addItem();
		const { panel } = wardrobe( item );

		pointer( item, 'pointerenter' );
		jest.advanceTimersByTime( OPEN_DELAY - 1 );
		expect( panel.hidden ).toBe( true );
		jest.advanceTimersByTime( 1 );
		expect( panel.hidden ).toBe( false );

		pointer( item, 'pointerleave' );
		jest.advanceTimersByTime( CLOSE_DELAY - 1 );
		expect( panel.hidden ).toBe( false );
		pointer( item, 'pointerenter' );
		jest.advanceTimersByTime( CLOSE_DELAY );
		expect( panel.hidden ).toBe( false );

		pointer( item, 'pointerleave' );
		jest.advanceTimersByTime( CLOSE_DELAY );
		expect( panel.hidden ).toBe( true );
	} );

	it( 'passing over without resting opens nothing', () => {
		const item = addItem();
		const { panel } = wardrobe( item );

		pointer( item, 'pointerenter' );
		jest.advanceTimersByTime( OPEN_DELAY / 2 );
		pointer( item, 'pointerleave' );
		jest.advanceTimersByTime( OPEN_DELAY + CLOSE_DELAY );
		expect( panel.hidden ).toBe( true );
	} );

	it( 'keeps a hover-opened panel open when the label is clicked', () => {
		const item = addItem();
		const { panel } = wardrobe( item );

		pointer( item, 'pointerenter' );
		jest.advanceTimersByTime( OPEN_DELAY );
		item.querySelector( 'button' ).click();
		expect( panel.hidden ).toBe( false );
	} );

	it( 'ignores touch and hover when set to open on click', () => {
		const item = addItem( 'click' );
		const { panel } = wardrobe( item );

		pointer( item, 'pointerenter' );
		jest.advanceTimersByTime( OPEN_DELAY );
		expect( panel.hidden ).toBe( true );

		const hoverItem = addItem();
		const hover = wardrobe( hoverItem );
		const touch = new Event( 'pointerenter' );
		touch.pointerType = 'touch';
		hoverItem.dispatchEvent( touch );
		jest.advanceTimersByTime( OPEN_DELAY );
		expect( hover.panel.hidden ).toBe( true );
	} );

	it( 'closes on Escape and puts focus back on the label', () => {
		const item = addItem();
		const toggle = item.querySelector( 'button' );
		const { panel } = wardrobe( item );

		toggle.click();
		panel.querySelector( 'a' ).focus();
		document.dispatchEvent(
			new window.KeyboardEvent( 'keydown', { key: 'Escape' } )
		);
		expect( panel.hidden ).toBe( true );
		expect( document.activeElement ).toBe( toggle );
	} );

	it( 'closes on a click outside and when focus leaves the item', () => {
		const item = addItem();
		const toggle = item.querySelector( 'button' );
		const { panel } = wardrobe( item );
		const outside = document.createElement( 'button' );
		document.body.append( outside );

		toggle.click();
		outside.click();
		expect( panel.hidden ).toBe( true );

		toggle.click();
		item.dispatchEvent(
			new window.FocusEvent( 'focusout', { relatedTarget: outside } )
		);
		expect( panel.hidden ).toBe( true );

		toggle.click();
		item.dispatchEvent(
			new window.FocusEvent( 'focusout', {
				relatedTarget: panel.querySelector( 'a' ),
			} )
		);
		expect( panel.hidden ).toBe( false );
	} );

	it( 'keeps one panel open at a time', () => {
		const first = addItem();
		const second = addItem();
		const one = wardrobe( first );
		const two = wardrobe( second );

		first.querySelector( 'button' ).click();
		second.querySelector( 'button' ).click();
		expect( one.panel.hidden ).toBe( true );
		expect( two.panel.hidden ).toBe( false );
	} );

	it( 'opens nothing where the label button is hidden', () => {
		const item = addItem();
		const toggle = item.querySelector( 'button' );
		toggle.style.display = 'none';
		const { open, panel } = wardrobe( item );

		open( true );
		expect( panel.hidden ).toBe( true );
	} );

	it( 'shows the look card for the link under the pointer or in focus', () => {
		const panel = document.createElement( 'div' );
		panel.innerHTML = `<ul class="purplish-wardrobe-links"><li><a href="#">New in</a></li><li><a href="#">Coats</a></li><li><a href="#">Shoes</a></li></ul>
			<div class="purplish-wardrobe-look">1</div><div class="purplish-wardrobe-look">2</div>`;
		looks( panel );
		const [ first, second ] = panel.querySelectorAll(
			'.purplish-wardrobe-look'
		);
		const links = panel.querySelectorAll( 'a' );

		expect( first.classList.contains( 'is-shown' ) ).toBe( true );
		pointer( links[ 1 ], 'pointerenter' );
		expect( second.classList.contains( 'is-shown' ) ).toBe( true );
		expect( first.classList.contains( 'is-shown' ) ).toBe( false );
		pointer( links[ 2 ], 'pointerenter' );
		expect( second.classList.contains( 'is-shown' ) ).toBe( true );
		links[ 0 ].dispatchEvent( new Event( 'focus' ) );
		expect( first.classList.contains( 'is-shown' ) ).toBe( true );
	} );

	it( 'shows a look card when Tab moves into it', () => {
		const panel = document.createElement( 'div' );
		panel.innerHTML = `<ul class="purplish-wardrobe-links"><li><a href="#">New in</a></li><li><a href="#">Coats</a></li></ul>
			<div class="purplish-wardrobe-look"><a href="#one">Shop</a></div><div class="purplish-wardrobe-look"><a href="#two">Shop</a></div>`;
		document.body.append( panel );
		looks( panel );
		const [ first, second ] = panel.querySelectorAll(
			'.purplish-wardrobe-look'
		);

		second.querySelector( 'a' ).focus();
		expect( second.classList.contains( 'is-shown' ) ).toBe( true );
		expect( first.classList.contains( 'is-shown' ) ).toBe( false );
		first.querySelector( 'a' ).focus();
		expect( first.classList.contains( 'is-shown' ) ).toBe( true );
		panel.remove();
	} );

	it( 'leaves a single look card alone', () => {
		const panel = document.createElement( 'div' );
		panel.innerHTML = `<ul class="purplish-wardrobe-links"><li><a href="#">New in</a></li></ul><div class="purplish-wardrobe-look">1</div>`;
		looks( panel );
		expect( panel.querySelector( '.is-shown' ) ).toBeNull();
	} );
} );
