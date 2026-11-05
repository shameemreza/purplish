/**
 * Turns each Scroll Lookbook on. The block sticks to the screen while the
 * page scrolls through it: the camera moves from shot to shot, each video
 * plays with the scroll, and at every stop that product's card comes in
 * beside the subject. Scrolling back rewinds it. Only transform and
 * opacity change while scrolling, and nothing runs off screen.
 *
 * With reduced motion, or where the block can't stick, the shots and cards
 * stack as a plain page. Without this script they show as the blocks are.
 */
import {
	activeStop,
	cardAt,
	clamp,
	clipAt,
	ease,
	layout,
	openingStop,
	outCubic,
	pathAt,
	range,
	runLength,
	side,
	smooth,
	stamp,
	stopAt,
	timeline,
} from './timing';

const ZOOM = 1.15;

/**
 * The option value named in the block's classes.
 *
 * @param {HTMLElement} root     The block.
 * @param {string}      option   Option name.
 * @param {string}      fallback Default value.
 * @return {string} The value.
 */
function optionOf( root, option, fallback ) {
	const prefix = `purplish-lookbook-${ option }-`;
	const found = [ ...root.classList ].find( ( name ) =>
		name.startsWith( prefix )
	);
	return found ? found.slice( prefix.length ) : fallback;
}

/**
 * The numbers the panel doesn't set, from the `purplish_scroll_lookbook_config`
 * filter.
 *
 * @param {HTMLElement} root The block.
 * @return {{smoothing: number, hold: number}} Config.
 */
function configOf( root ) {
	let config = {};
	try {
		config = JSON.parse( root.dataset.config || '{}' ) || {};
	} catch {
		config = {};
	}
	return {
		smoothing:
			Number( config.smoothing ) > 0 ? Number( config.smoothing ) : 9,
		hold: Number( config.hold ) >= 0 ? Number( config.hold ) : 5,
	};
}

/**
 * Whether the block can stick to the screen. A wrapper that hides or
 * scrolls its overflow (Storefront's page wrapper hides it) becomes what
 * the block sticks to, so the film would scroll away with the page.
 *
 * @param {HTMLElement} root The block.
 * @return {boolean} True when nothing between the block and the body clips.
 */
function canStick( root ) {
	for (
		let parent = root.parentElement;
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
 * The shot's photo or video, if it has one.
 *
 * @param {HTMLElement} shot A Cover block.
 * @return {HTMLImageElement|HTMLVideoElement|null} The media.
 */
function mediaOf( shot ) {
	return shot.querySelector(
		':scope > img.wp-block-cover__image-background, :scope > video.wp-block-cover__video-background'
	);
}

/**
 * Stops a video from playing on its own.
 *
 * @param {HTMLVideoElement} video The video.
 */
function still( video ) {
	video.muted = true;
	video.playsInline = true;
	video.loop = false;
	video.autoplay = false;
	video.removeAttribute( 'autoplay' );
	video.removeAttribute( 'loop' );
	video.pause();
}

/**
 * The plain stacked page: each shot with its card beside it. Cards stay
 * in DOM order; only their place on screen changes.
 *
 * @param {HTMLElement}   root  The block.
 * @param {HTMLElement[]} shots The Cover blocks.
 * @param {HTMLElement[]} cards The cards.
 */
function stack( root, shots, cards ) {
	root.classList.add( 'purplish-lookbook-stacked' );
	shots.forEach( ( shot, index ) => {
		const media = mediaOf( shot );
		if ( media && 'VIDEO' === media.tagName ) {
			still( media );
		}
		shot.style.setProperty( 'order', String( index * 2 ) );
		const card = cards[ index - 1 ];
		if ( ! card ) {
			return;
		}
		const position = (
			( media && media.dataset.objectPosition ) ||
			'50% 50%'
		).split( ' ' );
		const left =
			'left' === side( ( parseFloat( position[ 0 ] ) || 50 ) / 100 );
		shot.classList.add( 'purplish-lookbook-paired' );
		shot.style.setProperty( 'grid-column', left ? '2' : '1' );
		card.style.setProperty( 'grid-column', left ? '1' : '2' );
		card.style.setProperty( 'order', String( index * 2 + 1 ) );
	} );
}

/**
 * Runs one block.
 *
 * @param {HTMLElement} root The block.
 */
function scrollLookbook( root ) {
	const stage = root.querySelector( ':scope > .purplish-lookbook-stage' );
	if ( ! stage ) {
		return;
	}
	const children = [ ...stage.children ];
	const shots = children.filter( ( el ) =>
		el.classList.contains( 'wp-block-cover' )
	);
	const collection = children.find( ( el ) =>
		el.classList.contains( 'wp-block-woocommerce-product-collection' )
	);
	const cards = collection
		? [
				...collection.querySelectorAll(
					'.wc-block-product-template > .wc-block-product'
				),
			]
		: [];
	const lastChild = children[ children.length - 1 ];
	const outro =
		lastChild && lastChild.classList.contains( 'wp-block-group' )
			? lastChild
			: null;

	const mode = layout( {
		shots: shots.length,
		reduce: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches,
		stick: canStick( root ),
	} );
	if ( 'none' === mode ) {
		return;
	}

	root.style.setProperty(
		'--purplish-lookbook-total',
		`"${ String( Math.min( cards.length, shots.length - 1 ) ).padStart( 2, '0' ) }"`
	);
	if ( collection ) {
		collection.classList.add( 'purplish-lookbook-cards' );
	}
	if ( outro ) {
		outro.classList.add( 'purplish-lookbook-outro' );
	}
	children
		.filter(
			( el ) =>
				! shots.includes( el ) && el !== collection && el !== outro
		)
		.forEach( ( el ) => el.classList.add( 'purplish-lookbook-extra' ) );
	shots.forEach( ( shot ) => shot.classList.add( 'purplish-lookbook-shot' ) );
	shots[ 0 ].classList.add( 'purplish-lookbook-hero' );
	cards.forEach( ( card ) => card.classList.add( 'purplish-lookbook-card' ) );

	if ( 'stacked' === mode ) {
		stack( root, shots, cards );
		return;
	}

	film( root, stage, shots, cards, outro );
}

/**
 * The film.
 *
 * @param {HTMLElement}      root  The block.
 * @param {HTMLElement}      stage What sticks to the screen.
 * @param {HTMLElement[]}    shots The Cover blocks.
 * @param {HTMLElement[]}    cards The cards, one per stop.
 * @param {HTMLElement|null} outro The closing line.
 */
function film( root, stage, shots, cards, outro ) {
	const config = configOf( root );
	const stops = shots.length - 1;
	const time = timeline( stops, optionOf( root, 'length', 'medium' ) );
	const media = shots.map( mediaOf );
	const isVideo = media.map( ( el ) => !! el && 'VIDEO' === el.tagName );
	const hero = shots[ 0 ];

	// Covers that share a photo or video play as one continuous shot.
	const layerOf = [];
	const places = shots.map( ( shot, index ) => {
		const el = media[ index ];
		let layer = index;
		for ( let before = 0; el && before < index; before++ ) {
			const other = media[ before ];
			if (
				other &&
				other.getAttribute( 'src' ) === el.getAttribute( 'src' )
			) {
				layer = layerOf[ before ];
				break;
			}
		}
		layerOf[ index ] = layer;
		if ( layer !== index ) {
			shot.classList.add( 'purplish-lookbook-alias' );
		}
		const position = ( ( el && el.dataset.objectPosition ) || '50% 50%' )
			.split( ' ' )
			.map( parseFloat );
		return {
			layer,
			x: ( Number.isFinite( position[ 0 ] ) ? position[ 0 ] : 50 ) / 100,
			y: ( Number.isFinite( position[ 1 ] ) ? position[ 1 ] : 50 ) / 100,
			zoom: 0 === index ? 1 : ZOOM,
		};
	} );
	const sides = places.slice( 1 ).map( ( place ) => side( place.x ) );

	root.classList.add( 'purplish-lookbook-film' );
	root.style.setProperty(
		'--purplish-lookbook-run',
		`${ runLength( stops, optionOf( root, 'length', 'medium' ) ) }svh`
	);

	cards.forEach( ( card, index ) => {
		if ( index >= stops ) {
			card.classList.add( 'purplish-lookbook-spare' );
		} else if ( 'right' === sides[ index ] ) {
			card.classList.add( 'purplish-lookbook-card-right' );
		}
	} );
	const parts = cards.map( ( card ) => [ ...card.children ] );

	// Chrome built here: letterbox bars, a dim layer for the closing line,
	// the chapter list and the timecode. All of it is decoration.
	const layer = ( className, parent = stage ) => {
		const el = document.createElement( 'div' );
		el.className = className;
		el.setAttribute( 'aria-hidden', 'true' );
		parent.append( el );
		return el;
	};
	const bars = [
		layer( 'purplish-lookbook-bar purplish-lookbook-bar-top' ),
		layer( 'purplish-lookbook-bar purplish-lookbook-bar-bottom' ),
	];
	const dim = layer( 'purplish-lookbook-dim' );

	let rail = null;
	let ticks = [];
	if ( 'on' === optionOf( root, 'chapters', 'on' ) ) {
		rail = layer( 'purplish-lookbook-rail' );
		rail.append( document.createElement( 'i' ) );
		ticks = shots.slice( 1 ).map( ( shot, index ) => {
			const tick = document.createElement( 'span' );
			const label = document.createElement( 'b' );
			const chapter = shot.querySelector( '.is-chapter' );
			const title =
				cards[ index ] &&
				cards[ index ].querySelector( '.wp-block-post-title' );
			label.textContent = (
				chapter ||
				title || { textContent: '' }
			).textContent.trim();
			tick.append( label, String( index + 1 ).padStart( 2, '0' ) );
			rail.append( tick );
			return tick;
		} );
	}
	const timecode =
		'on' === optionOf( root, 'timecode', 'off' )
			? layer( 'purplish-lookbook-timecode' )
			: null;

	const heroBits = [
		...hero.querySelectorAll(
			':scope > .wp-block-cover__inner-container > *'
		),
	];
	// The Cover's overlay fades from its own dim, not from full.
	const heroShade = hero.querySelector(
		':scope > .wp-block-cover__background'
	);
	const shadeBase = heroShade
		? Number( window.getComputedStyle( heroShade ).opacity ) || 0
		: 0;
	const outroBits = outro
		? [
				...(
					outro.querySelector(
						':scope > .wp-block-group__inner-container'
					) || outro
				).children,
			]
		: [];

	// ---- Measure (on resize only, never inside the frame loop) ----------
	let width = 0;
	let height = 0;
	let top = 0;
	let narrow = false;
	const size = [];
	const cardBox = [];
	const measure = () => {
		width = stage.clientWidth;
		height = stage.clientHeight;
		top = parseFloat( window.getComputedStyle( stage ).top ) || 0;
		narrow = width < 768;
		root.classList.toggle( 'purplish-lookbook-narrow', narrow );
		media.forEach( ( el, index ) => {
			if ( ! el ) {
				return;
			}
			const iw = el.naturalWidth || el.videoWidth || 1920;
			const ih = el.naturalHeight || el.videoHeight || 1080;
			const base = Math.max( width / iw, height / ih );
			size[ index ] = { iw, ih, base };
			el.style.transformOrigin = '0 0';
			el.style.setProperty( '--purplish-lookbook-w', `${ iw * base }px` );
			el.style.setProperty( '--purplish-lookbook-h', `${ ih * base }px` );
		} );
		cards.forEach( ( card, index ) => {
			cardBox[ index ] = narrow
				? card.offsetHeight + 12
				: card.offsetWidth + 16;
		} );
		last.camera = '';
	};

	// ---- Video time. A seek waits for the last one to finish, so a fast
	// scroll never piles them up.
	const pending = new Map();
	const seek = ( video, at ) => {
		if ( Math.abs( video.currentTime - at ) < 0.02 ) {
			return;
		}
		if ( video.seeking ) {
			pending.set( video, at );
		} else {
			video.currentTime = at;
		}
	};

	// ---- The opening shot plays once on load, for the hold time at most,
	// and keeps that frame. After that only the scroll moves it.
	let heroBase = 0;
	let opening = isVideo[ 0 ] ? 'waiting' : 'done';
	const endOpening = () => {
		if ( 'done' === opening ) {
			return;
		}
		opening = 'done';
		const video = media[ 0 ];
		video.pause();
		heroBase = video.duration ? video.currentTime / video.duration : 0;
	};

	media.forEach( ( el, index ) => {
		if ( ! el ) {
			return;
		}
		if ( ! isVideo[ index ] ) {
			el.loading = 'eager';
			el.addEventListener( 'load', () => measure() );
			return;
		}
		still( el );
		el.preload = 'auto';
		el.addEventListener( 'loadedmetadata', () => {
			measure();
			wake();
		} );
		el.addEventListener( 'seeked', () => {
			if ( pending.has( el ) ) {
				const at = pending.get( el );
				pending.delete( el );
				seek( el, at );
			}
		} );
	} );

	if ( isVideo[ 0 ] ) {
		const video = media[ 0 ];
		video.addEventListener( 'timeupdate', () => {
			if (
				'playing' === opening &&
				video.currentTime >= openingStop( video.duration, config.hold )
			) {
				endOpening();
			}
		} );
		video.addEventListener( 'ended', endOpening );
	}

	// The camera settles in once the opening shot can show.
	let born = Infinity;
	const ready = () => {
		if ( born !== Infinity ) {
			return;
		}
		born = window.performance.now();
		root.classList.add( 'purplish-lookbook-ready' );
		if ( 'waiting' === opening ) {
			if ( progress() > 0.002 || config.hold <= 0 ) {
				endOpening();
			} else {
				opening = 'playing';
				media[ 0 ].play().catch( endOpening );
			}
		}
		wake();
	};

	// ---- Frame ----------------------------------------------------------
	const last = {
		camera: '',
		hero: -1,
		outro: -1,
		rail: '',
		fx: '',
		cards: [],
		on: [],
		stop: -1,
	};
	let shown = 0;
	let prev = 0;
	let frame = 0;
	let seen = false;

	function progress() {
		const box = root.getBoundingClientRect();
		return clamp( ( top - box.top ) / Math.max( 1, box.height - height ) );
	}

	const place = ( index, x, y, zoom, rx, ry, rw, rh ) => {
		const { iw, ih, base } = size[ index ];
		const scale = base * Math.max( 1, zoom );
		const tx = clamp( rx + rw / 2 - x * iw * scale, width - iw * scale, 0 );
		const ty = clamp(
			ry + rh / 2 - y * ih * scale,
			height - ih * scale,
			0
		);
		return `translate3d(${ tx.toFixed( 1 ) }px,${ ty.toFixed( 1 ) }px,0) scale(${ ( scale / base ).toFixed( 4 ) })`;
	};

	const tick = ( now ) => {
		frame = 0;
		const dt = Math.min( 0.05, ( now - ( prev || now ) ) / 1000 ) || 1 / 60;
		prev = now;
		const target = progress();
		shown = ease( shown, target, dt, config.smoothing );
		const u = shown;

		if ( 'playing' === opening && u > 0.002 ) {
			endOpening();
		}

		// Cards
		let active = -1;
		let activeAmount = 0;
		cards.forEach( ( card, index ) => {
			const amount = time.holds[ index ]
				? cardAt( u, time.holds[ index ], time )
				: 0;
			if ( amount > activeAmount ) {
				activeAmount = amount;
				active = index;
			}
			if ( amount === last.cards[ index ] ) {
				return;
			}
			last.cards[ index ] = amount;
			const off = ( 1 - amount ) * 100;
			card.style.opacity = amount < 0.002 ? '0' : '1';
			card.style.pointerEvents = amount > 0.6 ? 'auto' : 'none';
			card.style.transform = narrow
				? `translate3d(0,calc(${ off.toFixed( 2 ) }% + ${ off > 0 ? 2 : 0 }rem),0)`
				: `translate3d(calc(${ ( 'right' === sides[ index ] ? off : -off ).toFixed( 2 ) }% ${ 'right' === sides[ index ] ? '+' : '-' } ${ off > 0 ? 2 : 0 }rem),0,0)`;
			parts[ index ].forEach( ( part, order ) => {
				const t = clamp( ( amount - 0.3 - order * 0.07 ) / 0.45 );
				part.style.opacity = String( t );
				part.style.transform = `translate3d(0,${ ( ( 1 - outCubic( t ) ) * 22 ).toFixed( 1 ) }px,0)`;
			} );
		} );

		// The stop event, once per arrival.
		const stop = activeAmount > 0.6 ? activeStop( u, time ) : -1;
		if ( stop !== last.stop ) {
			last.stop = stop;
			if ( stop >= 0 ) {
				document.dispatchEvent(
					new window.CustomEvent( 'purplish:stop', {
						detail: { stop: stop + 1, block: root },
					} )
				);
			}
		}

		// The opening shot's words lift away.
		const heroAway = range( u, time.heroOut[ 0 ], time.heroOut[ 1 ] );
		if ( heroAway !== last.hero ) {
			last.hero = heroAway;
			const step = 0.4 / Math.max( 1, heroBits.length - 1 );
			heroBits.forEach( ( bit, index ) => {
				const k = clamp( ( heroAway - index * step ) / 0.6 );
				bit.style.opacity = String( 1 - k );
				bit.style.transform = `translate3d(0,${ ( -k * 26 ).toFixed( 1 ) }px,0)`;
			} );
			if ( heroShade ) {
				heroShade.style.opacity = (
					shadeBase *
					( 1 - heroAway )
				).toFixed( 3 );
			}
		}

		// The closing line rises over the opening shot.
		const outroIn = range( u, time.outroIn[ 0 ], time.outroIn[ 1 ] );
		if ( outro && outroIn !== last.outro ) {
			last.outro = outroIn;
			outro.style.visibility = outroIn <= 0 ? 'hidden' : 'visible';
			dim.style.opacity = ( outroIn * 0.45 ).toFixed( 3 );
			outroBits.forEach( ( bit, index ) => {
				const t = outCubic( clamp( ( outroIn - index * 0.18 ) / 0.7 ) );
				bit.style.opacity = t.toFixed( 3 );
				bit.style.transform = `translate3d(0,${ ( ( 1 - t ) * 2 ).toFixed( 2 ) }em,0)`;
			} );
		}

		// Chapter list, on the side away from the card.
		if ( rail ) {
			let chapter = -1;
			time.holds.forEach( ( [ a, b ], index ) => {
				if ( u >= a - 0.06 && u <= b + 0.02 ) {
					chapter = index;
				}
			} );
			const fill = range(
				u,
				time.holds[ 0 ][ 0 ] - 0.06,
				time.forwardEnd
			);
			const start =
				chapter >= 0
					? 'right' === sides[ chapter ]
					: rail.classList.contains( 'purplish-lookbook-rail-left' );
			const key = `${ chapter }|${ fill.toFixed( 3 ) }|${ start }`;
			if ( key !== last.rail ) {
				last.rail = key;
				rail.style.opacity = chapter < 0 ? '0' : '1';
				rail.style.setProperty(
					'--purplish-lookbook-fill',
					fill.toFixed( 4 )
				);
				ticks.forEach( ( item, index ) =>
					item.classList.toggle( 'is-on', index === chapter )
				);
				rail.classList.toggle( 'purplish-lookbook-rail-left', start );
			}
		}

		// Camera
		const p = pathAt( u, time.beats );
		const path = ( n ) => ( n <= stops ? n : 2 * stops - n );
		const seg = Math.min( 2 * stops - 1, Math.floor( p ) );
		const f = p - seg;
		const from = places[ path( seg ) ];
		const to = places[ path( seg + 1 ) ];
		const back = seg >= stops;

		// The card narrows the frame so the subject stays in view beside it.
		let rx = 0;
		let rw = width;
		let rh = height;
		if ( active >= 0 ) {
			const cut = ( cardBox[ active ] || 0 ) * activeAmount;
			if ( narrow ) {
				rh = height - cut;
			} else {
				rw = width - cut;
				if ( 'left' === sides[ active ] ) {
					rx = cut;
				}
			}
		}
		const hold =
			active >= 0
				? range(
						u,
						time.holds[ active ][ 0 ],
						time.holds[ active ][ 1 ]
					)
				: 0;
		const settling = clamp( ( now - born ) / 2200 );
		const lean =
			( 1 + 0.05 * activeAmount + 0.035 * hold * activeAmount ) *
			( 1 + 0.14 * ( 1 - outCubic( settling ) ) );

		const layers = [];
		if ( from.layer === to.layer || f < 0.0001 ) {
			layers.push( [
				from.layer,
				from.x + ( to.x - from.x ) * f,
				from.y + ( to.y - from.y ) * f,
				( from.zoom + ( to.zoom - from.zoom ) * f ) * lean,
				1,
			] );
		} else {
			// Forward pushes into the next shot; the rewind pulls back.
			const mix = smooth( range( f, 0.3, 0.7 ) );
			const zoomOut = back
				? from.zoom * ( 1 - 0.3 * f )
				: from.zoom * ( 1 + 0.9 * f * f );
			const zoomIn = to.zoom * ( 1 + 0.45 * ( 1 - outCubic( f ) ) );
			layers.push( [ from.layer, from.x, from.y, zoomOut * lean, 1 ] );
			layers.push( [ to.layer, to.x, to.y, zoomIn * lean, mix ] );
		}

		// Letterbox bars close in while the camera travels.
		const travel = Math.sin( Math.PI * f );
		const playing = u > time.heroOut[ 1 ] && u < time.outroIn[ 0 ];
		const code = stamp( p );
		const fx = `${ travel.toFixed( 3 ) }|${ playing }|${ code }`;
		if ( fx !== last.fx ) {
			last.fx = fx;
			bars.forEach( ( bar ) => {
				bar.style.transform = `scaleY(${ travel.toFixed( 4 ) })`;
			} );
			if ( timecode ) {
				timecode.classList.toggle( 'is-on', playing );
				timecode.textContent = `00:${ code }`;
			}
		}

		const camera = `${ width }|${ height }|${ rx.toFixed( 1 ) }|${ rw.toFixed( 1 ) }|${ rh.toFixed( 1 ) }|${ layers
			.map( ( item ) => item.map( ( n ) => n.toFixed( 4 ) ).join( ',' ) )
			.join( ';' ) }`;
		if ( camera !== last.camera ) {
			last.camera = camera;
			const on = new Set( layers.map( ( item ) => item[ 0 ] ) );
			shots.forEach( ( shot, index ) => {
				if ( layerOf[ index ] !== index ) {
					return;
				}
				const isOn = on.has( index );
				if ( isOn !== last.on[ index ] ) {
					last.on[ index ] = isOn;
					shot.classList.toggle( 'is-on', isOn );
					if ( ! isOn ) {
						shot.style.opacity = '0';
					}
				}
			} );
			layers.forEach( ( [ index, x, y, zoom, alpha ], order ) => {
				const shot = shots[ index ];
				shot.style.zIndex = String( 1 + order );
				shot.style.opacity = alpha.toFixed( 4 );
				if ( media[ index ] && size[ index ] ) {
					media[ index ].style.transform = place(
						index,
						x,
						y,
						zoom,
						rx,
						0,
						rw,
						rh
					);
				}
			} );
		}

		// Each video on screen shows the moment the scroll points to.
		layers.forEach( ( [ index ] ) => {
			const video = isVideo[ index ] ? media[ index ] : null;
			if (
				! video ||
				! video.duration ||
				( 0 === index && 'done' !== opening )
			) {
				return;
			}
			seek(
				video,
				clipAt( index, u, time, heroBase ) * ( video.duration - 0.05 )
			);
		} );

		if (
			seen ||
			shown !== target ||
			settling < 1 ||
			'playing' === opening
		) {
			frame = window.requestAnimationFrame( tick );
		} else {
			prev = 0;
		}
	};

	function wake() {
		if ( ! frame ) {
			prev = 0;
			frame = window.requestAnimationFrame( tick );
		}
	}

	measure();
	shown = progress();
	wake();
	if ( ! media[ 0 ] ) {
		ready();
	} else if ( isVideo[ 0 ] ) {
		if ( media[ 0 ].readyState >= 2 ) {
			ready();
		} else {
			media[ 0 ].addEventListener( 'loadeddata', ready, { once: true } );
		}
	} else if ( media[ 0 ].decode ) {
		media[ 0 ].decode().then( ready, ready );
	} else {
		ready();
	}
	window.setTimeout( ready, 2500 );

	new window.IntersectionObserver( ( entries ) => {
		seen = entries[ entries.length - 1 ].isIntersecting;
		if ( seen ) {
			wake();
		} else if ( 'playing' === opening ) {
			endOpening();
		}
	} ).observe( root );

	window.addEventListener( 'scroll', wake, { passive: true } );
	let resizing = 0;
	window.addEventListener( 'resize', () => {
		window.cancelAnimationFrame( resizing );
		resizing = window.requestAnimationFrame( () => {
			measure();
			last.cards = [];
			wake();
		} );
	} );
	if ( document.fonts ) {
		document.fonts.ready.then( measure );
	}

	// A focused card that isn't on screen: scroll the page to its stop.
	root.addEventListener( 'focusin', ( event ) => {
		const card = event.target.closest( '.purplish-lookbook-card' );
		const index = cards.indexOf( card );
		if (
			index < 0 ||
			index >= stops ||
			( last.cards[ index ] || 0 ) > 0.6
		) {
			return;
		}
		const box = root.getBoundingClientRect();
		const at = stopAt( index, time );
		window.scrollTo( {
			top: box.top + window.scrollY - top + at * ( box.height - height ),
			behavior: 'instant',
		} );
		shown = at;
		wake();
	} );
}

if ( 'IntersectionObserver' in window ) {
	document
		.querySelectorAll( '.wp-block-purplish-scroll-lookbook' )
		.forEach( scrollLookbook );
}
