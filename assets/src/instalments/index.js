/**
 * The instalment line on a variable product's page follows the picked
 * variation: each variation's text comes with the page, so nothing is
 * fetched. Clearing the picks puts back the "from" text.
 */
import './style.css';
import { pickKey } from './pick';

/**
 * The picked values in the Add to Cart + Options block or a classic form.
 *
 * @param {Element} form The block or the variations form.
 * @return {Object<string, string>} Values by `attribute_*` name.
 */
function readPicks( form ) {
	const picked = {};
	form.querySelectorAll(
		'input[type="hidden"][name^="attribute_"], select[name^="attribute_"]'
	).forEach( ( field ) => {
		picked[ field.name ] = field.value;
	} );
	return picked;
}

/**
 * Starts one line.
 *
 * @param {HTMLElement} line The `.purplish-instalments` element.
 */
function follow( line ) {
	let data;
	try {
		data = JSON.parse( line.dataset.purplishInstalments );
	} catch {
		return;
	}
	const text = line.querySelector( '.purplish-instalments__text' );
	const start = text.innerHTML;
	const forms = document.querySelectorAll(
		'.wp-block-woocommerce-add-to-cart-with-options, form.variations_form'
	);
	let timer = 0;

	const update = ( form ) => {
		const key = pickKey( data.v, readPicks( form ) );
		if ( '' === key ) {
			line.hidden = true;
			return;
		}
		line.hidden = false;
		const html = null === key ? start : data.l[ key ];
		if ( html !== undefined && text.innerHTML !== html ) {
			text.innerHTML = html;
		}
	};

	forms.forEach( ( form ) => {
		// WooCommerce updates the block's inputs in a microtask, and sets a
		// classic form's selects with jQuery, so the read waits a tick.
		const later = () => {
			window.clearTimeout( timer );
			timer = window.setTimeout( () => update( form ) );
		};
		[ 'click', 'change', 'keyup' ].forEach( ( type ) =>
			form.addEventListener( type, later )
		);
		new window.MutationObserver( later ).observe( form, {
			subtree: true,
			attributes: true,
			attributeFilter: [ 'aria-checked', 'value' ],
		} );
		window
			.jQuery?.( form )
			.on(
				'found_variation reset_data woocommerce_variation_has_changed',
				later
			);
		later();
	} );
}

document
	.querySelectorAll( '.purplish-instalments[data-purplish-instalments]' )
	.forEach( follow );
