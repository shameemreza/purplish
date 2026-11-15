/*
 * The Catalog Sorting preview lists the orderings the shop will show.
 */
import { describe, expect, it } from '@jest/globals';
import { shownOrderings } from '../../assets/src/editor/catalog-sorting-orderings';

const options = ( saved, rated ) =>
	shownOrderings( saved, rated ).map( ( { option } ) => option );

describe( 'shownOrderings', () => {
	it( 'lists every ordering with nothing hidden', () => {
		expect( options( {}, false ) ).toEqual( [
			'hidepopularity',
			'hiderating',
			'hidedate',
			'hideprice',
			'hidepricedesc',
		] );
	} );

	it( 'leaves out the hidden ones', () => {
		expect(
			options( { hidepopularity: true, hidepricedesc: true }, true )
		).toEqual( [ 'hiderating', 'hidedate', 'hideprice' ] );
	} );

	it( 'shows average rating only once a product has one', () => {
		expect( options( { rated: true }, false ) ).not.toContain(
			'hiderating'
		);
		expect( options( { rated: true }, true ) ).toContain( 'hiderating' );
	} );
} );
