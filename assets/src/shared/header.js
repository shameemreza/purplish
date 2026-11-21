/**
 * Finds where a theme header that stays at the top of the window ends, so
 * things that stick below it (a sticky Product Filters block, a kept
 * Column) aren't covered by it. Core can't know how tall the header is.
 */

const HEADERS =
	'header, [role="banner"], .wp-block-template-part, #masthead, .site-header';

/**
 * Where the lowest pinned header ends, in pixels from the top of the
 * window. A header counts when it's fixed or sticky, spans at least half
 * the window, sits in its top third, and isn't a full-screen menu.
 *
 * @param {Array<{position: string, top: number, rectTop: number, height: number, width: number}>} boxes Each candidate's position, CSS top, top on screen and size.
 * @param {{width: number, height: number}}                                                        view  The window.
 * @return {number} The bottom edge, or 0 with no pinned header.
 */
export function headerBottom( boxes, view ) {
	return boxes.reduce( ( lowest, box ) => {
		if (
			! [ 'fixed', 'sticky' ].includes( box.position ) ||
			box.width < view.width / 2 ||
			! box.height ||
			box.height > view.height / 2
		) {
			return lowest;
		}
		// A sticky header that hasn't reached its spot yet will stop at its
		// CSS top; one that slid up out of view counts from where it is.
		const top =
			'sticky' === box.position
				? Math.min( box.rectTop, box.top || 0 )
				: box.rectTop;
		if ( top > view.height / 3 ) {
			return lowest;
		}
		return Math.max( lowest, top + box.height );
	}, 0 );
}

/**
 * The fixed or sticky elements that could be the header: each candidate and
 * its children, since block themes put the sticky setting on the Group
 * inside the header template part.
 *
 * @param {Document}      doc  The page.
 * @param {HTMLElement[]} skip Elements left out with what's inside them.
 * @return {HTMLElement[]} The elements.
 */
function pinnable( doc, skip ) {
	const found = new Set();
	doc.querySelectorAll( HEADERS ).forEach( ( el ) => {
		[ el, ...el.children ].forEach( ( item ) => {
			if (
				! skip.some( ( s ) => s.contains( item ) ) &&
				[ 'fixed', 'sticky' ].includes(
					doc.defaultView.getComputedStyle( item ).position
				)
			) {
				found.add( item );
			}
		} );
	} );
	return [ ...found ];
}

/**
 * Where the lowest pinned header on the page ends right now. Looked up
 * each time: some themes only make the header fixed, shrink it or slide it
 * away once the page scrolls.
 *
 * @param {Document}      doc  The page.
 * @param {Window}        win  The window.
 * @param {HTMLElement[]} skip Elements left out with what's inside them.
 * @return {number} The bottom edge in pixels, or 0 with no pinned header.
 */
export function pinnedBottom( doc, win, skip ) {
	return headerBottom(
		pinnable( doc, skip ).map( ( el ) => {
			const rect = el.getBoundingClientRect();
			const style = win.getComputedStyle( el );
			return {
				position: style.position,
				top: parseFloat( style.top ),
				rectTop: rect.top,
				height: rect.height,
				width: rect.width,
			};
		} ),
		{ width: win.innerWidth, height: win.innerHeight }
	);
}
