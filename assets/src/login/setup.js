/**
 * Sets up the login and register options on one element, from its
 * `purplish-account-*` classes. The front script runs it on each element
 * on the page; the editor runs it on its sample forms.
 */
import { bloom, ripple, roll } from './buttons';
import { feedback } from './feedback';
import { floating, wave } from './labels';
import { light } from './light';
import { switcher } from './switch';

/**
 * @param {HTMLElement} group     The element with the option classes.
 * @param {number}      [index=0] Its place on the page, for unique IDs.
 */
export function setup( group, index = 0 ) {
	const has = ( name ) =>
		group.classList.contains( 'purplish-account-' + name );
	// Before the labels, so they find each email field in its wrap.
	if ( has( 'feedback' ) ) {
		feedback( group );
	}
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
	if ( has( 'aurora' ) || has( 'spotlight' ) ) {
		light( group, has( 'spotlight' ) );
	}
	if ( has( 'button-bloom' ) ) {
		bloom( group );
	}
	if ( has( 'button-roll' ) ) {
		roll( group );
	}
	if ( has( 'button-ripple' ) ) {
		ripple( group );
	}
}
