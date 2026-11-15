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
 * The meter's sentence, with the amount in bold.
 *
 * @param {HTMLElement} text   The sentence element.
 * @param {string}      amount The amount left, formatted, or '' when the cart ships free.
 */
function write( text, amount ) {
	const key = amount || 'done';
	if ( text.dataset.said === key ) {
		return;
	}
	text.dataset.said = key;
	if ( ! amount ) {
		text.textContent = __( 'Your order ships free.', 'purplish' );
		return;
	}
	/* translators: %s: amount left to spend, such as $12.00. */
	const [ before, after = '' ] = __(
		'Spend %s more for free shipping.',
		'purplish'
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
		write(
			meter.querySelector( '.purplish-meter-text' ),
			now.done ? '' : money( now.left, cart.totals )
		);
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

if ( document.querySelector( METER ) ) {
	document.body.addEventListener( 'wc-blocks_added_to_cart', refresh );
	document.body.addEventListener( 'wc-blocks_removed_from_cart', refresh );
	window.addEventListener( 'wc-blocks_store_sync_required', refresh );
	watchStore();
}
