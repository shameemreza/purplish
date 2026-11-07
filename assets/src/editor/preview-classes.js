/**
 * The same wrapper classes and effect color the server adds on the front
 * end, worked out in the editor from the map PHP hands over
 * (`BlockEffects::preview_map()`).
 */

const HEX = /^#([0-9a-f]{3}){1,2}$/i;

/**
 * For each glass class, the buttons inside it that take the highlight.
 */
export const GLASS_BUTTONS = {
	'purplish-buybutton-glass':
		'.wc-block-cart__submit-button, .wc-block-components-checkout-place-order-button',
};

/**
 * The selector of the glass buttons inside a block with these classes.
 *
 * @param {string[]} classes The block's Purplish classes.
 * @return {string} Selector, empty when the block has no glass look.
 */
export function glassSelector( classes ) {
	return classes
		.map( ( name ) => GLASS_BUTTONS[ name ] )
		.filter( Boolean )
		.join( ', ' );
}

/**
 * Wrapper classes for a block's saved options, as `classes_for()` in PHP.
 *
 * @param {Object} block Map entry: prefix, options and legacy keys.
 * @param {Object} saved The block's `purplish` attribute.
 * @return {string[]} Class names, empty when no option is on.
 */
export function classesFor( block, saved ) {
	if ( ! block || ! saved || typeof saved !== 'object' ) {
		return [];
	}

	const values = { ...saved };
	Object.entries( block.legacy || {} ).forEach(
		( [ old, [ key, value ] ] ) => {
			if ( values[ old ] === true && ! ( key in values ) ) {
				values[ key ] = value;
			}
		}
	);

	const prefix = 'purplish-' + block.prefix;
	const classes = [];
	Object.entries( block.options ).forEach( ( [ option, allowed ] ) => {
		if ( ! ( option in values ) ) {
			return;
		}
		if ( allowed === true && values[ option ] === true ) {
			classes.push( prefix + '-' + option );
		} else if (
			Array.isArray( allowed ) &&
			allowed.includes( values[ option ] )
		) {
			classes.push( prefix + '-' + option + '-' + values[ option ] );
		}
	} );

	return classes.length ? [ prefix, ...classes ] : [];
}

/**
 * Black or white, whichever reads better on the color, as
 * `Contrast::on()` in PHP.
 *
 * @param {string} hex Color, as #rgb or #rrggbb.
 * @return {string} `#000000` or `#ffffff`.
 */
export function onColor( hex ) {
	if ( ! HEX.test( hex ) ) {
		return '#000000';
	}
	const full = hex.length === 4 ? hex.replace( /[0-9a-f]/gi, '$&$&' ) : hex;
	const [ r, g, b ] = [ 1, 3, 5 ].map( ( i ) => {
		const channel = parseInt( full.slice( i, i + 2 ), 16 ) / 255;
		return channel <= 0.04045
			? channel / 12.92
			: Math.pow( ( channel + 0.055 ) / 1.055, 2.4 );
	} );
	const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;

	return ( luminance + 0.05 ) / 0.05 >= 1.05 / ( luminance + 0.05 )
		? '#000000'
		: '#ffffff';
}

/**
 * The effect color as the two custom properties the effect styles read,
 * or nothing when no valid color is saved.
 *
 * @param {Object} saved The block's `purplish` attribute.
 * @return {Object} Style properties.
 */
export function colorStyle( saved ) {
	const color = saved && saved.color;
	if ( typeof color !== 'string' || ! HEX.test( color ) ) {
		return {};
	}
	return {
		'--purplish-effect': color,
		'--purplish-effect-on': onColor( color ),
	};
}
