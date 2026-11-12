/**
 * The grid switch on Product Collection, kept apart from the page so it
 * can be tested: where the buttons go, and switching the grid.
 */

/**
 * Where the choice lasts for the visit. The inline script the server
 * prints beside the buttons reads the same key.
 */
export const KEY = 'purplish-cards-grid';

/**
 * Puts the buttons just after the nearest Sort by, looking up to three
 * levels above them, or leaves them above the grid. A Sort by inside the
 * grid is skipped, since WooCommerce draws it again with the grid.
 *
 * @param {HTMLElement} buttons The switch, printed just before the grid.
 * @return {HTMLElement|null} The Sort by block it sits beside, if any.
 */
export function placeSwitch( buttons ) {
	let scope = buttons.parentElement;
	for ( let level = 0; scope && level < 3; level++ ) {
		const sorting = [
			...scope.querySelectorAll(
				'.wp-block-woocommerce-catalog-sorting'
			),
		].find( ( found ) => ! found.closest( '[data-wp-router-region]' ) );
		if ( sorting ) {
			sorting.after( buttons );
			buttons.classList.add( 'is-beside' );
			return sorting;
		}
		scope = scope.parentElement;
	}
	buttons.classList.remove( 'is-beside' );
	return null;
}

/**
 * The size the shopper picked earlier in this visit.
 *
 * @return {string} `other` or `own`.
 */
export function remembered() {
	try {
		return 'other' === window.sessionStorage.getItem( KEY )
			? 'other'
			: 'own';
	} catch {
		return 'own';
	}
}

/**
 * Shows the grid at a size, marks the pressed button in every switch on
 * the page, and keeps the choice for the visit.
 *
 * @param {Document} doc  The page.
 * @param {string}   size `other` or `own`.
 */
export function setSize( doc, size ) {
	if ( 'other' === size ) {
		doc.documentElement.dataset.purplishGrid = 'other';
	} else {
		delete doc.documentElement.dataset.purplishGrid;
	}
	doc.querySelectorAll( '.purplish-grid-switch__button' ).forEach(
		( button ) =>
			button.setAttribute(
				'aria-pressed',
				String( size === button.dataset.size )
			)
	);
	try {
		window.sessionStorage.setItem( KEY, size );
	} catch {}
}

/**
 * Switches the grid, moving each card to its new place where the browser
 * can, and at once with reduced motion.
 *
 * @param {Document} doc     The page.
 * @param {string}   size    `other` or `own`.
 * @param {boolean}  reduced Whether the shopper asked for reduced motion.
 */
export function change( doc, size, reduced ) {
	if ( reduced || ! doc.startViewTransition ) {
		setSize( doc, size );
		return;
	}
	const cards = [
		...doc.querySelectorAll(
			'[class*="purplish-cards-grid-"] .wc-block-product-template > li'
		),
	];
	cards.forEach( ( card, index ) => {
		card.style.viewTransitionName = 'purplish-card-' + index;
	} );
	doc.documentElement.classList.add( 'is-purplish-regrid' );
	const done = () => {
		cards.forEach( ( card ) => {
			card.style.viewTransitionName = '';
		} );
		doc.documentElement.classList.remove( 'is-purplish-regrid' );
	};
	doc.startViewTransition( () => setSize( doc, size ) ).finished.then(
		done,
		done
	);
}
