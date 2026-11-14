/**
 * A sticky Product Filters block, like the sidebar in the Atelier and
 * Boutique shop patterns, sticks a set distance from the top of the window.
 * Core can't know how tall the theme's header is, so a header that also
 * stays at the top (sticky or fixed) covers the filters. This moves the
 * filters below that header, keeping the block's own distance, and lets
 * them go back up when the header scrolls or slides away. Filters taller
 * than the room left scroll inside their own box, so the last ones can be
 * reached without scrolling past every product.
 */

export const STICKY = '.wc-block-product-filters.is-position-sticky';
const RING = 4;
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
 * @param {Document} doc     The page.
 * @param {Element}  filters The filters, left out with what's inside them.
 * @return {HTMLElement[]} The elements.
 */
function pinnable( doc, filters ) {
	const found = new Set();
	doc.querySelectorAll( HEADERS ).forEach( ( el ) => {
		[ el, ...el.children ].forEach( ( item ) => {
			if (
				! filters.some( ( f ) => f.contains( item ) ) &&
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
 * Keeps each sticky Product Filters block below a pinned theme header.
 *
 * @param {Document} doc The page.
 * @param {Window}   win The window.
 */
export function watchSticky( doc, win ) {
	const filters = [ ...doc.querySelectorAll( STICKY ) ];
	if ( ! filters.length ) {
		return;
	}
	// Core sets the admin bar's offset on the sticky block itself.
	const admin = ( f ) =>
		parseFloat(
			win
				.getComputedStyle( f )
				.getPropertyValue( '--wp-admin--admin-bar--position-offset' )
		) || 0;
	// The block's own distance from the top, admin bar left out, and its
	// side padding and margins before any of this.
	const gaps = filters.map( ( f ) => {
		const top = parseFloat( win.getComputedStyle( f ).top );
		return Number.isFinite( top ) ? Math.max( 0, top - admin( f ) ) : 0;
	} );
	const sides = filters.map( ( f ) => {
		const style = win.getComputedStyle( f );
		return [ 'left', 'right' ].map( ( side ) => [
			parseFloat( style.getPropertyValue( 'padding-' + side ) ) || 0,
			parseFloat( style.getPropertyValue( 'margin-' + side ) ) || 0,
		] );
	} );

	const set = ( el, name, value ) => {
		if ( el.style.getPropertyValue( name ) !== value ) {
			el.style.setProperty( name, value );
		}
	};
	let queued = false;
	const place = () => {
		queued = false;
		const bottom = headerBottom(
			pinnable( doc, filters ).map( ( el ) => {
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
		filters.forEach( ( f, i ) => {
			// A theme that blocks sticky, or WooCommerce's open overlay,
			// leaves nothing to move.
			const sticky =
				'sticky' === win.getComputedStyle( f ).position &&
				! f.classList.contains( 'is-overlay-opened' );
			const below = sticky && bottom > admin( f );
			const top = ( below ? bottom : admin( f ) ) + gaps[ i ];
			const room = win.innerHeight - top - gaps[ i ];
			// scrollHeight is the whole list, even while the box is capped.
			const tall = sticky && f.scrollHeight > room + 1;
			set( f, 'top', below ? top + 'px' : '' );
			set( f, 'max-height', tall ? room + 'px' : '' );
			set( f, 'overflow-y', tall ? 'auto' : '' );
			set( f, 'overscroll-behavior', tall ? 'contain' : '' );
			// Room for focus rings, which a scrolling box clips at its edges.
			[ 'left', 'right' ].forEach( ( side, s ) => {
				const [ padding, margin ] = sides[ i ][ s ];
				set( f, 'padding-' + side, tall ? padding + RING + 'px' : '' );
				set( f, 'margin-' + side, tall ? margin - RING + 'px' : '' );
			} );
		} );
	};
	// Looked up again each time: some themes only make the header fixed,
	// shrink it or slide it away once the page scrolls.
	const queue = () => {
		if ( ! queued ) {
			queued = true;
			win.requestAnimationFrame( place );
		}
	};

	place();
	win.addEventListener( 'resize', queue, { passive: true } );
	win.addEventListener( 'scroll', queue, { passive: true } );
	// An accordion opening or a filter redraw changes the list's height.
	filters.forEach( ( f ) => {
		new win.MutationObserver( queue ).observe( f, {
			attributes: true,
			childList: true,
			subtree: true,
		} );
		f.addEventListener( 'transitionend', queue );
	} );
}
