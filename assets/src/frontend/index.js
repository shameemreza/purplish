/**
 * Follows the picked color on the product page, on block and classic
 * themes, and sets the colors on body.
 */
import './style.css';
import { nextColor } from './match';

const data = window.purplishData;
const body = document.body;
let current = data?.default ?? null;

/**
 * Sets a color set on body, or goes back to the theme colors.
 *
 * @param {Object|null} set The color set, or null.
 */
function apply( set ) {
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

	body.dispatchEvent(
		new CustomEvent( 'purplish:change', { detail: { set } } )
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

const GALLERIES =
	'.wc-block-product-gallery-large-image__container, .woocommerce-product-gallery__wrapper';
let swap = false;

/**
 * The box a photo transition plays on: the block gallery's container, or
 * on classic themes the viewport FlexSlider puts around its sliding strip,
 * so moving or clipping it never shifts the slides.
 *
 * @param {Element} element A gallery container or a classic wrapper.
 * @return {Element} The frame.
 */
function frameOf( element ) {
	const parent = element.parentElement;
	return parent?.classList.contains( 'flex-viewport' ) ? parent : element;
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
	document.querySelectorAll( GALLERIES ).forEach( ( element ) => {
		const gallery = frameOf( element );
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
	'main .wp-block-post-title, main .wp-block-woocommerce-product-price, .summary .product_title, .summary > .price';
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

	document
		.querySelectorAll( '.wp-block-woocommerce-add-to-cart-with-options' )
		.forEach( followBlock );

	if ( window.jQuery ) {
		document
			.querySelectorAll( 'form.variations_form' )
			.forEach( followClassicForm );
	}
}
