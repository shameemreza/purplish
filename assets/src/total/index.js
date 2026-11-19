/**
 * Order total on the Cart page's pinned checkout bar. On phones WooCommerce
 * pins Proceed to Checkout to the foot of the screen while the in-page
 * button is further down. This keeps the cart's total, in the store's
 * format, in a CSS variable on the Cart block, and the stylesheet shows it
 * after the label only while the bar is pinned. The Cart block's own
 * element holds it because WooCommerce draws everything inside it again.
 */
import { select, subscribe } from '@wordpress/data';

import { money } from '../meter/math';

const CART = '.wp-block-woocommerce-cart';

/**
 * What follows the label: WooCommerce's separator from Place Order and the
 * total, '' for an empty cart, or null before the cart has loaded.
 *
 * @return {string|null} The text.
 */
function after() {
	let cart;
	try {
		cart = select( 'wc/store/cart' );
	} catch {
		return null;
	}
	const totals = cart?.getCartTotals?.();
	if ( ! totals || ! cart.hasFinishedResolution?.( 'getCartData' ) ) {
		return null;
	}
	if ( ! cart.getCartData().itemsCount ) {
		return '';
	}
	return '· ' + money( parseInt( totals.total_price, 10 ) || 0, totals );
}

let shown = null;

/**
 * Writes the total on each Cart block when it changes.
 */
function paint() {
	const text = after();
	if ( null === text || text === shown ) {
		return;
	}
	shown = text;
	document.querySelectorAll( CART ).forEach( ( block ) => {
		if ( text ) {
			block.style.setProperty(
				'--purplish-total',
				JSON.stringify( text )
			);
		} else {
			block.style.removeProperty( '--purplish-total' );
		}
	} );
}

paint();
subscribe( paint );
