/**
 * The trade-in choice on the product page. The server prints Yes/No and
 * one device select grouped by brand, which posts with the form. This
 * swaps that select for Brand, Model and Storage, shows the estimate, and
 * stops Add to Cart when Yes has no device. The block adds to the cart
 * through the Store API, which only sends the product, so the choice is
 * added to that request; the server looks the device up again.
 */
import './style.css';
import {
	readDevices,
	choices,
	findDevice,
	estimateText,
	netText,
	pickPrice,
	addFields,
} from './devices';

const FIELDS = [];
const STORE = /wc\/store\/v1\/(batch|cart\/add-item)/;

/**
 * Whether Yes is picked.
 *
 * @param {Element} set The fieldset.
 * @return {boolean} True for Yes.
 */
const isYes = ( set ) =>
	'yes' === set.querySelector( 'input[type="radio"]:checked' )?.value;

/**
 * Replaces a select's options after its first, empty one.
 *
 * @param {HTMLSelectElement} select Select.
 * @param {string[]}          values Values, shown as they are.
 */
function setOptions( select, values ) {
	select.replaceChildren(
		select.options[ 0 ],
		...values.map( ( value ) => new window.Option( value, value ) )
	);
	select.disabled = ! values.length;
}

/**
 * Starts one trade-in field.
 *
 * @param {HTMLFieldSetElement} set The `.purplish-trade-in` fieldset.
 */
function start( set ) {
	let data;
	try {
		data = JSON.parse( set.dataset.purplishTradeIn );
	} catch {
		return;
	}
	const device = set.querySelector( 'select[name]' );
	const template = set.querySelector( 'template' );
	const error = set.querySelector( '.purplish-trade-in__error' );
	const estimate = set.querySelector( '.purplish-trade-in__estimate' );
	const net = set.querySelector( '.purplish-trade-in__net' );
	const terms = set.querySelector( 'input[type="checkbox"]' );
	const form = set.closest( 'form' );
	const devices = readDevices( device );
	const field = { set, device, terms, ids: data.i };
	FIELDS.push( field );

	const picks = {};
	if ( template ) {
		device.parentNode.after( template.content.cloneNode( true ) );
		device.parentNode.hidden = true;
		set.querySelectorAll( '[data-pick]' ).forEach( ( select ) => {
			picks[ select.dataset.pick ] = select;
		} );
		setOptions( picks.brand, choices( devices, 'brand', {} ) );
	}

	const update = () => {
		const found = devices.find( ( d ) => d.key === device.value );
		estimate.textContent = found ? estimateText( found, data ) : '';
		if ( net ) {
			const picked = {};
			form?.querySelectorAll(
				'input[type="hidden"][name^="attribute_"], select[name^="attribute_"]'
			).forEach( ( input ) => {
				picked[ input.name ] = input.value;
			} );
			const at = pickPrice( data.v, picked, data.p, data.f );
			net.textContent = found
				? netText( at.price, found, at.from, data )
				: '';
		}
	};

	const clearError = () => {
		error.hidden = true;
		error.textContent = '';
		set.querySelectorAll( '[aria-invalid]' ).forEach( ( el ) => {
			el.removeAttribute( 'aria-invalid' );
			el.removeAttribute( 'aria-describedby' );
		} );
	};

	field.problem = () => {
		if ( ! device.value ) {
			const empty = [ picks.brand, picks.model, picks.storage ].find(
				( select ) => select && ! select.value && ! select.disabled
			);
			return [ data.t.device, empty || picks.brand || device ];
		}
		return terms && ! terms.checked ? [ data.t.terms, terms ] : null;
	};
	field.show = ( [ message, target ] ) => {
		clearError();
		error.textContent = message;
		error.hidden = false;
		target.setAttribute( 'aria-invalid', 'true' );
		target.setAttribute( 'aria-describedby', error.id );
		target.focus();
	};

	set.addEventListener( 'change', ( event ) => {
		clearError();
		const pick = event.target.dataset?.pick;
		if ( pick ) {
			const picked = {
				brand: picks.brand.value,
				model: picks.model.value,
				storage: picks.storage.value,
			};
			if ( 'brand' === pick ) {
				setOptions(
					picks.model,
					picked.brand ? choices( devices, 'model', picked ) : []
				);
				picked.model = '';
			}
			if ( 'storage' !== pick ) {
				const sizes = picked.model
					? choices( devices, 'storage', picked )
					: [];
				setOptions( picks.storage, sizes );
				picked.storage = 1 === sizes.length ? sizes[ 0 ] : '';
				picks.storage.value = picked.storage;
				// A device without storage needs no third pick.
				picks.storage.parentNode.hidden =
					1 === sizes.length && '' === sizes[ 0 ];
			}
			device.value = findDevice( devices, picked )?.key || '';
		}
		update();
	} );

	if ( net && form ) {
		let timer = 0;
		const later = () => {
			window.clearTimeout( timer );
			timer = window.setTimeout( update );
		};
		[ 'click', 'change', 'keyup' ].forEach( ( type ) =>
			form.addEventListener( type, later )
		);
		// The block sets its attribute fields from its store, not events.
		new window.MutationObserver( later ).observe( form, {
			subtree: true,
			attributes: true,
			attributeFilter: [ 'aria-checked', 'value' ],
		} );
	}
	update();
}

document.querySelectorAll( '.purplish-trade-in' ).forEach( start );

if ( FIELDS.length ) {
	// Runs before WooCommerce's own submit handler on the form.
	document.addEventListener(
		'submit',
		( event ) => {
			const field = FIELDS.find( ( f ) =>
				event.target.contains( f.set )
			);
			const problem = field && isYes( field.set ) && field.problem();
			if ( problem ) {
				event.preventDefault();
				event.stopImmediatePropagation();
				field.show( problem );
			}
		},
		true
	);

	const fieldsFor = ( id ) => {
		const field = FIELDS.find(
			( f ) => f.ids.includes( id ) && isYes( f.set )
		);
		return field
			? {
					purplish_trade_in: 'yes',
					purplish_trade_in_device: field.device.value,
					purplish_trade_in_terms: field.terms?.checked ? '1' : '',
				}
			: null;
	};

	const nativeFetch = window.fetch;
	window.fetch = function ( input, init ) {
		try {
			const url = 'string' === typeof input ? input : input?.url || '';
			const match = STORE.exec( url );
			if ( match && 'string' === typeof init?.body ) {
				const body = JSON.parse( init.body );
				if ( addFields( body, 'batch' === match[ 1 ], fieldsFor ) ) {
					init = { ...init, body: JSON.stringify( body ) };
				}
			}
		} catch {
			// Not JSON: send it as it is.
		}
		return nativeFetch.call( this, input, init );
	};
}
