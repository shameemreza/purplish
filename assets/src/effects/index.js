/**
 * Purplish effects that need a script: the gallery's 3D tilt follows the
 * mouse by setting four CSS variables, and the slide styles learn which
 * way the gallery moved. The stylesheet does the rest.
 */
import './style.css';

const canTilt =
	window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches &&
	! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

/**
 * Makes a photo tilt toward the mouse.
 *
 * @param {HTMLElement} card The element that tilts.
 */
function tilt( card ) {
	card.addEventListener( 'pointermove', ( event ) => {
		const box = card.getBoundingClientRect();
		const x = ( event.clientX - box.left ) / box.width;
		const y = ( event.clientY - box.top ) / box.height;
		card.style.setProperty( '--purplish-ry', ( x - 0.5 ) * 22 + 'deg' );
		card.style.setProperty( '--purplish-rx', ( 0.5 - y ) * 16 + 'deg' );
		card.style.setProperty( '--purplish-mx', x * 100 + '%' );
		card.style.setProperty( '--purplish-my', y * 100 + '%' );
	} );
	card.addEventListener( 'pointerleave', () =>
		[
			'--purplish-rx',
			'--purplish-ry',
			'--purplish-mx',
			'--purplish-my',
		].forEach( ( name ) => card.style.removeProperty( name ) )
	);
}

if ( canTilt ) {
	document
		.querySelectorAll(
			'.purplish-gallery-tilt .wc-block-product-gallery-large-image'
		)
		.forEach( tilt );

	// Classic galleries tilt the slider's viewport, never the sliding strip.
	if ( document.body.classList.contains( 'purplish-classic-gallery-tilt' ) ) {
		window.addEventListener( 'load', () => {
			const gallery = document.querySelector(
				'.woocommerce-product-gallery'
			);
			const target =
				gallery?.querySelector( '.flex-viewport' ) ||
				gallery?.querySelector(
					'.woocommerce-product-gallery__wrapper'
				);
			if ( target ) {
				target.classList.add( 'purplish-tilt-target' );
				tilt( target );
			}
		} );
	}
}

/**
 * Tells the slide styles which way the gallery moved. WooCommerce shows
 * one slide and hides the rest; when another one shows, the strip gets
 * `--purplish-from` (1 for a later photo, -1 for an earlier one) and
 * `purplish-sliding`, before the new slide is painted.
 *
 * @param {HTMLElement} strip The gallery's list of slides.
 */
function followSlides( strip ) {
	const shown = () =>
		[ ...strip.children ].findIndex( ( slide ) => ! slide.hidden );
	let last = shown();

	new window.MutationObserver( () => {
		const now = shown();
		if ( now < 0 || now === last ) {
			return;
		}
		strip.style.setProperty( '--purplish-from', now > last ? '1' : '-1' );
		strip.classList.add( 'purplish-sliding' );
		last = now;
	} ).observe( strip, {
		subtree: true,
		attributes: true,
		attributeFilter: [ 'hidden' ],
	} );
}

document
	.querySelectorAll(
		'[class*="purplish-gallery-slider-"] .wc-block-product-gallery-large-image__container'
	)
	.forEach( followSlides );
