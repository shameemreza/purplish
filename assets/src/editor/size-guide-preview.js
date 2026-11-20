/**
 * The Size guide link on the Variation Selector in the editor: a sample
 * link at the end of the attribute name it shows beside on the store,
 * added again when the editor draws the names again. It goes inside the
 * name, since wrapping the name the way the server does would fight the
 * editor's own markup. It opens nothing here.
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { canvasElement } from './canvas';

const NAME =
	'.wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name';
const RULER =
	'<svg class="purplish-size-guide__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3.5 15.5 15.5 3.5l5 5-12 12z"/><path d="M7 12l1.6 1.6M9.5 9.5l2.2 2.2M12 7l1.6 1.6M14.5 4.5l2.2 2.2"/></svg>';

/**
 * Picks the name the link sits beside: one that reads like a size, or
 * the first.
 *
 * @param {Element} block The Variation Selector.
 * @return {Element|null} The name element.
 */
function sizeName( block ) {
	const names = Array.from( block.querySelectorAll( NAME ) );
	// The link's own words say "Size", so they don't count.
	const own = ( name ) =>
		name.textContent.replace(
			name.querySelector( '.purplish-size-guide__link' )?.textContent ??
				'',
			''
		);
	return (
		names.find( ( name ) => /size/i.test( own( name ) ) ) ||
		names[ 0 ] ||
		null
	);
}

/**
 * Adds the sample link.
 *
 * @param {string}      clientId The block's client ID.
 * @param {string|null} text     The link text, or null for no link.
 */
export function useSizeGuide( clientId, text ) {
	useEffect( () => {
		const block = null !== text && canvasElement( clientId );
		if ( ! block ) {
			return;
		}
		const doc = block.ownerDocument;
		const label = text || __( 'Size guide', 'purplish' );
		const place = () => {
			const name = sizeName( block );
			const current = block.querySelector( '.purplish-size-guide__link' );
			if (
				current &&
				current.parentElement === name &&
				current.textContent === label
			) {
				return;
			}
			current?.remove();
			if ( ! name ) {
				return;
			}
			const link = doc.createElement( 'span' );
			link.className = 'purplish-size-guide__link';
			link.setAttribute( 'contenteditable', 'false' );
			link.innerHTML = RULER;
			const words = doc.createElement( 'span' );
			words.className = 'purplish-size-guide__text';
			words.textContent = label;
			link.appendChild( words );
			name.appendChild( link );
		};
		place();
		const watch = new doc.defaultView.MutationObserver( place );
		watch.observe( block, { childList: true, subtree: true } );
		return () => {
			watch.disconnect();
			block
				.querySelectorAll( '.purplish-size-guide__link' )
				.forEach( ( link ) => link.remove() );
		};
	}, [ clientId, text ] );
}
