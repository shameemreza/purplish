/**
 * The Runway's math, kept apart from the page so Jest can run it: where
 * each look stands in the line, where the product rail sits, how the line
 * eases into place and where a wheel nudge lands.
 */

/**
 * The places in the line, from a look leaving past the front (offset -1)
 * to one coming out of the haze at the back (offset 5). Each place has the
 * look's middle as a share of the stage width, its foot as a share of the
 * stage height, its height as a share of the look size, its blur in px and
 * its opacity.
 */
export const PLACES = [
	{ x: 1.2, foot: 1.12, height: 1.16, blur: 0, opacity: 1 },
	{ x: 0.74, foot: 0.9, height: 0.84, blur: 0, opacity: 1 },
	{ x: 0.5, foot: 0.78, height: 0.58, blur: 1.5, opacity: 1 },
	{ x: 0.35, foot: 0.71, height: 0.44, blur: 4, opacity: 1 },
	{ x: 0.24, foot: 0.66, height: 0.34, blur: 8, opacity: 0.86 },
	{ x: 0.15, foot: 0.62, height: 0.26, blur: 12, opacity: 0.5 },
	{ x: 0.085, foot: 0.59, height: 0.19, blur: 17, opacity: 0 },
];

/**
 * How much a wheel nudge has to move before it counts as one look.
 */
export const NUDGE = 0.06;

const EASE = 0.0072;
const REST = 0.0004;

/**
 * A number wrapped into 0 to count, so the look after the last is the
 * first.
 *
 * @param {number} value Any number, negative or past the end.
 * @param {number} count How many there are.
 * @return {number} From 0 up to count.
 */
export function wrap( value, count ) {
	if ( count <= 0 ) {
		return 0;
	}
	return ( ( value % count ) + count ) % count;
}

/**
 * How far a look stands behind the front of the line, with the line at a
 * position. 0 is the front, 1 the next one back, and from 0 down to -1 the
 * look is leaving past the front.
 *
 * @param {number} index    The look.
 * @param {number} position The line's position, in looks.
 * @param {number} count    Looks in the line.
 * @return {number} Offset from -1 up to count - 1.
 */
export function offset( index, position, count ) {
	const behind = wrap( index - position, count );
	return behind > count - 1 ? behind - count : behind;
}

/**
 * The look's place values at an offset, blended between the two places it
 * stands between. Past the back it stays at the last place.
 *
 * @param {number} at Offset from offset().
 * @return {{x: number, foot: number, height: number, blur: number, opacity: number}} Place values.
 */
export function blend( at ) {
	const spot = Math.min( PLACES.length - 1, Math.max( 0, at + 1 ) );
	const from = Math.floor( spot );
	const to = Math.min( PLACES.length - 1, from + 1 );
	const share = spot - from;
	const values = {};
	Object.keys( PLACES[ from ] ).forEach( ( key ) => {
		values[ key ] =
			PLACES[ from ][ key ] +
			( PLACES[ to ][ key ] - PLACES[ from ][ key ] ) * share;
	} );
	return values;
}

/**
 * Where the product rail sits, counted in cards, with the line at a
 * position. Looks of the same product keep the rail still, and it moves
 * to the next card while the line moves to that product's first look. The
 * count keeps going past the last card, so the rail wraps forward.
 *
 * @param {number}   position The line's position, in looks.
 * @param {number[]} cardOf   The card each look belongs to, in look order.
 * @param {number}   cards    Cards in the rail.
 * @return {number} Rail position, in cards.
 */
export function railAt( position, cardOf, cards ) {
	const count = cardOf.length;
	if ( ! count ) {
		return 0;
	}
	const look = Math.floor( position );
	const cardAt = ( index ) =>
		cardOf[ wrap( index, count ) ] + Math.floor( index / count ) * cards;
	const from = cardAt( look );
	return from + ( cardAt( look + 1 ) - from ) * ( position - look );
}

/**
 * Where a card sits in a rail that wraps: its distance from the start of
 * the rail, between one card before the start and the end of the loop.
 *
 * @param {number} index Card index.
 * @param {number} rail  Rail position from railAt().
 * @param {number} size  One card's size along the rail, in px.
 * @param {number} cards Cards in the rail.
 * @return {number} Distance in px.
 */
export function slot( index, rail, size, cards ) {
	const loop = size * cards;
	const along = wrap( ( index - rail ) * size, loop );
	return along > loop - size ? along - loop : along;
}

/**
 * Moves the line one frame toward where it's headed, and lands on it once
 * close enough.
 *
 * @param {number} position Where the line is, in looks.
 * @param {number} target   Where it's headed.
 * @param {number} ms       Time since the last frame.
 * @return {number} The next position.
 */
export function ease( position, target, ms ) {
	const next =
		position + ( target - position ) * ( 1 - Math.exp( -ms * EASE ) );
	return Math.abs( target - next ) < REST ? target : next;
}

/**
 * Where the line settles once a wheel gesture stops: the nearest look,
 * unless that's the look the gesture started from and it moved more than
 * a nudge, which then goes one look that way instead of springing back.
 *
 * @param {number} target Where the wheel left the line, in looks.
 * @param {number} from   The look the gesture started from.
 * @return {number} The look to settle on.
 */
export function settle( target, from ) {
	const closest = Math.round( target );
	if ( closest === from && Math.abs( target - closest ) > NUDGE ) {
		return closest + Math.sign( target - closest );
	}
	return closest;
}

/**
 * The nearest position that shows a look at the front, from where the
 * line is, so moving to it takes the short way round.
 *
 * @param {number} index    The look.
 * @param {number} position Where the line is, in looks.
 * @param {number} count    Looks in the line.
 * @return {number} Position to move to.
 */
export function nearest( index, position, count ) {
	const now = Math.round( position );
	let step = wrap( index - now, count );
	if ( step > count / 2 ) {
		step -= count;
	}
	return now + step;
}
