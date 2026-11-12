/**
 * Grid switch, on the Product Collection block. The buttons the server
 * prints before the grid move just after Sort by and switch the grid
 * between its own column count and the second one. The stylesheet sets
 * the columns, and phones keep their grid.
 */
import { change, placeSwitch, remembered, setSize } from './switch';

const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );

document.querySelectorAll( '.purplish-grid-switch' ).forEach( ( buttons ) => {
	placeSwitch( buttons );
	buttons.hidden = false;
	buttons.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( '.purplish-grid-switch__button' );
		if ( button && 'true' !== button.getAttribute( 'aria-pressed' ) ) {
			change( document, button.dataset.size, reduced.matches );
		}
	} );
} );

setSize( document, remembered() );
