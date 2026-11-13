/**
 * The photo swap on Color Dots, kept apart from the page so it can be
 * tested: finding the card's photo, showing a color's photo on it with
 * the block's motion, and keeping the chosen color.
 */

/**
 * The card a dot sits on.
 *
 * @param {HTMLElement} dot A dot.
 * @return {HTMLElement|null} The card.
 */
export function cardOf( dot ) {
	return dot.closest(
		'.wc-block-product, .wp-block-post, .wp-block-woocommerce-single-product, li'
	);
}

/**
 * The card's main photo, leaving out the hover look's second photo and a
 * photo on its way out.
 *
 * @param {HTMLElement|null} card The card.
 * @return {HTMLImageElement|null} The photo.
 */
export function photoOf( card ) {
	return card
		? card.querySelector(
				'.wc-block-components-product-image img:not(.purplish-second-photo):not(.purplish-swap-old)'
			)
		: null;
}

/**
 * What a dot shows: its own photo, or the photo the card started with.
 *
 * @param {HTMLImageElement} photo The card's photo.
 * @param {HTMLElement|null} dot   A dot, or null for the main photo.
 * @return {{src: string, srcset: string}} The photo to show.
 */
export function wanted( photo, dot ) {
	if ( dot && dot.dataset.src ) {
		return { src: dot.dataset.src, srcset: dot.dataset.srcset || '' };
	}
	return {
		src: photo.dataset.purplishSrc || photo.getAttribute( 'src' ) || '',
		srcset:
			undefined !== photo.dataset.purplishSrcset
				? photo.dataset.purplishSrcset
				: photo.getAttribute( 'srcset' ) || '',
	};
}

/**
 * Shows a dot's photo on the card. The old photo stays on top until the
 * new one is ready, then leaves with the motion; reduced motion swaps at
 * once.
 *
 * @param {HTMLImageElement} photo   The card's photo.
 * @param {HTMLElement|null} dot     A dot, or null for the main photo.
 * @param {string}           motion  `fade`, `wipe` or `slide`.
 * @param {boolean}          reduced Whether the shopper asked for reduced motion.
 * @return {boolean} Whether the photo changed.
 */
export function show( photo, dot, motion, reduced ) {
	const next = wanted( photo, dot );
	if ( ! next.src || next.src === photo.getAttribute( 'src' ) ) {
		return false;
	}
	if ( undefined === photo.dataset.purplishSrc ) {
		photo.dataset.purplishSrc = photo.getAttribute( 'src' ) || '';
		photo.dataset.purplishSrcset = photo.getAttribute( 'srcset' ) || '';
	}

	photo.parentElement
		.querySelectorAll( ':scope > .purplish-swap-old' )
		.forEach( ( old ) => old.remove() );
	photo.classList.remove( 'is-purplish-slide-in' );

	let layer = null;
	if ( ! reduced ) {
		layer = photo.cloneNode( false );
		layer.className = 'purplish-swap-old is-' + motion;
		layer.alt = '';
		layer.setAttribute( 'aria-hidden', 'true' );
		[ 'loading', 'data-testid', 'data-image-id' ].forEach( ( name ) =>
			layer.removeAttribute( name )
		);
		photo.after( layer );
	}

	if ( next.srcset ) {
		photo.setAttribute( 'srcset', next.srcset );
	} else {
		photo.removeAttribute( 'srcset' );
	}
	photo.setAttribute( 'src', next.src );

	if ( layer ) {
		const leave = () => {
			if ( ! layer.isConnected ) {
				return;
			}
			layer.addEventListener( 'animationend', () => layer.remove() );
			layer.classList.add( 'is-going' );
			if ( 'slide' === motion ) {
				photo.classList.add( 'is-purplish-slide-in' );
			}
		};
		if ( photo.decode ) {
			photo.decode().then( leave, leave );
		} else {
			leave();
		}
	}
	return true;
}

/**
 * The dots' chosen dot, if any.
 *
 * @param {HTMLElement} dots The Color Dots block.
 * @return {HTMLElement|null} The pressed dot.
 */
export function chosen( dots ) {
	return dots.querySelector(
		'.purplish-color-dots-dot[aria-pressed="true"]'
	);
}

/**
 * Picks a dot, or lets it go when it was already picked, and shows the
 * photo for what is picked now.
 *
 * @param {HTMLElement} dot     The pressed dot.
 * @param {boolean}     reduced Whether the shopper asked for reduced motion.
 * @return {HTMLElement|null} The dot picked now.
 */
export function pick( dot, reduced ) {
	const dots = dot.closest( '.purplish-color-dots-swap' );
	const keep = 'true' !== dot.getAttribute( 'aria-pressed' );
	dots.querySelectorAll( '.purplish-color-dots-dot' ).forEach( ( each ) =>
		each.setAttribute( 'aria-pressed', String( keep && each === dot ) )
	);
	settle( dots, reduced );
	return keep ? dot : null;
}

/**
 * Shows a dot's photo for as long as the pointer is over it.
 *
 * @param {HTMLElement} dot     The dot under the pointer.
 * @param {boolean}     reduced Whether the shopper asked for reduced motion.
 */
export function preview( dot, reduced ) {
	const dots = dot.closest( '.purplish-color-dots-swap' );
	const photo = photoOf( cardOf( dots ) );
	if ( photo ) {
		show( photo, dot, dots.dataset.motion || 'fade', reduced );
	}
}

/**
 * Shows the chosen dot's photo, or the main photo when none is chosen.
 *
 * @param {HTMLElement} dots    The Color Dots block.
 * @param {boolean}     reduced Whether the shopper asked for reduced motion.
 */
export function settle( dots, reduced ) {
	const photo = photoOf( cardOf( dots ) );
	if ( photo ) {
		show( photo, chosen( dots ), dots.dataset.motion || 'fade', reduced );
	}
}

/**
 * Starts loading each dot's photo, once per block, so a swap is quick.
 *
 * @param {HTMLElement} dots The Color Dots block.
 */
export function warm( dots ) {
	if ( dots.dataset.purplishWarm ) {
		return;
	}
	dots.dataset.purplishWarm = '1';
	const photo = photoOf( cardOf( dots ) );
	dots.querySelectorAll( '.purplish-color-dots-dot[data-src]' ).forEach(
		( dot ) => {
			const image = new window.Image();
			if ( photo && photo.sizes ) {
				image.sizes = photo.sizes;
			}
			if ( dot.dataset.srcset ) {
				image.srcset = dot.dataset.srcset;
			}
			image.src = dot.dataset.src;
		}
	);
}
