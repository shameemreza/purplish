/**
 * The Story Deck's math, kept apart from the page so Jest can run it: which
 * card is where in the deck, when a drag counts as a throw, and where a
 * thrown card flies.
 */

/**
 * How far a card has to be dragged to count as a throw, as a share of its
 * width.
 */
export const THROW_SHARE = 0.3;

/**
 * A quick flick throws the card from this far, in px, at this speed or
 * faster, in px per ms.
 */
export const FLICK_DISTANCE = 16;
export const FLICK_SPEED = 0.5;

/**
 * The most a card leans while it's dragged or thrown, in degrees.
 */
export const MAX_LEAN = 18;

const LEAN_PER_PX = 0.06;
const SPEED_WINDOW = 100;

/**
 * An index wrapped into the deck, so the card after the last is the first.
 *
 * @param {number} index Any index, negative or past the end.
 * @param {number} count Cards in the deck.
 * @return {number} Index from 0 to count - 1.
 */
export function wrap( index, count ) {
	if ( count <= 0 ) {
		return 0;
	}
	return ( ( index % count ) + count ) % count;
}

/**
 * How deep a card sits under the top one: 0 is the top card, 1 the card
 * under it, and the card before the top one is the deepest.
 *
 * @param {number} index Card index.
 * @param {number} top   Index of the top card.
 * @param {number} count Cards in the deck.
 * @return {number} Depth from 0 to count - 1.
 */
export function depthOf( index, top, count ) {
	return wrap( index - top, count );
}

/**
 * Whether a released drag throws the card away: dragged far enough, or
 * flicked fast enough in the direction it was dragged.
 *
 * @param {number} dx    Drag distance in px, negative to the left.
 * @param {number} speed Pointer speed at release in px per ms.
 * @param {number} width Card width in px.
 * @return {boolean} True to throw, false to snap back.
 */
export function isThrow( dx, speed, width ) {
	const distance = Math.abs( dx );
	if ( distance >= width * THROW_SHARE ) {
		return true;
	}
	return (
		distance >= FLICK_DISTANCE &&
		Math.abs( speed ) >= FLICK_SPEED &&
		Math.sign( speed ) === Math.sign( dx )
	);
}

/**
 * The angle a dragged card leans: more the further it goes, up to
 * MAX_LEAN.
 *
 * @param {number} dx Drag distance in px.
 * @return {number} Angle in degrees.
 */
export function lean( dx ) {
	return Math.min( MAX_LEAN, Math.max( -MAX_LEAN, dx * LEAN_PER_PX ) );
}

/**
 * The pointer's sideways speed over its last moves.
 *
 * @param {{x: number, t: number}[]} samples Pointer x in px and time in ms, oldest first.
 * @return {number} Speed in px per ms, negative to the left.
 */
export function speedOf( samples ) {
	if ( samples.length < 2 ) {
		return 0;
	}
	const last = samples[ samples.length - 1 ];
	const first =
		samples.find( ( sample ) => last.t - sample.t <= SPEED_WINDOW ) || last;
	const time = last.t - first.t;
	return time > 0 ? ( last.x - first.x ) / time : 0;
}

/**
 * Where a thrown card ends: further the faster it went, but never past the
 * room on that side, so it never widens the page.
 *
 * @param {{x: number, y: number}} from  Where the card is, in px.
 * @param {number}                 way   -1 for left, 1 for right.
 * @param {number}                 room  Space on that side, in px.
 * @param {number}                 speed Throw speed in px per ms.
 * @return {{x: number, y: number, turn: number}} End position and angle.
 */
export function toss( from, way, room, speed = 0 ) {
	const start = Math.abs( from.x );
	const reach = Math.min(
		Math.max( room, 0 ),
		start + 160 + Math.abs( speed ) * 120
	);
	return {
		x: way * Math.max( start, reach ),
		y: from.y + 24,
		turn: way * MAX_LEAN,
	};
}
