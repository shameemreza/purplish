/**
 * The photo grid on the Product Gallery in the editor. The editor shows
 * only the block's one photo, so the grid shows it as the first tile and
 * copies of it as the rest, added again when the editor draws the block
 * again. The copies are pictures only: hidden from assistive tech and not
 * selectable.
 */
import { useEffect } from '@wordpress/element';

import { canvasElement } from './canvas';

const FRAME = '.wc-block-editor-product-gallery-large-image';
const PHOTO = '.wp-block-woocommerce-product-image img';
const TILE = 'purplish-gallery-tile';

// Five tiles in all: one large over two pairs, or two large over three.
export const COPIES = 4;

/**
 * Puts the copies after the block's photo, or takes them away when it has
 * none. Does nothing when they're already right, so it can run on every
 * change the editor makes.
 *
 * @param {Element} block The gallery block in the canvas.
 */
export function placeTiles( block ) {
	const frame = block.querySelector( FRAME );
	const photo = frame?.querySelector( PHOTO );
	const tiles = block.querySelectorAll( `.${ TILE }` );
	const src = photo?.getAttribute( 'src' ) || '';
	if (
		tiles.length === ( photo ? COPIES : 0 ) &&
		[ ...tiles ].every(
			( tile ) =>
				tile.parentElement === frame &&
				tile.firstChild?.getAttribute( 'src' ) === src
		)
	) {
		return;
	}
	tiles.forEach( ( tile ) => tile.remove() );
	if ( ! photo ) {
		return;
	}
	const doc = block.ownerDocument;
	for ( let at = 0; at < COPIES; at++ ) {
		const tile = doc.createElement( 'div' );
		tile.className = TILE;
		tile.setAttribute( 'aria-hidden', 'true' );
		tile.setAttribute( 'contenteditable', 'false' );
		const img = doc.createElement( 'img' );
		img.alt = '';
		img.src = src;
		[ 'srcset', 'sizes' ].forEach( ( name ) => {
			if ( photo.hasAttribute( name ) ) {
				img.setAttribute( name, photo.getAttribute( name ) );
			}
		} );
		tile.append( img );
		frame.append( tile );
	}
}

/**
 * Adds the copies.
 *
 * @param {string}  clientId The gallery block's client ID.
 * @param {boolean} on       Whether the gallery has a grid layout.
 */
export function useGalleryTiles( clientId, on ) {
	useEffect( () => {
		const block = on && canvasElement( clientId );
		if ( ! block ) {
			return;
		}
		const place = () => placeTiles( block );
		place();
		const watch = new block.ownerDocument.defaultView.MutationObserver(
			place
		);
		watch.observe( block, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: [ 'src' ],
		} );
		return () => {
			watch.disconnect();
			block
				.querySelectorAll( `.${ TILE }` )
				.forEach( ( tile ) => tile.remove() );
		};
	}, [ clientId, on ] );
}
