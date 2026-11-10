/**
 * Where a look stands on the floor, shared by the front end and the
 * editor. Each depth gives the smallest scale, the faintest opacity and
 * the most blur, all at the back of the floor.
 */
export const DEPTH = {
	subtle: [ 0.42, 0.3, 2.5 ],
	strong: [ 0.34, 0.16, 7 ],
	off: [ 0.62, 1, 0 ],
};

/**
 * One look's place on the floor.
 *
 * @param {number}  offset Look index minus the front position.
 * @param {number}  count  Number of looks.
 * @param {string}  depth  subtle, strong or off.
 * @param {boolean} blur   Whether the back looks blur.
 * @return {{x: number, y: number, s: number, o: number, b: number, z: number}} Side and depth shares (-1 to 1), scale, opacity, blur in px and stacking order.
 */
export function spot( offset, count, depth, blur ) {
	const [ minS, minO, maxB ] = DEPTH[ depth ] || DEPTH.subtle;
	const a = ( offset * Math.PI * 2 ) / Math.max( count, 1 );
	const d = Math.cos( a );
	const k = ( d + 1 ) / 2;
	return {
		x: Math.sin( a ),
		y: d,
		s: minS + ( 1 - minS ) * k,
		o: minO + ( 1 - minO ) * k * k,
		b: blur ? ( 1 - k ) * maxB : 0,
		z: Math.round( k * 100 ),
	};
}
