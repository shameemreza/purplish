/**
 * A small element maker for the login and register script.
 *
 * @param {Document}               doc   The document to make it in.
 * @param {string}                 tag   Tag name.
 * @param {Object<string, string>} attrs Attributes.
 * @param {string}                 text  Text content.
 * @return {HTMLElement} The element.
 */
export function el( doc, tag, attrs, text ) {
	const node = doc.createElement( tag );
	Object.keys( attrs || {} ).forEach( ( key ) => {
		node.setAttribute( key, attrs[ key ] );
	} );
	if ( text ) {
		node.textContent = text;
	}
	return node;
}
