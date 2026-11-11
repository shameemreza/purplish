/**
 * The Changing Room on the front end. Each ring is one continuous
 * position eased by a critically damped spring, and every piece is placed
 * from it each frame. Turning a ring, dragging a piece onto the model or
 * Try it on wears a piece: a copy flies onto his body, then the photo of
 * that pair changes under it. PHP prints the rings, the panel copy and the
 * outfit; this script only moves things and talks to the cart.
 */
import { addToCart } from '../showfloor/cart';
import { flyToCart } from '../../shared/fly';
import { clamp, offset, spot, vars } from './ring';

const P = 'purplish-changing-room-';
const SLOTS = [ 'top', 'bottom' ];
const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
// Spring stiffness: settles in about 0.6 s with a soft tail.
const W = 10;
const mod = ( a, n ) => ( ( a % n ) + n ) % n;
const fill = ( text, ...args ) =>
	args.reduce( ( t, a, i ) => t.replace( `%${ i + 1 }$s`, a ), text );
const hook = ( el, type, fn ) => el && el.addEventListener( type, fn );
const later = ( fn, ms ) => setTimeout( fn, ms );
const picture = ( name, src ) => {
	const img = document.createElement( 'img' );
	img.className = P + name;
	img.alt = '';
	img.src = src;
	return img;
};

/**
 * Starts one room.
 *
 * @param {HTMLElement} root The block.
 */
function room( root ) {
	const $ = ( s, el = root ) => el.querySelector( '.' + P + s );
	const data = JSON.parse( $( 'data' ).textContent );
	const S = data.s;
	const has = ( c ) => root.classList.contains( P + c );
	const on = ( k ) => ! has( k + '-off' );
	const still = () => reduce.matches || has( 'swap-none' );
	const stage = $( 'stage' );
	const figure = $( 'figure' );
	const drawer = $( 'drawer' );
	const toastEl = $( 'notice' );
	const live = $( 'live' );
	const outfit = $( 'outfit-add' );
	const state = { top: 0, bottom: 0, last: 'top' };
	const worn = ( slot ) => data[ slot ][ state[ slot ] ];
	const look = () =>
		data.looks[ worn( 'top' ).id + '|' + worn( 'bottom' ).id ];
	const colorOf = ( p ) => ( p.sizes[ p.pick ] || {} ).c || p.c;
	const sized = ( p ) => ( p.sizes[ p.pick ] || p ).n;

	const shared = new URLSearchParams( window.location.search ).get(
		'outfit'
	);
	const rows = {};
	SLOTS.forEach( ( slot, n ) => {
		const items = [ ...$( 'ring-' + slot ).children ];
		data[ slot ].forEach( ( p, i ) => {
			p.slot = slot;
			p.n = p.name;
			p.i = i;
			p.li = items[ i ];
			p.li.firstChild.p = p;
			if ( shared && String( p.id ) === shared.split( '-' )[ n ] ) {
				state[ slot ] = i;
			}
		} );
		const at = state[ slot ];
		rows[ slot ] = {
			slot,
			items,
			n: items.length,
			pos: at,
			target: at,
			vel: 0,
			hide: at,
		};
	} );

	/* Rings */
	let phone = false;
	function place( r ) {
		r.items.forEach( ( li, i ) => {
			const at = spot(
				offset( i, r.pos, r.n ),
				r.n,
				r.slot,
				on( 'far' ) && ! phone
			);
			const o = i === r.hide ? 0 : at.o;
			Object.entries( vars( at ) ).forEach( ( [ k, v ] ) =>
				li.style.setProperty( k, v )
			);
			li.style.opacity = o.toFixed( 3 );
			li.style.zIndex = at.z;
			li.inert = o < 0.06;
		} );
	}
	let raf = 0;
	let last = 0;
	function frame( now ) {
		const dt = Math.min( 0.032, ( now - last ) / 1000 );
		last = now;
		let moving = false;
		Object.values( rows ).forEach( ( r ) => {
			if ( ! r.drag ) {
				if ( reduce.matches ) {
					r.pos = r.target;
					r.vel = 0;
				}
				r.vel += ( W * W * ( r.target - r.pos ) - 2 * W * r.vel ) * dt;
				r.pos += r.vel * dt;
				if (
					Math.abs( r.target - r.pos ) < 0.0008 &&
					Math.abs( r.vel ) < 0.002
				) {
					r.pos = r.target;
					r.vel = 0;
				} else {
					moving = true;
				}
			} else {
				moving = true;
			}
			place( r );
		} );
		raf = moving ? window.requestAnimationFrame( frame ) : 0;
	}
	function kick() {
		if ( ! raf ) {
			last = performance.now();
			raf = window.requestAnimationFrame( frame );
		}
	}
	// Turns the ring the short way round to a piece.
	function aim( r, index ) {
		const cur = Math.round( r.target );
		r.target = cur + offset( index, cur, r.n );
		kick();
	}
	function rotate( slot, step ) {
		const r = rows[ slot ];
		r.target = Math.round( r.target ) + step;
		kick();
		wear( slot, mod( r.target, r.n ) );
	}

	/* Wearing: the piece flies onto him, then the photo changes under it */
	let token = 0;
	function showLook( delay ) {
		const my = ++token;
		const url = look()[ 0 ];
		$( 'missing' ).hidden = !! url;
		if ( ! url ) {
			return;
		}
		const img = picture( 'photo', url );
		Promise.all( [
			img.decode().catch( () => {} ),
			new Promise( ( r ) => later( r, still() ? 0 : delay ) ),
		] ).then( () => {
			if ( my !== token ) {
				return;
			}
			const done = () => {
				let p;
				while ( ( p = img.previousElementSibling ) ) {
					p.remove();
				}
				img.classList.remove( 'is-in' );
			};
			figure.prepend( img );
			figure
				.querySelectorAll( '.' + P + 'photo' )
				.forEach( ( old ) => old !== img && img.before( old ) );
			if ( still() ) {
				done();
			} else {
				img.classList.add( 'is-in' );
				hook( img, 'animationend', done );
			}
		} );
	}
	function flyOn( p, from ) {
		if ( still() || ! from || ! from.width ) {
			return;
		}
		const f = figure.getBoundingClientRect();
		const top = p.slot === 'top';
		const w = f.width * ( top ? 0.47 : 0.31 );
		const s = w / from.width;
		const el = picture( 'flyon', p.img );
		el.style.width = from.width + 'px';
		document.body.append( el );
		const a = `translate(${ from.left }px, ${ from.top }px)`;
		const b = `translate(${ f.left + f.width * 0.515 - w / 2 }px, ${
			f.top + f.height * ( top ? 0.36 : 0.715 ) - ( from.height * s ) / 2
		}px) scale(${ s })`;
		el.animate(
			[
				{ transform: a, easing: 'cubic-bezier(0.2, 0.8, 0.2, 1)' },
				{ transform: b, offset: 0.52 },
				{ transform: b, offset: 0.66, easing: 'ease-out' },
				{ transform: b, opacity: 0 },
			],
			{ duration: 620, fill: 'forwards' }
		).finished.then( () => el.remove() );
	}

	function wear( slot, i, from, delay = 180 ) {
		const r = rows[ slot ];
		if ( mod( Math.round( r.target ), r.n ) !== i || r.target % 1 ) {
			aim( r, i );
		}
		r.hide = i;
		if ( state[ slot ] === i ) {
			kick();
			return;
		}
		const p = data[ slot ][ i ];
		if ( ! from && +p.li.style.opacity > 0.1 ) {
			from = p.li.querySelector( 'img' ).getBoundingClientRect();
		}
		state[ slot ] = i;
		state.last = slot;
		flyOn( p, from );
		showLook( delay );
		update();
		kick();
		const url = new URL( window.location.href );
		url.searchParams.set(
			'outfit',
			worn( 'top' ).id + '-' + worn( 'bottom' ).id
		);
		window.history.replaceState( window.history.state, '', url );
	}

	/* Page color, and what he wears */
	function setVars( el, c ) {
		[ 'color', 'on', 'ink', 'tint' ].forEach( ( v ) =>
			c
				? el.style.setProperty( '--purplish-cr-' + v, c[ v ] )
				: el.style.removeProperty( '--purplish-cr-' + v )
		);
	}
	const tint = ( el, p ) =>
		setVars( el, has( 'color-off' ) ? 0 : colorOf( p ) );
	function update() {
		if ( ! has( 'color-off' ) ) {
			const c = colorOf(
				worn( SLOTS.find( ( s ) => has( 'color-' + s ) ) || state.last )
			);
			setVars( root, c );
			if ( has( 'area-page' ) ) {
				setVars( document.body, c );
				document.body.classList.toggle( P + 'page', !! c );
			}
		}
		SLOTS.forEach( ( slot ) => {
			const p = worn( slot );
			const tag = $( 'tag-' + slot );
			if ( tag ) {
				$( 'tag-name', tag ).textContent = p.name;
				$( 'tag-price', tag ).textContent = p.price;
				tag.style.setProperty( '--dot', ( colorOf( p ) || {} ).color );
			}
		} );
		if ( outfit ) {
			$( 'total' ).textContent = look()[ 1 ];
		}
		live.textContent = fill(
			S.wearing,
			worn( 'top' ).name,
			worn( 'bottom' ).name
		);
	}
	SLOTS.forEach( ( slot ) =>
		hook( $( 'tag-' + slot ), 'click', () => open( worn( slot ) ) )
	);

	/* Cart */
	let pending = false;
	const label = ( el, text ) => {
		( $( 'outfit-t', el ) || el ).textContent = text;
	};
	async function adding( button, ids, idle ) {
		button.disabled = true;
		label( button, S.adding );
		const ok = ( await addToCart( data.api, ids ) ).every( Boolean );
		button.disabled = false;
		label( button, ok ? S.added : idle );
		later( () => label( button, idle ), 1600 );
		if ( ok ) {
			document.body.dispatchEvent(
				new window.CustomEvent( 'wc-blocks_added_to_cart', {
					bubbles: true,
					detail: { preserveCartData: false },
				} )
			);
		}
		return ok;
	}
	async function addOutfit() {
		const parts = [ worn( 'top' ), worn( 'bottom' ) ];
		const missing = parts.find(
			( p ) => p.sizes.length && ! p.sizes[ p.pick ]
		);
		// Without the panel, a piece that can't be added here opens its page.
		const gone = parts.find(
			( p ) => ! p.a || ( p === missing && ! drawer )
		);
		if ( gone ) {
			window.location.href = gone.link;
			return;
		}
		if ( missing ) {
			pending = true;
			open( missing, fill( S.pickFor, missing.name ) );
			return;
		}
		pending = false;
		if ( drawer ) {
			close();
		}
		const ids = parts.map( ( p ) => ( p.sizes[ p.pick ] || p ).id );
		if ( await adding( outfit, ids, S.both ) ) {
			toast(
				parts[ 0 ],
				S.two,
				fill( S.and, sized( parts[ 0 ] ), sized( parts[ 1 ] ) )
			);
			fly( figure.querySelector( '.' + P + 'photo' ) );
		} else {
			toast( parts[ 0 ], S.failed, parts.map( sized ).join( ', ' ) );
		}
	}
	hook( outfit, 'click', addOutfit );

	/* Added to cart: a notice above everything, and the fly into the Mini-Cart */
	let toastTimer = 0;
	const hideToast = () => toastEl.hidePopover();
	function toast( p, title, what ) {
		live.textContent = title + ' ' + what;
		if ( ! toastEl || ! toastEl.showPopover ) {
			return;
		}
		$( 'notice-img' ).src = p.img;
		$( 'notice-text b' ).textContent = title;
		$( 'notice-text span' ).textContent = what;
		tint( toastEl, p );
		hideToast();
		toastEl.showPopover();
		clearTimeout( toastTimer );
		toastTimer = later( hideToast, 3600 );
	}
	hook( toastEl, 'pointerenter', () => clearTimeout( toastTimer ) );
	hook( toastEl, 'pointerleave', () => {
		toastTimer = later( hideToast, 1800 );
	} );
	function fly( photo ) {
		const cart = document.querySelector( '.wc-block-mini-cart__button' );
		if ( on( 'fly' ) && ! reduce.matches && photo && cart ) {
			flyToCart( photo, cart, 1, P + 'flyer', true );
		}
	}

	/* The panel */
	let current = null;
	const note = ( text ) => {
		$( 'note', drawer ).textContent = text;
	};
	function open( p, message = '' ) {
		if ( ! drawer ) {
			if ( p.slot ) {
				wear( p.slot, p.i );
			}
			return;
		}
		current = p;
		const sheet = $( 'sheet', p.li );
		$( 'kind', drawer ).textContent = sheet.dataset.kind;
		const scroll = $( 'scroll', drawer );
		scroll.replaceChildren( sheet.content.cloneNode( true ) );
		scroll.scrollTop = 0;
		if ( p.sizes[ p.pick ] ) {
			drawer
				.querySelector( `[data-size="${ p.pick }"]` )
				.setAttribute( 'aria-pressed', 'true' );
		}
		note( message );
		refresh();
		if ( ! drawer.open ) {
			drawer.showModal();
		}
	}
	const close = () => drawer.close();
	function refresh() {
		const p = current;
		const size = p.sizes[ p.pick ];
		$( 'stock', drawer ).textContent = size ? size.t : p.t;
		const picked = $( 'picked', drawer );
		if ( picked ) {
			picked.textContent = size ? size.l : '';
		}
		tint( drawer, p );
		label( $( 'add', drawer ), pending ? S.both : S.add );
		const button = $( 'wear', drawer );
		if ( button ) {
			const now = state[ p.slot ] === p.i;
			button.textContent = now ? S.worn : S.try;
			button.disabled = now;
		}
	}
	hook( drawer, 'click', ( e ) => {
		const chip = e.target.closest( '.' + P + 'chip' );
		if ( e.target === drawer || e.target.closest( '.' + P + 'close' ) ) {
			close();
		} else if ( chip ) {
			current.pick = +chip.dataset.size;
			drawer
				.querySelectorAll( '.' + P + 'chip' )
				.forEach( ( c ) =>
					c.setAttribute( 'aria-pressed', c === chip )
				);
			note( '' );
			refresh();
			if ( state[ current.slot ] === current.i ) {
				update();
			}
		}
	} );
	hook( drawer, 'close', () => {
		pending = false;
	} );
	hook( drawer && $( 'add', drawer ), 'click', async ( e ) => {
		const p = current;
		if ( ! p.a ) {
			window.location.href = p.link;
		} else if ( p.sizes.length && ! p.sizes[ p.pick ] ) {
			note( S.pick );
			const chip = drawer.querySelector(
				'.' + P + 'chip:not(:disabled)'
			);
			if ( chip ) {
				chip.focus();
			}
		} else if ( pending ) {
			// The panel stays open if the other piece needs a size too.
			wear( p.slot, p.i );
			addOutfit();
		} else if (
			await adding(
				e.currentTarget,
				[ ( p.sizes[ p.pick ] || p ).id ],
				S.add
			)
		) {
			toast( p, S.one, sized( p ) );
			fly( $( 'sheet-img', drawer ) );
		} else {
			note( S.failed );
		}
	} );
	hook( drawer && $( 'wear', drawer ), 'click', () => {
		const p = current;
		close();
		later( () => wear( p.slot, p.i ), reduce.matches ? 0 : 220 );
	} );

	/* Where he stands, for drops, the wheel and swipes */
	function zones() {
		const f = figure.getBoundingClientRect();
		return {
			f,
			cx: f.left + f.width * 0.515,
			waist: f.top + f.height * 0.53,
		};
	}
	const rowAt = ( y ) => rows[ y < zones().waist ? 'top' : 'bottom' ];
	const overHim = ( x, y ) => {
		const { f } = zones();
		return (
			x > f.left + f.width * 0.2 &&
			x < f.right - f.width * 0.18 &&
			y > f.top + f.height * 0.06 &&
			y < f.bottom - f.height * 0.03
		);
	};

	// A mouse wheel turns a ring one piece a notch; a trackpad turns it
	// smoothly and settles on the nearest piece.
	hook( stage, 'wheel', ( e ) => {
		const z = zones();
		const sideways = Math.abs( e.deltaX ) > Math.abs( e.deltaY );
		if (
			! on( 'wheel' ) ||
			( ! sideways &&
				! (
					Math.abs( e.clientX - z.cx ) > z.f.width / 4 &&
					e.clientY > z.f.top &&
					e.clientY < z.f.bottom
				) )
		) {
			return;
		}
		e.preventDefault();
		const r = rowAt( e.clientY );
		const delta = sideways ? e.deltaX : e.deltaY;
		const now = performance.now();
		if ( e.deltaMode || Math.abs( delta ) >= 60 ) {
			if ( now - ( r.notch || 0 ) > 140 ) {
				r.notch = now;
				rotate( r.slot, Math.sign( delta ) );
			}
			return;
		}
		const base = Math.round( r.target );
		r.hide = -1;
		r.target = clamp( r.target + delta / 260, base - 1.45, base + 1.45 );
		kick();
		clearTimeout( r.settle );
		r.settle = later( () => {
			r.target = Math.round( r.target );
			wear( r.slot, mod( r.target, r.n ) );
		}, 130 );
	} );

	/* Pointer: drag a piece onto him, drag across a ring to turn it, tap a piece */
	let dragged = false;
	function follow( move, up ) {
		const stop = new window.AbortController();
		const end = ( ev ) => {
			stop.abort();
			if ( up( ev ) ) {
				dragged = true;
				later( () => ( dragged = false ), 0 );
			}
		};
		[ move, end, end ].forEach( ( fn, n ) =>
			window.addEventListener(
				[ 'pointermove', 'pointerup', 'pointercancel' ][ n ],
				fn,
				stop
			)
		);
	}
	hook( stage, 'pointerdown', ( e ) => {
		if ( e.button > 0 ) {
			return;
		}
		const button = e.target.closest( '.' + P + 'piece' );
		if ( button && on( 'drag' ) ) {
			pickUp( e, button );
		} else if ( on( 'swipe' ) ) {
			turnByHand( e );
		}
	} );
	function pickUp( e, button ) {
		const p = button.p;
		const box = button.firstChild.getBoundingClientRect();
		const dx = e.clientX - box.left;
		const dy = e.clientY - box.top;
		let ghost = null;
		follow(
			( ev ) => {
				if ( ! ghost ) {
					if (
						Math.hypot(
							ev.clientX - e.clientX,
							ev.clientY - e.clientY
						) < 8
					) {
						return;
					}
					ghost = picture( 'ghost', p.img );
					ghost.style.width = box.width + 'px';
					document.body.append( ghost );
					p.li.classList.add( 'is-source' );
				}
				ghost.style.transform = `translate(${ ev.clientX - dx }px, ${ ev.clientY - dy }px)`;
				const over = overHim( ev.clientX, ev.clientY );
				ghost.classList.toggle( 'is-over', over );
				root.classList.toggle( 'is-over', over );
			},
			( ev ) => {
				if ( ! ghost ) {
					return false;
				}
				const over =
					root.classList.contains( 'is-over' ) &&
					ev.type === 'pointerup';
				root.classList.remove( 'is-over' );
				p.li.classList.remove( 'is-source' );
				if ( over ) {
					const from = ghost.getBoundingClientRect();
					ghost.remove();
					wear( p.slot, p.i, from, 150 );
				} else {
					ghost.remove();
				}
				return true;
			}
		);
	}
	// The ring follows the finger, then settles on the nearest piece,
	// carried on by the throw.
	function turnByHand( e ) {
		const r = rowAt( e.clientY );
		const start = r.target;
		const w = stage.clientWidth;
		const per = phone ? w * 0.36 : Math.min( w * 0.235, 350 );
		let active = false;
		let lastX = e.clientX;
		let lastT = performance.now();
		let v = 0;
		follow(
			( ev ) => {
				const dx = ev.clientX - e.clientX;
				if ( ! active ) {
					if (
						Math.abs( dx ) < 8 ||
						Math.abs( dx ) <
							Math.abs( ev.clientY - e.clientY ) * 1.2
					) {
						return;
					}
					active = r.drag = true;
					r.hide = -1;
					r.vel = 0;
					kick();
				}
				const now = performance.now();
				v =
					0.8 *
						( ( ev.clientX - lastX ) /
							Math.max( 1, now - lastT ) ) +
					0.2 * v;
				lastX = ev.clientX;
				lastT = now;
				r.pos = r.target = start - dx / per;
			},
			() => {
				if ( ! active ) {
					return false;
				}
				r.drag = false;
				r.vel = -( v * 1000 ) / per;
				r.target = clamp(
					Math.round( r.pos - ( v * 180 ) / per ),
					Math.round( start ) - 2,
					Math.round( start ) + 2
				);
				kick();
				wear( r.slot, mod( r.target, r.n ) );
				return true;
			}
		);
	}
	hook( stage, 'click', ( e ) => {
		const button = e.target.closest( '.' + P + 'piece' );
		if ( button && ! dragged ) {
			open( button.p );
		}
	} );

	/* Keyboard: arrows turn the ring the focused piece is in */
	SLOTS.forEach( ( slot ) =>
		hook( $( 'ring-' + slot ), 'keydown', ( e ) => {
			const step = { ArrowRight: 1, ArrowLeft: -1 }[ e.key ];
			if ( step ) {
				e.preventDefault();
				rotate( slot, step );
				const r = rows[ slot ];
				later( () =>
					r.items[ mod( r.target + step, r.n ) ].firstChild.focus( {
						preventScroll: true,
					} )
				);
			}
		} )
	);

	/* Start */
	new window.ResizeObserver( () => {
		phone = stage.clientWidth <= 700;
		Object.values( rows ).forEach( place );
	} ).observe( stage );
	if ( state.top || state.bottom ) {
		showLook( 0 );
	}
	update();
	root.classList.add( 'is-ready' );
}

document
	.querySelectorAll( '.wp-block-purplish-changing-room' )
	.forEach(
		( root ) => root.querySelector( '.' + P + 'data' ) && room( root )
	);
