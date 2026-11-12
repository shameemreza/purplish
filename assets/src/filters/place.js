/**
 * Where the Hide filters button goes, kept apart so it can be tested.
 */

/**
 * The filters' column, the columns row, and the column with the products.
 *
 * @param {HTMLElement} toggle The button, printed just before the filters.
 * @return {{side: HTMLElement, row: HTMLElement, main: HTMLElement}|null} The three, or null when the filters don't sit in a column beside another.
 */
export function sides( toggle ) {
	const side = toggle.closest( '.wp-block-column' );
	const row = side && side.parentElement;
	if ( ! row || ! row.classList.contains( 'wp-block-columns' ) ) {
		return null;
	}
	const main = [ ...row.children ].find(
		( column ) =>
			column !== side && column.classList.contains( 'wp-block-column' )
	);
	return main ? { side, row, main } : null;
}

/**
 * Puts the button just before Sort by when the products' column has it,
 * or at the top of the column.
 *
 * @param {HTMLElement} toggle The button.
 * @param {HTMLElement} main   The products' column.
 */
export function placeToggle( toggle, main ) {
	const sorting = main.querySelector(
		'.wp-block-woocommerce-catalog-sorting'
	);
	toggle.classList.toggle( 'is-beside', !! sorting );
	if ( sorting ) {
		sorting.before( toggle );
	} else {
		main.prepend( toggle );
	}
}
