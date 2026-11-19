/**
 * Finding a block's element in the editor canvas.
 */

/**
 * The block's element in the canvas, which may sit in the editor iframe.
 *
 * @param {string} clientId The block's client ID.
 * @return {HTMLElement|null} The element.
 */
export function canvasElement( clientId ) {
	const id = 'block-' + clientId;
	const docs = [
		document,
		...[
			...document.querySelectorAll( 'iframe[name="editor-canvas"]' ),
		].map( ( frame ) => frame.contentDocument ),
	];
	for ( const doc of docs ) {
		const element = doc && doc.getElementById( id );
		if ( element ) {
			return element;
		}
	}
	return null;
}
