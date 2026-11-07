/**
 * Shows the looks of blocks marked `preview` in the editor canvas: the same
 * wrapper classes and effect color as on the front end. The effect styles
 * load into the canvas from PHP; the front scripts don't, so the parts of
 * them that draw a hover look (the glass highlight, the photo hover styles)
 * are wired up here, and the googly eyes the server prints are added here.
 *
 * The 1.0 page options on Add to Cart + Options are body classes on the
 * front end, with the product's color. The editor puts the same classes on
 * the canvas body, with a sample color, since a template has no product.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect, useRef } from '@wordpress/element';

import { moveHighlight, clearHighlight } from '../shared/glass';
import { enterFrame, moveFrame, leaveFrame } from '../shared/hover';
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
 * @param {Object} options         What the block has.
 * @param {string} options.glass   Selector of glass buttons, or ''.
 * @param {string} options.hover   Selector of buttons that get `purplish-hover`, or ''.
 * @param {string} options.gallery The gallery hover style, or ''.
 * @param {Object} options.frame   Ref holding the photo frame under the mouse.
 * @return {Object} Props for the block wrapper, empty when there's nothing to follow.
 */
function pointerProps( { glass, hover, gallery, frame } ) {
	if ( ! glass && ! hover && ! gallery ) {
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
		},
	};
}

const withPurplishPreview = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const block = MAP[ props.name ];
		const saved = props.attributes && props.attributes.purplish;
		const classes = block ? classesFor( block, saved ) : [];
		const frame = useRef( null );

		usePageLook(
			props.clientId,
			props.name === PAGE_BLOCK ? pageClasses( PAGE, saved ) : []
		);
		useEyes(
			props.clientId,
			block ? EYES_BUTTONS[ block.prefix ] : '',
			block ? eyesIn( classes, block.prefix ) : 0
		);

		const hover = HOVER_BUTTONS[ props.name ] || '';
		if ( ! classes.length && ! hover ) {
			return <BlockListBlock { ...props } />;
		}

		const style = classes.find( ( name ) =>
			name.startsWith( GALLERY_HOVER )
		);
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
						gallery: style
							? style.slice( GALLERY_HOVER.length )
							: '',
						frame,
					} ),
					style: { ...wrapperProps.style, ...colorStyle( saved ) },
				} }
			/>
		);
	},
	'withPurplishPreview'
);

addFilter( 'editor.BlockListBlock', 'purplish/preview', withPurplishPreview );
