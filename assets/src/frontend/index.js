/**
 * Follows the picked color on the product page, on block and classic
 * themes, and sets the colors on body.
 */
import './style.css';
import { nextColor } from './match';
import { createOrder, pieceColor, quantityOf } from './pieces';
import { isTiled } from '../effects/tiles';

const data = window.purplishData;
const body = document.body;
let current = data?.default ?? null;
// Where the next photo transition plays; null for the main gallery.
let transitionFrames = null;

/**
 * Sets a color set on body, or goes back to the theme colors.
 *
 * @param {Object|null}    set    The color set, or null.
 * @param {Element[]|null} frames Where the photo transition plays; null for the main gallery.
 */
function apply( set, frames = null ) {
	const same =
		set?.color === current?.color &&
		body.classList.contains( 'purplish-active' ) === !! set;
	current = set;
	if ( same ) {
		return;
	}

	if ( set ) {
		body.style.setProperty( '--purplish-accent', set.color );
		body.style.setProperty( '--purplish-on', set.on );
		body.style.setProperty( '--purplish-ink', set.ink );
		body.style.setProperty( '--purplish-tint', set.tint );
		body.classList.add( 'purplish-active' );
	} else {
		body.classList.remove( 'purplish-active' );
		[
			'--purplish-accent',
			'--purplish-on',
			'--purplish-ink',
			'--purplish-tint',
		].forEach( ( name ) => body.style.removeProperty( name ) );
	}

	// Listeners run during dispatch, so the frames stay off the 1.0 event detail.
	transitionFrames = frames;
	body.dispatchEvent(
		new CustomEvent( 'purplish:change', { detail: { set } } )
	);
	transitionFrames = null;

	/*
	 * Public event for theme code and analytics, since 1.1.0. The detail is
	 * a copy of the new color set (color, on, ink, tint), or null when the
	 * page goes back to the theme colors, so a listener can't change the
	 * page's color.
	 */
	document.dispatchEvent(
		new CustomEvent( 'purplish:colorchange', {
			detail: set ? { ...set } : null,
		} )
	);
}

/**
 * The picked values in a block or a classic form, read from the inputs
 * WooCommerce keeps up to date for the chips and the dropdowns.
 *
 * @param {Element} block The Add to Cart + Options block or the variations form.
 * @return {Object<string, string>} Values by `attribute_*` name.
 */
function readBlock( block ) {
	const picked = {};
	block
		.querySelectorAll(
			'input[type="hidden"][name^="attribute_"], select[name^="attribute_"]'
		)
		.forEach( ( field ) => {
			picked[ field.name ] = field.value;
		} );
	return picked;
}

/**
 * The picked options of an item in a bundle or composite. Their selects
 * carry the plugin's own names, and WooCommerce's in `data-attribute_name`.
 *
 * @param {Element|null} scope The item.
 * @return {Object<string, string>} Values by `attribute_*` name.
 */
function readItem( scope ) {
	const picked = {};
	scope
		?.querySelectorAll( 'select[data-attribute_name^="attribute_"]' )
		.forEach( ( field ) => {
			picked[ field.dataset.attribute_name ] = field.value;
		} );
	return picked;
}

/**
 * Follows one Add to Cart + Options block. WooCommerce updates its inputs
 * in a microtask after the event, so the read waits for a 0 ms timeout,
 * which runs after every microtask and, unlike an animation frame, never
 * waits for the page to paint.
 *
 * @param {Element} block The block.
 */
function followBlock( block ) {
	let timer = 0;
	const update = () => {
		window.clearTimeout( timer );
		timer = window.setTimeout( () =>
			apply(
				nextColor(
					data.variations,
					readBlock( block ),
					data.default,
					current
				)
			)
		);
	};

	[ 'click', 'change', 'keyup' ].forEach( ( type ) =>
		block.addEventListener( type, update )
	);

	// Autoselect and the reset link change the selection without a click.
	new window.MutationObserver( update ).observe( block, {
		subtree: true,
		attributes: true,
		attributeFilter: [ 'aria-checked', 'value' ],
	} );
}

/**
 * Follows a classic theme's variation form. WooCommerce sets its selects
 * with jQuery, which native listeners don't hear, so this listens to
 * WooCommerce's own jQuery events and reads the selects.
 * `found_variation` only fires once every attribute is picked, so
 * `woocommerce_variation_has_changed` covers a color picked before a size
 * and the Clear link.
 *
 * @param {Element} form The variations form.
 */
function followClassicForm( form ) {
	let timer = 0;
	window
		.jQuery( form )
		.on(
			'found_variation reset_data woocommerce_variation_has_changed',
			() => {
				window.clearTimeout( timer );
				timer = window.setTimeout( () =>
					apply(
						nextColor(
							data.variations,
							readBlock( form ),
							data.default,
							current
						)
					)
				);
			}
		);
}

const order = createOrder( data?.default ?? null );

// Clicks on these open a photo or another page; they change nothing.
const NOT_A_CHANGE =
	'.bundled_product_images, .composited_product_images, .mnm_image, a[href]:not([href="#"])';

/**
 * Follows one item of a bundle, composite or mix and match box, or one
 * grouped child. The item counts once the shopper changes it; options the
 * plugin picks on load don't move the page off its starting color. Each
 * change records the item's color in the order of changes, and the page
 * shows the item changed last.
 *
 * @param {Element}                              scope The item.
 * @param {string}                               key   The item's key in the order.
 * @param {function(Object|null): (Object|null)} read  Reads the item's color, given its color until now.
 * @param {function(): Element[]}                photo The item's own photo, where the transition plays.
 */
function followPiece( scope, key, read, photo ) {
	let touched = false;
	let timer = 0;
	let mine = null;

	const update = () => {
		window.clearTimeout( timer );
		timer = window.setTimeout( () => {
			if ( touched ) {
				mine = read( mine );
				apply( order.update( key, mine ), photo() );
			}
		} );
	};
	const touch = ( event ) => {
		if (
			event.isTrusted &&
			! (
				'click' === event.type && event.target.closest?.( NOT_A_CHANGE )
			)
		) {
			touched = true;
			update();
		}
	};

	[ 'click', 'change', 'input', 'keyup' ].forEach( ( type ) =>
		scope.addEventListener( type, touch )
	);
	// The plugins set selects and quantities with jQuery and WooCommerce's variation events.
	window
		.jQuery?.( scope )
		.on(
			'change found_variation reset_data woocommerce_variation_has_changed wc-composite-component-loaded',
			update
		);
	// A quantity button can set the field after the click.
	new window.MutationObserver( update ).observe( scope, {
		subtree: true,
		attributes: true,
		// WPC Product Bundles writes the picked product and quantity on the item.
		attributeFilter: [ 'value', 'checked', 'data-id', 'data-qty' ],
	} );
}

/**
 * The photos inside an element that match a selector.
 *
 * @param {Element|null} element  The element.
 * @param {string}       selector The photo selector.
 * @return {Element[]} The photos.
 */
function photosIn( element, selector ) {
	return element ? [ ...element.querySelectorAll( selector ) ] : [];
}

/**
 * Product Bundles: each item with options, keyed by its bundled item ID.
 *
 * @param {Object} pieces The page's item data.
 */
function followBundle( pieces ) {
	document
		.querySelectorAll( '.bundle_form .cart[data-bundled_item_id]' )
		.forEach( ( item ) => {
			const id = item.dataset.bundled_item_id;
			const product = pieces.products[ pieces.items?.[ id ] ];
			if ( ! product?.variations ) {
				return;
			}
			followPiece(
				item,
				'bundle-' + id,
				( mine ) => pieceColor( product, readItem( item ), mine ),
				() =>
					photosIn(
						item.closest( '.bundled_product' ),
						'.bundled_product_image'
					)
			);
		} );
}

/**
 * Composite Products: each component, with the product picked in it and
 * that product's options.
 *
 * @param {Object} pieces The page's item data.
 */
function followComposite( pieces ) {
	document
		.querySelectorAll(
			'.composite_form .composite_component[id^="component_"]'
		)
		.forEach( ( component ) => {
			const id = component.id.slice( 'component_'.length );
			let shown = '';
			followPiece(
				component,
				'component-' + id,
				( mine ) => {
					const field = [
						...component.querySelectorAll(
							`[name="wccp_component_selection[${ id }]"]`
						),
					].find(
						( input ) =>
							! [ 'radio', 'checkbox' ].includes( input.type ) ||
							input.checked
					);
					const picked = field?.value ?? '';
					// Another product: its options start over.
					const before = picked === shown ? mine : null;
					shown = picked;
					return pieceColor(
						pieces.products[ picked ],
						readItem(
							component.querySelector( '.summary_content' )
						),
						before,
						'' !== picked
					);
				},
				() => photosIn( component, '.composited_product_image' )
			);
		} );
}

/**
 * Mix and Match rows and grouped children: an item counts while its
 * quantity is above 0.
 *
 * @param {Object} pieces The page's item data.
 */
function followRows( pieces ) {
	document
		.querySelectorAll(
			'.mnm_form input[name^="mnm_quantity["], .wp-block-woocommerce-add-to-cart-with-options-grouped-product-selector input[name^="quantity["], form.grouped_form input[name^="quantity["]'
		)
		.forEach( ( field ) => {
			const id = field.name.slice( field.name.indexOf( '[' ) + 1, -1 );
			const product = pieces.products[ id ];
			const row = field.closest(
				'.mnm_item, .wp-block-woocommerce-add-to-cart-with-options-grouped-product-item, .woocommerce-grouped-product-list-item'
			);
			if ( ! product || ! row ) {
				return;
			}
			followPiece(
				row,
				'row-' + id,
				( mine ) =>
					pieceColor( product, null, mine, quantityOf( field ) > 0 ),
				() => photosIn( row, '.mnm_child_product_image' )
			);
		} );
}

/**
 * WPC Product Bundles: each item the shopper can change (a quantity field,
 * or options on a variable item). The item names its product, or the
 * picked variation, in `data-id` and its quantity in `data-qty`.
 *
 * @param {Object} pieces The page's item data.
 */
function followWpc( pieces ) {
	document
		.querySelectorAll( '.woosb-bundled .woosb-product[data-key]' )
		.forEach( ( item ) => {
			if (
				! item.querySelector( '.woosb-qty:not([disabled])' ) &&
				! item.classList.contains( 'woosb-product-type-variable' )
			) {
				return;
			}
			followPiece(
				item,
				'wpc-' + item.dataset.key,
				( mine ) =>
					pieceColor(
						pieces.products[ item.dataset.id ],
						null,
						mine,
						parseFloat( item.dataset.qty ) > 0
					),
				() => photosIn( item, '.woosb-thumb-ori' )
			);
		} );
}

const GALLERIES =
	'.wc-block-product-gallery-large-image__container, .woocommerce-product-gallery__wrapper';
let swap = false;

/**
 * The boxes a photo transition plays on: the block gallery's container, or
 * on classic themes the viewport FlexSlider puts around its sliding strip,
 * so moving or clipping it never shifts the slides. In a photo grid, each
 * tile, so every photo turns in its own tile and the gaps stay clear. The
 * hidden ones too: a variation's photo is still hidden when its color
 * arrives, and starts the animation once WooCommerce shows it.
 *
 * @param {Element} element A gallery container or a classic wrapper.
 * @return {Element[]} The frames.
 */
function framesOf( element ) {
	if ( isTiled( element ) ) {
		return [ ...element.children ];
	}
	const parent = element.parentElement;
	return [ parent?.classList.contains( 'flex-viewport' ) ? parent : element ];
}

/**
 * Plays the photo transition once on each color change. Two class names
 * take turns, so the animation restarts every time.
 *
 * @param {CustomEvent} event The `purplish:change` event.
 */
function playTransition( event ) {
	const on = [ ...body.classList ].some(
		( name ) =>
			name.startsWith( 'purplish-transition-' ) &&
			name !== 'purplish-transition-none'
	);
	if ( ! on ) {
		return;
	}

	swap = ! swap;
	const frames =
		transitionFrames ??
		[ ...document.querySelectorAll( GALLERIES ) ].flatMap( framesOf );
	frames.forEach( ( gallery ) => {
		// The accent is still on its way to the new color; the wash shows it now.
		gallery.style.setProperty(
			'--purplish-wash',
			event.detail.set?.color ?? 'transparent'
		);
		gallery.classList.remove( 'purplish-swap-a', 'purplish-swap-b' );
		gallery.classList.add( swap ? 'purplish-swap-a' : 'purplish-swap-b' );
		gallery.addEventListener(
			'animationend',
			() =>
				gallery.classList.remove(
					'purplish-swap-a',
					'purplish-swap-b'
				),
			{ once: true }
		);
	} );
}

const RISERS =
	'main .wp-block-post-title:not(.wc-block-product *), main .wp-block-woocommerce-product-price:not(.wc-block-product *), .summary .product_title, .summary > .price';
const TITLES =
	'main .wp-block-post-title:not(.wc-block-product *), .summary .product_title';
let turn = false;

/**
 * Replays an animation on elements by giving them the other of two class
 * names, `<name>-a` or `<name>-b`.
 *
 * @param {Iterable<Element>} elements The elements.
 * @param {string}            name     The class name before `-a` or `-b`.
 */
function replay( elements, name ) {
	[ ...elements ].forEach( ( element ) => {
		element.classList.remove( name + '-a', name + '-b' );
		element.classList.add( name + ( turn ? '-a' : '-b' ) );
	} );
}

/**
 * The page effects that play once per color change: the title and price
 * rise, the title focus and the mesh moving to its next place.
 */
function playPageEffects() {
	turn = ! turn;
	if ( body.classList.contains( 'purplish-rise' ) ) {
		replay( document.querySelectorAll( RISERS ), 'purplish-rise' );
	}
	if ( body.classList.contains( 'purplish-focus' ) ) {
		replay( document.querySelectorAll( TITLES ), 'purplish-focus' );
	}
	if ( body.classList.contains( 'purplish-mesh' ) ) {
		replay( [ body ], 'purplish-mesh' );
	}
}

/**
 * Gives each option chip a dot in its own color, from the page data.
 */
function paintDots() {
	if ( ! data.dots || ! body.classList.contains( 'purplish-dots' ) ) {
		return;
	}

	Object.entries( data.dots ).forEach( ( [ id, color ] ) => {
		const chip = document.getElementById( id );
		if ( chip ) {
			chip.style.setProperty( '--purplish-dot', color );
			chip.classList.add( 'purplish-has-dot' );
		}
	} );
}

if ( data?.variations ) {
	body.addEventListener( 'purplish:change', playTransition );
	body.addEventListener( 'purplish:change', playPageEffects );
	paintDots();

	if ( data.pieces ) {
		// A container has no options of its own; its items' pickers would
		// only send the page back to its starting color.
		( {
			bundle: followBundle,
			composite: followComposite,
			rows: followRows,
			wpc: followWpc,
		} )[ data.pieces.kind ]?.( data.pieces );
	} else {
		document
			.querySelectorAll(
				'.wp-block-woocommerce-add-to-cart-with-options'
			)
			.forEach( followBlock );

		if ( window.jQuery ) {
			document
				.querySelectorAll( 'form.variations_form' )
				.forEach( followClassicForm );
		}
	}
}
