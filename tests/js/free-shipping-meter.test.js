/*
 * Free shipping meter: the cart total WooCommerce compares with the
 * minimum, the bar's share, and the amount in the store's format.
 */
import { describe, expect, it } from '@jest/globals';
import {
	counted,
	money,
	offersFree,
	standing,
} from '../../assets/src/meter/math';

const totals = {
	total_items: '4000',
	total_items_tax: '800',
	total_discount: '500',
	total_discount_tax: '100',
	currency_minor_unit: 2,
	currency_prefix: '$',
	currency_suffix: '',
	currency_decimal_separator: '.',
	currency_thousand_separator: ',',
};

describe( 'counted', () => {
	it( 'takes the subtotal less discounts, without tax', () => {
		expect( counted( totals, { tax: false, discounts: true } ) ).toBe(
			3500
		);
	} );

	it( 'adds the tax and takes off the discount tax when prices show with tax', () => {
		expect( counted( totals, { tax: true, discounts: true } ) ).toBe(
			4200
		);
	} );

	it( 'leaves discounts and their tax on when the method ignores them', () => {
		expect( counted( totals, { tax: true, discounts: false } ) ).toBe(
			4800
		);
		expect( counted( totals, { tax: false, discounts: false } ) ).toBe(
			4000
		);
	} );

	it( 'reads missing amounts as 0', () => {
		expect( counted( {}, { tax: true, discounts: true } ) ).toBe( 0 );
	} );
} );

describe( 'standing', () => {
	it( 'gives the amount left and the share', () => {
		expect( standing( 3000, 5000 ) ).toEqual( {
			left: 2000,
			share: 0.6,
			done: false,
		} );
	} );

	it( 'is done at the minimum and past it, with a full bar', () => {
		expect( standing( 5000, 5000 ) ).toEqual( {
			left: 0,
			share: 1,
			done: true,
		} );
		expect( standing( 9000, 5000 ).share ).toBe( 1 );
	} );

	it( 'is done when a free rate is on offer anyway', () => {
		expect( standing( 100, 5000, true ).done ).toBe( true );
	} );

	it( 'never draws a share under 0', () => {
		expect( standing( -200, 5000 ).share ).toBe( 0 );
	} );
} );

describe( 'offersFree', () => {
	it( 'finds a free shipping rate in any package', () => {
		const packages = [
			{ shipping_rates: [ { method_id: 'flat_rate' } ] },
			{ shipping_rates: [ { method_id: 'free_shipping' } ] },
		];
		expect( offersFree( packages ) ).toBe( true );
		expect( offersFree( packages.slice( 0, 1 ) ) ).toBe( false );
		expect( offersFree( undefined ) ).toBe( false );
	} );
} );

describe( 'money', () => {
	it( 'formats with the prefix, separators and minor units', () => {
		expect( money( 123456, totals ) ).toBe( '$1,234.56' );
		expect( money( 5, totals ) ).toBe( '$0.05' );
	} );

	it( 'follows a suffix currency with other separators', () => {
		const euro = {
			currency_minor_unit: 2,
			currency_prefix: '',
			currency_suffix: ' €',
			currency_decimal_separator: ',',
			currency_thousand_separator: '.',
		};
		expect( money( 1234500, euro ) ).toBe( '12.345,00 €' );
	} );

	it( 'leaves the decimals off a currency without minor units', () => {
		expect(
			money( 2500, {
				...totals,
				currency_minor_unit: 0,
				currency_prefix: '¥',
			} )
		).toBe( '¥2,500' );
	} );
} );
