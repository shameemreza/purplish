/**
 * Mouse drag with momentum for a carousel strip. Touch scrolling already
 * glides, so only the mouse is handled: the strip follows the drag, keeps
 * the speed it was let go at and eases to a stop, then the carousel's own
 * snap takes over again.
 */

/**
 * How much speed is kept per millisecond of the glide.
 */
const FRICTION = 0.996;

/**
 * Below this speed, in pixels per millisecond, the glide stops.
 */
const REST = 0.02;

/**
 * The speed after `time` milliseconds of glide.
 *
 * @param {number} speed Pixels per millisecond.
 * @param {number} time  Milliseconds since the last frame.
 * @return {number} The new speed, or 0 once it is too slow to see.
 */
export function slowDown( speed, time ) {
	const next = speed * Math.pow( FRICTION, time );
	return Math.abs( next ) < REST ? 0 : next;
}

/**
 * Lets the mouse drag and throw any strip matching `selector`, including
 * strips added after load.
 *
 * @param {string}  selector The strips.
 * @param {boolean} glide    Whether a throw keeps sliding; off for reduced motion.
 */
export function throwStrips( selector, glide ) {
	let drag = null;
	let frame = 0;

	const stop = ( strip ) => {
		window.cancelAnimationFrame( frame );
		frame = 0;
		strip.classList.remove( 'purplish-dragging' );
	};

	const slide = ( strip, speed, then ) => {
		frame = window.requestAnimationFrame( ( now ) => {
			const before = strip.scrollLeft;
			strip.scrollLeft -= speed * ( now - then );
			const next = slowDown( speed, now - then );
			if ( next && strip.scrollLeft !== before ) {
				slide( strip, next, now );
			} else {
				stop( strip );
			}
		} );
	};

	document.addEventListener( 'pointerdown', ( event ) => {
		const strip = event.target.closest?.( selector );
		if (
			! strip ||
			'mouse' !== event.pointerType ||
			0 !== event.button ||
			event.target.closest( 'button, input, select, textarea' )
		) {
			return;
		}
		stop( strip );
		drag = {
			strip,
			from: event.clientX,
			left: strip.scrollLeft,
			x: event.clientX,
			at: event.timeStamp,
			speed: 0,
			moved: false,
		};
	} );

	document.addEventListener( 'pointermove', ( event ) => {
		if ( ! drag ) {
			return;
		}
		const time = event.timeStamp - drag.at;
		if ( ! drag.moved && Math.abs( event.clientX - drag.from ) > 6 ) {
			drag.moved = true;
			drag.strip.classList.add( 'purplish-dragging' );
		}
		if ( ! drag.moved ) {
			return;
		}
		if ( time > 0 ) {
			drag.speed =
				( 0.8 * ( event.clientX - drag.x ) ) / time + 0.2 * drag.speed;
		}
		drag.x = event.clientX;
		drag.at = event.timeStamp;
		drag.strip.scrollLeft = drag.left - ( event.clientX - drag.from );
	} );

	document.addEventListener( 'pointerup', ( event ) => {
		if ( ! drag ) {
			return;
		}
		const { strip, moved, speed, at } = drag;
		drag = null;
		if ( ! moved ) {
			return;
		}
		// A drag that ends over the strip also clicks what's under the mouse.
		const swallow = ( click ) => {
			click.preventDefault();
			click.stopPropagation();
		};
		strip.addEventListener( 'click', swallow, {
			capture: true,
			once: true,
		} );
		window.setTimeout( () =>
			strip.removeEventListener( 'click', swallow, { capture: true } )
		);
		if ( glide && event.timeStamp - at < 80 ) {
			slide( strip, speed, window.performance.now() );
		} else {
			stop( strip );
		}
	} );

	// A wheel turn takes over from a glide.
	document.addEventListener(
		'wheel',
		( event ) => {
			const strip = event.target.closest?.( selector );
			if ( strip && frame ) {
				stop( strip );
			}
		},
		{ passive: true }
	);

	document.addEventListener(
		'dragstart',
		( event ) => {
			if ( event.target.closest?.( selector ) ) {
				event.preventDefault();
			}
		},
		true
	);
}
