/*
 * The Stock Indicator's dot follows the variation a shopper picks.
 * A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it } from '@jest/globals';
import { applyStock, stockFor, watchStock } from '../../assets/src/stock/stock';

const STATE = 'wc-block-components-product-stock-indicator--';

const store = {
	products: {
		10: {
			id: 10,
			stock_availability: { text: '', class: 'in-stock' },
			low_stock_remaining: null,
		},
	},
	productVariations: {
		11: {
			stock_availability: { text: '10 in stock', class: 'in-stock' },
			low_stock_remaining: null,
		},
		12: {
			stock_availability: { text: '2 in stock', class: 'in-stock' },
			low_stock_remaining: 2,
		},
		13: {
			stock_availability: { text: 'Out of stock', class: 'out-of-stock' },
			low_stock_remaining: null,
		},
		14: {
			stock_availability: {
				text: 'Available on backorder',
				class: 'available-on-backorder',
			},
			low_stock_remaining: 0,
		},
	},
};

const flush = () => new Promise( ( done ) => setTimeout( done, 0 ) );

describe( 'stock dot', () => {
	let indicator;

	beforeEach( () => {
		document.body.innerHTML = `<div class="wc-block-components-product-stock-indicator ${ STATE }in-stock purplish-stock purplish-stock-dot" data-wp-text="state.productInContext.stock_availability.text"></div>`;
		indicator = document.body.firstElementChild;
	} );

	it( 'finds the stock for the text shown', () => {
		expect( stockFor( '10 in stock', store ) ).toEqual( {
			state: 'in-stock',
			low: false,
		} );
		expect( stockFor( '2 in stock', store ) ).toEqual( {
			state: 'in-stock',
			low: true,
		} );
		expect( stockFor( 'Available on backorder', store ) ).toEqual( {
			state: 'available-on-backorder',
			low: false,
		} );
		expect( stockFor( 'Something else', store ) ).toBeNull();
		expect( stockFor( '', store ) ).toBeNull();
		expect( stockFor( '10 in stock', null ) ).toBeNull();
	} );

	it( 'swaps the stock classes, or drops them with no match', () => {
		applyStock( indicator, { state: 'in-stock', low: true } );
		expect( indicator.classList.contains( STATE + 'in-stock' ) ).toBe(
			true
		);
		expect( indicator.classList.contains( 'purplish-stock-low' ) ).toBe(
			true
		);

		applyStock( indicator, { state: 'out-of-stock', low: false } );
		expect( indicator.classList.contains( STATE + 'in-stock' ) ).toBe(
			false
		);
		expect( indicator.classList.contains( STATE + 'out-of-stock' ) ).toBe(
			true
		);
		expect( indicator.classList.contains( 'purplish-stock-low' ) ).toBe(
			false
		);

		applyStock( indicator, null );
		expect(
			[ ...indicator.classList ].filter( ( name ) =>
				name.startsWith( STATE )
			)
		).toEqual( [] );
		expect( indicator.classList.contains( 'purplish-stock-dot' ) ).toBe(
			true
		);
	} );

	it( 'follows the text WooCommerce writes for each variation', async () => {
		watchStock( indicator, store );

		indicator.textContent = '2 in stock';
		await flush();
		expect( indicator.classList.contains( 'purplish-stock-low' ) ).toBe(
			true
		);

		indicator.textContent = 'Out of stock';
		await flush();
		expect( indicator.classList.contains( STATE + 'out-of-stock' ) ).toBe(
			true
		);
		expect( indicator.classList.contains( 'purplish-stock-low' ) ).toBe(
			false
		);

		indicator.textContent = 'Available on backorder';
		await flush();
		expect(
			indicator.classList.contains( STATE + 'available-on-backorder' )
		).toBe( true );
	} );
} );
