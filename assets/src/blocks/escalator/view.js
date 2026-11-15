/**
 * Escalator: the pause button. The stylesheet moves the columns and stops
 * them on hover and keyboard focus; this only shows the button and keeps a
 * visitor's pause. The button keeps its label and reports its state with
 * aria-pressed.
 */

/**
 * Turns on the pause button of one Escalator.
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
}

document
	.querySelectorAll( '.wp-block-purplish-escalator' )
	.forEach( escalator );
