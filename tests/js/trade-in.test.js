/*
 * The trade-in picks, estimate words and the Store API fields. A space
 * between the amount and the symbol is a no-break space, as WooCommerce
 * prints it. A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it } from '@jest/globals';
import {
	readDevices,
	choices,
	findDevice,
	money,
	fill,
	estimateText,
	netText,
	pickPrice,
	addFields,
} from '../../assets/src/trade-in/devices';

const devices = [
	{
		key: 'a1',
		brand: 'Apple',
		model: 'iPhone 13',
		storage: '128GB',
		low: 600,
		high: 680,
	},
	{
		key: 'a2',
		brand: 'Apple',
		model: 'iPhone 13',
		storage: '256GB',
		low: 700,
		high: 780,
	},
	{
		key: 'a3',
		brand: 'Apple',
		model: 'iPhone 12',
		storage: '',
		low: 450,
		high: 450,
	},
	{
		key: 's1',
		brand: 'Samsung',
		model: 'Galaxy S23',
		storage: '256GB',
		low: 900,
		high: 1000,
	},
];

const data = {
	c: { s: 'RM', p: 'left', d: 2, ds: '.', ts: ',' },
	t: {
		one: 'Estimated trade-in value: %s',
		range: 'Estimated trade-in value: %1$s to %2$s',
		net: 'Price after trade-in: %s',
		netup: 'Price after trade-in: from %s',
	},
};

describe( 'readDevices', () => {
	it( 'reads the grouped select in order', () => {
		document.body.innerHTML =
			'<select><option value="">Choose</option><optgroup label="Apple">' +
			'<option value="a1" data-model="iPhone 13" data-storage="128GB" data-low="600" data-high="680">iPhone 13, 128GB</option>' +
			'<option value="a3" data-model="iPhone 12" data-storage="" data-low="450" data-high="450">iPhone 12</option>' +
			'</optgroup></select>';
		expect( readDevices( document.querySelector( 'select' ) ) ).toEqual( [
			devices[ 0 ],
			devices[ 2 ],
		] );
	} );
} );

describe( 'choices and findDevice', () => {
	it( 'lists each value once, filtered by the picks before it', () => {
		expect( choices( devices, 'brand', {} ) ).toEqual( [
			'Apple',
			'Samsung',
		] );
		expect( choices( devices, 'model', { brand: 'Apple' } ) ).toEqual( [
			'iPhone 13',
			'iPhone 12',
		] );
		expect(
			choices( devices, 'storage', {
				brand: 'Apple',
				model: 'iPhone 13',
			} )
		).toEqual( [ '128GB', '256GB' ] );
		expect(
			choices( devices, 'storage', {
				brand: 'Apple',
				model: 'iPhone 12',
			} )
		).toEqual( [ '' ] );
	} );

	it( 'finds the device the three picks point to', () => {
		expect(
			findDevice( devices, {
				brand: 'Apple',
				model: 'iPhone 12',
				storage: '',
			} ).key
		).toBe( 'a3' );
		expect(
			findDevice( devices, {
				brand: 'Apple',
				model: 'iPhone 13',
				storage: '',
			} )
		).toBeUndefined();
	} );
} );

describe( 'money and words', () => {
	it( 'formats like the store', () => {
		expect( money( 1234.5, data.c ) ).toBe( 'RM1,234.50' );
		expect( money( -5, data.c ) ).toBe( 'RM0.00' );
		expect(
			money( 1234.5, {
				s: '€',
				p: 'right_space',
				d: 0,
				ds: ',',
				ts: '.',
			} )
		).toBe( '1.235\u00a0€' );
		expect(
			money( 9, { s: '$', p: 'left_space', d: 2, ds: '.', ts: '' } )
		).toBe( '$\u00a09.00' );
	} );

	it( 'fills numbered and plain placeholders', () => {
		expect( fill( '%2$s then %1$s', [ 'a', 'b' ] ) ).toBe( 'b then a' );
		expect( fill( '%s and %s', [ 'a', 'b' ] ) ).toBe( 'a and b' );
	} );

	it( 'shows a range, or one figure when low and high match', () => {
		expect( estimateText( devices[ 0 ], data ) ).toBe(
			'Estimated trade-in value: RM600.00 to RM680.00'
		);
		expect( estimateText( devices[ 2 ], data ) ).toBe(
			'Estimated trade-in value: RM450.00'
		);
	} );

	it( 'takes the highest value off the price', () => {
		expect( netText( 4599, devices[ 2 ], false, data ) ).toBe(
			'Price after trade-in: RM4,149.00'
		);
		expect( netText( 4599, devices[ 0 ], false, data ) ).toBe(
			'Price after trade-in: from RM3,919.00'
		);
		expect( netText( 4599, devices[ 2 ], true, data ) ).toBe(
			'Price after trade-in: from RM4,149.00'
		);
		expect( netText( 400, devices[ 2 ], false, data ) ).toBe( '' );
	} );
} );

describe( 'pickPrice', () => {
	const rows = [
		[ { attribute_color: 'Pine', attribute_size: 'S' }, 149 ],
		[ { attribute_color: 'Pine', attribute_size: 'M' }, 159 ],
		[ { attribute_color: 'Harbor', attribute_size: '' }, 129 ],
	];

	it( 'keeps the product price with nothing picked or no fit', () => {
		expect( pickPrice( rows, {}, 129, true ) ).toEqual( {
			price: 129,
			from: true,
		} );
		expect(
			pickPrice( rows, { attribute_color: 'Red' }, 129, true )
		).toEqual( { price: 129, from: true } );
	} );

	it( 'takes the lowest fitting price, "from" while they differ', () => {
		expect(
			pickPrice( rows, { attribute_color: 'Pine' }, 129, true )
		).toEqual( { price: 149, from: true } );
		expect(
			pickPrice(
				rows,
				{ attribute_color: 'Pine', attribute_size: 'M' },
				129,
				true
			)
		).toEqual( { price: 159, from: false } );
	} );

	it( 'lets an empty variation value fit any pick', () => {
		expect(
			pickPrice(
				rows,
				{ attribute_color: 'Harbor', attribute_size: 'L' },
				129,
				true
			)
		).toEqual( { price: 129, from: false } );
	} );
} );

describe( 'addFields', () => {
	const fields = ( id ) =>
		12 === id
			? { purplish_trade_in: 'yes', purplish_trade_in_device: 'a1' }
			: null;

	it( 'adds the fields to matching add-item requests in a batch', () => {
		const body = {
			requests: [
				{
					path: '/wc/store/v1/cart/add-item',
					body: { id: 12, quantity: 1 },
				},
				{
					path: '/wc/store/v1/cart/add-item',
					body: { id: 99, quantity: 1 },
				},
				{ path: '/wc/store/v1/cart/remove-item', body: { id: 12 } },
			],
		};
		expect( addFields( body, true, fields ) ).toBe( true );
		expect( body.requests[ 0 ].body.purplish_trade_in_device ).toBe( 'a1' );
		expect( body.requests[ 1 ].body.purplish_trade_in ).toBeUndefined();
		expect( body.requests[ 2 ].body.purplish_trade_in ).toBeUndefined();
	} );

	it( 'adds the fields to a direct add, and leaves others alone', () => {
		const body = { id: '12', quantity: 1 };
		expect( addFields( body, false, fields ) ).toBe( true );
		expect( body.purplish_trade_in ).toBe( 'yes' );
		expect( addFields( { id: 99 }, false, fields ) ).toBe( false );
		expect( addFields( { nope: true }, true, fields ) ).toBe( false );
	} );
} );
