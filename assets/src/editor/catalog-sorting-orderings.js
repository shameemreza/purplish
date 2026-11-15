/**
 * The orderings on Catalog Sorting, apart from the panel so tests can load
 * them without the editor.
 */
import { __ } from '@wordpress/i18n';

/**
 * The orderings a block can hide, in WooCommerce's order, with the option
 * that hides each and its label with and without the "Sort by" label.
 */
export const ORDERINGS = [
	{
		option: 'hidepopularity',
		label: __( 'Popularity', 'purplish' ),
		long: __( 'Sort by popularity', 'purplish' ),
	},
	{
		option: 'hiderating',
		label: __( 'Average rating', 'purplish' ),
		long: __( 'Sort by average rating', 'purplish' ),
	},
	{
		option: 'hidedate',
		label: __( 'Latest', 'purplish' ),
		long: __( 'Sort by latest', 'purplish' ),
	},
	{
		option: 'hideprice',
		label: __( 'Price: low to high', 'purplish' ),
		long: __( 'Sort by price: low to high', 'purplish' ),
	},
	{
		option: 'hidepricedesc',
		label: __( 'Price: high to low', 'purplish' ),
		long: __( 'Sort by price: high to low', 'purplish' ),
	},
];

/**
 * The orderings the shop shows for the saved options.
 *
 * @param {Object}  saved The block's saved `purplish` attribute.
 * @param {boolean} rated Whether a product has a rating.
 * @return {Object[]} The shown orderings.
 */
export function shownOrderings( saved, rated ) {
	return ORDERINGS.filter( ( { option } ) => {
		if ( saved[ option ] ) {
			return false;
		}
		return ! ( 'hiderating' === option && saved.rated && ! rated );
	} );
}
