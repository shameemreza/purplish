/**
 * Which text the instalment line shows for the picked options.
 */

/**
 * The text key for the picked options: the key every variation that fits
 * the picks shares, `null` while they differ (the line keeps its "from"
 * text), or `''` when they share none (the line hides). A variation with
 * an empty value for an attribute fits any pick of it.
 *
 * @param {Array<[Object<string, string>, string]>} rows   Each variation's attributes and text key.
 * @param {Object<string, string>}                  picked Picked values by `attribute_*` name.
 * @return {string|null} The shared key, or null.
 */
export function pickKey( rows, picked ) {
	const chosen = Object.entries( picked ).filter( ( [ , value ] ) => value );
	if ( ! chosen.length ) {
		return null;
	}
	const keys = new Set();
	rows.forEach( ( [ attributes, key ] ) => {
		const fits = chosen.every(
			( [ name, value ] ) =>
				! attributes[ name ] || attributes[ name ] === value
		);
		if ( fits ) {
			keys.add( key );
		}
	} );
	if ( ! keys.size ) {
		return '';
	}
	return 1 === keys.size ? [ ...keys ][ 0 ] : null;
}
