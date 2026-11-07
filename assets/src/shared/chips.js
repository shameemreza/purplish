/**
 * Matching chips on the Product Filters block, shared by the front script
 * and the editor preview. WooCommerce prints the active filter chips and
 * the filter chips in different blocks, so the look is read from a chip
 * and set as `--purplish-chip-*` variables on the filters block. The
 * colors follow WooCommerce's own fallbacks for a selected chip.
 */

const CHIP =
	'.wp-block-woocommerce-product-filter-chips:not(.is-style-swatch) .wc-block-product-filter-chips__item';

/**
 * The variables for a selected chip, from the chips block's custom
 * properties and the chip's own computed look.
 *
 * @param {{getPropertyValue: (name: string) => string}} own  Computed style of the chips block.
 * @param {Object}                                       look Computed style of one chip.
 * @return {Object<string, string>} Values by name, as `background` for `--purplish-chip-background`.
 */
export function chipVars( own, look ) {
	const first = ( fallback, ...names ) =>
		names
			.map( ( name ) => own.getPropertyValue( name ).trim() )
			.find( Boolean ) || fallback;
	return {
		background: first(
			'CanvasText',
			'--wc-product-filter-chips-selected-background',
			'--wc-product-filter-chips-text'
		),
		border: first(
			'CanvasText',
			'--wc-product-filter-chips-selected-border',
			'--wc-product-filter-chips-text'
		),
		text: first(
			'Canvas',
			'--wc-product-filter-chips-selected-text',
			'--wc-product-filter-chips-background'
		),
		radius: look.borderRadius,
		padding: [ 'Top', 'Right', 'Bottom', 'Left' ]
			.map( ( side ) => look[ `padding${ side }` ] )
			.join( ' ' ),
		size: look.fontSize,
		spacing: look.letterSpacing,
		case: look.textTransform,
	};
}

/**
 * Keeps the filters block's chip variables in step with its chips. It
 * reads again whenever the filters change, since WooCommerce's chips
 * script fills in their colors after the page loads, and the editor draws
 * the chips again as they're edited.
 *
 * @param {HTMLElement} filters The Product Filters block.
 * @return {() => void} Stops watching and takes the variables off.
 */
export function matchChips( filters ) {
	const view = filters.ownerDocument.defaultView;
	const read = () => {
		const chip = filters.querySelector( CHIP );
		if ( ! chip ) {
			return;
		}
		const vars = chipVars(
			view.getComputedStyle(
				chip.closest( '.wp-block-woocommerce-product-filter-chips' )
			),
			view.getComputedStyle( chip )
		);
		// WooCommerce's filters script rewrites the whole style attribute,
		// so each variable is checked on the element, and set only when
		// it changed, which keeps the observer from looping.
		Object.entries( vars ).forEach( ( [ name, value ] ) => {
			const prop = `--purplish-chip-${ name }`;
			if ( filters.style.getPropertyValue( prop ) !== value ) {
				filters.style.setProperty( prop, value );
			}
		} );
	};

	let queued = false;
	let stopped = false;
	const watch = new view.MutationObserver( () => {
		if ( ! queued ) {
			queued = true;
			view.requestAnimationFrame( () => {
				queued = false;
				if ( ! stopped ) {
					read();
				}
			} );
		}
	} );
	watch.observe( filters, {
		subtree: true,
		childList: true,
		attributes: true,
		attributeFilter: [ 'style', 'class' ],
	} );
	read();

	return () => {
		stopped = true;
		watch.disconnect();
		[ ...filters.style ]
			.filter( ( prop ) => prop.startsWith( '--purplish-chip-' ) )
			.forEach( ( prop ) => filters.style.removeProperty( prop ) );
	};
}
