/**
 * Layout: Bar, on the Product Filters block. Each filter's label opens its
 * list. A filter in a core Accordion keeps the Accordion's own button; a
 * plain filter gets a button from the server, which this opens and closes.
 * Where the filters sit in a row, one list is open at a time, and a click
 * outside, Escape, or Tab moving past it closes it. With Open on hover, a
 * list also opens when the mouse rests on its label and closes soon after
 * the mouse leaves it.
 * Listeners sit on the document, so they still work after WooCommerce
 * redraws the filters.
 */

export const BAR = '.purplish-filters-layout-bar';
export const PLAIN = 'purplish-filters-bar-toggle';
const TOGGLES = `${ BAR } :is(.wp-block-accordion-heading__toggle, .${ PLAIN })`;
const HOVER = `${ BAR }.purplish-filters-hover`;

// Long enough that a mouse passing over the row opens nothing, short
// enough to feel instant. Closing waits a little longer, so a mouse that
// strays off the list for a moment doesn't lose it.
export const OPEN_DELAY = 120;
export const CLOSE_DELAY = 250;

/**
 * Whether a label's list is open.
 *
 * @param {HTMLElement} toggle The label's button.
 * @return {boolean} True when open.
 */
const isOpen = ( toggle ) => 'true' === toggle.getAttribute( 'aria-expanded' );

/**
 * A plain filter's list.
 *
 * @param {HTMLElement} toggle The label's button.
 * @return {HTMLElement|null} The list's box.
 */
const panelOf = ( toggle ) =>
	toggle.ownerDocument.getElementById(
		toggle.getAttribute( 'aria-controls' )
	);

/**
 * Opens or closes a plain filter's list.
 *
 * @param {HTMLElement} toggle The label's button.
 * @param {boolean}     open   Whether to open it.
 */
export function setPlain( toggle, open ) {
	const panel = panelOf( toggle );
	toggle.setAttribute( 'aria-expanded', String( open ) );
	if ( panel ) {
		panel.hidden = ! open;
	}
}

/**
 * Closes a label's list. An Accordion closes through its own button, so
 * its state stays right.
 *
 * @param {HTMLElement} toggle The label's button.
 */
function close( toggle ) {
	delete toggle.dataset.purplishHovered;
	if ( toggle.classList.contains( PLAIN ) ) {
		setPlain( toggle, false );
	} else {
		toggle.click();
	}
}

/**
 * Whether a click landed on a label or in its list.
 *
 * @param {HTMLElement} toggle The label's button.
 * @param {Node}        target What was clicked.
 * @return {boolean} True when inside.
 */
function within( toggle, target ) {
	if ( ! toggle.classList.contains( PLAIN ) ) {
		const item = toggle.closest( '.wp-block-accordion-item' );
		return !! item && item.contains( target );
	}
	const panel = panelOf( toggle );
	return (
		toggle.contains( target ) || ( !! panel && panel.contains( target ) )
	);
}

/**
 * Whether the filters are in a row: Layout is Bar and the overlay isn't
 * showing. With the overlay on every screen, the bar never applies.
 *
 * @param {HTMLElement} bar    The Product Filters block.
 * @param {boolean}     isWide Whether the screen is past the overlay breakpoint.
 * @return {boolean} True in a row.
 */
export function inRow( bar, isWide ) {
	return (
		isWide &&
		bar.matches( '.is-mobile-overlay, .is-filter-drawer-disabled' )
	);
}

/**
 * The open labels in rows, other than the one given.
 *
 * @param {Document}         doc    The page.
 * @param {MediaQueryList}   wide   Past the overlay breakpoint.
 * @param {HTMLElement|null} except A label to leave open.
 * @return {HTMLElement[]} The labels.
 */
function openInRows( doc, wide, except ) {
	return [ ...doc.querySelectorAll( TOGGLES ) ].filter(
		( toggle ) =>
			toggle !== except &&
			isOpen( toggle ) &&
			inRow( toggle.closest( BAR ), wide.matches )
	);
}

/**
 * Opens a label's list and closes the others in its row.
 *
 * @param {Document}       doc    The page.
 * @param {MediaQueryList} wide   Past the overlay breakpoint.
 * @param {HTMLElement}    toggle The label's button.
 */
function openList( doc, wide, toggle ) {
	if ( toggle.classList.contains( PLAIN ) ) {
		setPlain( toggle, true );
		openInRows( doc, wide, toggle ).forEach( close );
	} else {
		// The click handler in watchBars closes the others.
		toggle.click();
	}
}

/**
 * Opens lists on hover, in rows with Open on hover. A mouse only: touch
 * and pens keep the tap. A click on a list that hover opened keeps it
 * open, since the shopper meant to open it.
 *
 * @param {Document}       doc  The page.
 * @param {MediaQueryList} wide Past the overlay breakpoint.
 */
function watchHover( doc, wide ) {
	let opening = null;
	let closing = null;

	const hoverable = ( toggle ) =>
		!! toggle.closest( HOVER ) &&
		inRow( toggle.closest( BAR ), wide.matches );

	// The label a node belongs to: the label itself, or an open list's.
	const ownerOf = ( node ) => {
		const toggle =
			node instanceof window.Element ? node.closest( TOGGLES ) : null;
		return (
			toggle ||
			openInRows( doc, wide, null ).find( ( item ) =>
				within( item, node )
			) ||
			null
		);
	};

	const stop = ( pending ) => pending && clearTimeout( pending.timer );

	doc.addEventListener( 'pointerover', ( event ) => {
		if ( 'mouse' !== event.pointerType ) {
			return;
		}
		const toggle = ownerOf( event.target );
		if ( ! toggle || ! hoverable( toggle ) ) {
			return;
		}
		if ( closing && closing.toggle === toggle ) {
			stop( closing );
			closing = null;
		}
		if ( isOpen( toggle ) || ( opening && opening.toggle === toggle ) ) {
			return;
		}
		stop( opening );
		opening = {
			toggle,
			timer: setTimeout( () => {
				opening = null;
				if ( ! isOpen( toggle ) ) {
					openList( doc, wide, toggle );
					toggle.dataset.purplishHovered = '1';
				}
			}, OPEN_DELAY ),
		};
	} );

	doc.addEventListener( 'pointerout', ( event ) => {
		if ( 'mouse' !== event.pointerType ) {
			return;
		}
		const toggle = ownerOf( event.target );
		const next = event.relatedTarget;
		if ( ! toggle || ( next && within( toggle, next ) ) ) {
			return;
		}
		if ( opening && opening.toggle === toggle ) {
			stop( opening );
			opening = null;
		}
		if ( isOpen( toggle ) && hoverable( toggle ) ) {
			stop( closing );
			closing = {
				toggle,
				timer: setTimeout( () => {
					closing = null;
					if ( isOpen( toggle ) ) {
						close( toggle );
					}
				}, CLOSE_DELAY ),
			};
		}
	} );

	// A click decides on its own, so a pending open stops.
	doc.addEventListener(
		'click',
		() => {
			stop( opening );
			opening = null;
		},
		true
	);
}

/**
 * Starts the bar's listeners.
 *
 * @param {Document}       doc  The page.
 * @param {MediaQueryList} wide Past the overlay breakpoint.
 */
export function watchBars( doc, wide ) {
	// Before the Accordion's own handler, to read whether it was closed.
	doc.addEventListener(
		'click',
		( event ) => {
			const target = event.target;
			const toggle =
				target instanceof window.Element
					? target.closest( TOGGLES )
					: null;

			if ( toggle && toggle.dataset.purplishHovered ) {
				// Hover opened it; the click means keep it.
				delete toggle.dataset.purplishHovered;
				if ( isOpen( toggle ) ) {
					event.preventDefault();
					event.stopPropagation();
					return;
				}
			}

			if ( toggle ) {
				const opening = ! isOpen( toggle );
				if ( toggle.classList.contains( PLAIN ) ) {
					setPlain( toggle, opening );
				}
				if ( opening ) {
					openInRows( doc, wide, toggle ).forEach( close );
				}
				return;
			}

			openInRows( doc, wide, null )
				.filter( ( open ) => ! within( open, target ) )
				.forEach( close );
		},
		true
	);

	watchHover( doc, wide );

	// Tab past an open list closes it, so it doesn't cover the products
	// while focus is under it. Focus that goes nowhere, such as a click on
	// the list's padding or another window, leaves it open.
	doc.addEventListener( 'focusout', ( event ) => {
		const next = event.relatedTarget;
		if ( ! ( next instanceof window.Node ) ) {
			return;
		}
		openInRows( doc, wide, null )
			.filter(
				( open ) =>
					within( open, event.target ) && ! within( open, next )
			)
			.forEach( close );
	} );

	doc.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' !== event.key ) {
			return;
		}
		const open = openInRows( doc, wide, null );
		open.forEach( close );
		if ( open.length ) {
			open[ 0 ].focus();
		}
	} );
}
