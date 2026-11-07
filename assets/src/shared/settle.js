/**
 * Settles a carousel strip on the card nearest its middle. The browser
 * snaps to where cards are drawn, and carousel effects that pull cards
 * toward the middle move that, so cards would come to rest off center.
 * Those effects turn the snap off and settle here instead, by where each
 * card sits in the row.
 */

/**
 * How far, in pixels, a card may sit from the middle and count as there.
 */
const NEAR = 2;

/**
 * How long, in milliseconds, scrolling stays still before it counts as
 * ended, where the browser has no `scrollend` event.
 */
const STILL = 150;

/**
 * The scroll position that puts a card in the middle of its strip, left
 * to right or right to left.
 *
 * @param {HTMLElement} strip The strip.
 * @param {HTMLElement} card  A card in it.
 * @return {number} The strip's `scrollLeft` for that card.
 */
export function middleOf( strip, card ) {
	return card.offsetLeft + card.offsetWidth / 2 - strip.clientWidth / 2;
}

/**
 * The scroll position, from a list of card positions, nearest to where
 * the strip is.
 *
 * @param {number[]} places Each card's `middleOf`.
 * @param {number}   now    The strip's `scrollLeft`.
 * @return {number|null} The nearest place, or null with no cards.
 */
export function nearest( places, now ) {
	return places.reduce(
		( best, place ) =>
			null === best || Math.abs( place - now ) < Math.abs( best - now )
				? place
				: best,
		null
	);
}

/**
 * The strip's cards.
 *
 * @param {HTMLElement} strip The strip.
 * @return {HTMLElement[]} The cards.
 */
const cardsOf = ( strip ) => [
	...strip.querySelectorAll( ':scope > .wc-block-product' ),
];

/**
 * WooCommerce greys out its previous and next arrows from a scroll event,
 * and may start listening after the strip has already moved, so the
 * strip says it scrolled again once the page has loaded and when it first
 * comes into view.
 *
 * @param {HTMLElement} strip The strip.
 */
function nudge( strip ) {
	const scrolled = () => strip.dispatchEvent( new window.Event( 'scroll' ) );

	if ( 'complete' === document.readyState ) {
		window.setTimeout( scrolled );
	} else {
		window.addEventListener( 'load', scrolled, { once: true } );
	}

	const seen = new window.IntersectionObserver( ( entries ) => {
		if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
			seen.disconnect();
			window.setTimeout( scrolled );
		}
	} );
	seen.observe( strip );
}

/**
 * Settles every strip matching `selector` on its nearest card each time
 * it stops scrolling, and can start each one on its middle card.
 *
 * @param {string}  selector The strips.
 * @param {boolean} middle   Whether each strip starts on its middle card.
 */
export function settleStrips( selector, middle ) {
	const strips = [ ...document.querySelectorAll( selector ) ];

	const settle = ( strip ) => {
		if ( strip.classList.contains( 'purplish-dragging' ) ) {
			return;
		}
		const place = nearest(
			cardsOf( strip ).map( ( card ) => middleOf( strip, card ) ),
			strip.scrollLeft
		);
		if ( null !== place && Math.abs( place - strip.scrollLeft ) > NEAR ) {
			strip.scrollTo( { left: place, behavior: 'smooth' } );
		}
	};

	strips.forEach( ( strip ) => {
		if ( middle ) {
			const cards = cardsOf( strip );
			const card = cards[ Math.floor( ( cards.length - 1 ) / 2 ) ];
			if ( card ) {
				strip.scrollTo( {
					left: middleOf( strip, card ),
					behavior: 'instant',
				} );
				nudge( strip );
			}
		}

		// A drag or throw scrolls while the strip is held, so it settles
		// once it lets go.
		strip.addEventListener( 'purplish-thrown', () => settle( strip ) );

		if ( 'onscrollend' in window ) {
			strip.addEventListener( 'scrollend', () => settle( strip ) );
			return;
		}
		let timer = 0;
		strip.addEventListener(
			'scroll',
			() => {
				window.clearTimeout( timer );
				timer = window.setTimeout( () => settle( strip ), STILL );
			},
			{ passive: true }
		);
	} );
}
