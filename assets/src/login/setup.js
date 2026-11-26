/**
 * Sets up the login and register options on one element, from its
 * `purplish-account-*` classes. The front script runs it on each element
 * on the page; the editor runs it on its sample forms.
 */
import { floating, wave } from './labels';
import { switcher } from './switch';

/**
 * @param {HTMLElement} group     The element with the option classes.
 * @param {number}      [index=0] Its place on the page, for unique IDs.
 */
export function setup( group, index = 0 ) {
	const has = ( name ) =>
		group.classList.contains( 'purplish-account-' + name );
	if ( has( 'labels-wave' ) ) {
		wave( group );
	}
	if (
		has( 'labels-floating' ) ||
		has( 'labels-flip' ) ||
		has( 'labels-wave' )
	) {
		floating( group );
	}
	if (
		has( 'switch-tabs' ) ||
		has( 'switch-slide' ) ||
		has( 'switch-flip' )
	) {
		switcher( group, index );
	}
}
