/**
 * Purplish effects that need a script: the gallery's hover styles follow
 * the mouse by setting CSS variables, the slide styles learn which way the
 * gallery moved, and two full-screen styles measure the view. The
 * stylesheet does the rest.
 */
import { __, sprintf } from '@wordpress/i18n';
import './style.css';

const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const canHover =
	window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches &&
	! reduced;

const LOUPE_BOX = 180;
const LOUPE_ZOOM = 2.5;

/**
 * The hover style named in a class list, as in `purplish-gallery-hover-zoom`.
 *
 * @param {DOMTokenList} classes Class list to read.
 * @param {string}       prefix  Class prefix before the style.
 * @return {string} The style, or ''.
 */
function styleIn( classes, prefix ) {
	const found = [ ...classes ].find( ( name ) => name.startsWith( prefix ) );
	return found ? found.slice( prefix.length ) : '';
}

/**
 * The photo on screen inside a frame. The block gallery scrolls its strip
 * sideways, so it's the shown slide nearest the strip's left edge; on
 * classic, FlexSlider marks it.
 *
 * @param {HTMLElement} frame The frame.
 * @return {HTMLImageElement|null} The photo.
 */
function shownPhoto( frame ) {
	const strip = frame.querySelector(
		'.wc-block-product-gallery-large-image__container'
	);
	if ( ! strip ) {
		return (
			frame.querySelector( '.flex-active-slide img' ) ||
			frame.querySelector( '.woocommerce-product-gallery__image img' )
		);
	}
	const left = strip.getBoundingClientRect().left;
	let best = null;
	let gap = Infinity;
	strip
		.querySelectorAll(
			'.wc-block-product-gallery-large-image__wrapper:not([hidden])'
		)
		.forEach( ( slide ) => {
			const away = Math.abs( slide.getBoundingClientRect().left - left );
			if ( away < gap ) {
				gap = away;
				best = slide;
			}
		} );
	return best?.querySelector( 'img' ) || null;
}

/**
 * The largest file the page already names for a photo.
 *
 * @param {HTMLImageElement} img The photo.
 * @return {string} Its address.
 */
function largest( img ) {
	if ( img.dataset.large_image ) {
		return img.dataset.large_image;
	}
	let best = img.currentSrc || img.src;
	let width = 0;
	( img.getAttribute( 'srcset' ) || '' ).split( ',' ).forEach( ( entry ) => {
		const [ url, size = '' ] = entry.trim().split( ' ' );
		const w = parseInt( size, 10 );
		if ( url && w > width ) {
			best = url;
			width = w;
		}
	} );
	return best;
}

/**
 * Where the picture sits inside its box, after `object-fit`.
 *
 * @param {HTMLImageElement} img The photo.
 * @return {{left: number, top: number, width: number, height: number}} The picture's box on screen.
 */
function pictureBox( img ) {
	const box = img.getBoundingClientRect();
	const fit = window.getComputedStyle( img ).objectFit;
	if ( ! img.naturalWidth || ( 'cover' !== fit && 'contain' !== fit ) ) {
		return box;
	}
	const pick = 'cover' === fit ? Math.max : Math.min;
	const ratio = pick(
		box.width / img.naturalWidth,
		box.height / img.naturalHeight
	);
	const width = img.naturalWidth * ratio;
	const height = img.naturalHeight * ratio;
	return {
		left: box.left + ( box.width - width ) / 2,
		top: box.top + ( box.height - height ) / 2,
		width,
		height,
	};
}

/**
 * Makes a frame follow the mouse for its hover style.
 *
 * @param {HTMLElement} frame The element the mouse moves over.
 * @param {string}      style The hover style.
 */
function follow( frame, style ) {
	frame.addEventListener( 'pointerenter', () => {
		const img = shownPhoto( frame );
		if ( 'loupe' === style && img ) {
			frame.style.setProperty(
				'--purplish-loupe',
				'url("' + largest( img ).split( '"' ).join( '%22' ) + '")'
			);
			frame.style.setProperty( '--purplish-loupe-box', LOUPE_BOX + 'px' );
		}
	} );

	frame.addEventListener( 'pointermove', ( event ) => {
		const box = frame.getBoundingClientRect();
		const x = ( event.clientX - box.left ) / box.width;
		const y = ( event.clientY - box.top ) / box.height;
		frame.style.setProperty( '--purplish-ry', ( x - 0.5 ) * 22 + 'deg' );
		frame.style.setProperty( '--purplish-rx', ( 0.5 - y ) * 16 + 'deg' );
		frame.style.setProperty( '--purplish-mx', x * 100 + '%' );
		frame.style.setProperty( '--purplish-my', y * 100 + '%' );
		frame.style.setProperty( '--purplish-dx', String( x - 0.5 ) );
		frame.style.setProperty( '--purplish-dy', String( y - 0.5 ) );

		const img = 'loupe' === style && shownPhoto( frame );
		if ( img ) {
			const picture = pictureBox( img );
			const px = ( event.clientX - picture.left ) * LOUPE_ZOOM;
			const py = ( event.clientY - picture.top ) * LOUPE_ZOOM;
			frame.style.setProperty(
				'--purplish-loupe-size',
				picture.width * LOUPE_ZOOM +
					'px ' +
					picture.height * LOUPE_ZOOM +
					'px'
			);
			frame.style.setProperty(
				'--purplish-loupe-pos',
				LOUPE_BOX / 2 - px + 'px ' + ( LOUPE_BOX / 2 - py ) + 'px'
			);
		}
	} );

	// The tilt resets fully; the others keep the mouse position so the
	// photo settles back where it was instead of jumping to the center.
	const reset =
		'tilt' === style
			? [
					'--purplish-rx',
					'--purplish-ry',
					'--purplish-mx',
					'--purplish-my',
				]
			: [
					'--purplish-rx',
					'--purplish-ry',
					'--purplish-dx',
					'--purplish-dy',
				];
	frame.addEventListener( 'pointerleave', () =>
		reset.forEach( ( name ) => frame.style.removeProperty( name ) )
	);
}

if ( canHover ) {
	document
		.querySelectorAll( '[class*="purplish-gallery-hover-"]' )
		.forEach( ( gallery ) => {
			const style = styleIn(
				gallery.classList,
				'purplish-gallery-hover-'
			);
			gallery
				.querySelectorAll( '.wc-block-product-gallery-large-image' )
				.forEach( ( frame ) => follow( frame, style ) );
		} );

	// Classic galleries follow on the slider's viewport, never the sliding strip.
	const classic = styleIn(
		document.body.classList,
		'purplish-classic-gallery-hover-'
	);
	if ( classic ) {
		window.addEventListener( 'load', () => {
			const gallery = document.querySelector(
				'.woocommerce-product-gallery'
			);
			const target =
				gallery?.querySelector( '.flex-viewport' ) ||
				gallery?.querySelector(
					'.woocommerce-product-gallery__wrapper'
				);
			if ( target ) {
				target.classList.add( 'purplish-hover-target' );
				follow( target, classic );
			}
		} );
	}
}

/**
 * Zoom from photo: when the view opens, the photo on screen grows out of
 * its place on the page. The view is scrolled to that photo first, so the
 * measured end is where it stays.
 *
 * @param {HTMLElement} gallery The gallery block.
 * @param {HTMLElement} dialog  Its full-screen view.
 */
function zoomFromPhoto( gallery, dialog ) {
	const content = dialog.querySelector(
		'.wc-block-product-gallery-dialog__content'
	);
	const frame = gallery.querySelector(
		'.wc-block-product-gallery-large-image'
	);
	const source = frame && shownPhoto( frame );
	const id = source?.dataset.imageId;
	const img = id && content?.querySelector( `img[data-image-id="${ id }"]` );
	if ( ! img || img.hidden || ! img.naturalWidth ) {
		return;
	}

	const from = source
		.closest( '.wc-block-product-gallery-large-image__container' )
		.getBoundingClientRect();
	const view = content.getBoundingClientRect();
	let to = img.getBoundingClientRect();
	content.scrollTop +=
		to.top + to.height / 2 - ( view.top + view.height / 2 );
	to = img.getBoundingClientRect();

	const scale = Math.min( from.width / to.width, from.height / to.height );
	const dx = from.left + from.width / 2 - ( to.left + to.width / 2 );
	const dy = from.top + from.height / 2 - ( to.top + to.height / 2 );
	img.style.setProperty(
		'--purplish-zoom-from',
		`translate(${ dx }px, ${ dy }px) scale(${ scale })`
	);
	img.classList.add( 'purplish-zoom-from' );
	dialog.classList.add( 'purplish-zooming' );
	img.addEventListener(
		'animationend',
		() => {
			img.classList.remove( 'purplish-zoom-from' );
			dialog.classList.remove( 'purplish-zooming' );
		},
		{ once: true }
	);
}

/**
 * Filmstrip: a row of small photos along the bottom of the view, built
 * from the photos it shows each time it opens. A click scrolls to that
 * photo; the one in view is marked current.
 *
 * @param {HTMLElement} dialog The full-screen view.
 */
function filmstrip( dialog ) {
	const content = dialog.querySelector(
		'.wc-block-product-gallery-dialog__content'
	);
	if ( ! content ) {
		return;
	}
	dialog.querySelector( '.purplish-filmstrip' )?.remove();
	dialog.purplishWatch?.disconnect();

	const photos = [ ...content.children ].filter(
		( photo ) => ! photo.hidden
	);
	if ( photos.length < 2 ) {
		return;
	}

	const strip = document.createElement( 'div' );
	strip.className = 'purplish-filmstrip';
	const buttons = photos.map( ( photo, index ) => {
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.setAttribute(
			'aria-label',
			sprintf(
				/* translators: 1: photo number, 2: number of photos. */
				__( 'Photo %1$d of %2$d', 'purplish' ),
				index + 1,
				photos.length
			)
		);
		const thumb = document.createElement( 'img' );
		thumb.alt = '';
		thumb.src =
			photo.currentSrc ||
			photo.src ||
			photo.getAttribute( 'poster' ) ||
			'';
		button.append( thumb );
		button.addEventListener( 'click', () =>
			photo.scrollIntoView( {
				block: 'center',
				inline: 'nearest',
				behavior: reduced ? 'instant' : 'smooth',
			} )
		);
		strip.append( button );
		return button;
	} );
	dialog.append( strip );

	const watch = new window.IntersectionObserver(
		( entries ) =>
			entries
				.filter( ( entry ) => entry.isIntersecting )
				.forEach( ( entry ) => {
					const index = photos.indexOf( entry.target );
					buttons.forEach( ( button, at ) =>
						button.setAttribute(
							'aria-current',
							at === index ? 'true' : 'false'
						)
					);
					buttons[ index ].scrollIntoView( {
						block: 'nearest',
						inline: 'nearest',
					} );
				} ),
		{ root: content, threshold: 0.6 }
	);
	photos.forEach( ( photo ) => watch.observe( photo ) );
	dialog.purplishWatch = watch;
}

document
	.querySelectorAll(
		'.purplish-gallery-fullscreen-zoom, .purplish-gallery-fullscreen-filmstrip'
	)
	.forEach( ( gallery ) => {
		const dialog = gallery.querySelector(
			'.wc-block-product-gallery-dialog'
		);
		if ( ! dialog ) {
			return;
		}
		const zoom = gallery.classList.contains(
			'purplish-gallery-fullscreen-zoom'
		);
		new window.MutationObserver( () => {
			if ( ! dialog.hasAttribute( 'open' ) ) {
				return;
			}
			if ( zoom && ! reduced ) {
				zoomFromPhoto( gallery, dialog );
			} else if ( ! zoom ) {
				filmstrip( dialog );
			}
		} ).observe( dialog, {
			attributes: true,
			attributeFilter: [ 'open' ],
		} );
	} );

/**
 * Tells the slide styles which way the gallery moved. WooCommerce hides
 * the photos outside the selected set and scrolls the strip sideways
 * between the ones it shows. When another photo lands on screen, the
 * strip gets `--purplish-from` (1 for a later photo, -1 for an earlier
 * one) and `purplish-sliding` again, before the new photo is painted.
 *
 * @param {HTMLElement} strip The gallery's list of slides.
 */
function followSlides( strip ) {
	const children = [ ...strip.children ];
	const onScreen = () => {
		const shown = children.filter( ( slide ) => ! slide.hidden );
		const at = Math.round(
			Math.abs( strip.scrollLeft ) / ( strip.clientWidth || 1 )
		);
		return shown[ at ] || null;
	};
	let last = onScreen();

	const check = () => {
		const now = onScreen();
		if ( ! now || now === last ) {
			return;
		}
		const later = children.indexOf( now ) > children.indexOf( last );
		strip.style.setProperty( '--purplish-from', later ? '1' : '-1' );
		// Restarts the style, which already ran on every slide in the set.
		strip.classList.remove( 'purplish-sliding' );
		// eslint-disable-next-line no-unused-expressions
		strip.offsetWidth;
		strip.classList.add( 'purplish-sliding' );
		last = now;
	};

	new window.MutationObserver( check ).observe( strip, {
		subtree: true,
		attributes: true,
		attributeFilter: [ 'hidden' ],
	} );

	// Arrows and thumbnails smooth-scroll the strip. The slide style takes
	// the place of that slide, so the strip jumps and the style plays.
	const scrollTo = strip.scrollTo;
	strip.scrollTo = ( options, ...rest ) => {
		scrollTo.call(
			strip,
			options && 'object' === typeof options
				? { ...options, behavior: 'instant' }
				: options,
			...rest
		);
		check();
	};

	// A swipe moves the photo by hand, so it only updates the photo on screen.
	strip.addEventListener( 'scrollend', () => {
		last = onScreen() || last;
	} );
}

if ( ! reduced ) {
	document
		.querySelectorAll(
			'[class*="purplish-gallery-slider-"] .wc-block-product-gallery-large-image__container'
		)
		.forEach( followSlides );
}
