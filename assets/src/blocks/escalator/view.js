/**
 * Escalator: the pause button and the add to cart buttons. The stylesheet
 * moves the columns and stops them on hover and keyboard focus; this shows
 * the pause button and keeps a visitor's pause. The button keeps its label
 * and reports its state with aria-pressed. Add to cart goes through the
 * Store API, so the Mini-Cart updates, and the photo flies into it.
 */
import { flyToCart } from '../../shared/fly';
import { addToCart } from '../showfloor/cart';

const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * Turns on the add to cart buttons of one Escalator. A product shows in a
 * tile and in the copy that closes the loop, so both buttons follow along.
 *
 * @param {HTMLElement} block The block's wrapper.
 */
function carts( block ) {
	const api = block.dataset.api;
	if ( ! api ) {
		return;
	}
	const strings = JSON.parse( block.dataset.strings || '{}' );
	const twins = ( id ) =>
		block.querySelectorAll(
			'button.purplish-escalator-cart[data-product="' + id + '"]'
		);
	const show = ( id, key, state ) =>
		twins( id ).forEach( ( button ) => {
			const text = button.querySelector(
				'.purplish-escalator-cart-text'
			);
			text.textContent = strings[ key ] || text.textContent;
			button.disabled = 'adding' === key;
			button.classList.toggle( 'is-added', 'added' === state );
		} );

	block.addEventListener( 'click', async ( e ) => {
		const button = e.target.closest( 'button.purplish-escalator-cart' );
		if (
			! button ||
			button.disabled ||
			button.classList.contains( 'is-added' )
		) {
			return;
		}
		const id = button.dataset.product;
		show( id, 'adding' );
		const [ ok ] = await addToCart( api, [ Number( id ) ] );
		show( id, ok ? 'added' : 'again', ok ? 'added' : '' );
		if ( ! ok ) {
			return;
		}
		const cart = [
			...document.querySelectorAll( '.wc-block-mini-cart__button' ),
		].find( ( el ) => el.getClientRects().length );
		const photo = button
			.closest( '.purplish-escalator-tile' )
			.querySelector( 'img' );
		if ( cart && photo && ! reduce.matches ) {
			flyToCart( photo, cart, 1, 'purplish-escalator-fly' );
		}
		document.body.dispatchEvent(
			new CustomEvent( 'wc-blocks_added_to_cart', {
				bubbles: true,
				detail: { preserveCartData: false },
			} )
		);
	} );
}

/**
 * Turns on the pause button and add to cart buttons of one Escalator.
 *
 * @param {HTMLElement} block The block's wrapper.
 */
export function escalator( block ) {
	const button = block.querySelector( '.purplish-escalator-pause' );
	if ( ! button || ! button.hidden ) {
		return;
	}

	button.hidden = false;
	button.addEventListener( 'click', () => {
		const paused = ! block.classList.contains( 'is-paused' );
		block.classList.toggle( 'is-paused', paused );
		button.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
	} );
	carts( block );
}

document
	.querySelectorAll( '.wp-block-purplish-escalator' )
	.forEach( escalator );
