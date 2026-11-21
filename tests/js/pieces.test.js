/**
 * Bundles, composites and rows: the page follows the item the shopper
 * changed last.
 */
import { describe, expect, it } from '@jest/globals';
import {
	createOrder,
	pieceColor,
	quantityOf,
} from '../../assets/src/frontend/pieces';

const set = ( color ) => ( { color, on: '#fff', ink: color, tint: '#eee' } );
const own = set( '#c9a77c' );
const harbor = set( '#2b3345' );
const pine = set( '#375242' );
const blush = set( '#c0849b' );

const jacket = {
	variations: [
		{
			attributes: { attribute_pa_color: 'harbor', attribute_size: 'S' },
			...harbor,
		},
		{
			attributes: { attribute_pa_color: 'harbor', attribute_size: 'M' },
			...harbor,
		},
		{
			attributes: { attribute_pa_color: 'pine', attribute_size: 'S' },
			...pine,
		},
		{
			attributes: { attribute_pa_color: 'pine', attribute_size: 'M' },
			...pine,
		},
	],
};

describe( 'pieceColor', () => {
	it( 'reads a variable item from its picked options', () => {
		expect(
			pieceColor(
				jacket,
				{ attribute_pa_color: 'pine', attribute_size: '' },
				null
			)
		).toEqual( pine );
	} );

	it( 'keeps the item color when the picks no longer settle on one', () => {
		expect(
			pieceColor(
				jacket,
				{ attribute_pa_color: '', attribute_size: 'S' },
				harbor
			)
		).toEqual( harbor );
	} );

	it( 'clears when nothing is picked', () => {
		expect(
			pieceColor(
				jacket,
				{ attribute_pa_color: '', attribute_size: '' },
				harbor
			)
		).toBeNull();
	} );

	it( 'gives a fixed product its color only while it is chosen', () => {
		expect( pieceColor( { set: blush }, null, null, true ) ).toEqual(
			blush
		);
		expect( pieceColor( { set: blush }, null, blush, false ) ).toBeNull();
	} );

	it( 'gives nothing for a product without colors', () => {
		expect( pieceColor( undefined, {}, null ) ).toBeNull();
	} );
} );

describe( 'createOrder', () => {
	it( 'follows the item changed last and hands back on clear', () => {
		const order = createOrder( own );
		expect( order.update( 'jacket', harbor ) ).toEqual( harbor );
		expect( order.update( 'knit', blush ) ).toEqual( blush );
		expect( order.update( 'knit', null ) ).toEqual( harbor );
		expect( order.update( 'jacket', null ) ).toEqual( own );
	} );

	it( 'moves an item changed again to the top', () => {
		const order = createOrder( own );
		order.update( 'jacket', harbor );
		order.update( 'knit', blush );
		expect( order.update( 'jacket', pine ) ).toEqual( pine );
		expect( order.update( 'jacket', null ) ).toEqual( blush );
	} );

	it( 'clearing an item that was never in the order changes nothing', () => {
		const order = createOrder( own );
		order.update( 'jacket', harbor );
		expect( order.update( 'knit', null ) ).toEqual( harbor );
	} );

	it( 'goes back to the theme colors when there is no start color', () => {
		const order = createOrder( null );
		order.update( 'jacket', harbor );
		expect( order.update( 'jacket', null ) ).toBeNull();
	} );
} );

describe( 'quantityOf', () => {
	it( 'reads number fields and checkboxes', () => {
		expect( quantityOf( { type: 'number', value: '2' } ) ).toBe( 2 );
		expect( quantityOf( { type: 'number', value: '' } ) ).toBe( 0 );
		expect(
			quantityOf( { type: 'checkbox', checked: true, value: '1' } )
		).toBe( 1 );
		expect(
			quantityOf( { type: 'checkbox', checked: false, value: '1' } )
		).toBe( 0 );
	} );
} );
