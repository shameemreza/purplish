/**
 * Keeps the Stock Indicator's dot in step with the picked variation.
 * WooCommerce swaps the indicator's text when a shopper picks one, but not
 * its stock class, so the dot would keep the product's color.
 */

const STATE = 'wc-block-components-product-stock-indicator--';

/**
 * The stock class and low stock flag of the product or variation whose
 * stock text is the one shown, from WooCommerce's page state.
 *
 * @param {string} text  The indicator's text.
 * @param {Object} store The `woocommerce/products` state.
 * @return {{state: string, low: boolean}|null} Null when no entry matches.
 */
export function stockFor( text, store ) {
	if ( ! text || ! store ) {
		return null;
	}
	const entries = [
		...Object.values( store.productVariations || {} ),
		...Object.values( store.products || {} ),
	];
	for ( const entry of entries ) {
		const stock = entry && entry.stock_availability;
		if ( stock && stock.class && stock.text === text ) {
			return {
				state: stock.class,
				low:
					null !== entry.low_stock_remaining &&
					undefined !== entry.low_stock_remaining &&
					entry.low_stock_remaining > 0,
			};
		}
	}
	return null;
}

/**
 * Puts a stock state on an indicator. With no match the state classes go,
 * and the dot takes the text color, as the words still say it.
 *
 * @param {Element}                            indicator The indicator.
 * @param {{state: string, low: boolean}|null} stock     Its stock.
 */
export function applyStock( indicator, stock ) {
	[ ...indicator.classList ]
		.filter( ( name ) => name.startsWith( STATE ) )
		.forEach( ( name ) => indicator.classList.remove( name ) );
	indicator.classList.toggle( 'purplish-stock-low', !! stock && stock.low );
	if ( stock ) {
		indicator.classList.add( STATE + stock.state );
	}
}

/**
 * Watches an indicator's text and updates its stock state on each change.
 *
 * @param {Element} indicator The indicator, with WooCommerce's `data-wp-text`.
 * @param {Object}  store     The `woocommerce/products` state.
 */
export function watchStock( indicator, store ) {
	let last = indicator.textContent;
	new indicator.ownerDocument.defaultView.MutationObserver( () => {
		const text = indicator.textContent;
		if ( text !== last ) {
			last = text;
			applyStock( indicator, stockFor( text.trim(), store ) );
		}
	} ).observe( indicator, {
		childList: true,
		characterData: true,
		subtree: true,
	} );
}
