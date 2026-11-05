/**
 * Turns each Story Deck on: the stories stack into a deck of cards with the
 * top card's story beside it. Visitors flick the top card away, or use the
 * buttons and arrow keys; the last card leads back to the first. Nothing
 * moves on its own. Without this script the stories stay a plain list of
 * cards. With reduced motion, dragging is off and cards switch with a fade.
 */
import { depthOf, isThrow, lean, speedOf, toss, wrap } from './deck';

const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );

// Room a thrown card keeps from the screen edge, so its corners never
// widen the page.
const EDGE = 48;
const FLY = { duration: 380, easing: 'cubic-bezier(0.2, 0.7, 0.3, 1)' };

/**
 * A card's transform at a point of a drag or a throw.
 *
 * @param {{x: number, y: number, turn: number}} at    Position and angle.
 * @param {number}                               scale Card scale.
 * @return {string} CSS transform.
 */
function transformAt( at, scale = 1 ) {
	return `translate(${ at.x }px, ${ at.y }px) rotate(${ at.turn }deg) scale(${ scale })`;
}

/**
 * Runs one deck.
 *
 * @param {HTMLElement} deck The deck.
 */
function storyDeck( deck ) {
	const holder = deck.querySelector( ':scope > .purplish-deck-stories' );
	const nav = deck.querySelector( ':scope > .purplish-deck-nav' );
	const status = deck.querySelector( ':scope > .purplish-deck-status' );
	const stories = holder
		? [ ...holder.children ].filter( ( child ) =>
				child.classList.contains( 'wp-block-group' )
			)
		: [];
	if ( stories.length < 2 || ! nav || ! status ) {
		return;
	}

	const count = stories.length;
	const counter = nav.querySelector( '.purplish-deck-count' );
	const label = deck.dataset.storyLabel || '%1$s / %2$s';
	const nameOf = ( index ) =>
		label.replace( '%1$s', index + 1 ).replace( '%2$s', count );

	// The first photo in a story is its card. A story without one gets a
	// blank card, so the deck still has something to hold.
	// Classic themes wrap a group's blocks in an inner container.
	const cards = stories.map( ( story, index ) => {
		const body =
			story.querySelector(
				':scope > .wp-block-group__inner-container'
			) || story;
		let card = body.querySelector( ':scope > .wp-block-image' );
		if ( ! card ) {
			card = document.createElement( 'div' );
			card.className = 'purplish-deck-blank';
			card.setAttribute( 'aria-hidden', 'true' );
			body.prepend( card );
		}
		card.classList.add( 'purplish-deck-card' );
		[ ...body.children ].forEach( ( child ) => {
			if ( child !== card ) {
				child.classList.add( 'purplish-deck-words' );
			}
		} );
		card.querySelectorAll( 'img, a' ).forEach( ( part ) => {
			part.draggable = false;
		} );
		story.classList.add( 'purplish-deck-story' );
		story.setAttribute( 'role', 'group' );
		story.setAttribute( 'aria-label', nameOf( index ) );
		story.tabIndex = -1;
		return card;
	} );

	let top = 0;
	let flight = null;
	let dragged = false;

	const place = () => {
		stories.forEach( ( story, index ) => {
			const depth = depthOf( index, top, count );
			story.dataset.purplishDepth = depth > 3 ? 'more' : String( depth );
			story.style.zIndex = String( count - depth );
			story.inert = 0 !== depth;
		} );
		counter.textContent = top + 1 + ' / ' + count;
	};

	const wordsOf = ( story ) =>
		[ ...story.querySelectorAll( '.purplish-deck-words' ) ]
			.map( ( words ) => words.textContent )
			.join( ' ' )
			.replace( /\s+/g, ' ' )
			.trim();

	// Space beside a card. Past the page's start edge nothing scrolls, so a
	// card can leave the screen there; on the other side it has to stop
	// short of the edge, or it would widen the page.
	const roomOf = ( card, way ) => {
		const box = card.getBoundingClientRect();
		const width = document.documentElement.clientWidth;
		const start =
			'rtl' ===
			window.getComputedStyle( document.documentElement ).direction
				? 1
				: -1;
		if ( way === start ) {
			return way < 0 ? box.right + EDGE : width - box.left + EDGE;
		}
		return way < 0 ? box.left - EDGE : width - box.right - EDGE;
	};

	// The outer side of the deck, where cards fly off and come back from,
	// unless the screen edge is too close: then the side over the story.
	const outward = () => {
		const card = cards[ top ];
		const box = card.getBoundingClientRect();
		const area = holder.getBoundingClientRect();
		const middle =
			box.left + box.width / 2 - ( area.left + area.width / 2 );
		let way = 'rtl' === window.getComputedStyle( deck ).direction ? 1 : -1;
		if ( Math.abs( middle ) > 1 ) {
			way = middle < 0 ? -1 : 1;
		}
		return roomOf( card, way ) < box.width / 2 ? -way : way;
	};

	const fadeIn = ( card ) => {
		card.classList.add( 'purplish-deck-arriving' );
		void card.offsetWidth;
		card.classList.remove( 'purplish-deck-arriving' );
	};

	const land = () => {
		if ( flight ) {
			flight.finish();
		}
	};

	/**
	 * Moves the deck one card, after a visitor's action only.
	 *
	 * @param {number}  way    1 for the next story, -1 for the previous one.
	 * @param {?Object} throw_ Where the dragged card was let go, with its speed and room.
	 */
	const go = ( way, throw_ = null ) => {
		land();
		const leaving = top;
		const hadFocus = stories[ leaving ].contains(
			deck.ownerDocument.activeElement
		);
		top = wrap( top + way, count );

		if ( reduced.matches ) {
			place();
			fadeIn( cards[ top ] );
		} else if ( way > 0 ) {
			const card = cards[ leaving ];
			const side = throw_
				? Math.sign( throw_.x ) || outward()
				: outward();
			const from = throw_ || { x: 0, y: 0, turn: 0 };
			const to = toss(
				from,
				side,
				throw_ ? throw_.room : roomOf( card, side ),
				throw_ ? throw_.speed : 0
			);
			card.classList.add( 'purplish-deck-flying' );
			card.style.removeProperty( 'transform' );
			place();
			stories[ leaving ].style.zIndex = String( count + 1 );
			flight = card.animate(
				[
					{ transform: transformAt( from ), opacity: 1 },
					{ transform: transformAt( to, 0.92 ), opacity: 0 },
				],
				FLY
			);
			flight.onfinish = () => {
				flight = null;
				stories[ leaving ].style.zIndex = '1';
				card.classList.add( 'purplish-deck-arriving' );
				card.getAnimations().forEach( ( run ) => run.cancel() );
				card.classList.remove( 'purplish-deck-flying' );
				void card.offsetWidth;
				card.classList.remove( 'purplish-deck-arriving' );
			};
		} else {
			const card = cards[ top ];
			const side = outward();
			const from = toss( { x: 0, y: 0 }, side, roomOf( card, side ) );
			card.classList.add( 'purplish-deck-flying' );
			place();
			flight = card.animate(
				[
					{
						transform: transformAt( from, 0.92 ),
						opacity: 0,
						offset: 0,
					},
				],
				FLY
			);
			flight.onfinish = () => {
				flight = null;
				card.classList.remove( 'purplish-deck-flying' );
			};
		}

		if ( hadFocus ) {
			stories[ top ].focus( { preventScroll: true } );
		}
		status.textContent = nameOf( top ) + '. ' + wordsOf( stories[ top ] );
	};

	place();
	deck.classList.add( 'purplish-deck-on' );
	deck.setAttribute(
		'aria-roledescription',
		deck.dataset.roledescription || 'carousel'
	);
	nav.hidden = false;

	nav.querySelector( '.purplish-deck-prev' ).addEventListener( 'click', () =>
		go( -1 )
	);
	nav.querySelector( '.purplish-deck-next' ).addEventListener( 'click', () =>
		go( 1 )
	);

	deck.addEventListener( 'keydown', ( event ) => {
		if (
			event.altKey ||
			event.ctrlKey ||
			event.metaKey ||
			event.shiftKey ||
			event.target.closest( 'input, textarea, select, [contenteditable]' )
		) {
			return;
		}
		const rtl = 'rtl' === window.getComputedStyle( deck ).direction;
		if ( 'ArrowRight' === event.key ) {
			go( rtl ? -1 : 1 );
		} else if ( 'ArrowLeft' === event.key ) {
			go( rtl ? 1 : -1 );
		} else {
			return;
		}
		event.preventDefault();
	} );

	// Dragging the top card: it follows the pointer and leans, then flies
	// off when thrown or snaps back after a short drag.
	let drag = null;

	holder.addEventListener( 'pointerdown', ( event ) => {
		const card = cards[ top ];
		dragged = false;
		if (
			reduced.matches ||
			! event.isPrimary ||
			event.button > 0 ||
			! card.contains( event.target )
		) {
			return;
		}
		land();
		drag = {
			card,
			id: event.pointerId,
			x: event.clientX,
			y: event.clientY,
			at: { x: 0, y: 0, turn: 0 },
			left: roomOf( card, -1 ),
			right: roomOf( card, 1 ),
			samples: [ { x: event.clientX, t: event.timeStamp } ],
		};
		card.setPointerCapture( event.pointerId );
		card.classList.add( 'purplish-deck-dragging' );
	} );

	holder.addEventListener( 'pointermove', ( event ) => {
		if ( ! drag || event.pointerId !== drag.id ) {
			return;
		}
		const dx = event.clientX - drag.x;
		const x = Math.max(
			-Math.max( drag.left, 0 ),
			Math.min( Math.max( drag.right, 0 ), dx )
		);
		drag.at = { x, y: event.clientY - drag.y, turn: lean( x ) };
		drag.samples = [
			...drag.samples.slice( -5 ),
			{ x: event.clientX, t: event.timeStamp },
		];
		if ( Math.abs( dx ) > 4 ) {
			dragged = true;
		}
		drag.card.style.transform = transformAt( drag.at );
	} );

	const release = ( event ) => {
		if ( ! drag || event.pointerId !== drag.id ) {
			return;
		}
		const { card, at, left, right, samples } = drag;
		drag = null;
		card.classList.remove( 'purplish-deck-dragging' );
		const speed = speedOf( samples );
		if (
			'pointercancel' !== event.type &&
			isThrow( at.x, speed, card.offsetWidth )
		) {
			go( 1, { ...at, speed, room: at.x < 0 ? left : right } );
		} else {
			card.style.removeProperty( 'transform' );
		}
	};

	holder.addEventListener( 'pointerup', release );
	holder.addEventListener( 'pointercancel', release );

	// A drag that ends on a linked photo isn't a click.
	holder.addEventListener(
		'click',
		( event ) => {
			if ( dragged ) {
				dragged = false;
				event.preventDefault();
				event.stopPropagation();
			}
		},
		true
	);
}

document
	.querySelectorAll( '.wp-block-purplish-story-deck' )
	.forEach( storyDeck );
