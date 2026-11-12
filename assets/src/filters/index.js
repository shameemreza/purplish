/**
 * The Product Filters options that need a script: Layout: Bar (see
 * ./bar.js) and Sidebar can hide.
 *
 * Sidebar can hide, on the Product Filters block. The button the server
 * prints before the block moves above the products, beside Sort by when
 * the grid has it, and folds the filters' column away. The choice lasts
 * for the visit. The stylesheet does the motion, and phones keep
 * WooCommerce's own overlay.
 */
import { placeToggle, sides } from './place';
import { watchBars } from './bar';

const KEY = 'purplish-filters-folded';
const wide = window.matchMedia( '(min-width: 782px)' );

/**
 * Whether the shopper folded the filters earlier in this visit.
 *
 * @return {boolean} True when folded.
 */
function remembered() {
	try {
		return '1' === window.sessionStorage.getItem( KEY );
	} catch {
		return false;
	}
}

/**
 * Keeps the choice for the visit.
 *
 * @param {boolean} folded Whether the filters are folded.
 */
function remember( folded ) {
	try {
		window.sessionStorage.setItem( KEY, folded ? '1' : '0' );
	} catch {}
}

document
	.querySelectorAll( '.purplish-filters-toggle' )
	.forEach( ( toggle, index ) => {
		const found = sides( toggle );
		if ( ! found ) {
			toggle.remove();
			return;
		}
		const { side, row, main } = found;
		const text = toggle.querySelector( '.purplish-filters-toggle__text' );

		side.id = side.id || 'purplish-filters-' + index;
		side.classList.add( 'purplish-filters-side' );
		row.classList.add( 'purplish-filters-row' );
		toggle.setAttribute( 'aria-controls', side.id );

		const fold = ( folded ) => {
			const gap = parseFloat( window.getComputedStyle( row ).columnGap );
			side.style.setProperty(
				'--purplish-filters-gap',
				( gap || 0 ) + 'px'
			);
			row.classList.toggle( 'is-folded', folded );
			side.classList.toggle( 'is-folded', folded );
			toggle.classList.toggle( 'is-folded', folded );
			toggle.setAttribute( 'aria-expanded', String( ! folded ) );
			text.textContent = folded
				? toggle.dataset.show
				: toggle.dataset.hide;
			side.inert = folded && wide.matches;
		};

		row.classList.add( 'is-instant' );
		fold( remembered() );
		placeToggle( toggle, main );
		toggle.hidden = false;
		window.requestAnimationFrame( () =>
			window.requestAnimationFrame( () =>
				row.classList.remove( 'is-instant' )
			)
		);

		toggle.addEventListener( 'click', () => {
			const folded = ! side.classList.contains( 'is-folded' );
			fold( folded );
			remember( folded );
		} );
		wide.addEventListener( 'change', () => {
			side.inert = side.classList.contains( 'is-folded' ) && wide.matches;
		} );

		// A filter change redraws the products, Sort by and all.
		new window.MutationObserver( () => {
			if ( ! main.contains( toggle ) ) {
				placeToggle( toggle, main );
			}
		} ).observe( main, { childList: true, subtree: true } );
	} );

// WooCommerce's overlay shows up to 782px wide.
watchBars( document, window.matchMedia( '(width > 782px)' ) );
