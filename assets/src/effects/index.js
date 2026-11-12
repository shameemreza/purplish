/**
 * Purplish effects that need a script: the gallery's hover styles follow
 * the mouse by setting CSS variables, the slide styles learn which way the
 * gallery moved, two full-screen styles measure the view, the active
 * filter chips copy a chip's look, the mini-cart count pops, added
 * products fly into the mini-cart, the glass
 * button's highlight follows the mouse, googly eyes look at it, card
 * hover looks follow it, a momentum, helix or reel carousel can be thrown
 * with the mouse and settles on a card, a reel opens on its middle card,
 * and headings, groups and columns wait below the view until they scroll
 * in.
 * The stylesheet does the rest.
 */
import { __, sprintf } from '@wordpress/i18n';
import { moveHighlight, clearHighlight } from '../shared/glass';
import {
	enterFrame,
	moveFrame,
	leaveFrame,
	imageLight,
	fitImage,
} from '../shared/hover';
import { matchChips } from '../shared/chips';
import { flyToCart } from '../shared/fly';
import { throwStrips } from '../shared/throw';
import { settleStrips } from '../shared/settle';
import './style.css';
import './shop/style.css';

const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const canHover =
	window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches &&
	! reduced;

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
 * Makes a frame follow the mouse for its hover style.
 *
 * @param {HTMLElement}                   frame         The element that gets the mouse variables.
 * @param {string}                        style         The hover style.
 * @param {Object}                        [where]       Where the mouse is read, when it isn't the frame.
 * @param {HTMLElement}                   [where.area]  The element the mouse moves over.
 * @param {() => HTMLImageElement | null} [where.photo] The photo under the loupe.
 */
function follow(
	frame,
	style,
	{ area = frame, photo = () => shownPhoto( frame ) } = {}
) {
	area.addEventListener( 'pointerenter', () =>
		enterFrame( frame, style, photo() )
	);
	area.addEventListener( 'pointermove', ( event ) =>
		moveFrame(
			frame,
			style,
			event,
			area,
			'loupe' === style ? photo() : null
		)
	);
	area.addEventListener( 'pointerleave', () => leaveFrame( frame, style ) );
}

/**
 * A hover style on an image block. The photo is the frame, so the light
 * layer for the tilt, glare and loupe is added beside it on the first
 * hover, after the lightbox script has set up the figure.
 *
 * @param {HTMLElement} figure The image block.
 */
function imageHover( figure ) {
	const img = figure.querySelector( 'img' );
	if ( ! img ) {
		return;
	}
	const style = styleIn( figure.classList, 'purplish-image-hover-' );
	const light = imageLight( document, style );

	img.addEventListener( 'pointerenter', () =>
		fitImage( figure, img, light )
	);

	follow( figure, style, { area: img, photo: () => img } );
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

	document
		.querySelectorAll( '[class*="purplish-image-hover-"]' )
		.forEach( imageHover );

	// Covers that follow the mouse: the light goes in under the text.
	document
		.querySelectorAll(
			'.purplish-cover-hover-tilt, .purplish-cover-hover-glare'
		)
		.forEach( ( cover ) => {
			// Featured Category and Product hold their photo and text in a
			// wrapper, so the light goes in there, right after the photo.
			const wrapper = cover.querySelector(
				':scope > :is(.wc-block-featured-category__wrapper, .wc-block-featured-product__wrapper)'
			);
			const photo =
				wrapper?.querySelector(
					':scope > :is(.wc-block-featured-category__background-image, .wc-block-featured-product__background-image)'
				) ||
				wrapper?.querySelector( ':scope > .background-dim__overlay' );
			if ( photo ) {
				photo.after( imageLight( document, 'glare' ) );
			} else {
				cover.insertBefore(
					imageLight( document, 'glare' ),
					cover.querySelector( '.wp-block-cover__inner-container' )
				);
			}
			follow(
				cover,
				styleIn( cover.classList, 'purplish-cover-hover-' ),
				{
					photo: () => null,
				}
			);
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

const CARD_FOLLOW =
	'.purplish-cards-hovercard-tilt, .purplish-cards-hovercard-torch, .purplish-cards-hoverphoto-drift, .purplish-cards-hoverphoto-glare';

/**
 * Card hover looks that follow the mouse: the card gets the mouse
 * variables for its tilt and light, the photo its own for the drift and
 * glare. One listener on the page, so cards a collection loads later
 * follow too.
 */
function followCards() {
	const cardAt = ( node ) =>
		node.closest?.( `:is(${ CARD_FOLLOW }) .wc-block-product` );
	const photoIn = ( card ) =>
		card.querySelector( '.wc-block-components-product-image' );

	document.addEventListener(
		'pointermove',
		( event ) => {
			const card = cardAt( event.target );
			if ( ! card ) {
				return;
			}
			moveFrame( card, 'tilt', event, card, null );
			const photo = photoIn( card );
			if ( photo ) {
				moveFrame( photo, '', event, photo, null );
			}
		},
		{ passive: true }
	);
	document.addEventListener(
		'pointerout',
		( event ) => {
			const card = cardAt( event.target );
			if ( card && ! card.contains( event.relatedTarget ) ) {
				leaveFrame( card, 'tilt' );
				const photo = photoIn( card );
				if ( photo ) {
					leaveFrame( photo, '' );
				}
			}
		},
		{ passive: true }
	);
}

if ( canHover && document.querySelector( CARD_FOLLOW ) ) {
	followCards();
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

document
	.querySelectorAll( '.purplish-filters-chips' )
	.forEach( ( filters ) => matchChips( filters ) );

/**
 * Pops the mini-cart's count when it goes up. WooCommerce changes the
 * badge's text in place when something is added without a reload.
 *
 * @param {HTMLElement} cart The Mini-Cart block.
 */
function popCount( cart ) {
	const badge = cart.querySelector( '.wc-block-mini-cart__badge' );
	if ( ! badge ) {
		return;
	}
	let count = parseInt( badge.textContent, 10 ) || 0;
	new window.MutationObserver( () => {
		const now = parseInt( badge.textContent, 10 ) || 0;
		if ( now > count ) {
			cart.classList.remove( 'purplish-pop' );
			// eslint-disable-next-line no-unused-expressions
			cart.offsetWidth;
			cart.classList.add( 'purplish-pop' );
		}
		count = now;
	} ).observe( badge, {
		subtree: true,
		childList: true,
		characterData: true,
	} );
}

if ( ! reduced ) {
	document.querySelectorAll( '.purplish-minicart-pop' ).forEach( popCount );
}

const ADD_BUTTONS =
	'.wc-block-components-product-button__button, .add_to_cart_button, .single_add_to_cart_button';
const PRODUCT_PHOTOS =
	'.wc-block-product-gallery-large-image img, .woocommerce-product-gallery__image img, .wp-block-woocommerce-product-image img';

/**
 * The photo of the product a button adds: the card's photo, or the large
 * photo on a product page.
 *
 * @param {HTMLElement} button The add to cart button.
 * @return {HTMLImageElement|undefined} The photo, if one is on screen.
 */
function photoOf( button ) {
	const card = button.closest( '.wc-block-product, li.product' );
	return [
		...( card || document ).querySelectorAll(
			card ? 'img' : PRODUCT_PHOTOS
		),
	].find( ( photo ) => photo.getBoundingClientRect().width );
}

/**
 * Flies the product photo into the mini-cart once WooCommerce says the
 * product is in the cart, so a failed add flies nothing. Runway flies its
 * own photos, and a mini-cart whose drawer opens on add is covered by the
 * drawer, so neither flies here.
 */
function flyOnAdd() {
	const carts = [
		...document.querySelectorAll( '.purplish-minicart-fly' ),
	].filter(
		( cart ) =>
			! cart.hasAttribute(
				'data-wp-on-document--wc-blocks_added_to_cart---open-drawer'
			)
	);
	if ( ! carts.length ) {
		return;
	}
	let last = null;
	document.addEventListener(
		'click',
		( event ) => {
			const button = event.target.closest?.( ADD_BUTTONS );
			if ( button && ! button.closest( '.purplish-runway-fly-on' ) ) {
				last = { button, at: event.timeStamp };
			}
		},
		true
	);
	document.addEventListener( 'wc-blocks_added_to_cart', ( event ) => {
		const from = last;
		last = null;
		if ( ! from || event.timeStamp - from.at > 10000 ) {
			return;
		}
		const photo = photoOf( from.button );
		const target = carts
			.map( ( cart ) =>
				cart.querySelector( '.wc-block-mini-cart__button' )
			)
			.find( ( button ) => button && button.getClientRects().length );
		if ( photo && target ) {
			const way =
				'rtl' === window.getComputedStyle( target ).direction ? -1 : 1;
			flyToCart( photo, target, way, 'purplish-fly' );
		}
	} );
}

if ( ! reduced ) {
	flyOnAdd();
}

/**
 * Moves the glass button's highlight under the mouse. It drifts back to
 * the top when the mouse leaves.
 *
 * @param {HTMLElement} button The link or button.
 */
function glassHighlight( button ) {
	button.addEventListener( 'pointermove', ( event ) =>
		moveHighlight( button, event )
	);
	button.addEventListener( 'pointerleave', () => clearHighlight( button ) );
}

const STILL = 1000;

// WooCommerce takes these blocks' inner blocks out of the page and draws
// them again with React after this script runs, so a button inside them
// isn't there yet when the script looks.
const REDRAWN = '.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout';

/**
 * Turns every googly eye on the page toward the mouse, from one listener
 * and at most once a frame. The eyes blink when the mouse moves after a
 * still moment, never on a timer. The list is live, so eyes WooCommerce
 * draws later, such as in the empty cart, follow too.
 *
 * @param {HTMLCollection} eyes The eyes, a live list.
 */
function lookAt( eyes ) {
	let pointer = null;
	let frame = 0;
	let last = 0;

	const look = () => {
		frame = 0;
		Array.prototype.forEach.call( eyes, ( eye ) => {
			const box = eye.getBoundingClientRect();
			const dx = pointer.clientX - ( box.left + box.width / 2 );
			const dy = pointer.clientY - ( box.top + box.height / 2 );
			const away = Math.hypot( dx, dy ) || 1;
			const reach = Math.min( 1, away / ( box.width * 4 ) );
			eye.style.setProperty(
				'--purplish-ex',
				( ( dx / away ) * reach ).toFixed( 3 )
			);
			eye.style.setProperty(
				'--purplish-ey',
				( ( dy / away ) * reach ).toFixed( 3 )
			);
		} );
	};

	document.addEventListener(
		'pointermove',
		( event ) => {
			if ( 'touch' === event.pointerType || ! eyes.length ) {
				return;
			}
			if ( last && event.timeStamp - last > STILL ) {
				document
					.querySelectorAll( '.purplish-eyes' )
					.forEach( ( pair ) => {
						pair.classList.remove( 'purplish-blink' );
						// eslint-disable-next-line no-unused-expressions
						pair.offsetWidth;
						pair.classList.add( 'purplish-blink' );
					} );
			}
			last = event.timeStamp;
			pointer = event;
			if ( ! frame ) {
				frame = window.requestAnimationFrame( look );
			}
		},
		{ passive: true }
	);
}

if ( canHover ) {
	// WooCommerce draws the cart and checkout after this script runs, and
	// the drawer's buttons again when it fills, so the highlight starts on
	// the first pointer over each button.
	if (
		document.querySelector(
			'.purplish-button-glass, .purplish-cart-glass, .purplish-cards-hoverbutton-glass, .purplish-buybutton-glass, ' +
				REDRAWN
		)
	) {
		const ready = new WeakSet();
		document.addEventListener(
			'pointerover',
			( event ) => {
				const button = event.target.closest?.(
					'.purplish-button-glass .wp-block-button__link, .purplish-cart-glass .single_add_to_cart_button, .purplish-cards-hoverbutton-glass .wc-block-components-product-button__button, .purplish-buybutton-glass :is(.wc-block-cart__submit-button, .wc-block-components-checkout-place-order-button), .purplish-buybutton-glass.wc-block-mini-cart__footer-checkout'
				);
				if ( button && ! ready.has( button ) ) {
					ready.add( button );
					glassHighlight( button );
					moveHighlight( button, event );
				}
			},
			{ passive: true }
		);
	}

	const eyes = document.getElementsByClassName( 'purplish-eye' );
	if ( eyes.length || document.querySelector( REDRAWN ) ) {
		lookAt( eyes );
	}
}

if (
	window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches &&
	document.querySelector(
		'.purplish-cards-hover-momentum, .purplish-cards-hover-helix, .purplish-cards-hover-reel'
	)
) {
	throwStrips(
		':is(.purplish-cards-hover-momentum, .purplish-cards-hover-helix, .purplish-cards-hover-reel) .wc-block-product-template.is-horizontal',
		! reduced
	);
}

// Momentum, Helix and Reel pull cards toward the middle, which moves where
// the browser snaps, so they settle on the nearest card here instead. A
// reel opens on its middle card, so cards stack in on both sides.
if ( ! reduced && window.CSS?.supports( 'animation-timeline: view()' ) ) {
	settleStrips(
		':is(.purplish-cards-hover-momentum, .purplish-cards-hover-helix) .wc-block-product-template.is-horizontal',
		false
	);
	settleStrips(
		'.purplish-cards-hover-reel .wc-block-product-template.is-horizontal',
		true
	);
}

const ENTRANCES =
	'.purplish-heading-reveal-rise, .purplish-heading-reveal-focus, .purplish-group[class*="purplish-group-entrance-"], [class*="purplish-columns-entrance-"] > .wp-block-column, [class*="purplish-terms-entrance-"] .wp-block-term';

/**
 * Holds back each block below the view and lets it in once, when enough of
 * it shows: a fifth of it, or a fifth of the view for a block taller than
 * the view. Blocks already in or above the view at load stay as they are,
 * and nothing waits without this script. Blocks that come in together play
 * one after another.
 *
 * @param {HTMLElement[]} blocks The blocks, in page order.
 */
function enterOnScroll( blocks ) {
	const steps = Array.from( { length: 51 }, ( _, step ) => step / 50 );
	const watch = new window.IntersectionObserver(
		( entries ) => {
			let order = 0;
			entries.forEach( ( entry ) => {
				const seen = entry.intersectionRect.height;
				const enough =
					Math.min(
						entry.boundingClientRect.height,
						window.innerHeight
					) * 0.2;
				if ( ! entry.isIntersecting || seen < enough ) {
					return;
				}
				watch.unobserve( entry.target );
				entry.target.style.setProperty(
					'--purplish-delay',
					order * 0.12 + 's'
				);
				order++;
				entry.target.classList.replace(
					'purplish-waiting',
					'purplish-entered'
				);
			} );
		},
		{ threshold: steps }
	);

	blocks.forEach( ( block ) => {
		if ( block.getBoundingClientRect().top < window.innerHeight ) {
			return;
		}
		block.classList.add( 'purplish-waiting' );
		watch.observe( block );
	} );
}

if ( ! reduced && 'IntersectionObserver' in window ) {
	const blocks = [ ...document.querySelectorAll( ENTRANCES ) ];
	if ( blocks.length ) {
		enterOnScroll( blocks );
	}
}

/**
 * A condensing group inside a template part makes the part stick, and the
 * blocks above the group, like a note bar, would stick with it. The part
 * sticks that much higher instead, so they scroll away and the group stays
 * at the top. The group's own top margin, which the pill animates, is left
 * out. Without this script they stay too.
 *
 * @param {HTMLElement} group The condensing group.
 */
function condenseLead( group ) {
	const part = group.parentElement;
	const set = () =>
		part.style.setProperty(
			'--purplish-condense-lead',
			group.offsetTop -
				( parseFloat( window.getComputedStyle( group ).marginTop ) ||
					0 ) +
				'px'
		);
	set();
	if ( 'ResizeObserver' in window ) {
		new window.ResizeObserver( set ).observe( part );
	}
}

document
	.querySelectorAll( '.wp-block-template-part > .purplish-group-condense' )
	.forEach( condenseLead );
