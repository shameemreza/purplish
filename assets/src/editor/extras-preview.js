/**
 * Matching extras on Add to Cart + Options in the canvas: a sample of the
 * list under the block, and the product data the panel shares. Products
 * come from the Store API, the same data the front end prints. A template
 * has no product of its own, so Cross-sells and Auto match show the
 * store's best sellers.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { money } from '../meter/math';
import { canvasElement } from './canvas';

const SOURCES = [ 'pick', 'crosssells', 'auto' ];
const STYLES = [ 'cards', 'compact', 'tiles' ];
export const MAX = 6;
const LIST = 'purplish-extras';

const found = new Map();

/**
 * Products from the Store API, one request per query.
 *
 * @param {Object} query Query args.
 * @return {Promise<Object[]>} The products, or none on an error.
 */
export function products( query ) {
	const path =
		'/wc/store/v1/products?' +
		new window.URLSearchParams( query ).toString();
	if ( ! found.has( path ) ) {
		found.set(
			path,
			apiFetch( { path } ).catch( () => {
				found.delete( path );
				return [];
			} )
		);
	}
	return found.get( path );
}

/**
 * The block's extras settings with defaults, as the server reads them.
 *
 * @param {Object} saved The block's `purplish` attribute.
 * @return {Object} The settings.
 */
export function extrasSettings( saved = {} ) {
	const title =
		'string' === typeof saved.extrastitle ? saved.extrastitle.trim() : '';
	return {
		from: SOURCES.includes( saved.extrasfrom )
			? saved.extrasfrom
			: 'crosssells',
		pick: Array.isArray( saved.extraspick )
			? [ ...new Set( saved.extraspick.map( Number ) ) ].filter(
					( id ) => id > 0
				)
			: [],
		fallback: false !== saved.extrasfallback,
		title: title || __( 'Add matching extras?', 'purplish' ),
		notitle: true === saved.extrasnotitle,
		style: STYLES.includes( saved.extrasstyle )
			? saved.extrasstyle
			: 'cards',
		photo: false !== saved.extrasphoto,
		link: false !== saved.extraslink,
		desc: true === saved.extrasdesc,
		count: Math.max(
			1,
			Math.min(
				MAX,
				Number.isInteger( saved.extrascount ) ? saved.extrascount : 3
			)
		),
	};
}

/**
 * Whether a product can go in as an extra: simple, on sale and in stock.
 *
 * @param {Object} product A Store API product.
 * @return {boolean} Whether it fits.
 */
const usable = ( product ) =>
	'simple' === product.type && product.is_purchasable && product.is_in_stock;

/**
 * The products the sample shows: the picks in their order, or the store's
 * best sellers.
 *
 * @param {Object} settings From `extrasSettings()`.
 * @return {Promise<Object[]>} The products.
 */
async function sample( settings ) {
	if ( 'pick' === settings.from && settings.pick.length ) {
		const picked = await products( {
			include: settings.pick.join( ',' ),
			per_page: settings.pick.length,
		} );
		const list = settings.pick
			.map( ( id ) => picked.find( ( product ) => product.id === id ) )
			.filter( ( product ) => product && usable( product ) )
			.slice( 0, settings.count );
		if ( list.length ) {
			return list;
		}
	}
	const best = await products( {
		type: 'simple',
		stock_status: 'instock',
		orderby: 'popularity',
		order: 'desc',
		per_page: settings.count + 2,
	} );
	return best.filter( usable ).slice( 0, settings.count );
}

/**
 * A product name as text: the Store API sends it with HTML entities.
 *
 * @param {string} name The name.
 * @return {string} The text.
 */
export function decodeEntities( name ) {
	const area = document.createElement( 'textarea' );
	area.innerHTML = name || '';
	return area.value;
}

/**
 * Plain text from a bit of HTML, cut to a number of words.
 *
 * @param {string} html  The HTML.
 * @param {number} words How many words to keep.
 * @return {string} The text.
 */
function plain( html, words ) {
	const text = (
		new window.DOMParser().parseFromString( html || '', 'text/html' ).body
			.textContent || ''
	)
		.trim()
		.split( /\s+/ )
		.filter( Boolean );
	return (
		text.slice( 0, words ).join( ' ' ) + ( text.length > words ? '…' : '' )
	);
}

/**
 * The list's markup, as the server prints it.
 *
 * @param {Document} doc      The canvas document.
 * @param {Object[]} items    Store API products.
 * @param {Object}   settings From `extrasSettings()`.
 * @return {HTMLElement} The list.
 */
function build( doc, items, settings ) {
	const make = ( tag, className, text ) => {
		const element = doc.createElement( tag );
		element.className = className;
		if ( text ) {
			element.textContent = text;
		}
		return element;
	};
	const set = make( 'fieldset', `${ LIST } ${ LIST }-${ settings.style }` );
	set.setAttribute( 'contenteditable', 'false' );
	set.inert = true;
	set.append(
		make(
			'legend',
			'purplish-extras-title' +
				( settings.notitle ? ' screen-reader-text' : '' ),
			settings.title
		)
	);
	const list = make( 'div', 'purplish-extras-list' );
	items.forEach( ( product, index ) => {
		const item = make( 'div', 'purplish-extra' );
		const box = make( 'input', 'purplish-extra-check' );
		box.type = 'checkbox';
		box.checked = 0 === index;
		item.append( box );
		const photo = product.images?.[ 0 ];
		if ( settings.photo && photo ) {
			const image = make( 'img', 'purplish-extra-photo' );
			image.src = photo.thumbnail || photo.src;
			image.alt = '';
			item.append( image );
		}
		const text = make( 'div', 'purplish-extra-text' );
		text.append(
			make(
				'span',
				'purplish-extra-name',
				decodeEntities( product.name )
			)
		);
		const desc = settings.desc
			? plain( product.short_description, 14 )
			: '';
		if ( desc ) {
			text.append( make( 'p', 'purplish-extra-desc', desc ) );
		}
		if ( settings.link ) {
			text.append(
				make(
					'span',
					'purplish-extra-link',
					__( 'See more details', 'purplish' )
				)
			);
		}
		item.append(
			text,
			make(
				'span',
				'purplish-extra-price',
				sprintf(
					/* translators: %s: the extra's price. */
					__( '+ %s', 'purplish' ),
					money(
						parseInt( product.prices.price, 10 ) || 0,
						product.prices
					)
				)
			)
		);
		list.append( item );
	} );
	set.append( list );
	return set;
}

/**
 * Shows the list under the block in the canvas. While the template loads,
 * the editor swaps the block's element for a new one, so each change on
 * the canvas looks the block up again.
 *
 * @param {string}      clientId The block's client ID.
 * @param {Object|null} saved    The block's `purplish` attribute, or null without extras.
 */
export function useExtras( clientId, saved ) {
	const key = saved ? JSON.stringify( extrasSettings( saved ) ) : '';
	const [ items, setItems ] = useState( null );

	useEffect( () => {
		if ( ! key ) {
			setItems( null );
			return;
		}
		let live = true;
		sample( JSON.parse( key ) ).then(
			( list ) => live && setItems( list )
		);
		return () => {
			live = false;
		};
	}, [ key ] );

	useEffect( () => {
		const first = key && items?.length && canvasElement( clientId );
		if ( ! first ) {
			return;
		}
		const settings = JSON.parse( key );
		const doc = first.ownerDocument;
		const find = () => doc.getElementById( 'block-' + clientId );
		const place = () => {
			const block = find();
			if ( block && ! block.querySelector( `:scope > .${ LIST }` ) ) {
				block.append( build( doc, items, settings ) );
			}
		};
		place();
		const watch = new doc.defaultView.MutationObserver( place );
		watch.observe( doc.body, { childList: true, subtree: true } );
		return () => {
			watch.disconnect();
			find()
				?.querySelectorAll( `:scope > .${ LIST }` )
				.forEach( ( list ) => list.remove() );
		};
	}, [ clientId, key, items ] );
}
