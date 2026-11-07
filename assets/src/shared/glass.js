/**
 * The liquid glass highlight, shared by the front script and the editor
 * preview.
 */

/**
 * Puts the button's highlight under the pointer.
 *
 * @param {HTMLElement}  button The link or button.
 * @param {PointerEvent} event  The pointer move.
 */
export function moveHighlight( button, event ) {
	const box = button.getBoundingClientRect();
	button.style.setProperty(
		'--purplish-bx',
		( ( event.clientX - box.left ) / box.width ) * 100 + '%'
	);
	button.style.setProperty(
		'--purplish-by',
		( ( event.clientY - box.top ) / box.height ) * 100 + '%'
	);
}

/**
 * Lets the highlight drift back to the top.
 *
 * @param {HTMLElement} button The link or button.
 */
export function clearHighlight( button ) {
	button.style.removeProperty( '--purplish-bx' );
	button.style.removeProperty( '--purplish-by' );
}
