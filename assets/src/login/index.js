/**
 * Login and register options on My Account (see ./setup.js). Loads only
 * for logged-out visitors on a page with an option set.
 */
import { setup } from './setup';

const run = () =>
	document.querySelectorAll( '.purplish-account' ).forEach( setup );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', run );
} else {
	run();
}
