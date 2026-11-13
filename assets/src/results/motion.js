/**
 * Results change on Product Collection. Filters, sorting and page links
 * swap the cards through WooCommerce's client-side navigation, which keys
 * each card by its product, so a card that stays in the results is the same
 * element after the swap. The swap lands before the browser paints, so the
 * cards can start from where they were and move, rise or fade in.
 */

/** The cards in a collection's grid. */
export const CARDS = '.wc-block-product-template > .wc-block-product';

/** The most cards that wait their turn, so a long grid doesn't drag. */
export const MOST_STEPS = 10;

/**
 * A card's key: the router's own, or its product's post class.
 *
 * @param {Element} card A card.
 * @return {string} The key, or an empty string.
 */
export function keyOf( card ) {
	const own = card.getAttribute( 'data-wp-key' );
	if ( own ) {
		return own;
	}
	const post = ( card.getAttribute( 'class' ) || '' ).match( /\bpost-\d+\b/ );
	return post ? post[ 0 ] : '';
}

/**
 * Whether the new keys only add cards after the old ones, as Load more does.
 * Load more brings its own cards in, so the results look stays out of it.
 *
 * @param {string[]} before Keys before the swap.
 * @param {string[]} after  Keys after it.
 * @return {boolean} True for cards added at the end and nothing else.
 */
export function isAppend( before, after ) {
	return (
		before.length > 0 &&
		after.length > before.length &&
		before.every( ( key, index ) => key === after[ index ] )
	);
}

/**
 * Whether two key lists are the same, in the same order.
 *
 * @param {string[]} before Keys before.
 * @param {string[]} after  Keys after.
 * @return {boolean} True when nothing changed.
 */
export const same = ( before, after ) =>
	before.length === after.length &&
	before.every( ( key, index ) => key === after[ index ] );

/**
 * What each card does in Move: a card that was there slides from its old
 * place by the gap between the two, and a card that wasn't comes in.
 *
 * @param {Map<string, {x: number, y: number}>}        before Old places by key, in page pixels.
 * @param {Array<{key: string, x: number, y: number}>} after  New places, in order.
 * @return {Array<{key: string, dx: number, dy: number, fresh: boolean}>} One step per card.
 */
export function plan( before, after ) {
	return after.map( ( { key, x, y } ) => {
		const old = before.get( key );
		if ( ! old ) {
			return { key, dx: 0, dy: 0, fresh: true };
		}
		return {
			key,
			dx: Math.round( old.x - x ),
			dy: Math.round( old.y - y ),
			fresh: false,
		};
	} );
}

/**
 * The keyframes and timing for one card.
 *
 * @param {string} look  fade, rise or move.
 * @param {Object} step  The card's step from `plan`, for move.
 * @param {number} index The card's place in the grid.
 * @return {?{frames: Object[], timing: Object}} Null for a card that stays still.
 */
export function motionFor( look, step, index ) {
	const ease = 'cubic-bezier(0.22, 1, 0.36, 1)';
	if ( 'fade' === look ) {
		return {
			frames: [ { opacity: 0 }, { opacity: 1 } ],
			timing: { duration: 320, easing: 'ease-out' },
		};
	}
	if ( 'rise' === look ) {
		return {
			frames: [
				{ opacity: 0, transform: 'translateY(18px)' },
				{ opacity: 1, transform: 'none' },
			],
			timing: {
				duration: 480,
				delay: Math.min( index, MOST_STEPS ) * 45,
				easing: ease,
				fill: 'backwards',
			},
		};
	}
	if ( 'move' !== look || ! step ) {
		return null;
	}
	if ( step.fresh ) {
		return {
			frames: [
				{ opacity: 0, transform: 'scale(0.96)' },
				{ opacity: 1, transform: 'none' },
			],
			timing: {
				duration: 340,
				delay: 140,
				easing: ease,
				fill: 'backwards',
			},
		};
	}
	if ( ! step.dx && ! step.dy ) {
		return null;
	}
	return {
		frames: [
			{ transform: `translate(${ step.dx }px, ${ step.dy }px)` },
			{ transform: 'none' },
		],
		timing: { duration: 460, easing: ease },
	};
}

/**
 * The look a collection asks for, from its class.
 *
 * @param {Element} region The collection.
 * @return {string} fade, rise, move or an empty string.
 */
export function lookOf( region ) {
	const found = ( region.getAttribute( 'class' ) || '' ).match(
		/\bpurplish-cards-results-(fade|rise|move)\b/
	);
	return found ? found[ 1 ] : '';
}
