/*
 * The Product Gallery's photo grid: tile sizes, the phone counter, the
 * shown order, the stylesheet's span rules, focus and full screen on each
 * photo, and the editor's copies of the photo.
 * A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it, jest } from '@jest/globals';
import { readFileSync } from 'fs';
import { join } from 'path';
import { effectPart } from './effect-styles';
import {
	counterText,
	inShownOrder,
	isTiled,
	photoGrid,
	tileSpans,
} from '../../assets/src/effects/tiles';
import {
	COPIES,
	placeTiles,
} from '../../assets/src/editor/gallery-tiles-preview';

const SLIDE = '.wc-block-product-gallery-large-image__wrapper';

describe( 'tileSpans', () => {
	it( 'puts an odd photo out across both columns of two across', () => {
		expect( tileSpans( 1, 'grid' ) ).toEqual( [ 2 ] );
		expect( tileSpans( 2, 'grid' ) ).toEqual( [ 1, 1 ] );
		expect( tileSpans( 5, 'grid' ) ).toEqual( [ 2, 1, 1, 1, 1 ] );
		expect( tileSpans( 6, 'grid' ) ).toEqual( [ 1, 1, 1, 1, 1, 1 ] );
	} );

	it( 'fills the last row of two large over small', () => {
		expect( tileSpans( 1, 'mosaic' ) ).toEqual( [ 6 ] );
		expect( tileSpans( 2, 'mosaic' ) ).toEqual( [ 3, 3 ] );
		expect( tileSpans( 3, 'mosaic' ) ).toEqual( [ 3, 3, 6 ] );
		expect( tileSpans( 4, 'mosaic' ) ).toEqual( [ 3, 3, 3, 3 ] );
		expect( tileSpans( 5, 'mosaic' ) ).toEqual( [ 3, 3, 2, 2, 2 ] );
		expect( tileSpans( 6, 'mosaic' ) ).toEqual( [ 3, 3, 2, 2, 2, 6 ] );
		expect( tileSpans( 7, 'mosaic' ) ).toEqual( [ 3, 3, 2, 2, 2, 3, 3 ] );
	} );

	it( 'never leaves a hole in a row', () => {
		for ( let count = 1; count <= 12; count++ ) {
			const two = tileSpans( count, 'grid' );
			const six = tileSpans( count, 'mosaic' );
			expect( two.reduce( ( a, b ) => a + b, 0 ) % 2 ).toBe( 0 );
			expect( six.reduce( ( a, b ) => a + b, 0 ) % 6 ).toBe( 0 );
			// Each row of six closes exactly.
			let row = 0;
			six.forEach( ( span ) => {
				row += span;
				expect( row ).toBeLessThanOrEqual( 6 );
				row %= 6;
			} );
		}
	} );
} );

describe( 'the stylesheet', () => {
	const css = effectPart( 'gallery' ).replace( /\/\*[\s\S]*?\*\//g, '' );
	const wide = css.slice( css.indexOf( '@media (min-width: 600px)' ) );

	// Each `span N` rule for one layout's slides, in source order, as the
	// conditions after the slide class.
	const rules = ( layout ) =>
		[ ...wide.matchAll( /([^{}]+)\{\s*grid-column: span (\d+);\s*\}/g ) ]
			.map( ( [ , selectors, span ] ) => ( {
				span: Number( span ),
				chains: selectors
					.split( /,\s*/ )
					.map( ( one ) => one.trim() )
					.filter( ( one ) =>
						one.startsWith(
							`.purplish-gallery-layout-${ layout } ${ SLIDE }`
						)
					)
					.map( ( one ) =>
						[
							...one.matchAll(
								/:nth-(last-)?child\(([^)]*?) of :not\(\[hidden\]\)\)/g
							),
						].map( ( [ , last, formula ] ) => ( {
							last: !! last,
							formula: formula.replace( /\s/g, '' ),
						} ) )
					),
			} ) )
			.filter( ( rule ) => rule.chains.length );

	// Whether An+B matches a 1-based position.
	const nth = ( formula, at ) => {
		if ( 'odd' === formula ) {
			return 1 === at % 2;
		}
		const found = formula.match( /^(-?\d*)n([+-]\d+)?$/ );
		if ( ! found ) {
			return Number( formula ) === at;
		}
		const a = Number(
			'' === found[ 1 ] || '-' === found[ 1 ]
				? `${ found[ 1 ] }1`
				: found[ 1 ]
		);
		const b = Number( found[ 2 ] || 0 );
		const n = ( at - b ) / a;
		return Number.isInteger( n ) && n >= 0;
	};

	const spansFromCss = ( layout, count ) =>
		Array.from( { length: count }, ( _, index ) => {
			let span = 1;
			rules( layout ).forEach( ( rule ) => {
				const hit = rule.chains.some( ( chain ) =>
					chain.every( ( { last, formula } ) =>
						nth( formula, last ? count - index : index + 1 )
					)
				);
				if ( hit ) {
					span = rule.span;
				}
			} );
			return span;
		} );

	it( 'gives every count the same spans as the script', () => {
		for ( let count = 1; count <= 8; count++ ) {
			expect( spansFromCss( 'grid', count ) ).toEqual(
				tileSpans( count, 'grid' )
			);
			expect( spansFromCss( 'mosaic', count ) ).toEqual(
				tileSpans( count, 'mosaic' )
			);
		}
	} );

	it( 'draws the grid from 600px, with the theme’s block gap', () => {
		expect( wide ).toContain( 'gap: var(--wp--style--block-gap, 0.5em);' );
		expect( wide ).toContain( 'grid-template-columns: repeat(6' );
		expect( css ).toMatch(
			/\.purplish-gallery-tile \{\s*display: none;\s*\}/
		);
	} );

	it( 'fills each tile when the photo has no aspect ratio', () => {
		// WooCommerce letterboxes these with `contain !important`.
		expect( wide ).toMatch(
			/--aspect-ratio-auto img \{\s*object-fit: cover !important;\s*\}/
		);
	} );
} );

describe( 'counterText', () => {
	it( 'counts from one and stays in range', () => {
		expect( counterText( 0, 5 ) ).toBe( '1/5' );
		expect( counterText( 4, 5 ) ).toBe( '5/5' );
		expect( counterText( 9, 5 ) ).toBe( '5/5' );
		expect( counterText( -1, 5 ) ).toBe( '1/5' );
	} );
} );

describe( 'inShownOrder', () => {
	it( 'drops hidden slides and follows WooCommerce’s order', () => {
		document.body.innerHTML =
			'<li id="a" style="order: 1"></li><li id="b" hidden></li><li id="c" style="order: 0"></li><li id="d" style="order: 2"></li>';
		const slides = [ ...document.querySelectorAll( 'li' ) ];
		expect( inShownOrder( slides ).map( ( slide ) => slide.id ) ).toEqual( [
			'c',
			'a',
			'd',
		] );
	} );

	it( 'keeps page order without an order', () => {
		document.body.innerHTML = '<li id="a"></li><li id="b"></li>';
		const slides = [ ...document.querySelectorAll( 'li' ) ];
		expect( inShownOrder( slides ).map( ( slide ) => slide.id ) ).toEqual( [
			'a',
			'b',
		] );
	} );
} );

/**
 * A gallery with five shown photos and one variation photo, thumbnails
 * and a full-screen view, the way WooCommerce prints it.
 *
 * @param {string} layout `grid` or `mosaic`.
 * @return {HTMLElement} The gallery.
 */
const gallery = ( layout ) => {
	const ids = [ 11, 12, 13, 14, 15, 16 ];
	document.body.innerHTML = `<div class="wp-block-woocommerce-product-gallery purplish-gallery purplish-gallery-layout-${ layout }">
		<div class="wc-block-product-gallery-large-image">
			<ul class="wc-block-product-gallery-large-image__container" tabindex="0" aria-roledescription="carousel">
				${ ids
					.map(
						( id, at ) =>
							`<li class="wc-block-product-gallery-large-image__wrapper"${
								5 === at ? ' hidden' : ` style="order: ${ at }"`
							}><img data-image-id="${ id }" tabindex="-1" alt=""></li>`
					)
					.join( '' ) }
			</ul>
		</div>
		<div class="wc-block-product-gallery-thumbnails">
			${ ids
				.map(
					( id ) =>
						`<div class="wc-block-product-gallery-thumbnails__thumbnail"><img data-image-id="${ id }" alt=""></div>`
				)
				.join( '' ) }
		</div>
		<dialog class="wc-block-product-gallery-dialog"><div class="wc-block-product-gallery-dialog__content"></div></dialog>
	</div>`;
	return document.querySelector( '.wp-block-woocommerce-product-gallery' );
};

/**
 * Sets the window width the 600px query sees.
 *
 * @param {boolean} wide Whether the window is 600px or wider.
 */
const setWide = ( wide ) => {
	window.matchMedia = ( query ) => ( {
		matches: wide && query.includes( '600px' ),
		addEventListener: () => {},
	} );
};

// Lets the mutation observers run.
const settle = () => new Promise( ( done ) => setTimeout( done ) );

describe( 'photoGrid', () => {
	beforeEach( () => setWide( true ) );

	it( 'makes each photo take focus in the grid, not the strip', () => {
		const el = gallery( 'grid' );
		photoGrid( el );
		const strip = el.querySelector(
			'.wc-block-product-gallery-large-image__container'
		);
		expect( strip.tabIndex ).toBe( -1 );
		expect( strip.hasAttribute( 'aria-roledescription' ) ).toBe( false );
		expect(
			[ ...el.querySelectorAll( `${ SLIDE } img` ) ].map(
				( img ) => img.tabIndex
			)
		).toEqual( [ 0, 0, 0, 0, 0, 0 ] );
		expect( isTiled( strip ) ).toBe( true );
	} );

	it( 'keeps WooCommerce’s slider on phones, with a counter', () => {
		setWide( false );
		const el = gallery( 'mosaic' );
		photoGrid( el );
		const strip = el.querySelector(
			'.wc-block-product-gallery-large-image__container'
		);
		expect( strip.tabIndex ).toBe( 0 );
		expect( strip.getAttribute( 'aria-roledescription' ) ).toBe(
			'carousel'
		);
		const counter = el.querySelector( '.purplish-gallery-counter' );
		expect( counter.textContent ).toBe( '1/5' );
		expect( counter.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( counter.hidden ).toBe( false );
		expect( isTiled( strip ) ).toBe( false );
	} );

	it( 'hides the counter with one photo', async () => {
		setWide( false );
		const el = gallery( 'grid' );
		photoGrid( el );
		el.querySelectorAll( SLIDE ).forEach( ( slide, at ) => {
			slide.hidden = at > 0;
		} );
		await settle();
		expect( el.querySelector( '.purplish-gallery-counter' ).hidden ).toBe(
			true
		);
	} );

	it( 'sizes tiles by the shown order when a variation reorders them', async () => {
		const el = gallery( 'mosaic' );
		photoGrid( el );
		const slides = [ ...el.querySelectorAll( SLIDE ) ];
		expect( slides.map( ( slide ) => slide.style.gridColumn ) ).toEqual( [
			'',
			'',
			'',
			'',
			'',
			'',
		] );

		// The variation photo comes first in place of the main photo.
		slides[ 0 ].hidden = true;
		slides[ 5 ].hidden = false;
		slides[ 5 ].style.order = '0';
		await settle();
		expect( slides.map( ( slide ) => slide.style.gridColumn ) ).toEqual( [
			'',
			'span 3',
			'span 2',
			'span 2',
			'span 2',
			'span 3',
		] );

		// Back to the main photo: the stylesheet sizes them again.
		slides[ 0 ].hidden = false;
		slides[ 5 ].hidden = true;
		await settle();
		expect(
			slides.every( ( slide ) => '' === slide.style.gridColumn )
		).toBe( true );
	} );

	it( 'selects a clicked or focused photo through its thumbnail', () => {
		const el = gallery( 'grid' );
		photoGrid( el );
		const picked = [];
		el.querySelectorAll(
			'.wc-block-product-gallery-thumbnails img'
		).forEach( ( thumb ) =>
			thumb.addEventListener( 'click', () =>
				picked.push( thumb.dataset.imageId )
			)
		);
		const photos = el.querySelectorAll( `${ SLIDE } img` );
		photos[ 2 ].click();
		photos[ 3 ].dispatchEvent(
			new window.FocusEvent( 'focusin', { bubbles: true } )
		);
		expect( picked ).toEqual( [ '13', '14' ] );
		expect(
			el.querySelector( '.wc-block-product-gallery-large-image' )
				.purplishPicked
		).toBe( photos[ 3 ] );
	} );

	it( 'opens the focused photo on Enter before the strip hears it', () => {
		const el = gallery( 'grid' );
		photoGrid( el );
		const strip = el.querySelector(
			'.wc-block-product-gallery-large-image__container'
		);
		const heard = jest.fn();
		strip.addEventListener( 'keydown', heard );
		const photo = el.querySelectorAll( `${ SLIDE } img` )[ 1 ];
		const clicked = jest.fn();
		photo.addEventListener( 'click', clicked );

		const enter = new window.KeyboardEvent( 'keydown', {
			key: 'Enter',
			bubbles: true,
			cancelable: true,
		} );
		photo.dispatchEvent( enter );
		expect( clicked ).toHaveBeenCalledTimes( 1 );
		expect( heard ).not.toHaveBeenCalled();
		expect( enter.defaultPrevented ).toBe( true );

		photo.dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'ArrowRight',
				bubbles: true,
			} )
		);
		expect( heard ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'leaves Enter to WooCommerce on phones', () => {
		setWide( false );
		const el = gallery( 'grid' );
		photoGrid( el );
		const strip = el.querySelector(
			'.wc-block-product-gallery-large-image__container'
		);
		const heard = jest.fn();
		strip.addEventListener( 'keydown', heard );
		strip.dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'Enter',
				bubbles: true,
			} )
		);
		expect( heard ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'placeTiles', () => {
	const editorGallery = ( src ) => {
		document.body.innerHTML = `<div class="wp-block-woocommerce-product-gallery">
			<div class="wc-block-editor-product-gallery-large-image">
				<div class="wc-block-product-gallery-large-image__inner-blocks">
					<div class="wp-block-woocommerce-product-image">${
						src
							? `<img src="${ src }" srcset="${ src } 600w" sizes="100vw" alt="Shirt">`
							: ''
					}</div>
				</div>
			</div>
		</div>`;
		return document.body.firstElementChild;
	};

	it( 'adds four copies of the photo after its block, hidden from assistive tech', () => {
		const block = editorGallery( 'https://example.test/shirt.jpg' );
		placeTiles( block );
		const tiles = [ ...block.querySelectorAll( '.purplish-gallery-tile' ) ];
		expect( tiles ).toHaveLength( COPIES );
		expect( COPIES ).toBe( 4 );
		tiles.forEach( ( tile ) => {
			expect( tile.parentElement.className ).toBe(
				'wc-block-editor-product-gallery-large-image'
			);
			expect( tile.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			expect( tile.getAttribute( 'contenteditable' ) ).toBe( 'false' );
			const img = tile.querySelector( 'img' );
			expect( img.getAttribute( 'src' ) ).toBe(
				'https://example.test/shirt.jpg'
			);
			expect( img.getAttribute( 'sizes' ) ).toBe( '100vw' );
			expect( img.alt ).toBe( '' );
		} );
	} );

	it( 'leaves the copies alone when they are right, and follows a new photo', () => {
		const block = editorGallery( 'https://example.test/shirt.jpg' );
		placeTiles( block );
		const first = block.querySelector( '.purplish-gallery-tile' );
		placeTiles( block );
		expect( block.querySelector( '.purplish-gallery-tile' ) ).toBe( first );

		block
			.querySelector( '.wp-block-woocommerce-product-image img' )
			.setAttribute( 'src', 'https://example.test/dress.jpg' );
		placeTiles( block );
		const tiles = block.querySelectorAll( '.purplish-gallery-tile img' );
		expect( tiles ).toHaveLength( COPIES );
		expect( tiles[ 0 ].getAttribute( 'src' ) ).toBe(
			'https://example.test/dress.jpg'
		);
	} );

	it( 'adds nothing without a photo', () => {
		const block = editorGallery( '' );
		placeTiles( block );
		expect(
			block.querySelectorAll( '.purplish-gallery-tile' )
		).toHaveLength( 0 );
	} );
} );

describe( 'the panel', () => {
	it( 'offers the layouts the server knows', () => {
		const php = readFileSync(
			join( __dirname, '../../src/Frontend/BlockEffects.php' ),
			'utf8'
		);
		expect( php ).toMatch( /'layout'\s*=>\s*array\( 'grid', 'mosaic' \)/ );
		const panel = readFileSync(
			join( __dirname, '../../assets/src/editor/gallery.js' ),
			'utf8'
		);
		const values = [
			...panel
				.slice( panel.indexOf( 'LAYOUTS' ) )
				.matchAll( /value: '(\w*)'/g ),
		]
			.slice( 0, 3 )
			.map( ( [ , value ] ) => value );
		expect( values ).toEqual( [ '', 'grid', 'mosaic' ] );
	} );
} );
