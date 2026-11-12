/**
 * Load more, on Query Pagination inside Product Collection. The server
 * prints the link; this keeps the page in place after it loads.
 */
import { track } from './track';

track(
	document,
	window,
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
);
