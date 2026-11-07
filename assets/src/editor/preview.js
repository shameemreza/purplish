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
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect, useRef } from '@wordpress/element';

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
} from './preview-classes';

const MAP = window.purplishPreview || {};
const PAGE = window.purplishPage || {};
const PAGE_BLOCK = 'woocommerce/add-to-cart-with-options';
const FRAME = '.wc-block-product-gallery-large-image';
const GALLERY_HOVER = 'purplish-gallery-hover-';
const IMAGE_HOVER = 'purplish-image-hover-';
const MATCHING_CHIPS = 'purplish-filters-chips';
const CARD = '.wc-block-product';
const CARD_PHOTO = '.wc-block-components-product-image';
const CARD_FOLLOW = [
	'purplish-cards-hovercard-tilt',
	'purplish-cards-hovercard-torch',
	'purplish-cards-hoverphoto-drift',
	'purplish-cards-hoverphoto-glare',
];

/**
 * The block's element in the canvas, which may sit in the editor iframe.
 *
 * @param {string} clientId The block's client ID.
 * @return {HTMLElement|null} The element.
 */
function canvasElement( clientId ) {
	const id = 'block-' + clientId;
	const docs = [
		document,
		...[
			...document.querySelectorAll( 'iframe[name="editor-canvas"]' ),
		].map( ( frame ) => frame.contentDocument ),
	];
	for ( const doc of docs ) {
		const element = doc && doc.getElementById( id );
		if ( element ) {
			return element;
		}
	}
	return null;
}

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
 * them, and adds them again when the editor draws the button again.
 *
 * @param {string} clientId The block's client ID.
 * @param {string} button   Selector of the button.
 * @param {number} count    How many eyes, 0 for none.
 */
function useEyes( clientId, button, count ) {
	useEffect( () => {
		const block = button && count && canvasElement( clientId );
		if ( ! block ) {
			return;
		}
		const doc = block.ownerDocument;
		const place = () => {
			block.querySelectorAll( button ).forEach( ( target ) => {
				const pair = target.querySelector( ':scope > .purplish-eyes' );
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
		watch.observe( block, { childList: true, subtree: true } );
		return () => {
			watch.disconnect();
			block
				.querySelectorAll( button + ' > .purplish-eyes' )
				.forEach( ( pair ) => pair.remove() );
		};
	}, [ clientId, button, count ] );
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
	const buttons = ( wrapper ) =>
		selector ? [ ...wrapper.querySelectorAll( selector ) ] : [];
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
				const photo = event.target.closest( FRAME );
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
		useImageHover( props.clientId, styleIn( classes, IMAGE_HOVER ) );

		const hover = HOVER_BUTTONS[ props.name ] || '';
		if ( ! classes.length && ! hover ) {
			return <BlockListBlock { ...props } />;
		}

		const gallery = styleIn( classes, GALLERY_HOVER );
		const wrapperProps = props.wrapperProps || {};
		return (
			<BlockListBlock
				{ ...props }
				className={ [ props.className, ...classes ]
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
					style: { ...wrapperProps.style, ...colorStyle( saved ) },
				} }
			/>
		);
	},
	'withPurplishPreview'
);

addFilter( 'editor.BlockListBlock', 'purplish/preview', withPurplishPreview );
