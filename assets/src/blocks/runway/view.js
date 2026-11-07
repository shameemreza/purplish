/**
 * Turns each Runway on: the product photos stand in one line that fades
 * into the distance, the intro sits over it, and the first Product
 * Collection becomes a rail of cards that follows the look at the front.
 * Visitors walk the line by dragging, swiping, a sideways wheel, the arrow
 * keys or the buttons. Nothing moves on its own. Without this script the
 * collection stays as WooCommerce draws it. With reduced motion there's no
 * blur and no easing: each move is one look, with a short fade.
 */
import {
	blend,
	ease,
	nearest,
	offset,
	railAt,
	settle,
	slot,
	wrap,
} from './timing';
import { flyToCart } from '../../shared/fly';

const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );

// At or under this block width the rail moves under the stage as one row
// (purplish-runway-narrow in style.css).
const PHONE = 820;

// A look's width to its height, and its box against the look size, so the
// one leaving past the front can grow without a bigger image box.
const RATIO = 0.75;
const BOX = 1.2;

/**
 * Runs one runway.
 *
 * @param {HTMLElement} block The runway.
 */
function runway( block ) {
	const stage = block.querySelector( ':scope > .purplish-runway-stage' );
	const content = block.querySelector( ':scope > .purplish-runway-content' );
	const rail =
		content &&
		content.querySelector(
			':scope > .wp-block-woocommerce-product-collection'
		);
	const list = rail && rail.querySelector( '.wc-block-product-template' );
	if ( ! stage || ! list ) {
		return;
	}

	const cards = [ ...list.children ].filter( ( child ) =>
		child.classList.contains( 'wc-block-product' )
	);
	const ids = cards.map( ( card ) => {
		const found = card.className.match( /\bpost-(\d+)\b/ );
		return found ? found[ 1 ] : '';
	} );
	const looks = [
		...stage.querySelectorAll( '.purplish-runway-look' ),
	].filter( ( look ) => {
		if ( ids.includes( look.dataset.product ) ) {
			return true;
		}
		look.remove();
		return false;
	} );
	if ( looks.length < 2 ) {
		return;
	}

	const count = looks.length;
	const total = cards.length;
	const cardOf = looks.map( ( look ) => ids.indexOf( look.dataset.product ) );
	const line = stage.querySelector( '.purplish-runway-line' );
	const roll = stage.querySelector( '.purplish-runway-roll' );
	const shop = stage.querySelector( '.purplish-runway-shop' );
	const tag = stage.querySelector( '.purplish-runway-tag' );
	const status = stage.querySelector( '.purplish-runway-status' );
	const label = block.dataset.lookLabel || '%1$s / %2$s';
	const shopLabel = block.dataset.shopLabel || '%s';
	const nameOf = ( index ) =>
		label.replace( '%1$s', index + 1 ).replace( '%2$s', count );

	// The heading and line above the collection become the intro over the
	// line of looks.
	const intro = document.createElement( 'div' );
	intro.className = 'purplish-runway-intro';
	[ ...content.children ].forEach( ( child ) => {
		if (
			! child.classList.contains(
				'wp-block-woocommerce-product-collection'
			)
		) {
			intro.append( child );
		}
	} );
	stage.prepend( intro );

	for ( let index = 0; index <= count; index++ ) {
		const number = document.createElement( 'b' );
		number.textContent = String( ( index % count ) + 1 ).padStart( 2, '0' );
		roll.append( number );
	}

	const ring = document.createElement( 'div' );
	ring.className = 'purplish-runway-ring';
	ring.setAttribute( 'aria-hidden', 'true' );
	stage.append( ring );

	rail.classList.add( 'purplish-runway-rail' );
	block.classList.add( 'purplish-runway-on' );
	stage.hidden = false;

	let position = 0;
	let target = 0;
	let frame = 0;
	let last = 0;
	let width = 0;
	let height = 0;
	let size = 0;
	let floor = 0;
	let spread = 0;
	let shift = 0;
	let phone = false;
	let way = 1;
	let along = 0;
	let reach = 0;
	let loops = false;
	let shown = -1;
	let told = 0;
	let nudged = 0;
	let acted = false;
	let dirty = true;
	let drag = null;
	let mouse = null;
	const ringAt = { x: 0, y: 0 };
	const looked = looks.map( () => ( { filter: '', opacity: '' } ) );

	const measure = () => {
		phone = block.clientWidth <= PHONE;
		block.classList.toggle( 'purplish-runway-narrow', phone );
		way = 'rtl' === window.getComputedStyle( block ).direction ? -1 : 1;
		width = stage.clientWidth;
		height = stage.clientHeight;
		spread = width * ( phone ? 0.9 : 1 );
		floor = height;
		size = Math.min( height, width * 1.25 );
		shift = 0;
		stage.style.removeProperty( '--purplish-runway-room' );
		// A phone has no room beside the intro and the tag, so the line
		// stands between them, and moves over until the front look fits.
		// Wider, the intro and the tag wrap in the room before the front
		// look.
		if ( phone ) {
			const box = stage.getBoundingClientRect();
			const gap = 16;
			const top = intro.getBoundingClientRect().bottom - box.top + gap;
			floor = Math.min(
				height,
				tag.getBoundingClientRect().top - box.top - gap
			);
			const front = blend( 0 );
			size = Math.min(
				size,
				( front.foot * floor - top ) / front.height
			);
			shift = Math.min(
				0,
				width -
					gap -
					( front.x * spread + ( front.height * size * RATIO ) / 2 )
			);
		} else {
			const front = blend( 0 );
			const edge = parseFloat(
				window.getComputedStyle( intro ).insetInlineStart
			);
			const room =
				front.x * spread -
				( front.height * size * RATIO ) / 2 -
				edge -
				24;
			stage.style.setProperty(
				'--purplish-runway-room',
				Math.max( 160, room ) + 'px'
			);
		}
		looks.forEach( ( look ) => {
			look.style.width = size * BOX * RATIO + 'px';
			look.style.height = size * BOX + 'px';
			look.style.transformOrigin = '0 0';
		} );
		reach = phone ? rail.clientWidth : rail.clientHeight;
		const rem = parseFloat(
			window.getComputedStyle( document.documentElement ).fontSize
		);
		along = phone ? 11 * rem : Math.max( reach / 3, 12.5 * rem );
		loops = along * total >= reach + along;
		rail.style.setProperty( '--purplish-runway-slot', along + 'px' );
		dirty = true;
	};

	const shimmer = ( card ) => {
		const button = card && card.querySelector( '.wp-block-button__link' );
		if ( ! button || reduced.matches ) {
			return;
		}
		button.classList.remove( 'purplish-runway-shimmer' );
		void button.offsetWidth;
		button.classList.add( 'purplish-runway-shimmer' );
	};

	const paint = () => {
		const front = blend( 0 );
		const frontLeft =
			front.x * spread - ( front.height * size * RATIO ) / 2 + shift;

		looks.forEach( ( look, index ) => {
			const at = offset( index, position, count );
			const place = blend( at );
			const tall = place.height * size;
			const wide = tall * RATIO;
			const scale = tall / ( size * BOX );
			let left = place.x * spread - wide / 2 + shift;
			// A framed photo is wide, so the leaving look slides on until
			// it has cleared the stage before it wraps to the back.
			if ( at < 0 ) {
				left = Math.max(
					left,
					frontLeft + ( width - frontLeft ) * -at
				);
			}
			const x = way > 0 ? left : width - left - wide;
			const y = place.foot * floor - tall;
			look.style.transform = `translate3d(${ x.toFixed(
				1
			) }px, ${ y.toFixed( 1 ) }px, 0) scale(${ scale.toFixed( 4 ) })`;
			look.style.zIndex = String( 100 - Math.round( at * 10 ) );

			const blur = reduced.matches ? 0 : place.blur / scale;
			const filter =
				blur < 0.2 ? 'none' : `blur(${ blur.toFixed( 1 ) }px)`;
			const opacity = place.opacity.toFixed( 3 );
			if ( filter !== looked[ index ].filter ) {
				looked[ index ].filter = filter;
				look.style.filter = filter;
			}
			if ( opacity !== looked[ index ].opacity ) {
				looked[ index ].opacity = opacity;
				look.style.opacity = opacity;
			}
		} );

		const at = railAt( position, cardOf, total );
		const most = Math.max( 0, along * total - reach );
		cards.forEach( ( card, index ) => {
			const spot = loops
				? slot( index, at, along, total )
				: index * along - Math.min( most, wrap( at, total ) * along );
			card.style.transform = phone
				? `translate3d(${ ( spot * way ).toFixed( 1 ) }px, 0, 0)`
				: `translate3d(0, ${ spot.toFixed( 1 ) }px, 0)`;
		} );

		const digit = roll.firstElementChild.offsetHeight;
		roll.style.transform = `translate3d(0, ${ (
			-wrap( position, count ) * digit
		).toFixed( 1 ) }px, 0)`;

		const now = wrap( Math.round( position ), count );
		if ( now !== shown ) {
			shown = now;
			shop.href = looks[ now ].dataset.url;
			shop.setAttribute(
				'aria-label',
				shopLabel.replace( '%s', looks[ now ].dataset.name )
			);
			stage.setAttribute( 'aria-label', nameOf( now ) );
		}
	};

	// Once the line stands still after a visitor's move: say which look is
	// at the front, and sweep a shine over its product's button once.
	const settled = () => {
		if ( ! acted ) {
			return;
		}
		if ( shown !== told ) {
			told = shown;
			status.textContent =
				nameOf( shown ) + '. ' + looks[ shown ].dataset.name;
		}
		if ( shown !== nudged ) {
			nudged = shown;
			shimmer( cards[ cardOf[ shown ] ] );
		}
	};

	const tick = ( now ) => {
		frame = 0;
		const ms = last ? Math.min( 50, now - last ) : 16;
		last = now;
		const was = position;
		position = reduced.matches ? target : ease( position, target, ms );
		if ( position !== was || dirty ) {
			dirty = false;
			paint();
		}
		if ( position === target && ! drag ) {
			settled();
		}

		let following = false;
		if ( mouse ) {
			ringAt.x += ( mouse.x - ringAt.x ) * 0.32;
			ringAt.y += ( mouse.y - ringAt.y ) * 0.32;
			ring.style.transform = `translate3d(${ ringAt.x.toFixed(
				1
			) }px, ${ ringAt.y.toFixed( 1 ) }px, 0) translate(-50%, -50%)`;
			following =
				Math.abs( mouse.x - ringAt.x ) +
					Math.abs( mouse.y - ringAt.y ) >
				0.5;
		}

		if ( position !== target || following ) {
			frame = window.requestAnimationFrame( tick );
		} else {
			last = 0;
		}
	};

	const wake = () => {
		if ( ! frame ) {
			frame = window.requestAnimationFrame( tick );
		}
	};

	const go = ( next ) => {
		acted = true;
		target = next;
		if ( reduced.matches ) {
			line.animate( [ { opacity: 0.2 }, { opacity: 1 } ], {
				duration: 200,
				easing: 'ease-out',
			} );
		}
		wake();
	};

	const step = ( by ) => go( Math.round( target ) + by );

	stage
		.querySelector( '.purplish-runway-prev' )
		.addEventListener( 'click', () => step( -1 ) );
	stage
		.querySelector( '.purplish-runway-next' )
		.addEventListener( 'click', () => step( 1 ) );

	stage.addEventListener( 'keydown', ( event ) => {
		if ( event.altKey || event.ctrlKey || event.metaKey ) {
			return;
		}
		const forward = way > 0 ? 'ArrowRight' : 'ArrowLeft';
		const back = way > 0 ? 'ArrowLeft' : 'ArrowRight';
		if ( forward === event.key || 'ArrowDown' === event.key ) {
			event.preventDefault();
			step( 1 );
		} else if ( back === event.key || 'ArrowUp' === event.key ) {
			event.preventDefault();
			step( -1 );
		}
	} );

	const pull = () => Math.max( 150, width * 0.2 );

	// A tap on a look further back brings it to the front.
	const pick = ( event ) => {
		const hit = document
			.elementsFromPoint( event.clientX, event.clientY )
			.find( ( part ) => looks.includes( part ) );
		if ( ! hit ) {
			return;
		}
		const ahead = Math.round(
			offset( looks.indexOf( hit ), Math.round( target ), count )
		);
		if ( ahead > 0 ) {
			go( Math.round( target ) + ahead );
		}
	};

	stage.addEventListener( 'pointerdown', ( event ) => {
		if ( 0 !== event.button || event.target.closest( 'a, button' ) ) {
			return;
		}
		drag = { x: event.clientX, from: target, moved: 0 };
		stage.setPointerCapture( event.pointerId );
		ring.classList.add( 'purplish-runway-dragging' );
	} );

	stage.addEventListener( 'pointermove', ( event ) => {
		const pointing =
			'mouse' === event.pointerType &&
			! reduced.matches &&
			( drag || ! event.target.closest( 'a, button' ) );
		stage.classList.toggle( 'purplish-runway-pointing', !! pointing );
		if ( pointing ) {
			const box = stage.getBoundingClientRect();
			const at = {
				x: event.clientX - box.left,
				y: event.clientY - box.top,
			};
			if ( ! mouse ) {
				ringAt.x = at.x;
				ringAt.y = at.y;
			}
			mouse = at;
		} else {
			mouse = null;
		}

		if ( drag ) {
			const moved = ( event.clientX - drag.x ) * way;
			drag.moved = Math.max( drag.moved, Math.abs( moved ) );
			if ( ! reduced.matches ) {
				acted = true;
				target = drag.from + moved / pull();
			}
		}
		wake();
	} );

	const release = ( event ) => {
		if ( ! drag ) {
			return;
		}
		const done = drag;
		drag = null;
		ring.classList.remove( 'purplish-runway-dragging' );
		const moved = ( event.clientX - done.x ) * way;
		if ( done.moved < 6 ) {
			target = done.from;
			pick( event );
		} else if ( reduced.matches ) {
			if ( Math.abs( moved ) > 40 ) {
				step( moved > 0 ? 1 : -1 );
			}
		} else {
			go( Math.round( target ) );
		}
		wake();
	};
	stage.addEventListener( 'pointerup', release );
	stage.addEventListener( 'pointercancel', release );

	stage.addEventListener( 'pointerleave', () => {
		mouse = null;
		stage.classList.remove( 'purplish-runway-pointing' );
	} );

	// Only a sideways wheel walks the line (trackpad swipes, Shift+wheel),
	// so a normal wheel always scrolls the page past the runway.
	let wheeling = 0;
	let wheelFrom = 0;
	stage.addEventListener(
		'wheel',
		( event ) => {
			let delta = 0;
			if ( Math.abs( event.deltaX ) > Math.abs( event.deltaY ) ) {
				delta = event.deltaX;
			} else if ( event.shiftKey ) {
				delta = event.deltaY;
			}
			if ( ! delta ) {
				return;
			}
			event.preventDefault();
			delta *= way;
			if ( ! wheeling ) {
				wheelFrom = Math.round( target );
				if ( reduced.matches ) {
					step( Math.sign( delta ) );
				}
			}
			if ( ! reduced.matches ) {
				acted = true;
				target += Math.max( -120, Math.min( 120, delta ) ) / 420;
				wake();
			}
			window.clearTimeout( wheeling );
			wheeling = window.setTimeout( () => {
				wheeling = 0;
				if ( ! reduced.matches ) {
					go( settle( target, wheelFrom ) );
				}
			}, 150 );
		},
		{ passive: false }
	);

	// Focus in a card brings its product's first look to the front, which
	// also moves the card into view.
	rail.addEventListener( 'focusin', ( event ) => {
		const index = cards.indexOf(
			event.target.closest( '.wc-block-product' )
		);
		const first = cardOf.indexOf( index );
		if ( first < 0 || cardOf[ shown ] === index ) {
			return;
		}
		go( nearest( first, target, count ) );
	} );

	if ( block.classList.contains( 'purplish-runway-fly-on' ) ) {
		rail.addEventListener( 'click', ( event ) => {
			const button = event.target.closest(
				'button.add_to_cart_button, button.wc-block-components-product-button__button'
			);
			const card = button && button.closest( '.wc-block-product' );
			const photo = card && card.querySelector( 'img' );
			const cart = [
				...document.querySelectorAll( '.wc-block-mini-cart__button' ),
			].find( ( part ) => part.getClientRects().length );
			if ( photo && cart && ! reduced.matches ) {
				flyToCart( photo, cart, way, 'purplish-runway-fly' );
			}
		} );
	}

	measure();
	paint();
	dirty = false;
	new window.ResizeObserver( () => {
		measure();
		wake();
	} ).observe( block );
}

if ( 'ResizeObserver' in window ) {
	document.querySelectorAll( '.wp-block-purplish-runway' ).forEach( runway );
}
