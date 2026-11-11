/**
 * Where a piece stands in its ring, for the editor and the front end.
 * A ring is a carousel seen from the front: the piece the model wears sits
 * on him (hidden), its neighbours stand one step out on each side, and the
 * pieces further round get smaller and fade. ChangingRoom::spot() in PHP
 * does the same sums for the first paint.
 */

// Height of each ring on the figure, as a share of the figure's height,
// and how far it bends as the pieces go round.
const RISE = { top: [ 0.35, 0.09 ], bottom: [ 0.7, -0.075 ] };

export const clamp = ( value, low, high ) =>
	Math.min( high, Math.max( low, value ) );

/**
 * How many steps a piece is from the front, the short way round.
 *
 * @param {number} index    The piece's place in its ring.
 * @param {number} position Where the ring has turned to.
 * @param {number} count    Pieces in the ring.
 * @return {number} Steps, negative to the left.
 */
export function offset( index, position, count ) {
	const steps = ( ( ( index - position ) % count ) + count ) % count;
	return steps > count / 2 ? steps - count : steps;
}

/**
 * A piece's place.
 *
 * @param {number}  steps Steps from the front, from offset().
 * @param {number}  count Pieces in the ring.
 * @param {string}  slot  `top` or `bottom`.
 * @param {boolean} far   Whether pieces two steps out show, faded.
 * @return {{x: number, y: number, s: number, r: number, o: number, z: number}}
 *  x in near steps from the middle, y as a share of the figure's height,
 *  scale, rotation in degrees, opacity and stacking order.
 */
export function spot( steps, count, slot, far ) {
	const angle = Math.PI / count;
	const near = Math.cos( angle );
	const turn = Math.cos( steps * angle );
	const away = Math.abs( steps );
	let o = clamp( ( away - 0.3 ) / 0.45, 0, 1 );
	if ( far ) {
		if ( away > 2 ) {
			o *= 0.55 * clamp( ( 2.5 - away ) / 0.5, 0, 1 );
		} else if ( away > 1 ) {
			o *= 1 - 0.45 * ( away - 1 );
		}
	} else {
		o *= clamp( ( 1.5 - away ) / 0.5, 0, 1 );
	}
	return {
		x: Math.sin( steps * angle ) / Math.sin( angle ),
		y: RISE[ slot ][ 0 ] + RISE[ slot ][ 1 ] * ( near - turn ),
		s: ( 1 - 0.487 * ( 1 - turn ) ) / ( 1 - 0.487 * ( 1 - near ) ),
		r: clamp( steps * 3, -7, 7 ),
		o,
		z: Math.round( 10 + turn * 10 ),
	};
}

/**
 * The CSS custom properties for a place, which the stylesheet turns into
 * the transform.
 *
 * @param {Object} at A place from spot().
 * @return {Object<string, string>} Property names and values.
 */
export const vars = ( at ) => ( {
	'--x': at.x.toFixed( 4 ),
	'--y': at.y.toFixed( 4 ),
	'--s': at.s.toFixed( 4 ),
	'--r': at.r.toFixed( 2 ) + 'deg',
} );
