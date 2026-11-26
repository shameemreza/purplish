/**
 * Sets up the login and register options on one element, from its
 * `purplish-account-*` classes. The front script runs it on each element
 * on the page; the editor runs it on its sample forms.
 */
import { floating, wave } from './labels';

/**
 * @param {HTMLElement} group The element with the option classes.
 */
export function setup( group ) {
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
}
