/**
 * The button looks that need a script: Bloom, Roll and Ripple. The rest
 * are CSS only.
 */
import { el } from './dom';

const BUTTONS = 'form button[type="submit"]';

/**
 * Bloom: the effect color starts where the pointer comes in and shrinks
 * back to where it leaves.
 *
 * @param {HTMLElement} group The element with the option classes.
 */
export function bloom( group ) {
	const place = ( e ) => {
		const button = e.target.closest && e.target.closest( BUTTONS );
		if ( ! button ) {
			return;
		}
		const r = button.getBoundingClientRect();
		button.style.setProperty( '--pa-bx', e.clientX - r.left + 'px' );
		button.style.setProperty( '--pa-by', e.clientY - r.top + 'px' );
	};
	group.addEventListener( 'pointerover', place );
	group.addEventListener( 'pointerout', place );
}

/**
 * Roll: the button words sit in a clip with a copy below them, which CSS
 * rolls up on hover and focus.
 *
 * @param {HTMLElement} group The element with the option classes.
 */
export function roll( group ) {
	const doc = group.ownerDocument;
	group.querySelectorAll( BUTTONS ).forEach( ( button ) => {
		const text = button.textContent.trim();
		if ( ! text || button.querySelector( '.pa-roll' ) ) {
			return;
		}
		const clip = el( doc, 'span', { class: 'pa-roll-clip' } );
		clip.appendChild(
			el( doc, 'span', { class: 'pa-roll', 'data-text': text }, text )
		);
		button.textContent = '';
		button.appendChild( clip );
	} );
}

/**
 * Ripple: ink spreads from where the button was pressed, or from the
 * middle for Enter and Space.
 *
 * @param {HTMLElement} group The element with the option classes.
 */
export function ripple( group ) {
	const doc = group.ownerDocument;
	const reduce = doc.defaultView.matchMedia(
		'(prefers-reduced-motion: reduce)'
	);
	group.addEventListener( 'click', ( e ) => {
		const button = e.target.closest( BUTTONS );
		if ( ! button || reduce.matches ) {
			return;
		}
		const r = button.getBoundingClientRect();
		const keyboard = 0 === e.detail;
		const size = Math.hypot( r.width, r.height ) * 2 + 'px';
		const ink = el( doc, 'span', {
			class: 'pa-ripple',
			'aria-hidden': 'true',
		} );
		ink.style.width = size;
		ink.style.height = size;
		ink.style.left = ( keyboard ? r.width / 2 : e.clientX - r.left ) + 'px';
		ink.style.top = ( keyboard ? r.height / 2 : e.clientY - r.top ) + 'px';
		ink.addEventListener( 'animationend', () => ink.remove() );
		button.appendChild( ink );
	} );
}
