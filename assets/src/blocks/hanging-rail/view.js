/**
 * Turns each Hanging Rail on: the section sticks while the page scrolls
 * through it, the row slides along the rope with the scroll, and each card
 * swings on its clip when the row speeds up or stops. Without this script,
 * with reduced motion, and where the section can't stick, the row stays a
 * plain strip that scrolls sideways.
 */
import { lean, progress, runLength, step, stiffness } from './swing';

const ROW =
	':scope > .purplish-rail-stage > :nth-child(1 of .wp-block-woocommerce-product-collection)';

/**
 * The scroll length named in a rail's classes.
 *
 * @param {HTMLElement} rail The rail.
 * @return {string} short, medium or long.
 */
function lengthOf( rail ) {
	const prefix = 'purplish-rail-length-';
	const found = [ ...rail.classList ].find( ( name ) =>
		name.startsWith( prefix )
	);
	return found ? found.slice( prefix.length ) : 'medium';
}

/**
 * Whether the section can stick to the screen. A wrapper that hides or
 * scrolls its overflow (Storefront's page wrapper hides it) becomes what
 * the section sticks to, so it would scroll away with the page while the
 * row slides out of sight. The body passes its overflow on to the screen,
 * so it doesn't count.
 *
 * @param {HTMLElement} rail The rail.
 * @return {boolean} True when nothing between the rail and the body clips.
 */
function canStick( rail ) {
	for (
		let parent = rail.parentElement;
		parent && parent !== document.body;
		parent = parent.parentElement
	) {
		const style = window.getComputedStyle( parent );
		if (
			[ style.overflowX, style.overflowY ].some(
				( value ) => 'visible' !== value && 'clip' !== value
			)
		) {
			return false;
		}
	}
	return true;
}

/**
 * Runs one rail. Nothing happens while it's off screen, and the frame loop
 * stops once the row and every card are still.
 *
 * @param {HTMLElement} rail The rail.
 */
function hangingRail( rail ) {
	const row = rail.querySelector( ROW );
	const list = row && row.querySelector( '.wc-block-product-template' );
	const cards = list ? [ ...list.children ] : [];
	if ( ! cards.length || ! canStick( rail ) ) {
		return;
	}

	const stage = row.parentElement;
	const length = lengthOf( rail );
	const springs = cards.map( ( _, index ) => stiffness( index ) );
	let swings = cards.map( () => ( { angle: 0, speed: 0, still: true } ) );
	let hidden = 0;
	let run = 0;
	let inset = 0;
	let stick = 0;
	let way = -1;
	let seen = false;
	let frame = 0;
	let last = 0;
	let x = null;
	let speed = 0;

	const measure = () => {
		rail.classList.add( 'purplish-rail-moving' );
		hidden = list.offsetWidth - row.clientWidth;
		run = runLength( hidden, length, window.innerHeight );
		if ( ! run ) {
			rail.classList.remove( 'purplish-rail-moving' );
			rail.style.removeProperty( '--purplish-rail-x' );
			return;
		}
		const style = window.getComputedStyle( rail );
		inset =
			parseFloat( style.paddingTop ) + parseFloat( style.borderTopWidth );
		stick = parseFloat( window.getComputedStyle( stage ).top ) || 0;
		way = 'rtl' === window.getComputedStyle( list ).direction ? 1 : -1;
		rail.style.setProperty( '--purplish-rail-run', run + 'px' );
	};

	const scrolled = () => stick - ( rail.getBoundingClientRect().top + inset );

	const tick = ( now ) => {
		frame = 0;
		const dt = last ? ( now - last ) / 1000 : 1 / 60;
		last = now;

		const next = run ? progress( scrolled(), run ) * hidden * way : 0;
		const moved = null === x ? 0 : next - x;
		x = next;
		rail.style.setProperty( '--purplish-rail-x', x.toFixed( 1 ) + 'px' );

		speed = speed * 0.6 + ( moved / Math.max( dt, 1 / 240 ) ) * 0.4;
		if ( Math.abs( speed ) < 1 ) {
			speed = 0;
		}

		const target = lean( speed );
		swings = swings.map( ( swing, index ) =>
			step( swing, target, dt, springs[ index ] )
		);
		cards.forEach( ( card, index ) =>
			card.style.setProperty(
				'--purplish-swing',
				swings[ index ].angle.toFixed( 2 ) + 'deg'
			)
		);

		if ( speed || swings.some( ( swing ) => ! swing.still ) ) {
			frame = window.requestAnimationFrame( tick );
		} else {
			last = 0;
		}
	};

	const wake = () => {
		if ( seen && ! frame ) {
			frame = window.requestAnimationFrame( tick );
		}
	};

	measure();
	tick( 0 );

	new window.IntersectionObserver( ( entries ) => {
		seen = entries[ entries.length - 1 ].isIntersecting;
		wake();
	} ).observe( rail );

	window.addEventListener( 'scroll', wake, { passive: true } );
	window.addEventListener( 'resize', () => {
		measure();
		wake();
	} );

	// A focused card that sits off the visible part of the rail: scroll the
	// page to the point where the row brings it to the middle.
	rail.addEventListener( 'focusin', ( event ) => {
		const card = event.target.closest( '.wc-block-product' );
		if ( ! run || ! card || card.parentElement !== list ) {
			return;
		}
		const box = card.getBoundingClientRect();
		const view = row.getBoundingClientRect();
		if ( box.left >= view.left && box.right <= view.right ) {
			return;
		}
		const start =
			way < 0
				? card.offsetLeft
				: list.offsetWidth - card.offsetLeft - card.offsetWidth;
		const along = progress(
			start + card.offsetWidth / 2 - row.clientWidth / 2,
			hidden
		);
		const top =
			rail.getBoundingClientRect().top + window.scrollY + inset - stick;
		window.scrollTo( { top: top + along * run, behavior: 'instant' } );
	} );
}

if (
	! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches &&
	'IntersectionObserver' in window
) {
	document
		.querySelectorAll( '.wp-block-purplish-hanging-rail' )
		.forEach( hangingRail );
}
