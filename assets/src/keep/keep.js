/**
 * Column, keep in view while the photos scroll. The stylesheet makes the
 * column sticky from 782px and works out its top as
 * `min(head + gap, 100vh - height - gap)`: a short column sticks a gap
 * below the header, a tall one scrolls with the page until its end shows,
 * then sticks there. This tells the stylesheet where the admin bar or a
 * pinned theme header ends (`head`) and how tall the column is (`height`).
 */
import { pinnedBottom } from '../shared/header';

export const KEEP = '.wp-block-columns > .purplish-columns-keep';

/**
 * The custom properties for one kept column.
 *
 * @param {number} header Where a pinned theme header ends, 0 with none.
 * @param {number} admin  The admin bar's height, 0 without it.
 * @param {number} height The column's height.
 * @return {Object<string, string>} Property names and values.
 */
export function keepVars( header, admin, height ) {
	return {
		'--purplish-keep-head': Math.round( Math.max( header, admin ) ) + 'px',
		'--purplish-keep-height': Math.round( height ) + 'px',
	};
}

/**
 * Keeps each kept column's head and height up to date.
 *
 * @param {Document}       doc  The page.
 * @param {Window}         win  The window.
 * @param {MediaQueryList} wide Where columns sit side by side.
 */
export function watchKeep( doc, win, wide ) {
	const columns = [ ...doc.querySelectorAll( KEEP ) ];
	if ( ! columns.length ) {
		return;
	}
	let queued = false;
	const place = () => {
		queued = false;
		if ( ! wide.matches ) {
			return;
		}
		const header = pinnedBottom( doc, win, columns );
		const admin =
			parseFloat(
				win
					.getComputedStyle( doc.documentElement )
					.getPropertyValue( '--wp-admin--admin-bar--height' )
			) || 0;
		columns.forEach( ( column ) => {
			const vars = keepVars(
				header,
				admin,
				column.getBoundingClientRect().height
			);
			Object.keys( vars ).forEach( ( name ) => {
				if ( column.style.getPropertyValue( name ) !== vars[ name ] ) {
					column.style.setProperty( name, vars[ name ] );
				}
			} );
		} );
	};
	const queue = () => {
		if ( ! queued ) {
			queued = true;
			win.requestAnimationFrame( place );
		}
	};

	place();
	win.addEventListener( 'resize', queue, { passive: true } );
	// A header can slide away or shrink once the page scrolls.
	win.addEventListener( 'scroll', queue, { passive: true } );
	wide.addEventListener( 'change', queue );
	// A variation, an accordion or a late photo changes the column's height.
	if ( 'ResizeObserver' in win ) {
		const sizes = new win.ResizeObserver( queue );
		columns.forEach( ( column ) => sizes.observe( column ) );
	}
}
