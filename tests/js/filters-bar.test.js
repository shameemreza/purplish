/*
 * Layout: Bar on Product Filters: a label opens its list, one list is open
 * at a time in a row, and a click outside or Escape closes it. A plain
 * comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import {
	afterEach,
	beforeAll,
	beforeEach,
	describe,
	expect,
	it,
	jest,
} from '@jest/globals';
import {
	CLOSE_DELAY,
	OPEN_DELAY,
	inRow,
	setPlain,
	watchBars,
} from '../../assets/src/filters/bar';

const wide = { matches: true };

const plain = (
	id,
	label
) => `<div data-block-name="woocommerce/product-filter-attribute">
	<h3 class="wp-block-heading"><button type="button" class="purplish-filters-bar-toggle" aria-expanded="false" aria-controls="${ id }">${ label }</button></h3>
	<div class="purplish-filters-bar-panel" id="${ id }" hidden><input type="checkbox" id="${ id }-box"></div>
</div>`;

// Stands in for the core Accordion's own click handler.
const accordion = (
	id,
	label
) => `<div data-block-name="woocommerce/product-filter-price">
	<div class="wp-block-accordion"><div class="wp-block-accordion-item">
		<h4 class="wp-block-accordion-heading"><button type="button" class="wp-block-accordion-heading__toggle" aria-expanded="false" id="${ id }">${ label }</button></h4>
		<div class="wp-block-accordion-panel" hidden><span id="${ id }-in">Slider</span></div>
	</div></div>
</div>`;

const page = ( overlay = 'is-mobile-overlay', more = '' ) => {
	document.body.innerHTML = `<div class="wc-block-product-filters purplish-filters-layout-bar ${ overlay } ${ more }">
		<div class="wc-block-product-filters__overlay-content">
			${ plain( 'color', 'Color' ) }${ plain( 'size', 'Size' ) }${ accordion(
				'price',
				'Price'
			) }
		</div>
	</div><p id="away">Products</p><a id="next" href="#products">Products</a>`;
	document
		.querySelectorAll( '.wp-block-accordion-heading__toggle' )
		.forEach( ( toggle ) =>
			toggle.addEventListener( 'click', () => {
				const open = 'true' !== toggle.getAttribute( 'aria-expanded' );
				toggle.setAttribute( 'aria-expanded', String( open ) );
				toggle
					.closest( '.wp-block-accordion-item' )
					.querySelector( '.wp-block-accordion-panel' ).hidden =
					! open;
			} )
		);
};

const label = ( id ) =>
	document.querySelector( `[aria-controls="${ id }"], #${ id }` );
const states = () =>
	[ 'color', 'size', 'price' ]
		.map( ( id ) => label( id ).getAttribute( 'aria-expanded' ) )
		.join( ',' );

beforeAll( () => {
	watchBars( document, wide );
} );

beforeEach( () => {
	wide.matches = true;
	page();
} );

describe( 'setPlain', () => {
	it( 'opens and closes the list with its label', () => {
		setPlain( label( 'color' ), true );
		expect( label( 'color' ).getAttribute( 'aria-expanded' ) ).toBe(
			'true'
		);
		expect( document.getElementById( 'color' ).hidden ).toBe( false );

		setPlain( label( 'color' ), false );
		expect( document.getElementById( 'color' ).hidden ).toBe( true );
	} );
} );

describe( 'inRow', () => {
	it( 'is a row past the breakpoint, with the overlay on phones or off', () => {
		const bar = document.querySelector( '.purplish-filters-layout-bar' );
		expect( inRow( bar, true ) ).toBe( true );
		expect( inRow( bar, false ) ).toBe( false );

		bar.classList.replace(
			'is-mobile-overlay',
			'is-filter-drawer-disabled'
		);
		expect( inRow( bar, true ) ).toBe( true );

		bar.classList.remove( 'is-filter-drawer-disabled' );
		expect( inRow( bar, true ) ).toBe( false );
	} );
} );

describe( 'watchBars', () => {
	it( 'keeps one list open at a time, plain or Accordion', () => {
		label( 'color' ).click();
		expect( states() ).toBe( 'true,false,false' );

		label( 'size' ).click();
		expect( states() ).toBe( 'false,true,false' );
		expect( document.getElementById( 'color' ).hidden ).toBe( true );

		label( 'price' ).click();
		expect( states() ).toBe( 'false,false,true' );

		label( 'color' ).click();
		expect( states() ).toBe( 'true,false,false' );
	} );

	it( 'closes a list from its own label', () => {
		label( 'size' ).click();
		label( 'size' ).click();
		expect( states() ).toBe( 'false,false,false' );
	} );

	it( 'keeps a list open for a click inside it', () => {
		label( 'color' ).click();
		document.getElementById( 'color-box' ).click();
		expect( states() ).toBe( 'true,false,false' );

		label( 'price' ).click();
		document.getElementById( 'price-in' ).click();
		expect( states() ).toBe( 'false,false,true' );
	} );

	it( 'closes on a click outside', () => {
		label( 'price' ).click();
		document.getElementById( 'away' ).click();
		expect( states() ).toBe( 'false,false,false' );
	} );

	it( 'closes on Escape and puts focus back on the label', () => {
		label( 'size' ).click();
		document.getElementById( 'away' ).focus();
		document.dispatchEvent(
			new window.KeyboardEvent( 'keydown', { key: 'Escape' } )
		);
		expect( states() ).toBe( 'false,false,false' );
		expect( document.activeElement ).toBe( label( 'size' ) );
	} );

	it( 'closes a list when Tab moves focus past it', () => {
		label( 'color' ).click();
		label( 'color' ).focus();
		document.getElementById( 'color-box' ).focus();
		expect( states() ).toBe( 'true,false,false' );
		document.getElementById( 'next' ).focus();
		expect( states() ).toBe( 'false,false,false' );

		label( 'price' ).click();
		label( 'price' ).focus();
		document.getElementById( 'next' ).focus();
		expect( states() ).toBe( 'false,false,false' );
	} );

	it( 'closes a list when Tab moves on to the next label', () => {
		label( 'color' ).click();
		label( 'color' ).focus();
		label( 'size' ).focus();
		expect( states() ).toBe( 'false,false,false' );
	} );

	it( 'keeps a list open when focus leaves the page or lands on nothing', () => {
		label( 'color' ).click();
		document.getElementById( 'color-box' ).focus();
		document.getElementById( 'color-box' ).blur();
		expect( states() ).toBe( 'true,false,false' );
	} );

	it( 'lets several lists open in the overlay, and leaves Escape to it', () => {
		wide.matches = false;
		label( 'color' ).click();
		label( 'size' ).click();
		document.getElementById( 'away' ).click();
		document.dispatchEvent(
			new window.KeyboardEvent( 'keydown', { key: 'Escape' } )
		);
		expect( states() ).toBe( 'true,true,false' );
		document.getElementById( 'color-box' ).focus();
		document.getElementById( 'next' ).focus();
		expect( states() ).toBe( 'true,true,false' );
	} );

	it( 'never makes a row with the overlay on every screen', () => {
		page( '' );
		label( 'color' ).click();
		label( 'size' ).click();
		expect( states() ).toBe( 'true,true,false' );
	} );
} );

describe( 'Open on hover', () => {
	// A mouse moving from one node to another.
	const move = ( from, to, pointerType = 'mouse' ) => {
		const fire = ( type, target, related ) => {
			const event = new window.MouseEvent( type, {
				bubbles: true,
				relatedTarget: related,
			} );
			Object.defineProperty( event, 'pointerType', {
				value: pointerType,
			} );
			target.dispatchEvent( event );
		};
		if ( from ) {
			fire( 'pointerout', from, to );
		}
		if ( to ) {
			fire( 'pointerover', to, from );
		}
	};
	const away = () => document.getElementById( 'away' );

	beforeEach( () => {
		jest.useFakeTimers();
		page( 'is-mobile-overlay', 'purplish-filters-hover' );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'opens after a short rest on the label, and moves to the next one', () => {
		move( away(), label( 'color' ) );
		expect( states() ).toBe( 'false,false,false' );
		jest.advanceTimersByTime( OPEN_DELAY );
		expect( states() ).toBe( 'true,false,false' );

		move( label( 'color' ), label( 'price' ) );
		jest.advanceTimersByTime( CLOSE_DELAY );
		expect( states() ).toBe( 'false,false,true' );
	} );

	it( 'opens nothing for a mouse passing over the row', () => {
		move( away(), label( 'color' ) );
		move( label( 'color' ), away() );
		jest.advanceTimersByTime( CLOSE_DELAY );
		expect( states() ).toBe( 'false,false,false' );
	} );

	it( 'stays open while the mouse is in the list, and closes after it leaves', () => {
		move( away(), label( 'size' ) );
		jest.advanceTimersByTime( OPEN_DELAY );
		move( label( 'size' ), document.getElementById( 'size-box' ) );
		jest.advanceTimersByTime( CLOSE_DELAY );
		expect( states() ).toBe( 'false,true,false' );

		move( document.getElementById( 'size-box' ), away() );
		jest.advanceTimersByTime( CLOSE_DELAY - 1 );
		expect( states() ).toBe( 'false,true,false' );
		jest.advanceTimersByTime( 1 );
		expect( states() ).toBe( 'false,false,false' );
	} );

	it( 'keeps a list open when its label is clicked after hover opened it', () => {
		move( away(), label( 'price' ) );
		jest.advanceTimersByTime( OPEN_DELAY );
		label( 'price' ).click();
		expect( states() ).toBe( 'false,false,true' );

		label( 'price' ).click();
		expect( states() ).toBe( 'false,false,false' );
	} );

	it( 'leaves touch, and rows without the option, to the tap', () => {
		move( away(), label( 'color' ), 'touch' );
		jest.advanceTimersByTime( OPEN_DELAY );
		expect( states() ).toBe( 'false,false,false' );

		page();
		move( away(), label( 'color' ) );
		jest.advanceTimersByTime( OPEN_DELAY );
		expect( states() ).toBe( 'false,false,false' );
	} );
} );
