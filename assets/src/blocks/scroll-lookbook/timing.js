/**
 * The Scroll Lookbook's math, kept apart from the page so Jest can run it:
 * how much scrolling the film takes, where it holds at each stop, where
 * the camera stands at a scroll position, when a card comes and goes, and
 * which moment of each clip shows.
 */

/**
 * Scrolling per stop, in svh, for each scroll length.
 */
export const LENGTHS = { short: 150, medium: 250, long: 350 };

/**
 * Scrolling, in svh, before the first move (the opening shot), for the
 * rewind back to the opening shot, and for the closing line.
 */
export const INTRO = 40;
export const REWIND = 70;
export const END = 90;

/**
 * A card starts to come in this far before its hold starts and is fully
 * in this far after it; it starts to leave this far before the hold ends.
 */
export const CARD_LEAD = 14;
export const CARD_SETTLE = 30;

/**
 * Share of each stop's scrolling spent moving to it; the rest holds.
 */
export const TRAVEL = 0.42;

/**
 * Keeps a number between two others.
 *
 * @param {number} value Any number.
 * @param {number} min   Lowest.
 * @param {number} max   Highest.
 * @return {number} The number, kept between min and max.
 */
export function clamp( value, min = 0, max = 1 ) {
	return Math.min( max, Math.max( min, value ) );
}

/**
 * Where a value sits between two others, from 0 to 1.
 *
 * @param {number} value Any number.
 * @param {number} from  Where 0 is.
 * @param {number} to    Where 1 is.
 * @return {number} From 0 to 1.
 */
export function range( value, from, to ) {
	return to === from
		? Number( value >= to )
		: clamp( ( value - from ) / ( to - from ) );
}

/**
 * Slow at both ends.
 *
 * @param {number} t From 0 to 1.
 * @return {number} From 0 to 1.
 */
export function smooth( t ) {
	return t * t * ( 3 - 2 * t );
}

/**
 * Fast start, slow end.
 *
 * @param {number} t From 0 to 1.
 * @return {number} From 0 to 1.
 */
export function outCubic( t ) {
	return 1 - Math.pow( 1 - t, 3 );
}

/**
 * The scrolling a film takes beyond its own screen, in svh.
 *
 * @param {number} stops  Stops after the opening shot.
 * @param {string} length short, medium or long.
 * @return {number} svh.
 */
export function runLength( stops, length ) {
	const per = LENGTHS[ length ] || LENGTHS.medium;
	return INTRO + Math.max( 0, stops ) * per + REWIND + END;
}

/**
 * The film's timeline, with every point as a share of its scrolling (0 at
 * the top, 1 at the end).
 *
 * The camera path counts 0 for the opening shot, 1 to stops for the stops,
 * then back down through the rewind: stops + 1 is the stop before the
 * last again, and 2 * stops the opening shot.
 *
 * @param {number} stops  Stops after the opening shot, at least 1.
 * @param {string} length short, medium or long.
 * @return {{stops: number, beats: number[][], holds: number[][], spans: number[][], forwardEnd: number, heroOut: number[], outroIn: number[], lead: number, settle: number}} The timeline.
 */
export function timeline( stops, length ) {
	const run = runLength( stops, length );
	const per = ( LENGTHS[ length ] || LENGTHS.medium ) / run;
	const intro = INTRO / run;
	const beats = [
		[ 0, 0 ],
		[ intro, 0 ],
	];
	const holds = [];
	const spans = [];
	let at = intro;
	for ( let stop = 1; stop <= stops; stop++ ) {
		const from = at;
		at += per * TRAVEL;
		beats.push( [ at, stop ] );
		holds.push( [ at, at + per * ( 1 - TRAVEL ) ] );
		at += per * ( 1 - TRAVEL );
		beats.push( [ at, stop ] );
		spans.push( [ from, at ] );
	}
	const forwardEnd = at;
	const end = END / run;
	beats.push( [ at + REWIND / run, 2 * stops ], [ 1, 2 * stops ] );

	return {
		stops,
		beats,
		holds,
		spans,
		forwardEnd,
		heroOut: [ 0.002, intro + per * TRAVEL * 0.3 ],
		outroIn: [ 1 - end * 0.95, 1 - end * 0.45 ],
		lead: CARD_LEAD / run,
		settle: CARD_SETTLE / run,
	};
}

/**
 * Where the camera stands on its path at a scroll position.
 *
 * @param {number}     u     Scroll position, 0 to 1.
 * @param {number[][]} beats The timeline's beats.
 * @return {number} Path position, 0 to 2 * stops.
 */
export function pathAt( u, beats ) {
	let index = 0;
	while ( index < beats.length - 2 && u > beats[ index + 1 ][ 0 ] ) {
		index++;
	}
	const [ u0, t0 ] = beats[ index ];
	const [ u1, t1 ] = beats[ index + 1 ];
	return t0 + ( t1 - t0 ) * smooth( range( u, u0, u1 ) );
}

/**
 * How far a card has come in: 0 out, 1 fully in. It comes in as the
 * camera lands and leaves as the camera moves on.
 *
 * @param {number}   u    Scroll position, 0 to 1.
 * @param {number[]} hold The stop's hold, start and end.
 * @param {Object}   time The timeline (for lead and settle).
 * @return {number} From 0 to 1.
 */
export function cardAt( u, hold, time ) {
	const [ start, end ] = hold;
	if ( u <= start - time.lead || u >= end ) {
		return 0;
	}
	if ( u < start + time.settle ) {
		return outCubic( range( u, start - time.lead, start + time.settle ) );
	}
	if ( u <= end - time.settle ) {
		return 1;
	}
	return 1 - Math.pow( range( u, end - time.settle, end ), 3 );
}

/**
 * The stop whose card is most in at a scroll position.
 *
 * @param {number} u    Scroll position, 0 to 1.
 * @param {Object} time The timeline.
 * @return {number} Stop index from 0, or -1 between stops.
 */
export function activeStop( u, time ) {
	let active = -1;
	let most = 0;
	time.holds.forEach( ( hold, index ) => {
		const amount = cardAt( u, hold, time );
		if ( amount > most ) {
			most = amount;
			active = index;
		}
	} );
	return active;
}

/**
 * The scroll position in the middle of a stop's hold, where focusing its
 * card takes the page.
 *
 * @param {number} index Stop index from 0.
 * @param {Object} time  The timeline.
 * @return {number} Scroll position, 0 to 1.
 */
export function stopAt( index, time ) {
	const hold = time.holds[ clamp( index, 0, time.holds.length - 1 ) ];
	return ( hold[ 0 ] + hold[ 1 ] ) / 2;
}

/**
 * Which moment of a shot's clip shows, as a share of the clip. Going
 * forward, a stop's clip plays from the moment the camera leaves the stop
 * before through its own hold. Through the rewind each clip runs backwards
 * as the camera passes it. The opening shot carries on from where its
 * opening play stopped.
 *
 * @param {number} shot     Shot index, 0 for the opening shot.
 * @param {number} u        Scroll position, 0 to 1.
 * @param {Object} time     The timeline.
 * @param {number} heroBase Where the opening play stopped, as a share of its clip.
 * @return {number} From 0 to 1.
 */
export function clipAt( shot, u, time, heroBase = 0 ) {
	const { stops } = time;
	if ( u > time.forwardEnd ) {
		const p = pathAt( u, time.beats );
		const back = p <= stops ? p : 2 * stops - p;
		return 0 === shot ? range( back, 0, 1 ) : range( back, shot - 1, shot );
	}
	if ( 0 === shot ) {
		const base = clamp( heroBase );
		return base + ( 1 - base ) * range( u, 0, time.holds[ 0 ][ 0 ] ) * 0.6;
	}
	const span = time.spans[ shot - 1 ];
	return span ? range( u, span[ 0 ], span[ 1 ] ) : 0;
}

/**
 * When the opening shot stops its one play on load: at the hold time or
 * the end of the clip, whichever comes first. Nothing loops (WCAG 2.2.2).
 *
 * @param {number} duration Clip length in seconds, 0 or NaN when unknown.
 * @param {number} hold     Longest play in seconds.
 * @return {number} Seconds.
 */
export function openingStop( duration, hold ) {
	const longest = Math.max( 0, Number( hold ) || 0 );
	return duration > 0
		? Math.min( longest, Math.max( 0, duration - 0.05 ) )
		: longest;
}

/**
 * Eases the shown position toward the scroll position at the same speed
 * whatever the frame rate.
 *
 * @param {number} shown  Position shown last frame.
 * @param {number} target Scroll position.
 * @param {number} dt     Seconds since last frame.
 * @param {number} rate   How fast it catches up.
 * @return {number} Position to show.
 */
export function ease( shown, target, dt, rate ) {
	const next = shown + ( target - shown ) * ( 1 - Math.exp( -dt * rate ) );
	return Math.abs( target - next ) < 0.00005 ? target : next;
}

/**
 * The side a card sits on, away from the subject: the Cover's focal point
 * on the left half puts the card on the right.
 *
 * @param {number} x Focal point across, 0 to 1.
 * @return {string} left or right.
 */
export function side( x ) {
	return x >= 0.5 ? 'left' : 'right';
}

/**
 * How the block shows. Fewer than two shots leave nothing to play. With
 * reduced motion, or where the film can't stick to the screen, the shots
 * and cards stack as a plain page.
 *
 * @param {Object}  state        What the page allows.
 * @param {number}  state.shots  Cover blocks.
 * @param {boolean} state.reduce Reduced motion is on.
 * @param {boolean} state.stick  The block can stick to the screen.
 * @return {string} none, stacked or film.
 */
export function layout( { shots, reduce, stick } ) {
	if ( shots < 2 ) {
		return 'none';
	}
	return reduce || ! stick ? 'stacked' : 'film';
}

/**
 * The timecode for a path position, as minutes, seconds and frames at 24
 * frames a second.
 *
 * @param {number} p Path position.
 * @return {string} MM:SS:FF.
 */
export function stamp( p ) {
	const secs = Math.max( 0, p ) * 4.25;
	return [
		Math.floor( secs / 60 ),
		Math.floor( secs ) % 60,
		Math.floor( ( secs % 1 ) * 24 ),
	]
		.map( ( n ) => String( n ).padStart( 2, '0' ) )
		.join( ':' );
}
