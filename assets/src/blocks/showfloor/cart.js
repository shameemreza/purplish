/**
 * Adds products to the cart through the Store API: one request for one
 * product, one batch request for several. The nonce comes from the cart
 * response, so a cached page still works.
 */

let nonce = '';

/**
 * Adds one of each product.
 *
 * @param {string}   api Store API root, ending in a slash.
 * @param {number[]} ids Product IDs.
 * @return {Promise<boolean[]>} Whether each product was added, in order.
 */
export async function addToCart( api, ids ) {
	const item = ( id ) => ( { id, quantity: 1 } );
	try {
		if ( ! nonce ) {
			const cart = await fetch( api + 'cart', {
				credentials: 'same-origin',
			} );
			nonce = cart.headers.get( 'Nonce' ) || '';
		}
		const batch = ids.length > 1;
		const body = batch
			? {
					requests: ids.map( ( id ) => ( {
						path: '/wc/store/v1/cart/add-item',
						method: 'POST',
						body: item( id ),
						headers: { Nonce: nonce },
					} ) ),
				}
			: item( ids[ 0 ] );
		const response = await fetch(
			api + ( batch ? 'batch' : 'cart/add-item' ),
			{
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', Nonce: nonce },
				body: JSON.stringify( body ),
			}
		);
		nonce = response.headers.get( 'Nonce' ) || nonce;
		if ( ! response.ok ) {
			return ids.map( () => false );
		}
		if ( ! batch ) {
			return [ true ];
		}
		const { responses } = await response.json();
		return ids.map(
			( id, n ) => !! responses[ n ] && responses[ n ].status < 300
		);
	} catch {
		return ids.map( () => false );
	}
}
