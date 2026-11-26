/**
 * The log in and register switch: Tabs, Flip and Slide, with Slide's
 * motions. Without the script, both forms sit side by side as WooCommerce
 * prints them.
 */
import { __ } from '@wordpress/i18n';

import { el } from './dom';

const EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';
const NAMES = [ 'login', 'register' ];

/**
 * The words on Slide's panel, one side for each open form.
 *
 * @return {Array<{when: string, to: string, title: string, text: string, button: string}>} Both sides.
 */
function slideCopy() {
	return [
		{
			when: 'login',
			to: 'register',
			title: __( 'New here?', 'purplish' ),
			text: __(
				'Create an account to track orders and check out faster.',
				'purplish'
			),
			button: __( 'Create an account', 'purplish' ),
		},
		{
			when: 'register',
			to: 'login',
			title: __( 'Welcome back', 'purplish' ),
			text: __(
				'Log in to see your orders and saved addresses.',
				'purplish'
			),
			button: __( 'Log in', 'purplish' ),
		},
	];
}

/**
 * Sets up the switch on one element.
 *
 * @param {HTMLElement} group The element with the option classes.
 * @param {number}      index Its place on the page, for unique IDs.
 */
export function switcher( group, index ) {
	const doc = group.ownerDocument;
	const win = doc.defaultView;
	const has = ( name ) =>
		group.classList.contains( 'purplish-account-' + name );
	const wrap = group.querySelector( '.woocommerce > .u-columns' );
	const cols = wrap
		? wrap.querySelectorAll( ':scope > .u-column1, :scope > .u-column2' )
		: [];
	if ( 2 !== cols.length ) {
		return;
	}
	const reduce = win.matchMedia( '(prefers-reduced-motion: reduce)' );
	const panels = {};
	const tabs = {};
	const sides = {};
	let current = null;
	let lastPoint = null;

	const tablist = el( doc, 'div', {
		role: 'tablist',
		class: 'pa-tabs',
		'aria-label': __( 'Account', 'purplish' ),
	} );

	NAMES.forEach( ( name, i ) => {
		const col = cols[ i ];
		const heading = col.querySelector( 'h2' );
		const tabId = 'pa-tab-' + index + '-' + name;
		const panelId = 'pa-panel-' + index + '-' + name;
		const tab = el(
			doc,
			'button',
			{
				type: 'button',
				role: 'tab',
				id: tabId,
				'aria-controls': panelId,
			},
			heading ? heading.textContent.trim() : name
		);
		col.id = panelId;
		col.setAttribute( 'role', 'tabpanel' );
		col.setAttribute( 'aria-labelledby', tabId );
		tab.addEventListener( 'click', () => set( name, false ) );
		tablist.appendChild( tab );
		tabs[ name ] = tab;
		panels[ name ] = col;
	} );

	tablist.addEventListener( 'keydown', ( e ) => {
		const keys = { ArrowLeft: 0, ArrowRight: 1, Home: 0, End: 1 };
		if ( ! ( e.key in keys ) ) {
			return;
		}
		e.preventDefault();
		const arrow = 'ArrowLeft' === e.key || 'ArrowRight' === e.key;
		const other = 'login' === current ? 'register' : 'login';
		const name = arrow ? other : NAMES[ keys[ e.key ] ];
		set( name, false );
		tabs[ name ].focus();
	} );

	wrap.parentNode.insertBefore( tablist, wrap );

	// The panel that slides over the closed form, from 782px.
	let slide = null;
	if ( has( 'switch-slide' ) ) {
		slide = el( doc, 'div', { class: 'pa-slide' } );
		slideCopy().forEach( ( copy ) => {
			const side = el( doc, 'div', {
				class: 'pa-slide-side pa-when-' + copy.when,
			} );
			const button = el( doc, 'button', { type: 'button' }, copy.button );
			side.appendChild( el( doc, 'h3', {}, copy.title ) );
			side.appendChild( el( doc, 'p', {}, copy.text ) );
			side.appendChild( button );
			button.addEventListener( 'click', () => set( copy.to, true ) );
			slide.appendChild( side );
			sides[ copy.when ] = side;
		} );
		// Shutter's bands each take their turn from --i.
		const pieces = ( cls, count, numbered ) => {
			const box = el( doc, 'div', { class: cls, 'aria-hidden': 'true' } );
			for ( let i = 0; i < count; i++ ) {
				const piece = doc.createElement( 'i' );
				if ( numbered ) {
					piece.style.setProperty( '--i', i );
				}
				box.appendChild( piece );
			}
			slide.appendChild( box );
		};
		if ( has( 'slide-blinds' ) ) {
			pieces( 'pa-strips', 8, false );
		}
		if ( has( 'slide-shutter' ) ) {
			pieces( 'pa-bands', 6, true );
		}
		if ( has( 'slide-mosaic' ) ) {
			pieces( 'pa-tiles', 48, false );
		}
		wrap.appendChild( slide );
	}
	let flood = null;
	if ( slide && has( 'slide-flood' ) ) {
		flood = el( doc, 'div', { class: 'pa-flood', 'aria-hidden': 'true' } );
		wrap.appendChild( flood );
	}
	group.addEventListener( 'pointerdown', ( e ) => {
		lastPoint = { x: e.clientX, y: e.clientY };
	} );

	const visible = ( node ) =>
		node && 'none' !== win.getComputedStyle( node ).display;

	// Tabs: the box eases to the new form's height while the new form
	// arrives from the side it lives on.
	function swapTabs( name, done ) {
		const from = wrap.offsetHeight;
		done();
		if ( reduce.matches ) {
			panels[ name ].animate( [ { opacity: 0 }, { opacity: 1 } ], {
				duration: 200,
				easing: 'ease-out',
			} );
			return;
		}
		const to = wrap.offsetHeight;
		const dir = 'register' === name ? 1 : -1;
		wrap.animate( [ { height: from + 'px' }, { height: to + 'px' } ], {
			duration: 480,
			easing: EASE,
		} );
		panels[ name ].animate(
			[
				{
					opacity: 0,
					transform: 'translateX(' + 32 * dir + 'px)',
					filter: 'blur(6px)',
				},
				{ opacity: 1, transform: 'none', filter: 'blur(0)' },
			],
			{ duration: 480, easing: EASE }
		);
	}

	// Flood: a circle of the effect color grows from the press over the
	// whole card, the forms swap under it, then it drains into the new half.
	function pour( name, done ) {
		const r = wrap.getBoundingClientRect();
		const reach = Math.hypot( r.width, r.height );
		const inside =
			lastPoint && lastPoint.x >= r.left && lastPoint.x <= r.right;
		const o = inside
			? { x: lastPoint.x - r.left, y: lastPoint.y - r.top }
			: {
					x: r.width * ( 'register' === name ? 0.75 : 0.25 ),
					y: r.height / 2,
				};
		const c = {
			x: r.width * ( 'register' === name ? 0.25 : 0.75 ),
			y: r.height / 2,
		};
		const at = ( rad, p ) =>
			'circle(' + rad + 'px at ' + p.x + 'px ' + p.y + 'px)';
		flood.getAnimations().forEach( ( a ) => a.cancel() );
		flood
			.animate(
				[ { clipPath: at( 0, o ) }, { clipPath: at( reach, o ) } ],
				{
					duration: 460,
					easing: 'cubic-bezier(0.7, 0, 0.84, 0)',
					fill: 'forwards',
				}
			)
			.finished.then( () => {
				done();
				flood.animate(
					[ { clipPath: at( reach, c ) }, { clipPath: at( 0, c ) } ],
					{ duration: 700, easing: EASE, fill: 'forwards' }
				);
			} )
			.catch( () => {} );
	}

	// Blinds: each strip gets its turn and the side it grows from.
	function blinds( name ) {
		slide.querySelectorAll( '.pa-strips i' ).forEach( ( strip, i ) => {
			const left = i < 4;
			let step = left ? i : i - 4;
			if ( 'register' === name ) {
				step = left ? 3 - i : 7 - i;
			}
			strip.style.setProperty( '--d', step * 0.055 + 's' );
			strip.style.setProperty( '--o', left ? 'right' : 'left' );
		} );
	}

	// Liquid: the leading edge bulges ahead and the trailing edge bows
	// after it, then both settle straight on the new half.
	function liquid( name ) {
		const w = slide.offsetWidth;
		const h = slide.offsetHeight;
		const shape = ( xl, xlc, xr, xrc ) =>
			`path("M ${ xl } 0 C ${ xlc } ${ h * 0.3 } ${ xlc } ${
				h * 0.7
			} ${ xl } ${ h } L ${ xr } ${ h } C ${ xrc } ${ h * 0.7 } ${ xrc } ${
				h * 0.3
			} ${ xr } 0 Z")`;
		const login = shape( w / 2, w / 2, w, w );
		const register = shape( 0, 0, w / 2, w / 2 );
		const frames =
			'register' === name
				? [
						login,
						shape( w * 0.08, w * -0.1, w * 0.75, w * 0.6 ),
						register,
					]
				: [
						register,
						shape( w * 0.25, w * 0.4, w * 0.92, w * 1.1 ),
						login,
					];
		slide.getAnimations().forEach( ( a ) => a.cancel() );
		slide.animate(
			frames.map( ( f ) => ( { clipPath: f } ) ),
			{ duration: 900, easing: 'cubic-bezier(0.65, 0, 0.25, 1)' }
		);
	}

	// Mosaic: tiles nearer the far edge go first, with a scatter so they
	// never fall in rows.
	function mosaic( name ) {
		slide.querySelectorAll( '.pa-tiles i' ).forEach( ( tile, i ) => {
			const col = i % 8;
			const row = Math.floor( i / 8 );
			const reach = 'register' === name ? 7 - col : col;
			const scatter = ( ( row * 7 + col * 13 ) % 9 ) * 0.025;
			tile.style.setProperty( '--d', reach * 0.045 + scatter + 's' );
		} );
	}

	function apply( name, focusField ) {
		current = name;
		NAMES.forEach( ( other ) => {
			const on = other === name;
			panels[ other ].classList.toggle( 'pa-off', ! on );
			panels[ other ].inert = ! on;
			tabs[ other ].setAttribute(
				'aria-selected',
				on ? 'true' : 'false'
			);
			tabs[ other ].tabIndex = on ? 0 : -1;
			if ( sides[ other ] ) {
				sides[ other ].hidden = ! on;
				sides[ other ].inert = ! on;
			}
		} );
		tablist.dataset.active = name;
		if ( slide ) {
			slide.dataset.active = name;
		}
		if ( focusField ) {
			const field = panels[ name ].querySelector(
				'input:not([type="hidden"])'
			);
			if ( field ) {
				field.focus( { preventScroll: true } );
			}
		}
	}

	function set( name, focusField ) {
		if ( name === current ) {
			return;
		}
		const run = () => apply( name, focusField );
		if ( null === current ) {
			run();
			return;
		}
		group.classList.add( 'pa-moved' );
		if ( visible( tablist ) && ! has( 'switch-flip' ) ) {
			swapTabs( name, run );
			return;
		}
		if ( flood && visible( slide ) && ! reduce.matches ) {
			current = name;
			pour( name, run );
			return;
		}
		if ( slide && slide.querySelector( '.pa-strips' ) ) {
			blinds( name );
		}
		if ( slide && slide.querySelector( '.pa-tiles' ) ) {
			mosaic( name );
		}
		if (
			slide &&
			has( 'slide-liquid' ) &&
			visible( slide ) &&
			! reduce.matches
		) {
			liquid( name );
		}
		if ( slide && has( 'slide-page' ) ) {
			slide.classList.add( 'pa-turning' );
			win.setTimeout( () => slide.classList.remove( 'pa-turning' ), 650 );
		}
		run();
	}

	const opensRegister =
		has( 'open-register' ) || '#register' === win.location.hash;
	set( opensRegister ? 'register' : 'login', false );
	group.classList.add( 'pa-switching' );

	win.addEventListener( 'hashchange', () => {
		const hash = win.location.hash;
		if ( '#register' === hash || '#login' === hash ) {
			set( hash.slice( 1 ), true );
		}
	} );
}
