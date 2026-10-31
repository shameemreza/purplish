/**
 * The matching rule, shared by block and classic themes. The same rule as
 * PageData::match() in PHP.
 */

/**
 * @typedef {Object} ColorSet
 * @property {string} color Accent color.
 * @property {string} on    Text color on the accent.
 * @property {string} tint  Page tint.
 */

/**
 * The color set the picked values point to: keep the variations that fit
 * every picked value (an empty value in a variation means any), and use
 * their color when they all share one.
 *
 * @param {Array<Object>}          variations Variations with `attributes` and their color set.
 * @param {Object<string, string>} picked     Picked values by `attribute_*` name; empty means not picked.
 * @return {ColorSet|null} Null when nothing is picked or the colors differ.
 */
export function match( variations, picked ) {
	const chosen = Object.entries( picked ).filter(
		( [ , value ] ) => '' !== String( value ?? '' )
	);
	if ( ! chosen.length ) {
		return null;
	}

	const sets = new Map();
	for ( const variation of variations ) {
		const fits = chosen.every( ( [ name, value ] ) => {
			const own = String( variation.attributes?.[ name ] ?? '' );
			return '' === own || own === String( value );
		} );
		if ( fits ) {
			sets.set( variation.color, {
				color: variation.color,
				on: variation.on,
				tint: variation.tint,
			} );
		}
	}

	return 1 === sets.size ? [ ...sets.values() ][ 0 ] : null;
}

/**
 * The color set to show next. Nothing picked goes back to the default;
 * picked values that don't settle on one color keep the current set.
 *
 * @param {Array<Object>}          variations Variations with their color sets.
 * @param {Object<string, string>} picked     Picked values.
 * @param {ColorSet|null}          fallback   The default set, or null for the theme colors.
 * @param {ColorSet|null}          current    The set showing now.
 * @return {ColorSet|null} The set to show, or null for the theme colors.
 */
export function nextColor( variations, picked, fallback, current ) {
	const anyPicked = Object.values( picked ).some(
		( value ) => '' !== String( value ?? '' )
	);
	if ( ! anyPicked ) {
		return fallback;
	}
	return match( variations, picked ) ?? current;
}
