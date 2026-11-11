/**
 * Showfloor on the front end. Places each look on an oval floor, turns it
 * by drag, sideways swipe, keys, buttons, the look list or autoplay, swaps
 * the copy and panel for the front look, switches to a grid, opens a look
 * in a dialog, and adds pieces to the cart through the Store API so the
 * Mini-Cart updates. On phones the floor can be a swipe row instead.
 */
import { flyToCart } from '../../shared/fly';
import { addToCart } from './cart';
import { spot } from './depth';

const P = 'purplish-showfloor-';
/**
 * Fills %s, or %1$s and %2$s, in a translated string.
 *
 * @param {string}             text   String with placeholders.
 * @param {...(string|number)} values Values in order.
 * @return {string} The filled string.
 */
const fill = ( text, ...values ) =>
	text
		.replace( /%(\d)\$s/g, ( match, n ) => values[ n - 1 ] )
		.replace( '%s', values[ 0 ] );

/**
 * Starts one Showfloor.
 *
 * @param {HTMLElement} root The block wrapper.
 */
function init( root ) {
	const $ = ( name, el = root ) => el.querySelector( '.' + P + name );
	const has = ( option ) => root.classList.contains( P + option );
	const stage = $( 'stage' );
	const ring = $( 'ring' );
	const looks = [ ...root.querySelectorAll( '.' + P + 'look' ) ];
	const names = [ ...root.querySelectorAll( '.' + P + 'name' ) ];
	const title = $( 'title' );
	const line = $( 'line' );
	const count = $( 'count' );
	const pieces = $( 'pieces' );
	const meta = $( 'meta' );
	const total = $( 'panel-total' );
	const cursor = $( 'cursor' );
	const cursorText = cursor.firstElementChild;
	const dialog = $( 'dialog' );
	const ringBtn = $( 'view-ring' );
	const gridBtn = $( 'view-grid' );
	const panelWhole = $( 'whole', $( 'panel' ) );
	const tagWhole = $( 'whole', dialog );
	const play = $( 'play' );
	const playRing = play && $( 'play-ring', play );
	const strings = JSON.parse( root.dataset.strings || '{}' );
	const api = root.dataset.api;
	const autoMs = Number( root.dataset.speed ) || 4000;
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	// Same query as the stacked layout in style.css.
	const small = window.matchMedia(
		'(max-width: 781px), (max-width: 1023px) and (orientation: portrait)'
	);
	const N = looks.length;
	const mod = ( n ) => ( ( n % N ) + N ) % N;
	const isGrid = () => root.classList.contains( 'is-grid' );
	const rows = ( li ) => $( 'rows', li ).content;
	let pos = 0;
	let goal = 0;
	let raf = 0;
	let active = -1;
	let rx = 0;
	let ry = 0;
	let lastDrag = 0;
	let row = false;
	let fresh = true;

	function measure() {
		const w = stage.clientWidth;
		const h = stage.clientHeight;
		const phone = small.matches;
		const was = row;
		row = phone && has( 'phone-row' ) && ! isGrid();
		root.classList.toggle( 'is-row', row );
		rx = phone ? w * 0.3 : Math.min( w * 0.24, 420 );
		ry = h * ( has( 'shape-flat' ) ? 0.015 : 1 ) * ( phone ? 0.06 : 0.075 );
		const s = root.style;
		s.setProperty( '--' + P + 'rx', rx + 'px' );
		s.setProperty( '--' + P + 'ry', ry + 'px' );
		s.setProperty(
			'--' + P + 'fig-h',
			Math.round( h * ( phone ? 0.74 : 0.58 ) ) + 'px'
		);
		s.setProperty( '--' + P + 'floor-y', phone ? '80%' : '74%' );
		s.setProperty( '--' + P + 'cx', phone ? '50%' : '54%' );
		if ( was !== row ) {
			pos = goal = Math.max( active, 0 );
			active = -1;
		}
		place();
		if ( row ) {
			jump( was ? Math.max( active, 0 ) : pos );
		}
	}

	function place() {
		if ( row ) {
			return;
		}
		const blur = ! ( small.matches || reduce.matches );
		const depth =
			[ 'strong', 'off' ].find( ( d ) => has( 'depth-' + d ) ) ||
			'subtle';
		looks.forEach( ( li, i ) => {
			const here = spot( i - pos, N, depth, blur );
			const s = li.style;
			s.setProperty( '--x', ( here.x * rx ).toFixed( 1 ) + 'px' );
			s.setProperty( '--y', ( here.y * ry ).toFixed( 1 ) + 'px' );
			s.setProperty( '--s', here.s.toFixed( 3 ) );
			s.setProperty( '--o', here.o.toFixed( 3 ) );
			s.setProperty( '--b', here.b.toFixed( 2 ) + 'px' );
			s.setProperty( '--z', here.z );
		} );
		const now = mod( Math.round( pos ) );
		if ( now !== active ) {
			show( now );
		}
	}

	function swap( el, text ) {
		if ( ! el ) {
			return;
		}
		el.textContent = text;
		el.classList.remove( 'is-swap' );
		// Reading the width restarts the animation.
		el.getBoundingClientRect();
		el.classList.add( 'is-swap' );
	}

	function setWhole( btn, li ) {
		btn.classList.remove( 'is-added' );
		btn.disabled = false;
		btn.hidden = ! li.dataset.whole;
		$( 'whole-t', btn ).textContent = li.dataset.whole;
		$( 'whole-p', btn ).textContent = li.dataset.total;
	}

	function show( i ) {
		active = i;
		const li = looks[ i ];
		count.textContent = fill( strings.count, i + 1, N );
		// PHP printed the first look's copy and products.
		if ( ! fresh || i ) {
			swap( title, li.dataset.name );
			swap( meta, li.dataset.meta );
			swap( line, li.dataset.line );
			pieces.replaceChildren( rows( li ).cloneNode( true ) );
		}
		fresh = false;
		// A look with no products (one from a pattern) has nothing to open.
		root.classList.toggle( 'is-bare', ! li.dataset.meta );
		total.textContent = li.dataset.total;
		setWhole( panelWhole, li );
		names.forEach( ( b, n ) =>
			b.setAttribute( 'aria-current', String( n === i ) )
		);
		looks.forEach( ( l, n ) => {
			$( 'figure', l ).setAttribute(
				'aria-label',
				n !== i || l.dataset.meta
					? fill(
							n === i ? strings.open : strings.turn,
							l.dataset.name
						)
					: l.dataset.name
			);
		} );
	}

	function run() {
		window.cancelAnimationFrame( raf );
		if ( reduce.matches ) {
			pos = goal;
			place();
			return;
		}
		const tick = () => {
			const d = goal - pos;
			if ( Math.abs( d ) < 0.0008 ) {
				pos = goal;
				place();
				return;
			}
			pos += d * 0.12;
			place();
			raf = window.requestAnimationFrame( tick );
		};
		raf = window.requestAnimationFrame( tick );
	}

	// Phone swipe row: native scroll with snap; the look nearest the
	// middle is the front one.
	const target = ( i ) =>
		looks[ i ].offsetLeft +
		looks[ i ].offsetWidth / 2 -
		ring.clientWidth / 2;
	function jump( i ) {
		ring.scrollLeft = target( i );
		show( i );
	}
	let rowRaf = 0;
	ring.addEventListener(
		'scroll',
		() => {
			if ( ! row ) {
				return;
			}
			window.cancelAnimationFrame( rowRaf );
			rowRaf = window.requestAnimationFrame( () => {
				const c = ring.scrollLeft + ring.clientWidth / 2;
				let best = 0;
				let gap = Infinity;
				looks.forEach( ( li, i ) => {
					const d = Math.abs(
						li.offsetLeft + li.offsetWidth / 2 - c
					);
					if ( d < gap ) {
						gap = d;
						best = i;
					}
				} );
				if ( best !== active ) {
					show( best );
				}
			} );
		},
		{ passive: true }
	);

	function go( i ) {
		if ( row ) {
			ring.scrollTo( {
				left: target( i ),
				behavior: reduce.matches ? 'auto' : 'smooth',
			} );
			return;
		}
		const base = Math.round( goal );
		let d = mod( i - base );
		if ( d > N / 2 ) {
			d -= N;
		}
		goal = base + d;
		run();
	}

	function step( n ) {
		if ( row ) {
			go( mod( active + n ) );
			return;
		}
		goal = Math.round( goal ) + n;
		run();
	}

	// Autoplay, off unless the store turns it on. Holds on hover, keyboard
	// focus, drag, touch, an open look, the grid and a hidden tab, and
	// never runs with reduced motion.
	const hold = new Set();
	let autoLeft = autoMs;
	let autoStart = 0;
	let autoTimer = 0;
	let paused = false;
	root.style.setProperty( '--' + P + 'auto', autoMs + 'ms' );
	const autoLive = () =>
		!! play &&
		! reduce.matches &&
		! paused &&
		! document.hidden &&
		! hold.size;
	function restart() {
		autoLeft = autoMs;
		autoStart = 0;
		if ( playRing ) {
			playRing.classList.add( 'is-reset' );
			playRing.getBoundingClientRect();
			playRing.classList.remove( 'is-reset' );
		}
	}
	function auto() {
		clearTimeout( autoTimer );
		if ( autoStart ) {
			autoLeft -= performance.now() - autoStart;
			autoStart = 0;
		}
		const live = autoLive();
		root.classList.toggle( 'is-auto-live', live );
		count.setAttribute( 'aria-live', live ? 'off' : 'polite' );
		if ( ! live ) {
			return;
		}
		autoStart = performance.now();
		autoTimer = setTimeout(
			() => {
				autoStart = 0;
				restart();
				step( 1 );
				auto();
			},
			Math.max( 0, autoLeft )
		);
	}
	function nudge() {
		restart();
		auto();
	}
	function setHold( key, on ) {
		hold[ on ? 'add' : 'delete' ]( key );
		auto();
	}
	if ( play ) {
		[ stage, $( 'panel' ), $( 'names' ) ].forEach( ( el ) => {
			if ( ! el ) {
				return;
			}
			el.addEventListener( 'pointerenter', ( e ) => {
				if ( e.pointerType === 'mouse' ) {
					setHold( 'hover', true );
				}
			} );
			el.addEventListener( 'pointerleave', () =>
				setHold( 'hover', false )
			);
		} );
		root.addEventListener( 'focusin', ( e ) => {
			setHold(
				'focus',
				e.target.matches( ':focus-visible' ) && e.target !== play
			);
		} );
		root.addEventListener( 'focusout', ( e ) => {
			if ( ! root.contains( e.relatedTarget ) ) {
				setHold( 'focus', false );
			}
		} );
		document.addEventListener( 'visibilitychange', auto );
		reduce.addEventListener( 'change', auto );
		play.addEventListener( 'click', () => {
			paused = ! paused;
			root.classList.toggle( 'is-auto-off', paused );
			play.setAttribute(
				'aria-label',
				paused ? strings.play : strings.pause
			);
			if ( ! paused ) {
				restart();
			}
			auto();
		} );
	}

	// Drag to turn, with a short glide after release.
	let drag = null;
	let moved = false;
	stage.addEventListener( 'pointerdown', ( e ) => {
		if ( e.pointerType !== 'mouse' ) {
			setHold( 'touch', true );
		}
		if ( isGrid() || row || e.button !== 0 ) {
			return;
		}
		window.cancelAnimationFrame( raf );
		drag = {
			x: e.clientX,
			start: pos,
			last: e.clientX,
			t: e.timeStamp,
			v: 0,
			id: e.pointerId,
		};
		moved = false;
	} );
	stage.addEventListener( 'pointermove', ( e ) => {
		if ( ! drag || e.pointerId !== drag.id ) {
			return;
		}
		const dx = e.clientX - drag.x;
		if ( ! moved ) {
			if ( Math.abs( dx ) < 6 ) {
				return;
			}
			moved = true;
			stage.setPointerCapture( e.pointerId );
			root.classList.add( 'is-dragging', 'has-dragged' );
			hold.add( 'drag' );
		}
		drag.v =
			( e.clientX - drag.last ) / Math.max( 1, e.timeStamp - drag.t );
		drag.last = e.clientX;
		drag.t = e.timeStamp;
		pos = drag.start - dx / ( rx * 0.9 );
		place();
	} );
	const end = () => {
		if ( hold.delete( 'touch' ) ) {
			nudge();
		}
		if ( ! drag ) {
			return;
		}
		if ( moved ) {
			goal = Math.round( pos - ( drag.v * 220 ) / ( rx * 0.9 ) );
			lastDrag = performance.now();
			root.classList.remove( 'is-dragging' );
			hold.delete( 'drag' );
			nudge();
		}
		drag = null;
		run();
	};
	stage.addEventListener( 'pointerup', end );
	stage.addEventListener( 'pointercancel', end );

	// Drag cursor: a label or ring that trails the mouse.
	let mouse = null;
	const at = { x: 0, y: 0 };
	let follow = 0;
	const trail = () => {
		if ( ! mouse ) {
			follow = 0;
			return;
		}
		at.x += ( mouse.x - at.x ) * 0.28;
		at.y += ( mouse.y - at.y ) * 0.28;
		// Keep the whole label inside the stage, which clips it.
		const hw = cursor.offsetWidth / 2 + 4;
		const hh = cursor.offsetHeight / 2 + 4;
		const x = Math.min( Math.max( at.x, hw ), stage.clientWidth - hw );
		const y = Math.min( Math.max( at.y, hh ), stage.clientHeight - hh );
		cursor.style.setProperty( '--cx', x.toFixed( 1 ) + 'px' );
		cursor.style.setProperty( '--cy', y.toFixed( 1 ) + 'px' );
		follow = window.requestAnimationFrame( trail );
	};
	function label( key ) {
		const text = strings[ key ];
		if ( cursorText.textContent !== text ) {
			cursorText.textContent = text;
		}
		cursor.classList.toggle( 'is-drag', key === 'drag' );
		cursor.classList.toggle( 'is-open', key === 'enter' );
	}
	stage.addEventListener( 'pointermove', ( e ) => {
		const on = e.pointerType === 'mouse' && ! reduce.matches && ! row;
		root.classList.toggle( 'is-pointing', on );
		if ( ! on ) {
			mouse = null;
			return;
		}
		const box = stage.getBoundingClientRect();
		const next = { x: e.clientX - box.left, y: e.clientY - box.top };
		if ( ! mouse ) {
			at.x = next.x;
			at.y = next.y;
		}
		mouse = next;
		if ( ! follow ) {
			follow = window.requestAnimationFrame( trail );
		}
		const fig = ! drag && e.target.closest( '.' + P + 'figure' );
		const front = looks[ active ];
		if ( ! fig || ( fig.parentNode === front && ! front.dataset.meta ) ) {
			label( 'drag' );
		} else {
			label( fig.parentNode === front ? 'enter' : 'spin' );
		}
	} );
	stage.addEventListener( 'pointerleave', () => {
		mouse = null;
		root.classList.remove( 'is-pointing' );
	} );

	// A sideways trackpad swipe turns the floor; a normal wheel still
	// scrolls the page.
	let wheelRest = 0;
	stage.addEventListener(
		'wheel',
		( e ) => {
			if (
				isGrid() ||
				row ||
				Math.abs( e.deltaX ) <= Math.abs( e.deltaY )
			) {
				return;
			}
			e.preventDefault();
			window.cancelAnimationFrame( raf );
			root.classList.add( 'has-dragged' );
			pos += e.deltaX / ( rx * 1.4 );
			place();
			clearTimeout( wheelRest );
			wheelRest = setTimeout( () => {
				goal = Math.round( pos );
				run();
				nudge();
			}, 140 );
		},
		{ passive: false }
	);

	stage.addEventListener( 'keydown', ( e ) => {
		if ( isGrid() ) {
			return;
		}
		const turn = { ArrowRight: 1, ArrowLeft: -1 }[ e.key ];
		if ( turn ) {
			step( document.dir === 'rtl' ? -turn : turn );
			nudge();
			e.preventDefault();
		} else if ( e.key === 'Enter' && e.target === stage ) {
			open( active );
		}
	} );

	looks.forEach( ( li, i ) => {
		$( 'figure', li ).addEventListener( 'click', () => {
			if ( performance.now() - lastDrag < 300 ) {
				return;
			}
			if ( isGrid() ) {
				pos = goal = i;
				place();
				open( i );
			} else if (
				i === active &&
				( row || Math.abs( goal - pos ) < 0.05 )
			) {
				open( i );
			} else {
				go( i );
				nudge();
			}
		} );
	} );

	// Floor and grid views.
	function view( grid ) {
		if ( grid === isGrid() ) {
			return;
		}
		const apply = () => {
			root.classList.toggle( 'is-grid', grid );
			if ( ringBtn ) {
				ringBtn.setAttribute( 'aria-pressed', String( ! grid ) );
				gridBtn.setAttribute( 'aria-pressed', String( grid ) );
			}
			measure();
			setHold( 'grid', grid );
		};
		if ( ! document.startViewTransition || reduce.matches ) {
			apply();
			return;
		}
		looks.forEach( ( li, i ) => {
			$( 'figure', li ).style.viewTransitionName = P + i;
		} );
		document.startViewTransition( apply );
	}

	// The open look.
	function open( i ) {
		const li = looks[ i ];
		if ( ! li.dataset.meta ) {
			return;
		}
		const img = $( 'figure img', li );
		const solo = $( 'solo img', dialog );
		solo.src = img.currentSrc || img.src;
		solo.alt = img.alt;
		const marks = $( 'solo-marks', dialog );
		marks.replaceChildren( $( 'marks', li ).content.cloneNode( true ) );
		[ ...marks.children ].forEach( ( m, n ) => {
			m.style.setProperty( '--i', n );
			if ( has( 'marks-add' ) ) {
				m.setAttribute(
					'aria-label',
					fill( strings.mark, m.dataset.name )
				);
			}
		} );
		$( 'tag-title', dialog ).textContent = li.dataset.name;
		$( 'tag-line', dialog ).textContent = li.dataset.line;
		$( 'tag-pieces', dialog ).replaceChildren(
			rows( li ).cloneNode( true )
		);
		$( 'tag-total strong', dialog ).textContent = li.dataset.total;
		setWhole( tagWhole, li );
		dialog.setAttribute( 'aria-label', li.dataset.name );
		dialog.showModal();
		setHold( 'dialog', true );
	}
	dialog.addEventListener( 'close', () => setHold( 'dialog', false ) );

	const tagRow = ( n ) =>
		dialog.querySelector(
			'.' + P + 'tag-pieces [data-piece="' + n + '"] .' + P + 'add'
		);
	function light( n ) {
		dialog
			.querySelectorAll( '.' + P + 'mark, .' + P + 'tag-pieces li' )
			.forEach( ( el ) =>
				el.classList.toggle( 'is-lit', el.dataset.piece === n )
			);
	}
	dialog.addEventListener( 'click', ( e ) => {
		const t = e.target;
		if (
			t === dialog ||
			t.classList.contains( P + 'dialog-body' ) ||
			t.closest( '.' + P + 'close' )
		) {
			dialog.close();
			return;
		}
		const mark = t.closest( '.' + P + 'mark' );
		if ( ! mark ) {
			return;
		}
		light( mark.dataset.piece );
		const btn = has( 'marks-add' ) && tagRow( mark.dataset.piece );
		if ( ! btn ) {
			return;
		}
		if ( btn.tagName === 'A' ) {
			btn.click();
			return;
		}
		add( [ btn ] ).then( ( ok ) =>
			mark.classList.toggle( 'is-added', ok )
		);
	} );
	dialog.addEventListener( 'pointerover', ( e ) => {
		const hit = e.target.closest(
			'.' + P + 'mark, .' + P + 'tag-pieces li'
		);
		if ( hit ) {
			light( hit.dataset.piece );
		}
	} );

	// Add to cart, with the button text following along.
	const text = ( btn, key ) => {
		$( 'add-t', btn ).textContent = strings[ key ];
	};
	function fly( btn ) {
		const photo = btn.closest( 'li' ).querySelector( 'img' );
		const cart = [
			...document.querySelectorAll( '.wc-block-mini-cart__button' ),
		].find( ( el ) => el.getClientRects().length );
		if ( has( 'fly-on' ) && ! reduce.matches && ! dialog.open && cart ) {
			flyToCart( photo, cart, 1, P + 'fly' );
		}
	}
	async function add( list ) {
		list = list.filter(
			( b ) => ! b.disabled && ! b.classList.contains( 'is-added' )
		);
		if ( ! list.length ) {
			return true;
		}
		list.forEach( ( b ) => {
			b.disabled = true;
			text( b, 'adding' );
		} );
		const results = await addToCart(
			api,
			list.map( ( b ) => Number( b.dataset.product ) )
		);
		list.forEach( ( b, n ) => {
			b.disabled = false;
			b.classList.toggle( 'is-added', results[ n ] );
			text( b, results[ n ] ? 'added' : 'again' );
		} );
		const first = list.find( ( b, n ) => results[ n ] );
		if ( first ) {
			fly( first );
			document.body.dispatchEvent(
				new CustomEvent( 'wc-blocks_added_to_cart', {
					bubbles: true,
					detail: { preserveCartData: false },
				} )
			);
		}
		return results.every( Boolean );
	}

	root.addEventListener( 'click', ( e ) => {
		const whole = e.target.closest( '.' + P + 'whole' );
		const btn = e.target.closest( 'button.' + P + 'add' );
		if ( btn ) {
			add( [ btn ] );
		}
		if ( ! whole || whole.disabled ) {
			return;
		}
		const box = whole.parentNode;
		const caption = $( 'whole-t', whole );
		whole.disabled = true;
		caption.textContent = strings.adding;
		add( [ ...box.querySelectorAll( 'button.' + P + 'add' ) ] ).then(
			( ok ) => {
				caption.textContent = strings[ ok ? 'done' : 'failed' ];
				whole.classList.toggle( 'is-added', ok );
				whole.disabled = false;
				if ( ok && box !== whole.closest( 'aside' ) ) {
					dialog
						.querySelectorAll( '.' + P + 'mark' )
						.forEach( ( m ) => m.classList.add( 'is-added' ) );
				}
			}
		);
	} );

	names.forEach( ( b ) =>
		b.addEventListener( 'click', () => {
			go( Number( b.dataset.index ) );
			nudge();
		} )
	);
	[
		[ 'prev', -1 ],
		[ 'next', 1 ],
	].forEach( ( [ name, n ] ) =>
		$( name ).addEventListener( 'click', () => {
			step( n );
			nudge();
		} )
	);
	const shop = $( 'open' );
	if ( shop ) {
		shop.addEventListener( 'click', () => open( active ) );
	}
	if ( ringBtn ) {
		ringBtn.addEventListener( 'click', () => view( false ) );
		gridBtn.addEventListener( 'click', () => view( true ) );
	}

	root.classList.add( 'is-ready' );
	new window.ResizeObserver( measure ).observe( stage );
	if ( has( 'start-grid' ) ) {
		root.classList.add( 'is-grid' );
		if ( ringBtn ) {
			ringBtn.setAttribute( 'aria-pressed', 'false' );
			gridBtn.setAttribute( 'aria-pressed', 'true' );
		}
		hold.add( 'grid' );
	}
	measure();
	auto();
}

document
	.querySelectorAll( '.wp-block-purplish-showfloor' )
	.forEach( ( root ) => {
		if ( root.querySelector( '.' + P + 'look' ) ) {
			init( root );
		}
	} );
