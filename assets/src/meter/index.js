/**
 * Free shipping meter: keeps the amount left and the bar up to date as the
 * cart changes. The server prints the meter with the store's minimum and
 * how it counts the cart; this reads the cart from the Store API after each
 * change and works out the same sums. Its styles are in the effects
 * stylesheet, which the block's options already load, so the editor has
 * them too.
 */
import { __ } from '@wordpress/i18n';

import { counted, money, offersFree, standing } from './math';

const METER = '.purplish-meter';

/**
 * The meter's sentence, with the amount in bold. The server prints the
 * block's wording on the meter; these are for a meter without it.
 *
 * @param {HTMLElement} meter  The meter.
 * @param {string}      amount The amount left, formatted, or '' when the cart ships free.
 */
function write( meter, amount ) {
	const text = meter.querySelector( '.purplish-meter-text' );
	const key = amount || 'done';
	if ( text.dataset.said === key ) {
		return;
	}
	text.dataset.said = key;
	if ( ! amount ) {
		text.textContent =
			meter.dataset.done || __( 'Your order ships free.', 'purplish' );
		return;
	}
	const [ before, after = '' ] = (
		meter.dataset.left ||
		/* translators: %s: amount left to spend, such as $12.00. */
		__( 'Spend %s more for free shipping.', 'purplish' )
	).split( '%s' );
	const strong = document.createElement( 'strong' );
	strong.textContent = amount;
	text.replaceChildren( before, strong, after );
}

/**
 * Draws every meter for a cart.
 *
 * @param {Object} cart A Store API cart.
 */
function draw( cart ) {
	document.querySelectorAll( METER ).forEach( ( meter ) => {
		meter.hidden = ! cart.items_count;
		if ( meter.hidden ) {
			return;
		}
		const total = counted( cart.totals, {
			tax: meter.dataset.tax === '1',
			discounts: meter.dataset.discounts === '1',
		} );
		const now = standing(
			total,
			parseInt( meter.dataset.min, 10 ),
			meter.dataset.either === '1' && offersFree( cart.shipping_rates )
		);
		meter.classList.toggle( 'is-done', now.done );
		meter.style.setProperty( '--purplish-meter', now.share.toFixed( 4 ) );
		write( meter, now.done ? '' : money( now.left, cart.totals ) );
	} );
}

let timer = 0;
let asked = 0;

/**
 * Reads the cart a moment after the last change, so a run of changes
 * makes one request, and draws only the answer to the latest one.
 */
function refresh() {
	window.clearTimeout( timer );
	timer = window.setTimeout( () => {
		const meter = document.querySelector( METER );
		if ( ! meter ) {
			return;
		}
		const ask = ++asked;
		window
			.fetch( meter.dataset.endpoint, { credentials: 'same-origin' } )
			.then( ( response ) => ( response.ok ? response.json() : null ) )
			.then( ( cart ) => {
				if ( cart && cart.totals && ask === asked ) {
					draw( cart );
				}
			} )
			.catch( () => {} );
	}, 250 );
}

/**
 * The Cart page changes its cart through the block data store. Its totals
 * changing is the cue to read the cart again.
 */
function watchStore() {
	const data = window.wp && window.wp.data;
	if ( ! data || ! data.select || ! data.subscribe ) {
		return;
	}
	let last = '';
	data.subscribe( () => {
		let totals;
		try {
			totals = data.select( 'wc/store/cart' )?.getCartTotals?.();
		} catch {
			return;
		}
		const now = totals ? JSON.stringify( totals ) : '';
		if ( now && last && now !== last ) {
			refresh();
		}
		last = now || last;
	} );
}

/**
 * Moves a Cart page meter set to the totals column to the top of that
 * column, and back in whenever WooCommerce draws the column again. The
 * server prints it before the Cart block, where it stays hidden.
 */
function placeInTotals() {
	document
		.querySelectorAll( '.purplish-meter-totals' )
		.forEach( ( meter ) => {
			const cart = meter.nextElementSibling;
			if ( ! cart || ! cart.matches( '.wp-block-woocommerce-cart' ) ) {
				return;
			}
			const place = () => {
				const column = cart.querySelector( '.wc-block-cart__sidebar' );
				if ( column && column.firstElementChild !== meter ) {
					column.prepend( meter );
				}
			};
			place();
			new window.MutationObserver( place ).observe( cart, {
				childList: true,
				subtree: true,
			} );
		} );
}

if ( document.querySelector( METER ) ) {
	placeInTotals();
	document.body.addEventListener( 'wc-blocks_added_to_cart', refresh );
	document.body.addEventListener( 'wc-blocks_removed_from_cart', refresh );
	window.addEventListener( 'wc-blocks_store_sync_required', refresh );
	watchStore();
}
