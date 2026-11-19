/**
 * Matching extras under Add to Cart + Options. The server prints the list
 * inside the block's form. When WooCommerce posts that form, the ticked
 * boxes post with it and the server adds them. When the block adds through
 * its own store instead, this notes the ticked boxes on submit, waits for
 * WooCommerce to say the product went in, then adds each extra through the
 * Store API and asks the cart to refresh.
 */

const LIST = '.purplish-extras';
// How long after the click the main product's add still counts.
const WAIT = 20000;

/**
 * Adds products to the cart through the Store API, one after another.
 *
 * @param {string}   api The Store API root, ending in a slash.
 * @param {string[]} ids Product IDs.
 * @return {Promise<void>} Resolves once all have been tried.
 */
async function add( api, ids ) {
	const cart = await fetch( api + 'cart', { credentials: 'same-origin' } );
	const nonce = cart.headers.get( 'Nonce' ) || '';
	for ( const id of ids ) {
		await fetch( api + 'cart/add-item', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', Nonce: nonce },
			body: JSON.stringify( { id: Number( id ), quantity: 1 } ),
		} );
	}
}

document.querySelectorAll( LIST ).forEach( ( list ) => {
	const form = list.closest( 'form' );
	// A form WooCommerce posts adds the extras on the server.
	if ( ! form || ! form.hasAttribute( 'data-wp-on--submit' ) ) {
		return;
	}
	let ticked = [];
	let at = 0;

	form.addEventListener( 'submit', () => {
		at = Date.now();
		ticked = form.classList.contains( 'is-invalid' )
			? []
			: Array.from(
					list.querySelectorAll( 'input:checked' ),
					( box ) => box.value
				);
	} );

	document.body.addEventListener( 'wc-blocks_added_to_cart', () => {
		const ids = Date.now() - at < WAIT ? ticked : [];
		ticked = [];
		if ( ! ids.length ) {
			return;
		}
		add( list.dataset.api, ids )
			.catch( () => {} )
			.then( () => {
				list.querySelectorAll( 'input:checked' ).forEach( ( box ) => {
					box.checked = false;
				} );
				window.dispatchEvent(
					new window.CustomEvent( 'wc-blocks_store_sync_required', {
						detail: { type: 'from_@wordpress/data' },
					} )
				);
			} );
	} );
} );
