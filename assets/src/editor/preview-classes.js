/**
 * The same wrapper classes and effect color the server adds on the front
 * end, worked out in the editor from the map PHP hands over
 * (`BlockEffects::preview_map()`).
 */

const HEX = /^#([0-9a-f]{3}){1,2}$/i;

/**
 * The add to cart button as the editor draws it. On the front end the same
 * button also has `single_add_to_cart_button`.
 */
const CART_BUTTON = '.wc-block-components-product-button__button';

/**
 * The link inside a button block. In the editor it's the text box.
 */
const BUTTON_LINK = '.wp-block-button__link';

/**
 * A product row in the Cart page or the Mini-Cart drawer.
 */
const CART_ROW = '.wc-block-cart-items__row';

/**
 * For each glass class, the buttons that take the highlight: inside the
 * block, or the block itself, as the drawer's Go to checkout.
 */
export const GLASS_BUTTONS = {
	'purplish-buybutton-glass':
		'.wc-block-cart__submit-button, .wc-block-components-checkout-place-order-button, .wc-block-mini-cart__footer-checkout',
	'purplish-cart-glass': CART_BUTTON,
	'purplish-cards-hoverbutton-glass': CART_BUTTON,
	'purplish-button-glass': BUTTON_LINK,
};

/**
 * Buttons and cart rows the editor draws inside an inert wrapper, which
 * never matches `:hover`. The preview marks them `purplish-hover` while the
 * mouse is over them, and the hover styles accept that class too.
 */
export const HOVER_BUTTONS = {
	'woocommerce/add-to-cart-with-options': CART_BUTTON,
	'woocommerce/product-collection': CART_BUTTON,
	'woocommerce/mini-cart-contents': CART_ROW,
	'woocommerce/cart': CART_ROW,
};

/**
 * For each block prefix with googly eyes, the button they go inside.
 */
export const EYES_BUTTONS = {
	cart: CART_BUTTON,
	button: BUTTON_LINK,
};

/**
 * How many googly eyes the classes ask for.
 *
 * @param {string[]} classes The block's Purplish classes.
 * @param {string}   prefix  The block's prefix, as `cart`.
 * @return {number} 0, 1 or 2.
 */
export function eyesIn( classes, prefix ) {
	if ( classes.includes( 'purplish-' + prefix + '-eyes-two' ) ) {
		return 2;
	}
	return classes.includes( 'purplish-' + prefix + '-eyes-one' ) ? 1 : 0;
}

/**
 * The 1.0 page options that have a resting or hover look, as the body
 * classes `PageData::body_class()` prints with a color set. Missing keys
 * take the defaults, as `Options::clean()`. Photo transitions, title
 * effects, the halo and color dots play on a color change or need each
 * option's own color, so the editor leaves them out.
 *
 * @param {Object} page  Page data: `parts` (every part with its default) and `switches` (the ones the editor shows).
 * @param {Object} saved The block's `purplish` attribute.
 * @return {string[]} Body classes.
 */
export function pageClasses( page, saved ) {
	if ( ! page || ! page.parts ) {
		return [];
	}
	const values = saved && typeof saved === 'object' ? saved : {};
	const known = Object.keys( page.parts );
	const parts = Array.isArray( values.parts )
		? known.filter( ( part ) => values.parts.includes( part ) )
		: known.filter( ( part ) => page.parts[ part ] );

	return [
		'purplish',
		'purplish-active',
		...parts.map( ( part ) => 'purplish-part-' + part ),
		...( page.switches || [] )
			.filter(
				( name ) => values[ name ] === true || values[ name ] === 'yes'
			)
			.map( ( name ) => 'purplish-' + name ),
	];
}

/**
 * The sample color set as the custom properties the page styles read.
 *
 * @param {Object} page Page data with `sample`: color, on, ink and tint.
 * @return {Object} Custom properties, empty without a valid sample.
 */
export function pageStyle( page ) {
	const sample = page && page.sample;
	if (
		! sample ||
		! [ 'color', 'on', 'ink', 'tint' ].every( ( key ) =>
			HEX.test( sample[ key ] )
		)
	) {
		return {};
	}
	return {
		'--purplish-accent': sample.color,
		'--purplish-on': sample.on,
		'--purplish-ink': sample.ink,
		'--purplish-tint': sample.tint,
	};
}

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
 * Whether saved options meet an option's needs, as `needs_met()` in PHP.
 * Each need is an option, its values and whether the saved value must be
 * one of them.
 *
 * @param {Array}  needs  The option's needs.
 * @param {Object} values The block's saved options.
 * @return {boolean} Whether every need is met.
 */
export function needsMet( needs, values ) {
	return needs.every(
		( [ option, list, must ] ) => list.includes( values[ option ] ) === must
	);
}

/**
 * Wrapper classes for a block's saved options, as `classes_for()` in PHP.
 *
 * @param {Object} block Map entry: prefix, options, legacy and needs keys.
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
	const needs = block.needs || {};
	const classes = [];
	Object.entries( block.options ).forEach( ( [ option, allowed ] ) => {
		if (
			! ( option in values ) ||
			( needs[ option ] && ! needsMet( needs[ option ], values ) )
		) {
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
