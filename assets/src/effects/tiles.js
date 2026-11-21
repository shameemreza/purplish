/**
 * The Product Gallery's photo grid. From 600px the stylesheet turns
 * WooCommerce's slide strip into a grid; this keeps each photo working as
 * its own tile: it takes focus and opens full screen at itself, the tile
 * sizes follow the order WooCommerce shows a variation's photos in, and
 * below 600px a counter follows the slider.
 */

export const LAYOUT = 'purplish-gallery-layout-';
export const WIDE = '(min-width: 600px)';

const STRIP = '.wc-block-product-gallery-large-image__container';
const FRAME = '.wc-block-product-gallery-large-image';
const SLIDE = '.wc-block-product-gallery-large-image__wrapper';
const THUMB = '.wc-block-product-gallery-thumbnails__thumbnail';
const PHOTO = '[data-image-id]';

/**
 * How many grid tracks each tile spans, in the order shown. Two across has
 * two tracks, and an odd photo out goes first across both. Two large over
 * small has six: halves, then thirds, and a short last row fills the row
 * (one photo takes it all, two take half each).
 *
 * @param {number} count  Photos shown.
 * @param {string} layout `grid` or `mosaic`.
 * @return {number[]} Spans.
 */
export function tileSpans( count, layout ) {
	if ( 'mosaic' !== layout ) {
		return Array.from( { length: count }, ( _, at ) =>
			0 === at && count % 2 ? 2 : 1
		);
	}
	if ( 1 === count ) {
		return [ 6 ];
	}
	const spans = Array.from( { length: count }, ( _, at ) =>
		at < 2 ? 3 : 2
	);
	const left = ( count - 2 ) % 3;
	if ( 1 === left ) {
		spans[ count - 1 ] = 6;
	} else if ( 2 === left ) {
		spans[ count - 1 ] = 3;
		spans[ count - 2 ] = 3;
	}
	return spans;
}

/**
 * The shown slides in the order they're drawn: WooCommerce hides the ones
 * outside the photo set and gives the rest an `order`; without one, page
 * order.
 *
 * @param {HTMLElement[]} slides The slides, in page order.
 * @return {HTMLElement[]} Shown slides, in shown order.
 */
export function inShownOrder( slides ) {
	return slides
		.map( ( slide, at ) => ( {
			slide,
			order: Number( slide.style.order ) || 0,
			at,
		} ) )
		.filter( ( { slide } ) => ! slide.hidden )
		.sort( ( a, b ) => a.order - b.order || a.at - b.at )
		.map( ( { slide } ) => slide );
}

/**
 * The counter's words.
 *
 * @param {number} index Photo on screen, from 0.
 * @param {number} count Photos shown.
 * @return {string} Like `2/5`.
 */
export function counterText( index, count ) {
	return `${ Math.min( Math.max( index, 0 ), count - 1 ) + 1 }/${ count }`;
}

/**
 * Whether a gallery is showing its grid right now.
 *
 * @param {Element} element An element inside the gallery.
 * @return {boolean} True from 600px on a grid layout.
 */
export function isTiled( element ) {
	const gallery = element.closest( `[class*="${ LAYOUT }"]` );
	return (
		!! gallery &&
		!! element.ownerDocument.defaultView?.matchMedia( WIDE ).matches
	);
}

/**
 * Sets up one gallery with a grid layout.
 *
 * @param {HTMLElement} gallery The Product Gallery block.
 */
export function photoGrid( gallery ) {
	const strip = gallery.querySelector( STRIP );
	const frame = gallery.querySelector( FRAME );
	if ( ! strip || ! frame ) {
		return;
	}
	const view = gallery.ownerDocument.defaultView;
	const wide = view.matchMedia( WIDE );
	const layout = gallery.classList.contains( LAYOUT + 'mosaic' )
		? 'mosaic'
		: 'grid';
	const slides = [ ...strip.querySelectorAll( `:scope > ${ SLIDE }` ) ];
	const role = strip.getAttribute( 'aria-roledescription' );

	const counter = gallery.ownerDocument.createElement( 'span' );
	counter.className = 'purplish-gallery-counter';
	counter.setAttribute( 'aria-hidden', 'true' );
	frame.append( counter );

	const count = () => {
		const shown = inShownOrder( slides );
		const at = Math.round(
			Math.abs( strip.scrollLeft ) / ( strip.clientWidth || 1 )
		);
		counter.textContent = counterText( at, shown.length );
		counter.hidden = shown.length < 2;
	};

	// The stylesheet sizes tiles by page order. A variation's photos can
	// come in another order, so then each tile gets its span here.
	const place = () => {
		const shown = inShownOrder( slides );
		const paged = slides.filter( ( slide ) => ! slide.hidden );
		const same = shown.every( ( slide, at ) => slide === paged[ at ] );
		const spans = tileSpans( shown.length, layout );
		slides.forEach( ( slide ) => {
			const at = shown.indexOf( slide );
			const span = same || at < 0 ? '' : `span ${ spans[ at ] }`;
			if ( slide.style.gridColumn !== span ) {
				slide.style.gridColumn = span;
			}
		} );
		count();
	};

	// In the grid each photo takes focus itself; the strip isn't a
	// carousel there.
	const mode = () => {
		strip.tabIndex = wide.matches ? -1 : 0;
		if ( wide.matches ) {
			strip.removeAttribute( 'aria-roledescription' );
		} else if ( role ) {
			strip.setAttribute( 'aria-roledescription', role );
		}
		slides.forEach( ( slide ) => {
			const photo = slide.querySelector( PHOTO );
			if ( photo ) {
				photo.tabIndex = wide.matches ? 0 : -1;
			}
		} );
	};

	// A click or focus on a tile selects its photo through WooCommerce's
	// own thumbnail, so full screen opens at it.
	const pick = ( event ) => {
		const slide = wide.matches && event.target.closest?.( SLIDE );
		const photo = slide && slide.querySelector( PHOTO );
		if ( ! photo ) {
			return;
		}
		frame.purplishPicked = photo;
		gallery
			.querySelector(
				`${ THUMB } [data-image-id="${ photo.dataset.imageId }"]`
			)
			?.click();
	};
	strip.addEventListener( 'click', pick, true );
	strip.addEventListener( 'focusin', pick );

	// WooCommerce reads a key press on the strip as leaving its scroll
	// position, and selects the photo it finds in view: in a grid, always
	// the first. So Enter and Space open the focused photo with a click
	// instead, before the strip hears them.
	gallery.addEventListener(
		'keydown',
		( event ) => {
			const photo =
				wide.matches &&
				( 'Enter' === event.key || ' ' === event.key ) &&
				event.target.matches?.( `${ SLIDE } ${ PHOTO }` ) &&
				event.target;
			if ( ! photo || ! strip.contains( photo ) ) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			photo.click();
		},
		true
	);

	// Without a thumbnails block there's nothing to select through, so the
	// view is scrolled to the photo once it opens.
	const dialog = gallery.querySelector( '.wc-block-product-gallery-dialog' );
	if ( dialog ) {
		new view.MutationObserver( () => {
			const photo = frame.purplishPicked;
			const id = photo?.dataset.imageId;
			if (
				! dialog.hasAttribute( 'open' ) ||
				! wide.matches ||
				! id ||
				gallery.querySelector( `${ THUMB } [data-image-id="${ id }"]` )
			) {
				return;
			}
			view.requestAnimationFrame( () =>
				dialog
					.querySelector(
						`.wc-block-product-gallery-dialog__content [data-image-id="${ id }"]`
					)
					?.scrollIntoView( { block: 'center', inline: 'nearest' } )
			);
		} ).observe( dialog, {
			attributes: true,
			attributeFilter: [ 'open' ],
		} );
	}

	let frameId = 0;
	strip.addEventListener(
		'scroll',
		() => {
			view.cancelAnimationFrame( frameId );
			frameId = view.requestAnimationFrame( count );
		},
		{ passive: true }
	);
	new view.MutationObserver( place ).observe( strip, {
		subtree: true,
		attributes: true,
		attributeFilter: [ 'hidden' ],
	} );
	wide.addEventListener( 'change', mode );

	mode();
	place();
}
