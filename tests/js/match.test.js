/**
 * The matching rule the page uses to follow the picked color.
 */
import { describe, expect, it } from '@jest/globals';
import { match, nextColor } from '../../assets/src/frontend/match';

const orange = { color: '#d94421', on: '#000000', tint: '#ffebe6' };
const blue = { color: '#2a519e', on: '#ffffff', tint: '#e8f1ff' };

const sweater = [
	{
		attributes: { attribute_color: 'Orange', attribute_size: 'S' },
		...orange,
	},
	{
		attributes: { attribute_color: 'Orange', attribute_size: 'M' },
		...orange,
	},
	{ attributes: { attribute_color: 'Blue', attribute_size: 'S' }, ...blue },
	{ attributes: { attribute_color: 'Blue', attribute_size: 'M' }, ...blue },
];

describe( 'match', () => {
	it( 'gives the color before a size is picked', () => {
		expect(
			match( sweater, { attribute_color: 'Blue', attribute_size: '' } )
		).toEqual( blue );
	} );

	it( 'gives the color for a full selection', () => {
		expect(
			match( sweater, { attribute_color: 'Orange', attribute_size: 'M' } )
		).toEqual( orange );
	} );

	it( 'gives nothing when a size alone fits both colors', () => {
		expect( match( sweater, { attribute_size: 'S' } ) ).toBeNull();
	} );

	it( 'gives nothing when nothing is picked', () => {
		expect(
			match( sweater, { attribute_color: '', attribute_size: '' } )
		).toBeNull();
	} );

	it( 'treats an empty value in a variation as any', () => {
		const anySize = [
			{
				attributes: { attribute_color: 'Blue', attribute_size: '' },
				...blue,
			},
		];
		expect(
			match( anySize, { attribute_color: 'Blue', attribute_size: 'XL' } )
		).toEqual( blue );
	} );

	it( 'gives nothing for a value no variation has', () => {
		expect( match( sweater, { attribute_color: 'Green' } ) ).toBeNull();
	} );
} );

describe( 'nextColor', () => {
	it( 'goes back to the default when the selection is reset', () => {
		expect(
			nextColor(
				sweater,
				{ attribute_color: '', attribute_size: '' },
				orange,
				blue
			)
		).toEqual( orange );
	} );

	it( 'goes back to the theme colors on reset without a default', () => {
		expect(
			nextColor( sweater, { attribute_color: '' }, null, blue )
		).toBeNull();
	} );

	it( 'keeps the current color when the pick fits more than one color', () => {
		expect(
			nextColor( sweater, { attribute_size: 'S' }, orange, blue )
		).toEqual( blue );
	} );

	it( 'follows a color pick', () => {
		expect(
			nextColor( sweater, { attribute_color: 'Blue' }, orange, orange )
		).toEqual( blue );
	} );
} );
