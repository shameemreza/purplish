/**
 * Pure helpers for the Delivery Line's script, shared with Jest: pick the
 * state for a moment, word the time left, and split a sentence into text
 * and strong parts.
 */

/**
 * The state that holds at a moment: the last one that has started. Before
 * the first one (a clock behind the server), the first.
 *
 * @param {Array<{t: number}>} states States, oldest first.
 * @param {number}             now    The moment, in seconds.
 * @return {Object|null} The state.
 */
export function pick( states, now ) {
	let found = null;
	for ( const state of states ) {
		if ( state.t > now ) {
			break;
		}
		found = state;
	}
	return found || states[ 0 ] || null;
}

/**
 * Hours and minutes from seconds left, at least one minute.
 *
 * @param {number} seconds Seconds left.
 * @return {{hours: number, minutes: number}} Whole hours and the minutes after them.
 */
export function split( seconds ) {
	const total = Math.max( 1, Math.floor( seconds / 60 ) );
	return { hours: Math.floor( total / 60 ), minutes: total % 60 };
}

/**
 * A number with a unit, in the site's language.
 *
 * @param {string} locale BCP 47 locale.
 * @param {string} name   hour or minute.
 * @param {number} value  The number.
 * @return {string} Like "3 hours".
 */
function unit( locale, name, value ) {
	const options = { style: 'unit', unit: name, unitDisplay: 'long' };
	try {
		return new Intl.NumberFormat( locale, options ).format( value );
	} catch {
		return new Intl.NumberFormat( undefined, options ).format( value );
	}
}

/**
 * Time left as shoppers read it, like "3 hours 20 minutes".
 *
 * @param {number} seconds Seconds left.
 * @param {string} locale  BCP 47 locale.
 * @param {string} both    The sentence for hours and minutes, like "%1$s %2$s".
 * @return {string} The words.
 */
export function timeLeft( seconds, locale, both ) {
	const { hours, minutes } = split( seconds );
	if ( ! hours ) {
		return unit( locale, 'minute', minutes );
	}
	if ( ! minutes ) {
		return unit( locale, 'hour', hours );
	}
	return both
		.replace( '%1$s', unit( locale, 'hour', hours ) )
		.replace( '%2$s', unit( locale, 'minute', minutes ) );
}

/**
 * Splits a sentence with numbered placeholders into parts.
 *
 * @param {string}   template A sentence, like "Arrives by %1$s".
 * @param {string[]} values   Values, in order.
 * @param {number[]} plain    Positions (from 1) that aren't strong.
 * @return {Array<{text: string, strong: boolean}>} Parts, in order.
 */
export function parts( template, values, plain = [] ) {
	return template
		.split( /(%\d+\$s)/ )
		.filter( ( piece ) => '' !== piece )
		.map( ( piece ) => {
			const match = /^%(\d+)\$s$/.exec( piece );
			if ( ! match ) {
				return { text: piece, strong: false };
			}
			const at = Number( match[ 1 ] );
			return {
				text: values[ at - 1 ] ?? '',
				strong: ! plain.includes( at ),
			};
		} );
}

/**
 * The date line's sentence and values for a state.
 *
 * @param {Object} data  The block's data.
 * @param {Object} state A state.
 * @param {number} now   The moment, in seconds.
 * @return {{template: string, values: string[]}} What to show.
 */
export function dateLine( data, state, now ) {
	const range = data.range && state.a !== state.b;
	const dates = range ? [ state.a, state.b ] : [ state.b ];
	if ( data.countdown && state.c > now ) {
		return {
			template: data.texts[ range ? 'range_left' : 'by_left' ],
			values: [
				timeLeft( state.c - now, data.locale, data.texts.hm ),
				...dates,
			],
		};
	}
	return { template: data.texts[ range ? 'range' : 'by' ], values: dates };
}
