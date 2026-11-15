/**
 * The free shipping meter's sums, from a Store API cart. Amounts are in
 * the currency's minor units, as the Store API sends them.
 */

/**
 * A Store API money string as a whole number.
 *
 * @param {string|number|undefined} value The amount.
 * @return {number} The amount, 0 when it isn't one.
 */
const minor = ( value ) => parseInt( value, 10 ) || 0;

/**
 * The cart total the Free shipping method compares with its minimum,
 * worked out as WooCommerce does: the subtotal as shown, then the
 * discounts and their tax when discounts count.
 *
 * @param {Object}  totals          The cart's `totals`.
 * @param {Object}  rules           How the store counts.
 * @param {boolean} rules.tax       Whether prices show with tax.
 * @param {boolean} rules.discounts Whether discounts come off.
 * @return {number} The total.
 */
export function counted( totals, { tax, discounts } ) {
	let total = minor( totals.total_items );
	if ( tax ) {
		total += minor( totals.total_items_tax );
	}
	if ( discounts ) {
		total -= minor( totals.total_discount );
		if ( tax ) {
			total -= minor( totals.total_discount_tax );
		}
	}
	return total;
}

/**
 * Whether a free shipping rate is on offer, which a coupon can give before
 * the minimum is reached.
 *
 * @param {Array} packages The cart's `shipping_rates`.
 * @return {boolean} True when one package offers it.
 */
export function offersFree( packages ) {
	return ( packages || [] ).some( ( pack ) =>
		( pack.shipping_rates || [] ).some(
			( rate ) => rate.method_id === 'free_shipping'
		)
	);
}

/**
 * Where the cart stands against the minimum.
 *
 * @param {number}  total The counted total.
 * @param {number}  min   The minimum.
 * @param {boolean} free  Whether free shipping is on offer anyway.
 * @return {{left: number, share: number, done: boolean}} The amount left, the bar's share from 0 to 1, and whether it ships free.
 */
export function standing( total, min, free = false ) {
	const done = free || total >= min;
	return {
		left: done ? 0 : min - total,
		share: done ? 1 : Math.max( 0, Math.min( 1, total / min ) ),
		done,
	};
}

/**
 * An amount in the store's currency format.
 *
 * @param {number} amount   The amount in minor units.
 * @param {Object} currency The cart's `totals`, which carry the currency fields.
 * @return {string} The amount, such as `$12.00` or `12,00 €`.
 */
export function money( amount, currency ) {
	const places = minor( currency.currency_minor_unit );
	const sign = amount < 0 ? '-' : '';
	const whole = Math.abs( amount );
	const units = Math.floor( whole / 10 ** places ).toString();
	const grouped = units.replace(
		/\B(?=(\d{3})+(?!\d))/g,
		currency.currency_thousand_separator ?? ','
	);
	const cents = places
		? ( currency.currency_decimal_separator ?? '.' ) +
			( whole % 10 ** places ).toString().padStart( places, '0' )
		: '';
	return (
		sign +
		( currency.currency_prefix || '' ) +
		grouped +
		cents +
		( currency.currency_suffix || '' )
	);
}
