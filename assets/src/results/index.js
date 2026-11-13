/**
 * Results change on Product Collection: watches each collection with the
 * option and moves its cards when WooCommerce swaps them.
 */
import {
	CARDS,
	keyOf,
	isAppend,
	same,
	plan,
	motionFor,
	lookOf,
} from './motion';

const SELECTOR =
	'.purplish-cards-results-fade, .purplish-cards-results-rise, .purplish-cards-results-move';
const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );

/** What each collection looked like last, by collection. */
const known = new WeakMap();
/** The animations started here, so only these are cancelled. */
const running = new WeakMap();
/** When each collection's motion ends, so a click mid-motion leaves it be. */
const busy = new WeakMap();

/**
 * The cards and their places in page pixels, without the motion's own
 * offsets, since a card mid-move would report where it is drawn.
 *
 * @param {Element} region A collection.
 * @return {Array<{card: Element, key: string, x: number, y: number}>} The cards.
 */
const measure = ( region ) =>
	Array.from( region.querySelectorAll( CARDS ) ).map( ( card ) => {
		( running.get( card ) || [] ).forEach( ( animation ) =>
			animation.cancel()
		);
		running.delete( card );
		const box = card.getBoundingClientRect();
		return {
			card,
			key: keyOf( card ),
			x: box.left + window.scrollX,
			y: box.top + window.scrollY,
		};
	} );

/**
 * Notes where a collection's cards are now.
 *
 * @param {Element} region A collection.
 * @param {Array}   cards  Its cards, from `measure`.
 */
const remember = ( region, cards ) =>
	known.set( region, {
		keys: cards.map( ( card ) => card.key ),
		places: new Map( cards.map( ( { key, x, y } ) => [ key, { x, y } ] ) ),
	} );

/**
 * Moves the cards after a swap.
 *
 * @param {Element} region A collection.
 */
const changed = ( region ) => {
	const before = known.get( region );
	const cards = measure( region );
	remember( region, cards );
	const keys = cards.map( ( card ) => card.key );
	if (
		! before ||
		same( before.keys, keys ) ||
		isAppend( before.keys, keys ) ||
		reduced.matches
	) {
		return;
	}
	const look = lookOf( region );
	const steps = plan( before.places, cards );
	busy.set( region, window.performance.now() + 1000 );
	cards.forEach( ( { card }, index ) => {
		const motion = motionFor( look, steps[ index ], index );
		if ( motion && card.animate ) {
			running.set( card, [
				card.animate( motion.frames, motion.timing ),
			] );
		}
	} );
};

/**
 * Starts watching one collection.
 *
 * @param {Element} region A collection.
 */
const watch = ( region ) => {
	remember( region, measure( region ) );
	new window.MutationObserver( ( records ) => {
		// Only the grid's own list, not what happens inside a card.
		const grid = records.some(
			( record ) =>
				record.target === region ||
				record.target.matches( '.wc-block-product-template' ) ||
				Array.from( record.addedNodes )
					.concat( Array.from( record.removedNodes ) )
					.some(
						( node ) =>
							1 === node.nodeType &&
							( node.matches( '.wc-block-product-template' ) ||
								!! node.querySelector(
									'.wc-block-product-template'
								) )
					)
		);
		if ( grid ) {
			changed( region );
		}
	} ).observe( region, { childList: true, subtree: true } );
};

/**
 * Notes where every card is just before a shopper acts, so a resize or
 * the grid switch since the last swap doesn't throw Move off.
 */
const refresh = () =>
	document.querySelectorAll( SELECTOR ).forEach( ( region ) => {
		if ( window.performance.now() < ( busy.get( region ) || 0 ) ) {
			return;
		}
		const before = known.get( region );
		const cards = measure( region );
		if (
			before &&
			same(
				before.keys,
				cards.map( ( card ) => card.key )
			)
		) {
			remember( region, cards );
		}
	} );

document.querySelectorAll( SELECTOR ).forEach( watch );
[ 'pointerdown', 'keydown' ].forEach( ( type ) =>
	document.addEventListener( type, refresh, { capture: true, passive: true } )
);
