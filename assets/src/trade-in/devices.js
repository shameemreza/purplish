/**
 * The trade-in helpers that don't touch the page: reading the devices from
 * the grouped select, the lists each pick offers, the estimate and price
 * words, and adding the choice to a Store API add.
 */

/**
 * The devices in the grouped select, in its order.
 *
 * @param {HTMLSelectElement} select The device select.
 * @return {Array<{key: string, brand: string, model: string, storage: string, low: number, high: number}>} Devices.
 */
export function readDevices( select ) {
	return Array.from(
		select.querySelectorAll( 'optgroup option' ),
		( o ) => ( {
			key: o.value,
			brand: o.parentNode.label,
			model: o.dataset.model,
			storage: o.dataset.storage || '',
			low: Number( o.dataset.low ),
			high: Number( o.dataset.high ),
		} )
	);
}

/**
 * The values one pick offers, given the picks before it, each once and in
 * table order.
 *
 * @param {Array<Object>} devices Devices.
 * @param {string}        field   `brand`, `model` or `storage`.
 * @param {Object}        picked  Picks so far, by field.
 * @return {string[]} Values.
 */
export function choices( devices, field, picked ) {
	const before = [ 'brand', 'model' ].slice(
		0,
		[ 'brand', 'model', 'storage' ].indexOf( field )
	);
	const values = devices
		.filter( ( d ) =>
			before.every( ( key ) => d[ key ] === picked[ key ] )
		)
		.map( ( d ) => d[ field ] );
	return [ ...new Set( values ) ];
}

/**
 * The device the three picks point to, or undefined.
 *
 * @param {Array<Object>} devices Devices.
 * @param {Object}        picked  Picks by field.
 * @return {Object|undefined} Device.
 */
export function findDevice( devices, picked ) {
	return devices.find(
		( d ) =>
			d.brand === picked.brand &&
			d.model === picked.model &&
			d.storage === picked.storage
	);
}

/**
 * An amount in the store's money format.
 *
 * @param {number} amount Amount.
 * @param {Object} c      Format: s symbol, p position, d decimals, ds and ts separators.
 * @return {string} Price.
 */
export function money( amount, c ) {
	const [ whole, part ] = Math.max( 0, amount ).toFixed( c.d ).split( '.' );
	const number =
		whole.replace( /\B(?=(\d{3})+(?!\d))/g, c.ts ) +
		( part ? c.ds + part : '' );
	return (
		{
			right: number + c.s,
			left_space: c.s + ' ' + number,
			right_space: number + ' ' + c.s,
		}[ c.p ] || c.s + number
	);
}

/**
 * Fills `%s`, `%1$s` and `%2$s` in a phrase.
 *
 * @param {string}   phrase Phrase.
 * @param {string[]} values Values.
 * @return {string} Text.
 */
export function fill( phrase, values ) {
	let next = 0;
	return phrase.replace( /%(?:(\d)\$)?s/g, ( match, n ) =>
		n ? values[ n - 1 ] : values[ next++ ]
	);
}

/**
 * The estimate line: a range, or one figure when low and high match.
 *
 * @param {Object} device Device.
 * @param {Object} data   The field's data: c money format, t words.
 * @return {string} Text.
 */
export function estimateText( device, data ) {
	return device.low === device.high
		? fill( data.t.one, [ money( device.high, data.c ) ] )
		: fill( data.t.range, [
				money( device.low, data.c ),
				money( device.high, data.c ),
			] );
}

/**
 * The price for the picked options: the lowest price among the variations
 * that fit the picks, worded "from" while they differ. With nothing picked
 * or no fit, the product's own price. A variation with an empty value for
 * an attribute fits any pick of it.
 *
 * @param {Array<[Object<string, string>, number]>} rows   Each variation's attributes and price.
 * @param {Object<string, string>}                  picked Picked values by `attribute_*` name.
 * @param {number}                                  price  The product's lowest price.
 * @param {boolean}                                 from   Whether the product's prices differ.
 * @return {{price: number, from: boolean}} Price.
 */
export function pickPrice( rows, picked, price, from ) {
	const chosen = Object.entries( picked ).filter( ( [ , value ] ) => value );
	const prices = new Set();
	if ( chosen.length ) {
		rows.forEach( ( [ attributes, amount ] ) => {
			if (
				chosen.every(
					( [ name, value ] ) =>
						! attributes[ name ] || attributes[ name ] === value
				)
			) {
				prices.add( amount );
			}
		} );
	}
	return prices.size
		? { price: Math.min( ...prices ), from: prices.size > 1 }
		: { price, from };
}

/**
 * The price after trade-in: the price minus the highest value, worded
 * "from" when the estimate is a range or the price isn't settled yet.
 * Empty when the estimate covers the whole price.
 *
 * @param {number}  price  Price.
 * @param {Object}  device Device.
 * @param {boolean} from   Whether the price is the lowest of several.
 * @param {Object}  data   The field's data.
 * @return {string} Text.
 */
export function netText( price, device, from, data ) {
	if ( price - device.high <= 0 ) {
		return '';
	}
	const up = from || device.low !== device.high;
	return fill( up ? data.t.netup : data.t.net, [
		money( price - device.high, data.c ),
	] );
}

/**
 * Adds the trade-in fields to each Store API add-item body for a product
 * that has them. A batch carries several requests; a direct add carries
 * one body.
 *
 * @param {Object}                      body   Parsed request body.
 * @param {boolean}                     batch  Whether the body is a batch.
 * @param {(id: number) => Object|null} fields The fields for a product ID, or null.
 * @return {boolean} Whether anything was added.
 */
export function addFields( body, batch, fields ) {
	let added = false;
	const bodies = batch
		? ( Array.isArray( body?.requests ) ? body.requests : [] )
				.filter( ( r ) => /cart\/add-item/.test( r?.path || '' ) )
				.map( ( r ) => r.body )
		: [ body ];
	bodies.forEach( ( item ) => {
		const extra = item && fields( Number( item.id ) );
		if ( extra ) {
			Object.assign( item, extra );
			added = true;
		}
	} );
	return added;
}
