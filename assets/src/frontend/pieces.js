/**
 * Pages made of other products (bundles, composites, mix and match,
 * grouped): the page follows the item the shopper changed last.
 */
import { nextColor } from './match';

/**
 * @typedef {import('./match').ColorSet} ColorSet
 */

/**
 * The color an item gives the page. A variable item follows its picked
 * options with the same rule as a variable product; a product with one
 * color gives it while it is chosen.
 *
 * @param {Object|undefined}            product Product data: `variations`, or a fixed `set`.
 * @param {Object<string, string>|null} picked  Picked options by `attribute_*` name.
 * @param {ColorSet|null}               current The item's color now.
 * @param {boolean}                     chosen  Whether a fixed product is chosen (quantity above 0, or picked in a component).
 * @return {ColorSet|null} Null when the item gives no color.
 */
export function pieceColor( product, picked, current, chosen = true ) {
	if ( product?.set ) {
		return chosen ? product.set : null;
	}
	if ( ! product?.variations?.length ) {
		return null;
	}
	return nextColor( product.variations, picked ?? {}, null, current );
}

/**
 * Keeps the items in the order the shopper changed them.
 *
 * @param {ColorSet|null} start The page's starting color, or null for the theme colors.
 * @return {{update: function(string, ColorSet|null): (ColorSet|null)}} `update( key, set )` records an item's new color and returns the color the page shows.
 */
export function createOrder( start ) {
	const order = [];
	const colors = new Map();

	return {
		update( key, set ) {
			const at = order.indexOf( key );
			if ( -1 !== at ) {
				order.splice( at, 1 );
			}
			if ( set ) {
				order.push( key );
				colors.set( key, set );
			} else {
				colors.delete( key );
			}
			return order.length
				? colors.get( order[ order.length - 1 ] )
				: start;
		},
	};
}

/**
 * The quantity a row's field holds: a number field's value, or 1 for a
 * ticked checkbox (sold-individually grouped children).
 *
 * @param {{type: string, value: string, checked?: boolean}} field The field.
 * @return {number} The quantity.
 */
export function quantityOf( field ) {
	if ( 'checkbox' === field.type ) {
		return field.checked ? 1 : 0;
	}
	const value = parseFloat( field.value );
	return Number.isFinite( value ) && value > 0 ? value : 0;
}
