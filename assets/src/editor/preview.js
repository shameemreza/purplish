/**
 * Shows the looks of blocks marked `preview` in the editor canvas: the same
 * wrapper classes and effect color as on the front end. The effect styles
 * load into the canvas from PHP; the front scripts don't, so the parts of
 * them that draw a hover look (the glass highlight, the photo hover styles,
 * the card hover looks that follow the mouse) are wired up here, and the googly eyes the server prints are added here.
 *
 * The 1.0 page options on Add to Cart + Options are body classes on the
 * front end, with the product's color. The editor puts the same classes on
 * the canvas body, with a sample color, since a template has no product.
 * A Group that takes the page color shows it there too, as it does on a
 * product page.
 *
 * Matching chips on Product Filters copy a chip's look with the same code
 * as the front script, since the editor draws both kinds of chips.
 *
 * A button block's link is its text box in the editor. The googly eyes go
 * inside it as on the front end; the text box reads no text from them, so
 * they're never saved into the button.
 *
 * Matching extras on Add to Cart + Options show under the block with the
 * picked products, or the store's best sellers when there are none.
 *
 * The Variation Selector shows a sample Size guide link.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { moveHighlight, clearHighlight } from '../shared/glass';
import {
	enterFrame,
	moveFrame,
	leaveFrame,
	imageLight,
	fitImage,
} from '../shared/hover';
import { matchChips } from '../shared/chips';
import {
	classesFor,
	colorStyle,
	eyesIn,
	EYES_BUTTONS,
	glassSelector,
	HOVER_BUTTONS,
	pageClasses,
	pageStyle,
	paperOf,
} from './preview-classes';
import { canvasElement } from './canvas';
import { useExtras } from './extras-preview';
import { useSizeGuide } from './size-guide-preview';
import { usePhotoSwatches } from './photo-swatches-preview';
import { useGalleryTiles } from './gallery-tiles-preview';
import { LAYOUT, isTiled } from '../effects/tiles';

const MAP = window.purplishPreview || {};
const PAGE = window.purplishPage || {};
const PAGE_BLOCK = 'woocommerce/add-to-cart-with-options';
const ATTRIBUTE_BLOCK =
	'woocommerce/add-to-cart-with-options-variation-selector-attribute';
const FRAME = '.wc-block-product-gallery-large-image';
// In a photo grid each tile is a frame: the block's photo and its copies.
const TILE =
	'.purplish-gallery-tile, .wc-block-editor-product-gallery-large-image > .wc-block-product-gallery-large-image__inner-blocks';
const GALLERY_HOVER = 'purplish-gallery-hover-';
const IMAGE_HOVER = 'purplish-image-hover-';
const MATCHING_CHIPS = 'purplish-filters-chips';
const RECEIPT = 'purplish-summary-receipt';
const SAMPLE = window.purplishMeter || {};
/**
 * Where each block's sample meter goes: inside the drawer's footer, past
 * its block wrapper, so it takes the footer's padding; the top of the
 * Cart's totals column; or the top of the Cart. The first match wins.
 */
const METERS = {
	'purplish-drawer-meter':
		'.wc-block-mini-cart__footer, .wp-block-woocommerce-mini-cart-footer-block',
	'purplish-cartpage-meterplace-totals':
		'.wc-block-cart__sidebar, .wp-block-woocommerce-cart-totals-block',
	'purplish-cartpage-meter': '',
};
const STICKY = 'purplish-cart-sticky';
const TOTAL = 'purplish-buybutton-total';
const CART_TOTAL =
	'.wc-block-components-totals-footer-item .wc-block-components-totals-item__value';
const CARD = '.wc-block-product';
const CARD_PHOTO = '.wc-block-components-product-image';
const CARD_FOLLOW = [
	'purplish-cards-hovercard-tilt',
	'purplish-cards-hovercard-torch',
	'purplish-cards-hoverphoto-drift',
	'purplish-cards-hoverphoto-glare',
];

/**
 * The page looks each Add to Cart + Options block asks for, per canvas
 * body, so two blocks on one template don't undo each other.
 */
const pageLooks = new WeakMap();

/**
 * Puts the classes and colors every block asks for on the canvas body,
 * and takes off the ones no block asks for any more.
 *
 * @param {HTMLElement} body The canvas body.
 */
function paintBody( body ) {
	const looks = pageLooks.get( body );
	const wanted = new Set();
	looks.blocks.forEach( ( classes ) =>
		classes.forEach( ( name ) => wanted.add( name ) )
	);
	looks.applied.forEach( ( name ) => {
		if ( ! wanted.has( name ) ) {
			body.classList.remove( name );
		}
	} );
	wanted.forEach( ( name ) => body.classList.add( name ) );
	looks.applied = wanted;

	Object.entries( pageStyle( PAGE ) ).forEach( ( [ name, value ] ) => {
		if ( wanted.size ) {
			body.style.setProperty( name, value );
		} else {
			body.style.removeProperty( name );
		}
	} );
}

/**
 * Shows the block's page options on the canvas body while it's on the page.
 *
 * @param {string}   clientId The block's client ID.
 * @param {string[]} classes  The body classes it asks for.
 */
function usePageLook( clientId, classes ) {
	const key = classes.join( ' ' );
	useEffect( () => {
		const body = key && canvasElement( clientId )?.ownerDocument.body;
		if ( ! body ) {
			return;
		}
		if ( ! pageLooks.has( body ) ) {
			pageLooks.set( body, { blocks: new Map(), applied: new Set() } );
		}
		pageLooks.get( body ).blocks.set( clientId, key.split( ' ' ) );
		paintBody( body );
		return () => {
			pageLooks.get( body ).blocks.delete( clientId );
			paintBody( body );
		};
	}, [ clientId, key ] );
}

/**
 * Adds the googly eyes inside the block's button, as the server prints
 * them, and adds them again when the editor draws the button again. Like
 * the sticky bar sample, it looks the block up again on each change on the
 * canvas, since the editor swaps the block's element while a template loads.
 *
 * @param {string} clientId The block's client ID.
 * @param {string} button   Selector of the button.
 * @param {number} count    How many eyes, 0 for none.
 */
function useEyes( clientId, button, count ) {
	useEffect( () => {
		const first = button && count && canvasElement( clientId );
		if ( ! first ) {
			return;
		}
		const doc = first.ownerDocument;
		const find = () => doc.getElementById( 'block-' + clientId );
		const place = () => {
			find()
				?.querySelectorAll( button )
				.forEach( ( target ) => {
					const pair = target.querySelector(
						':scope > .purplish-eyes'
					);
					if ( pair && pair.children.length === count ) {
						return;
					}
					pair?.remove();
					const eyes = doc.createElement( 'span' );
					eyes.className = 'purplish-eyes';
					eyes.setAttribute( 'aria-hidden', 'true' );
					eyes.setAttribute( 'contenteditable', 'false' );
					for ( let i = 0; i < count; i++ ) {
						const eye = doc.createElement( 'span' );
						eye.className = 'purplish-eye';
						eyes.appendChild( eye );
					}
					target.prepend( eyes );
				} );
		};
		place();
		const watch = new doc.defaultView.MutationObserver( place );
		watch.observe( doc.body, { childList: true, subtree: true } );
		return () => {
			watch.disconnect();
			find()
				?.querySelectorAll( button + ' > .purplish-eyes' )
				.forEach( ( pair ) => pair.remove() );
		};
	}, [ clientId, button, count ] );
}

/**
 * Adds a sample free shipping meter where the store prints the real one,
 * and adds it again when the editor draws that part again. The editor has
 * no cart, so the sample sits at 60% of the store's minimum.
 *
 * @param {string} clientId The block's client ID.
 * @param {string} where    Selector of the part it goes into, '' for the block itself, or null for none.
 * @param {string} left     The block's own amount-left sentence, with `%s`, or ''.
 */
function useMeter( clientId, where, left ) {
	useEffect( () => {
		const block = where !== null && canvasElement( clientId );
		if ( ! block ) {
			return;
		}
		const doc = block.ownerDocument;
		const place = () => {
			const target = where
				? where
						.split( ', ' )
						.map( ( part ) => block.querySelector( part ) )
						.find( Boolean )
				: block;
			if (
				! target ||
				target.querySelector( ':scope > .purplish-meter' )
			) {
				return;
			}
			const meter = doc.createElement( 'div' );
			meter.className =
				'purplish-meter' + ( where ? '' : ' purplish-meter-page' );
			meter.setAttribute( 'contenteditable', 'false' );
			meter.style.setProperty(
				'--purplish-meter',
				String( SAMPLE.share || 0.6 )
			);
			const text = doc.createElement( 'p' );
			text.className = 'purplish-meter-text';
			const [ before, after = '' ] = (
				left ||
				SAMPLE.left ||
				/* translators: %s: amount left to spend, such as $12.00. */
				__( 'Spend %s more for free shipping.', 'purplish' )
			).split( '%s' );
			const amount = doc.createElement( 'strong' );
			amount.textContent = SAMPLE.amount || '';
			text.append( before, amount, after );
			const bar = doc.createElement( 'div' );
			bar.className = 'purplish-meter-bar';
			bar.setAttribute( 'aria-hidden', 'true' );
			bar.appendChild( doc.createElement( 'span' ) );
			meter.append( text, bar );
			target.prepend( meter );
		};
		place();
		const watch = new doc.defaultView.MutationObserver( place );
		watch.observe( block, { childList: true, subtree: true } );
		return () => {
			watch.disconnect();
			block
				.querySelectorAll( '.purplish-meter' )
				.forEach( ( meter ) => meter.remove() );
		};
	}, [ clientId, where, left ] );
}

/**
 * Adds a sample of the sticky Add to Cart bar under the block, with the
 * canvas's own title, price and button. While the template loads, the
 * editor swaps the block's element for a new one, so each change on the
 * canvas looks the block up again. On the front end the bar sits at the
 * foot of a phone screen.
 *
 * @param {string}   clientId The block's client ID.
 * @param {string[]} classes  The block's Purplish classes, or none without the bar.
 */
function useBar( clientId, classes ) {
	const key = classes.join( ' ' );
	useEffect( () => {
		const first = key && canvasElement( clientId );
		if ( ! first ) {
			return;
		}
		const doc = first.ownerDocument;
		const find = () => doc.getElementById( 'block-' + clientId );
		const place = () => {
			const block = find();
			const button = block?.querySelector( EYES_BUTTONS.cart );
			if ( ! button || block.querySelector( ':scope > .purplish-bar' ) ) {
				return;
			}
			const bar = doc.createElement( 'div' );
			bar.className = [
				'purplish-bar',
				'is-sample',
				...key.split( ' ' ).filter( ( name ) => name !== STICKY ),
			].join( ' ' );
			bar.setAttribute( 'contenteditable', 'false' );
			bar.inert = true;
			const text = doc.createElement( 'div' );
			text.className = 'purplish-bar-text';
			const name = doc.createElement( 'p' );
			name.className = 'purplish-bar-name';
			name.textContent =
				doc.querySelector( '.wp-block-post-title' )?.textContent ||
				__( 'Product name', 'purplish' );
			const price = doc.createElement( 'div' );
			price.className = 'purplish-bar-price';
			const shown = doc.querySelector(
				'.wp-block-woocommerce-product-price .wc-block-components-product-price'
			);
			if ( shown ) {
				price.append( ...shown.cloneNode( true ).childNodes );
			}
			text.append( name, price );
			const copy = button.cloneNode( true );
			copy.classList.add( 'single_add_to_cart_button' );
			bar.append( text, copy );
			block.append( bar );
		};
		place();
		const watch = new doc.defaultView.MutationObserver( place );
		watch.observe( doc.body, { childList: true, subtree: true } );
		return () => {
			watch.disconnect();
			find()
				?.querySelectorAll( ':scope > .purplish-bar' )
				.forEach( ( bar ) => bar.remove() );
		};
	}, [ clientId, key ] );
}

/**
 * Shows the Cart's sample total on Proceed to Checkout, which the front end
 * shows only while WooCommerce pins the button on phones. Like the sticky
 * bar sample, it looks the block up again on each change on the canvas.
 *
 * @param {string}  clientId The block's client ID.
 * @param {boolean} on       Whether Total in the sticky bar is on.
 */
function useTotal( clientId, on ) {
	useEffect( () => {
		const first = on && canvasElement( clientId );
		if ( ! first ) {
			return;
		}
		const doc = first.ownerDocument;
		const find = () => doc.getElementById( 'block-' + clientId );
		const paint = () => {
			const block = find();
			const total = doc.querySelector( CART_TOTAL )?.textContent.trim();
			const text = total ? JSON.stringify( '· ' + total ) : '';
			if (
				! block ||
				block.style.getPropertyValue( '--purplish-total' ) === text
			) {
				return;
			}
			if ( text ) {
				block.style.setProperty( '--purplish-total', text );
			} else {
				block.style.removeProperty( '--purplish-total' );
			}
		};
		paint();
		const watch = new doc.defaultView.MutationObserver( paint );
		watch.observe( doc.body, {
			childList: true,
			subtree: true,
			characterData: true,
		} );
		return () => {
			watch.disconnect();
			find()?.style.removeProperty( '--purplish-total' );
		};
	}, [ clientId, on ] );
}

/**
 * Gives the active filter chips the look of a selected chip while the
 * block has matching chips on.
 *
 * @param {string}  clientId The block's client ID.
 * @param {boolean} on       Whether matching chips is on.
 */
function useChips( clientId, on ) {
	useEffect( () => {
		const block = on && canvasElement( clientId );
		return block ? matchChips( block ) : undefined;
	}, [ clientId, on ] );
}

/**
 * Plays an image block's hover style on its photo, as the front script
 * does, with the light layer for the tilt, glare and loupe. The editor
 * draws the photo without pointer events, so it never matches `:hover`:
 * the photo is found by its box and marked `purplish-hover` while the
 * mouse is over it, and the hover styles accept that class too.
 *
 * @param {string} clientId The block's client ID.
 * @param {string} style    The hover style, or '' for none.
 */
function useImageHover( clientId, style ) {
	useEffect( () => {
		const figure = style && canvasElement( clientId );
		if ( ! figure ) {
			return;
		}
		const light = imageLight( figure.ownerDocument, style );
		let photo = null;

		const leave = () => {
			if ( photo ) {
				photo.classList.remove( 'purplish-hover' );
				leaveFrame( figure, style );
				photo = null;
			}
		};
		// The editor can swap the photo or redraw its classes, so both are
		// checked on each move.
		const move = ( event ) => {
			const img = figure.querySelector( 'img' );
			if ( ! img || ! under( img, event ) ) {
				leave();
				return;
			}
			if ( img !== photo ) {
				leave();
				photo = img;
				fitImage( figure, img, light );
				enterFrame( figure, style, img );
			}
			img.classList.add( 'purplish-hover' );
			moveFrame( figure, style, event, img, img );
		};

		figure.addEventListener( 'pointermove', move );
		figure.addEventListener( 'pointerleave', leave );
		return () => {
			figure.removeEventListener( 'pointermove', move );
			figure.removeEventListener( 'pointerleave', leave );
			leave();
			light?.remove();
			// The effect color on the same element belongs to the editor.
			[ ...figure.style ]
				.filter(
					( prop ) =>
						prop.startsWith( '--purplish-' ) &&
						! prop.startsWith( '--purplish-effect' )
				)
				.forEach( ( prop ) => figure.style.removeProperty( prop ) );
		};
	}, [ clientId, style ] );
}

/**
 * Whether the pointer is over the element's box.
 *
 * @param {HTMLElement}  element The element.
 * @param {PointerEvent} event   The pointer event.
 * @return {boolean} Whether it's inside.
 */
function under( element, event ) {
	const box = element.getBoundingClientRect();
	return (
		event.clientX >= box.left &&
		event.clientX <= box.right &&
		event.clientY >= box.top &&
		event.clientY <= box.bottom
	);
}

/**
 * Pointer handlers for the hover looks a script draws on the front end.
 * Buttons are found by their box, since the editor can draw them inert.
 *
 * @param {Object}  options         What the block has.
 * @param {string}  options.glass   Selector of glass buttons, or ''.
 * @param {string}  options.hover   Selector of buttons that get `purplish-hover`, or ''.
 * @param {string}  options.gallery The gallery hover style, or ''.
 * @param {Object}  options.frame   Ref holding the photo frame under the mouse.
 * @param {boolean} options.cards   Whether product cards follow the mouse.
 * @param {Object}  options.card    Ref holding the product card under the mouse.
 * @return {Object} Props for the block wrapper, empty when there's nothing to follow.
 */
function pointerProps( { glass, hover, gallery, frame, cards, card } ) {
	if ( ! glass && ! hover && ! gallery && ! cards ) {
		return {};
	}
	const selector = [ glass, hover ].filter( Boolean ).join( ', ' );
	const buttons = ( wrapper ) => {
		if ( ! selector ) {
			return [];
		}
		const inside = [ ...wrapper.querySelectorAll( selector ) ];
		return wrapper.matches( selector ) ? [ wrapper, ...inside ] : inside;
	};
	const leavePhoto = () => {
		if ( frame.current ) {
			leaveFrame( frame.current, gallery );
			frame.current = null;
		}
	};
	// The card takes the tilt's variables, its photo the drift's and glare's.
	const followCard = ( target, event ) => {
		if ( card.current && card.current !== target ) {
			leaveFrame( card.current, 'tilt' );
			const photo = card.current.querySelector( CARD_PHOTO );
			if ( photo ) {
				leaveFrame( photo, '' );
			}
		}
		card.current = target;
		if ( target && event ) {
			moveFrame( target, 'tilt', event, target, null );
			const photo = target.querySelector( CARD_PHOTO );
			if ( photo ) {
				moveFrame( photo, '', event, photo, null );
			}
		}
	};

	return {
		onPointerMove: ( event ) => {
			buttons( event.currentTarget ).forEach( ( button ) => {
				const over = under( button, event );
				if ( hover && button.matches( hover ) ) {
					button.classList.toggle( 'purplish-hover', over );
				}
				if ( glass && button.matches( glass ) ) {
					if ( over ) {
						moveHighlight( button, event );
					} else {
						clearHighlight( button );
					}
				}
			} );

			if ( cards ) {
				followCard( event.target.closest( CARD ), event );
			}

			if ( gallery ) {
				const photo = event.target.closest(
					isTiled( event.target ) ? TILE : FRAME
				);
				if ( photo !== frame.current ) {
					leavePhoto();
					if ( photo ) {
						frame.current = photo;
						enterFrame(
							photo,
							gallery,
							photo.querySelector( 'img' )
						);
					}
				}
				if ( photo ) {
					moveFrame(
						photo,
						gallery,
						event,
						photo,
						photo.querySelector( 'img' )
					);
				}
			}
		},
		onPointerLeave: ( event ) => {
			buttons( event.currentTarget ).forEach( ( button ) => {
				button.classList.remove( 'purplish-hover' );
				clearHighlight( button );
			} );
			leavePhoto();
			if ( cards ) {
				followCard( null );
			}
		},
	};
}

/**
 * The style a class with this prefix names, as `purplish-image-hover-tilt`.
 *
 * @param {string[]} classes The block's Purplish classes.
 * @param {string}   prefix  The class prefix.
 * @return {string} The style, or '' when no class has the prefix.
 */
function styleIn( classes, prefix ) {
	const found = classes.find( ( name ) => name.startsWith( prefix ) );
	return found ? found.slice( prefix.length ) : '';
}

const withPurplishPreview = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const block = MAP[ props.name ];
		const saved = props.attributes && props.attributes.purplish;
		const classes = block ? classesFor( block, saved ) : [];
		const frame = useRef( null );
		const card = useRef( null );

		usePageLook(
			props.clientId,
			props.name === PAGE_BLOCK ? pageClasses( PAGE, saved ) : []
		);
		useEyes(
			props.clientId,
			block ? EYES_BUTTONS[ block.prefix ] : '',
			block ? eyesIn( classes, block.prefix ) : 0
		);

		useChips( props.clientId, classes.includes( MATCHING_CHIPS ) );
		const meter = Object.keys( METERS ).find( ( name ) =>
			classes.includes( name )
		);
		useMeter(
			props.clientId,
			meter ? METERS[ meter ] : null,
			( saved &&
				'string' === typeof saved.metertext &&
				saved.metertext ) ||
				''
		);
		useImageHover( props.clientId, styleIn( classes, IMAGE_HOVER ) );
		useBar( props.clientId, classes.includes( STICKY ) ? classes : [] );
		useTotal( props.clientId, classes.includes( TOTAL ) );
		useExtras(
			props.clientId,
			props.name === PAGE_BLOCK && saved && true === saved.extras
				? saved
				: null
		);

		useSizeGuide(
			props.clientId,
			props.name === ATTRIBUTE_BLOCK && false !== saved?.sizeguide
				? ( 'string' === typeof saved?.sizelabel && saved.sizelabel ) ||
						''
				: null
		);

		usePhotoSwatches(
			props.clientId,
			props.name === ATTRIBUTE_BLOCK && 'photos' === saved?.swatch
				? {
						attribute:
							( 'string' === typeof saved.swatchattr &&
								saved.swatchattr ) ||
							'',
						name: false !== saved.swatchname,
					}
				: null
		);

		useGalleryTiles(
			props.clientId,
			classes.some( ( name ) => name.startsWith( LAYOUT ) )
		);

		const hover = HOVER_BUTTONS[ props.name ] || '';
		if ( ! classes.length && ! hover ) {
			return <BlockListBlock { ...props } />;
		}

		const gallery = styleIn( classes, GALLERY_HOVER );
		const wrapperProps = props.wrapperProps || {};
		const paper = classes.includes( RECEIPT )
			? paperOf( props.attributes )
			: '';
		return (
			<BlockListBlock
				{ ...props }
				className={ [
					props.className,
					...classes,
					paper && 'purplish-summary-paper',
				]
					.filter( Boolean )
					.join( ' ' ) }
				wrapperProps={ {
					...wrapperProps,
					...pointerProps( {
						glass: glassSelector( classes ),
						hover,
						gallery,
						frame,
						cards: CARD_FOLLOW.some( ( name ) =>
							classes.includes( name )
						),
						card,
					} ),
					style: {
						...wrapperProps.style,
						...colorStyle( saved ),
						...( paper ? { '--purplish-paper': paper } : {} ),
					},
				} }
			/>
		);
	},
	'withPurplishPreview'
);

addFilter( 'editor.BlockListBlock', 'purplish/preview', withPurplishPreview );
