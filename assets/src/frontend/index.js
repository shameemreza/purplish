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
		body.style.setProperty( '--purplish-tint', set.tint );
		body.classList.add( 'purplish-active' );
	} else {
		body.classList.remove( 'purplish-active' );
		[ '--purplish-accent', '--purplish-on', '--purplish-tint' ].forEach(
			( name ) => body.style.removeProperty( name )
		);
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

if ( data?.variations ) {
	document
		.querySelectorAll( '.wp-block-woocommerce-add-to-cart-with-options' )
		.forEach( followBlock );

	if ( window.jQuery ) {
		document
			.querySelectorAll( 'form.variations_form' )
			.forEach( followClassicForm );
	}
}
