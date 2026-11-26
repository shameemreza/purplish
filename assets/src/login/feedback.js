/**
 * Feedback: the forms answer back. A refused field or a failed send
 * shakes, the button shows busy dots while a form sends, and a tick draws
 * in once an email address is valid.
 */
import { el } from './dom';

/**
 * Shakes an element once, restarting the shake if it is still running.
 *
 * @param {?HTMLElement} node The element to shake.
 */
function shake( node ) {
	if ( ! node ) {
		return;
	}
	node.classList.remove( 'pa-shake' );
	// Reading the width restarts the animation.
	void node.offsetWidth;
	node.classList.add( 'pa-shake' );
	node.addEventListener(
		'animationend',
		() => node.classList.remove( 'pa-shake' ),
		{ once: true }
	);
}

/**
 * @param {HTMLElement} group The element with the option classes.
 */
export function feedback( group ) {
	const doc = group.ownerDocument;
	const win = doc.defaultView;

	group.addEventListener(
		'invalid',
		( e ) => shake( e.target.closest( '.form-row' ) ),
		true
	);

	// The button is not disabled while busy: its name is part of what
	// WooCommerce reads.
	group.addEventListener( 'submit', ( e ) => {
		const button = e.target.querySelector( 'button[type="submit"]' );
		if (
			e.defaultPrevented ||
			! button ||
			button.classList.contains( 'pa-busy' )
		) {
			return;
		}
		const dots = el( doc, 'span', {
			class: 'pa-dots',
			'aria-hidden': 'true',
		} );
		dots.style.setProperty(
			'--pa-dots',
			win.getComputedStyle( button ).color
		);
		for ( let i = 0; i < 3; i++ ) {
			dots.appendChild( doc.createElement( 'i' ) );
		}
		button.classList.add( 'pa-busy' );
		button.setAttribute( 'aria-busy', 'true' );
		button.appendChild( dots );
	} );

	// Back from the next page, the button is ready again.
	win.addEventListener( 'pageshow', ( e ) => {
		if ( ! e.persisted ) {
			return;
		}
		group.querySelectorAll( '.pa-busy' ).forEach( ( button ) => {
			button.classList.remove( 'pa-busy' );
			button.removeAttribute( 'aria-busy' );
			button
				.querySelectorAll( '.pa-dots' )
				.forEach( ( dots ) => dots.remove() );
		} );
	} );

	// The page came back with an error: the form it belongs to shakes.
	if (
		group.querySelector(
			'.woocommerce-error, .wc-block-components-notice-banner.is-error'
		)
	) {
		const form = group.querySelector(
			group.classList.contains( 'purplish-account-open-register' )
				? 'form.register'
				: 'form.login'
		);
		win.requestAnimationFrame( () => shake( form ) );
	}

	group
		.querySelectorAll( 'form .form-row input[type="email"].input-text' )
		.forEach( ( input ) => {
			const wrap = el( doc, 'span', { class: 'pa-field' } );
			input.parentNode.insertBefore( wrap, input );
			wrap.appendChild( input );
			wrap.insertAdjacentHTML(
				'beforeend',
				'<svg class="pa-tick" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M4 10.5l4 4 8-9"/></svg>'
			);
		} );
}
