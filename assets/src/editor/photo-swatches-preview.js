/**
 * Photo swatches on the Variation Selector in the editor. The editor shows
 * WooCommerce's sample options (Blue, Red, Green) or the product's option
 * names, never their photos, so each color option gets a drawn sample
 * sweater in its color. The first option shows as picked. Drawn again
 * when the editor draws the chips again.
 */
import { useEffect } from '@wordpress/element';

import { canvasElement } from './canvas';

const NAME =
	'.wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name';
const ROW =
	'.wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute .wp-block-group';
const CHIPS = '.wc-block-product-filter-chips';
const ITEM = '.wc-block-product-filter-chips__item';
const SWATCH = '.wc-block-product-filter-chips__swatch';
const PICKED = 'purplish-photo-swatches__picked';
const MARK = 'data-purplish-photo';
const SAMPLE = 'data-purplish-sample';
const NO_COLOR = 'wc-block-product-filter-chips__swatch--no-color';

const SAMPLES = {
	blue: '#3c5a8a',
	red: '#a83a32',
	green: '#3f6a4e',
};

/**
 * The color a sample sweater is drawn in: WooCommerce's sample colors,
 * then the option name when it is a CSS color, then gray.
 *
 * @param {string} name   Option name.
 * @param {Window} [view] Window to ask about CSS colors.
 * @return {string} A CSS color.
 */
export function sampleColor( name, view = window ) {
	const key = String( name ).trim().toLowerCase();
	if ( SAMPLES[ key ] ) {
		return SAMPLES[ key ];
	}
	const word = key.replace( /\s+/g, '' );
	if ( word && view.CSS?.supports?.( 'color', word ) ) {
		return word;
	}
	return '#8a8790';
}

/**
 * A small sweater drawing on a light ground, as a CSS image.
 *
 * @param {string} color CSS color.
 * @return {string} `url("data:...")`.
 */
export function sweater( color ) {
	const svg =
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' +
		'<rect width="64" height="64" fill="#f3f1ee"/>' +
		`<path fill="${ color }" d="M24 12h16l14 8-5 12-5-3v23H20V29l-5 3-5-12z"/>` +
		'<path fill="none" stroke="#000" stroke-opacity=".18" stroke-width="1.5" d="M26 12a6 4 0 0 0 12 0M20 49h24"/>' +
		'</svg>';
	return `url("data:image/svg+xml,${ encodeURIComponent( svg ) }")`;
}

/**
 * The row photo swatches go on: the picked attribute when set, else one
 * named like a color.
 *
 * @param {Element} block     The Variation Selector attribute block.
 * @param {string}  attribute Saved attribute slug, empty for Auto.
 * @return {Element|null} The row (name and chips).
 */
export function photoRow( block, attribute ) {
	const rows = Array.from( block.querySelectorAll( ROW ) ).filter(
		( row ) => row.querySelector( NAME ) && row.querySelector( CHIPS )
	);
	const named = ( row ) => {
		const name = row.querySelector( NAME );
		return {
			slug: ( name.getAttribute( 'for' ) || '' ).toLowerCase(),
			text: name.textContent.replace(
				name.querySelector( `.${ PICKED }` )?.textContent ?? '',
				''
			),
		};
	};
	if ( attribute ) {
		const want = attribute.toLowerCase();
		return (
			rows.find( ( row ) => {
				const { slug, text } = named( row );
				return (
					slug === want ||
					slug === `pa_${ want }` ||
					text.trim().toLowerCase() === want
				);
			} ) || null
		);
	}
	return (
		rows.find( ( row ) => {
			const { slug, text } = named( row );
			return /colou?r/i.test( slug ) || /colou?r/i.test( text );
		} ) || null
	);
}

/**
 * Draws the samples in a row, changing only what differs so the editor's
 * redraw watcher settles.
 *
 * @param {Element} row  The row.
 * @param {boolean} name Whether to show the picked name.
 */
export function paint( row, name ) {
	const view = row.ownerDocument.defaultView;
	const chips = row.querySelector( CHIPS );
	if ( ! chips.classList.contains( 'purplish-photo-swatches' ) ) {
		chips.classList.add( 'purplish-photo-swatches' );
		chips.setAttribute(
			MARK,
			chips.classList.contains( 'is-style-swatch' ) ? 'own' : 'added'
		);
		chips.classList.add( 'is-style-swatch' );
	}
	const items = Array.from( chips.querySelectorAll( ITEM ) );
	items.forEach( ( item, index ) => {
		const swatch = item.querySelector( SWATCH );
		const label = item.textContent.trim();
		// Each write below is guarded: even a no-op class change is a
		// mutation, and the watcher would draw again forever.
		if ( swatch?.classList.contains( NO_COLOR ) ) {
			swatch.classList.remove( NO_COLOR );
		}
		if ( swatch && swatch.getAttribute( SAMPLE ) !== label ) {
			swatch.setAttribute( SAMPLE, label );
			swatch.style.backgroundImage = sweater(
				sampleColor( label, view )
			);
		}
		const checked = 0 === index ? 'true' : 'false';
		if ( item.getAttribute( 'aria-checked' ) !== checked ) {
			item.setAttribute( 'aria-checked', checked );
		}
	} );

	const title = row.querySelector( NAME );
	const current = title.querySelector( `.${ PICKED }` );
	const first = items[ 0 ]?.textContent.trim() ?? '';
	if ( ! name || ! first ) {
		current?.remove();
		return;
	}
	if ( current?.textContent === first ) {
		return;
	}
	current?.remove();
	const picked = row.ownerDocument.createElement( 'span' );
	picked.className = PICKED;
	picked.setAttribute( 'aria-hidden', 'true' );
	picked.setAttribute( 'contenteditable', 'false' );
	picked.textContent = first;
	title.appendChild( picked );
}

/**
 * Takes the samples out again.
 *
 * @param {Element} block The Variation Selector attribute block.
 */
function clear( block ) {
	block.querySelectorAll( `.${ PICKED }` ).forEach( ( el ) => el.remove() );
	block.querySelectorAll( `[${ SAMPLE }]` ).forEach( ( swatch ) => {
		swatch.removeAttribute( SAMPLE );
		swatch.style.backgroundImage = '';
		swatch.classList.add( NO_COLOR );
		swatch.closest( ITEM )?.setAttribute( 'aria-checked', 'false' );
	} );
	block.querySelectorAll( `[${ MARK }]` ).forEach( ( chips ) => {
		chips.classList.remove( 'purplish-photo-swatches' );
		if ( 'added' === chips.getAttribute( MARK ) ) {
			chips.classList.remove( 'is-style-swatch' );
		}
		chips.removeAttribute( MARK );
	} );
}

/**
 * Adds the sample photos.
 *
 * @param {string}                                  clientId The block's client ID.
 * @param {{attribute: string, name: boolean}|null} settings Photo swatch settings, or null when off.
 */
export function usePhotoSwatches( clientId, settings ) {
	const on = null !== settings;
	const attribute = settings?.attribute ?? '';
	const name = settings?.name ?? true;
	useEffect( () => {
		const block = on && canvasElement( clientId );
		if ( ! block ) {
			return;
		}
		const place = () => {
			const row = photoRow( block, attribute );
			if ( row ) {
				paint( row, name );
			}
		};
		place();
		const watch = new block.ownerDocument.defaultView.MutationObserver(
			place
		);
		watch.observe( block, {
			childList: true,
			subtree: true,
			attributeFilter: [ 'class', 'aria-checked' ],
		} );
		return () => {
			watch.disconnect();
			clear( block );
		};
	}, [ clientId, on, attribute, name ] );
}
