/**
 * Products for the editor, from the Store API, so names, photos and
 * prices match what visitors see. Each product loads once per editor
 * session.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const PATH = '/wc/store/v1/products';
const cache = new Map();

/**
 * The products with these IDs, in the same order, once they've loaded.
 * Products that are gone or not published are left out.
 *
 * @param {number[]} ids Product IDs.
 * @return {Object[]} Store API products.
 */
export function useProducts( ids ) {
	const key = ids.join( ',' );
	const [ , setLoaded ] = useState( 0 );
	useEffect( () => {
		const missing = key
			.split( ',' )
			.map( Number )
			.filter( ( id ) => id && ! cache.has( id ) );
		if ( ! missing.length ) {
			return;
		}
		let live = true;
		apiFetch( {
			path: `${ PATH }?include=${ missing.join( ',' ) }&per_page=100`,
		} )
			.then( ( list ) => {
				list.forEach( ( product ) => cache.set( product.id, product ) );
				missing.forEach(
					( id ) => cache.has( id ) || cache.set( id, null )
				);
				if ( live ) {
					setLoaded( ( n ) => n + 1 );
				}
			} )
			.catch( () => {} );
		return () => {
			live = false;
		};
	}, [ key ] );
	return ids.map( ( id ) => cache.get( id ) ).filter( Boolean );
}

/**
 * Up to eight products whose name matches the search.
 *
 * @param {string} search Search words.
 * @return {Promise<Object[]>} Store API products.
 */
export function searchProducts( search ) {
	return apiFetch( {
		path: `${ PATH }?search=${ encodeURIComponent( search ) }&per_page=8`,
	} ).then( ( list ) => {
		list.forEach( ( product ) => cache.set( product.id, product ) );
		return list;
	} );
}

/**
 * A price in the store's currency, from Store API minor units.
 *
 * @param {Object} prices Store API prices.
 * @param {number} amount Amount in minor units; defaults to the price.
 * @return {string} The price, like $45.00.
 */
export function money( prices, amount = Number( prices.price ) ) {
	const digits = prices.currency_minor_unit;
	const value = ( amount / 10 ** digits )
		.toFixed( digits )
		.replace( '.', prices.currency_decimal_separator );
	return prices.currency_prefix + value + prices.currency_suffix;
}
