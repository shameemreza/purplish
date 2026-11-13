/**
 * One Wardrobe menu item on the page. The label button opens and closes the
 * panel; with the Navigation set to open submenus on hover, a mouse resting
 * on the item opens it too. Escape, a click outside and focus leaving the
 * item close it, and only one panel is open at a time. Where the button is
 * hidden (phones and the Navigation overlay show the plain link), nothing
 * opens.
 */
import { place } from './place';

export const OPEN_DELAY = 120;
export const CLOSE_DELAY = 220;

let openOne = null;

/**
 * Turns one item on.
 *
 * @param {HTMLElement} item The Wardrobe menu item.
 * @return {?Object} open( bool ) and the panel, or null without a panel.
 */
export function wardrobe( item ) {
	const toggle = item.querySelector( ':scope > .purplish-wardrobe-toggle' );
	const panel = item.querySelector( ':scope > .purplish-wardrobe-panel' );
	if ( ! toggle || ! panel ) {
		return null;
	}

	const doc = item.ownerDocument;
	const view = doc.defaultView;
	let timer;
	let byHover = false;

	const usable = () => 'none' !== view.getComputedStyle( toggle ).display;

	const open = ( state ) => {
		clearTimeout( timer );
		if ( state === ! panel.hidden || ( state && ! usable() ) ) {
			return;
		}
		if ( state ) {
			if ( openOne ) {
				openOne( false );
			}
			panel.hidden = false;
			place( item, panel );
			openOne = open;
		} else {
			panel.hidden = true;
			byHover = false;
			openOne = openOne === open ? null : openOne;
		}
		toggle.setAttribute( 'aria-expanded', String( state ) );
		item.classList.toggle( 'is-open', state );
	};

	const later = ( state, wait ) => {
		clearTimeout( timer );
		timer = setTimeout( () => {
			open( state );
			byHover = state;
		}, wait );
	};

	// A click right after the hover opened the panel keeps it open.
	toggle.addEventListener( 'click', () => {
		const state = byHover || panel.hidden;
		byHover = false;
		open( state );
	} );

	if ( 'hover' === item.dataset.open ) {
		item.addEventListener( 'pointerenter', ( event ) => {
			if ( 'mouse' !== event.pointerType ) {
				return;
			}
			if ( panel.hidden ) {
				later( true, OPEN_DELAY );
			} else {
				clearTimeout( timer );
			}
		} );
		item.addEventListener( 'pointerleave', ( event ) => {
			if ( 'mouse' === event.pointerType ) {
				later( false, CLOSE_DELAY );
			}
		} );
	}

	doc.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && ! panel.hidden ) {
			const inside = item.contains( doc.activeElement );
			open( false );
			if ( inside ) {
				toggle.focus();
			}
		}
	} );
	doc.addEventListener( 'click', ( event ) => {
		if ( ! item.contains( event.target ) ) {
			open( false );
		}
	} );
	item.addEventListener( 'focusout', ( event ) => {
		if ( event.relatedTarget && ! item.contains( event.relatedTarget ) ) {
			open( false );
		}
	} );
	view.addEventListener( 'resize', () => {
		if ( ! panel.hidden ) {
			return usable() ? place( item, panel ) : open( false );
		}
	} );

	looks( panel );
	item.classList.add( 'is-ready' );
	return { open, panel };
}

/**
 * With more than one look card in the panel, the card that matches the
 * link under the pointer or in focus shows: the first link's card, the
 * second link's card, and so on. Links past the last card keep the card
 * that shows. Tab moving into a card shows that card, so every card's own
 * links can be reached from the keyboard.
 *
 * @param {HTMLElement} panel The panel.
 */
export function looks( panel ) {
	const cards = panel.querySelectorAll( '.purplish-wardrobe-look' );
	if ( cards.length < 2 ) {
		return;
	}
	const show = ( index ) =>
		cards.forEach( ( card, at ) =>
			card.classList.toggle( 'is-shown', at === index )
		);
	panel
		.querySelectorAll( '.purplish-wardrobe-links a' )
		.forEach( ( link, index ) => {
			if ( index < cards.length ) {
				link.addEventListener( 'pointerenter', () => show( index ) );
				link.addEventListener( 'focus', () => show( index ) );
			}
		} );
	cards.forEach( ( card, index ) =>
		card.addEventListener( 'focusin', () => show( index ) )
	);
	show( 0 );
}
