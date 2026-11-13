/**
 * Swap the photo, on the Color Dots block. A click or tap on a dot keeps
 * its color on the card's photo, with a ring on the dot. With On hover, a
 * mouse over a dot shows its color until it leaves the dots. Listens on
 * the page, so cards that Load more or a filter brings in work too.
 */
import { pick, preview, settle, warm } from './swap';

const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );
const DOT = '.purplish-color-dots-swap .purplish-color-dots-dot';

document.addEventListener( 'click', ( event ) => {
	const dot = event.target.closest( DOT );
	if ( dot ) {
		pick( dot, reduced.matches );
	}
} );

document.addEventListener( 'pointerover', ( event ) => {
	const dots = event.target.closest( '.purplish-color-dots-swap' );
	if ( ! dots ) {
		return;
	}
	warm( dots );
	const dot = event.target.closest( DOT );
	if (
		dot &&
		'mouse' === event.pointerType &&
		dots.classList.contains( 'purplish-color-dots-swap-hover' )
	) {
		preview( dot, reduced.matches );
	}
} );

document.addEventListener( 'pointerout', ( event ) => {
	const dots = event.target.closest( '.purplish-color-dots-swap-hover' );
	if (
		dots &&
		'mouse' === event.pointerType &&
		! dots.contains( event.relatedTarget )
	) {
		settle( dots, reduced.matches );
	}
} );

document.addEventListener( 'focusin', ( event ) => {
	const dots = event.target.closest( '.purplish-color-dots-swap' );
	if ( dots ) {
		warm( dots );
	}
} );
