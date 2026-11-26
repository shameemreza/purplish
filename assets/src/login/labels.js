/**
 * Floating, Flip and Wave labels. Without the script, labels sit above the
 * field as WooCommerce prints them.
 */
import { el } from './dom';

const FIELD = 'form .form-row input.input-text';

/**
 * Marks each row whose field has a value, so its label stays up, and each
 * row that has had focus, so Flip plays only after a field is used.
 *
 * @param {HTMLElement} group The element with the option classes.
 */
export function floating( group ) {
	const mark = ( field ) => {
		const row = field.closest( '.form-row' );
		if ( row ) {
			row.classList.toggle( 'pa-filled', '' !== field.value );
		}
	};
	group.querySelectorAll( FIELD ).forEach( mark );
	[ 'input', 'change' ].forEach( ( type ) => {
		group.addEventListener( type, ( e ) => {
			if ( e.target.matches( 'input.input-text' ) ) {
				mark( e.target );
			}
		} );
	} );
	group.addEventListener( 'focusin', ( e ) => {
		const row = e.target.closest( '.form-row' );
		if ( row ) {
			row.classList.add( 'pa-touched' );
		}
	} );
	group.classList.add( 'pa-ready' );
}

/**
 * Wave: each label's words become letters that ripple up one after
 * another. Screen readers get the words once, from a hidden copy.
 *
 * @param {HTMLElement} group The element with the option classes.
 */
export function wave( group ) {
	const doc = group.ownerDocument;
	group
		.querySelectorAll( 'form .form-row > label[for]' )
		.forEach( ( label ) => {
			const text = label.firstChild;
			if (
				! text ||
				3 !== text.nodeType ||
				'' === text.nodeValue.trim()
			) {
				return;
			}
			const words = text.nodeValue.replace( /\s+$/, '' );
			const letters = el( doc, 'span', { 'aria-hidden': 'true' } );
			Array.from( words ).forEach( ( ch, i ) => {
				const span = el( doc, 'span', { class: 'pa-ch' }, ch );
				span.style.setProperty( '--i', i );
				letters.appendChild( span );
			} );
			label.replaceChild( letters, text );
			label.insertBefore(
				el( doc, 'span', { class: 'pa-sr' }, words ),
				letters
			);
			label.insertBefore(
				doc.createTextNode( ' ' ),
				letters.nextSibling
			);
		} );
}
