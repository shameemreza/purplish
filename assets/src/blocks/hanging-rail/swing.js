/**
 * The Hanging Rail's math, kept apart from the page so Jest can run it:
 * how much scrolling the rail takes, how far along it is, and the spring
 * each card swings on.
 */

/**
 * How many times the row's hidden width each scroll length takes.
 */
export const LENGTHS = { short: 1, medium: 1.5, long: 2 };

/**
 * The most a card leans, in degrees.
 */
export const MAX_LEAN = 12;

const LEAN_PER_PX = 0.012;
const DAMPING = 4.5;
const REST = 0.05;

/**
 * The scrolling the rail takes: the row's hidden width times the scroll
 * length, never less than the floor (the height of the view).
 *
 * @param {number} hidden Row width past the visible part, in px.
 * @param {string} length short, medium or long.
 * @param {number} floor  Least scrolling, in px.
 * @return {number} Scrolling in px, 0 when nothing is hidden.
 */
export function runLength( hidden, length, floor ) {
	if ( hidden <= 0 ) {
		return 0;
	}
	return Math.max( hidden * ( LENGTHS[ length ] || LENGTHS.medium ), floor );
}

/**
 * How far along the rail the page is, from 0 to 1.
 *
 * @param {number} scrolled Scrolling since the section stuck, in px.
 * @param {number} run      The rail's scrolling, in px.
 * @return {number} Progress from 0 to 1.
 */
export function progress( scrolled, run ) {
	if ( run <= 0 ) {
		return 0;
	}
	return Math.min( 1, Math.max( 0, scrolled / run ) );
}

/**
 * The angle a card leans toward while the row moves: its foot trails
 * behind, more the faster the row goes, up to MAX_LEAN.
 *
 * @param {number} speed Row speed in px per second, negative to the left.
 * @return {number} Angle in degrees.
 */
export function lean( speed ) {
	return Math.min( MAX_LEAN, Math.max( -MAX_LEAN, speed * LEAN_PER_PX ) );
}

/**
 * The spring's stiffness for the card at an index. Neighbors get slightly
 * different ones so the row doesn't swing in lockstep.
 *
 * @param {number} index Card index in the row.
 * @return {number} Stiffness.
 */
export function stiffness( index ) {
	return 80 * ( 1 + ( ( ( index * 7 ) % 5 ) - 2 ) * 0.06 );
}

/**
 * Moves a card's swing one step toward an angle on a damped spring. Once
 * it's close and slow enough it lands on the angle and stops.
 *
 * @param {{angle: number, speed: number}} swing  The card's angle and turn speed.
 * @param {number}                         target Angle the spring pulls toward.
 * @param {number}                         dt     Seconds since the last step.
 * @param {number}                         k      Stiffness.
 * @return {{angle: number, speed: number, still: boolean}} The next swing.
 */
export function step( swing, target, dt, k = stiffness( 0 ) ) {
	const time = Math.min( dt, 1 / 30 );
	const speed =
		swing.speed +
		( k * ( target - swing.angle ) - DAMPING * swing.speed ) * time;
	const angle = swing.angle + speed * time;

	if ( Math.abs( angle - target ) < REST && Math.abs( speed ) < REST ) {
		return { angle: target, speed: 0, still: true };
	}
	return { angle, speed, still: false };
}
