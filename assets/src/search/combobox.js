/**
 * Instant results under a Search block: a combobox on the field, filled
 * from Purplish's search route while the shopper types. Plain script, no
 * library. Without it the form submits to the results page as before.
 */

// Tags and attributes the price HTML keeps; everything else is unwrapped.
const KEEP = [ 'SPAN', 'DEL', 'INS', 'BDI', 'BDO' ];
const DROP = [ 'SCRIPT', 'STYLE' ];

/**
 * WooCommerce's price HTML, reduced to the few tags it uses for amounts,
 * sale prices and screen reader text.
 *
 * @param {Document} doc  The page.
 * @param {string}   html Price HTML from the route.
 * @return {DocumentFragment} Safe nodes.
 */
export function cleanPrice( doc, html ) {
	const box = doc.createElement( 'template' );
	box.innerHTML = html;
	clean( box.content );
	return box.content;
}

/**
 * Cleans the children of a node in place.
 *
 * @param {Node} node Parent node.
 */
function clean( node ) {
	[ ...node.childNodes ].forEach( ( child ) => {
		if ( 3 === child.nodeType ) {
			return;
		}
		if ( 1 !== child.nodeType || DROP.includes( child.tagName ) ) {
			child.remove();
			return;
		}
		clean( child );
		if ( ! KEEP.includes( child.tagName ) ) {
			child.replaceWith( ...child.childNodes );
			return;
		}
		[ ...child.attributes ].forEach( ( { name, value } ) => {
			const keep =
				( 'class' === name && 'screen-reader-text' === value ) ||
				'aria-hidden' === name;
			if ( ! keep ) {
				child.removeAttribute( name );
			}
		} );
	} );
}

/**
 * A query as the route keys it, so repeats hit the same cache.
 *
 * @param {string} value Typed text.
 * @return {string} Trimmed, lowercase, single spaces.
 */
export const normalize = ( value ) =>
	value.trim().replace( /\s+/g, ' ' ).toLowerCase();

/**
 * Turns one Search form into a combobox.
 *
 * @param {HTMLFormElement} form The form with `data-purplish-search`.
 * @param {Object}          env  Timers and fetch, for tests.
 * @return {Object|undefined} Hooks for tests.
 */
export function setup( form, env = window ) {
	const doc = form.ownerDocument;
	const input = form.querySelector( 'input[type="search"]' );
	const panel = form.querySelector( '.purplish-search' );
	const list = panel && panel.querySelector( '[role="listbox"]' );
	if ( ! input || ! list ) {
		return;
	}
	const live = form.querySelector( '.purplish-search__live' );
	const o = JSON.parse( form.dataset.purplishSearch );

	const memo = new Map();
	let timer = 0;
	let ctrl = null;
	let shown = '';
	let active = -1;

	Object.entries( {
		role: 'combobox',
		'aria-autocomplete': 'list',
		'aria-expanded': 'false',
		'aria-controls': list.id,
		autocomplete: 'off',
	} ).forEach( ( [ name, value ] ) => input.setAttribute( name, value ) );

	const options = () => [ ...list.querySelectorAll( '[role="option"]' ) ];

	const el = ( tag, cls, text ) => {
		const node = doc.createElement( tag );
		node.className = cls ? 'purplish-search__' + cls : '';
		if ( text !== undefined ) {
			node.textContent = text;
		}
		return node;
	};

	const option = ( url, cls ) => {
		const a = el( 'a', cls );
		a.href = url;
		a.tabIndex = -1;
		a.setAttribute( 'role', 'option' );
		a.setAttribute( 'aria-selected', 'false' );
		a.id = list.id + '-' + options().length;
		return a;
	};

	const group = ( name ) => {
		const box = el( 'div', name );
		box.setAttribute( 'role', 'group' );
		box.setAttribute( 'aria-label', o[ name ] );
		list.append( box );
		return box;
	};

	// Marks one option active, or none with -1. Focus stays in the field.
	const move = ( to ) => {
		const all = options();
		if ( all[ active ] ) {
			all[ active ].setAttribute( 'aria-selected', 'false' );
		}
		active = all[ to ] ? to : -1;
		if ( active < 0 ) {
			input.removeAttribute( 'aria-activedescendant' );
			return;
		}
		all[ active ].setAttribute( 'aria-selected', 'true' );
		input.setAttribute( 'aria-activedescendant', all[ active ].id );
		all[ active ].scrollIntoView?.( { block: 'nearest' } );
	};

	const open = ( yes ) => {
		panel.hidden = ! yes;
		input.setAttribute( 'aria-expanded', String( yes ) );
		if ( ! yes ) {
			move( -1 );
		}
	};

	const show = ( q, data ) => {
		list.textContent = '';
		active = -1;
		shown = q;
		if ( data.cats && data.cats.length ) {
			const box = group( 'cats' );
			data.cats.forEach( ( cat ) => {
				const a = option( cat.url, 'cat' );
				a.append( cat.name, ' ', el( 'span', 'count', cat.count ) );
				box.append( a );
			} );
		}
		if ( data.items.length ) {
			const box = group( 'items' );
			data.items.forEach( ( item ) => {
				const a = option( item.url, 'item' );
				const img = el( 'img' );
				img.src = item.img;
				img.alt = '';
				img.width = img.height = 48;
				img.loading = 'lazy';
				const text = el( 'span', 'text' );
				text.append( el( 'span', 'name', item.name ) );
				if ( o.p && item.price ) {
					const price = el( 'span', 'price' );
					price.append( cleanPrice( doc, item.price ) );
					text.append( price );
				}
				a.append( img, text );
				if ( item.sold ) {
					a.append( el( 'span', 'sold', o.sold ) );
				}
				box.append( a );
			} );
		}
		list.append(
			Object.assign( option( data.all, 'all' ), {
				textContent: o.all.replace( '%s', q ),
			} )
		);
		live.textContent =
			o.counts[ Math.min( data.items.length, o.counts.length - 1 ) ];
		open( doc.activeElement === input );
	};

	const ask = ( q ) => {
		if ( memo.has( q ) ) {
			show( q, memo.get( q ) );
			return;
		}
		if ( ctrl ) {
			ctrl.abort();
		}
		ctrl = new env.AbortController();
		const url = new URL( o.url, doc.baseURI );
		url.searchParams.set( 'q', q );
		url.searchParams.set( 'n', o.n );
		url.searchParams.set( 'c', o.c );
		env.fetch( url, { signal: ctrl.signal, credentials: 'omit' } )
			.then( ( r ) => ( r.ok ? r.json() : Promise.reject( r ) ) )
			.then( ( data ) => {
				memo.set( q, data );
				if ( memo.size > 20 ) {
					memo.delete( memo.keys().next().value );
				}
				if ( normalize( input.value ) === q ) {
					show( q, data );
				}
			} )
			.catch( () => {} );
	};

	input.addEventListener( 'input', () => {
		env.clearTimeout( timer );
		const q = normalize( input.value );
		if ( q.length < 2 ) {
			if ( ctrl ) {
				ctrl.abort();
			}
			open( false );
			return;
		}
		timer = env.setTimeout( () => ask( q ), 250 );
	} );

	input.addEventListener( 'focus', () => {
		if ( shown && shown === normalize( input.value ) ) {
			open( true );
		}
	} );

	input.addEventListener( 'keydown', ( event ) => {
		const isOpen = ! panel.hidden;
		const key = event.key;
		if ( 'ArrowDown' === key || 'ArrowUp' === key ) {
			if ( ! shown ) {
				return;
			}
			event.preventDefault();
			if ( ! isOpen ) {
				open( true );
			}
			const last = options().length - 1;
			if ( 'ArrowDown' === key ) {
				move( active < last ? active + 1 : 0 );
			} else {
				move( active > 0 ? active - 1 : last );
			}
		} else if ( 'Enter' === key && isOpen && active > -1 ) {
			event.preventDefault();
			options()[ active ].click();
		} else if ( 'Escape' === key && isOpen ) {
			// Keeps core's expandable search from closing the field, and
			// the browser from clearing the text, on this first Escape.
			event.stopPropagation();
			event.preventDefault();
			open( false );
		}
	} );

	// Keeps focus in the field while a row is clicked, so the click lands.
	// A click anywhere else moves focus out of the form, which closes it.
	panel.addEventListener( 'mousedown', ( event ) => event.preventDefault() );

	form.addEventListener( 'focusout', ( event ) => {
		if ( ! form.contains( event.relatedTarget ) ) {
			open( false );
		}
	} );

	return { ask, show, open };
}
