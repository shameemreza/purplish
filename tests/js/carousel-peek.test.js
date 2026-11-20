/**
 * Carousel peek: whole cards from the start edge, then the set share of the
 * next card, at every carousel width WooCommerce steps through. The test
 * reads the sizes, steps and card width from the stylesheet and does the
 * sum the browser does.
 */
import { describe, expect, it } from '@jest/globals';
import { readFileSync } from 'fs';
import { join } from 'path';
import { effectPart } from './effect-styles';

const css = effectPart( 'cards' ).replace( /\/\*[\s\S]*?\*\//g, '' );

const SIZES = { small: 1 / 5, medium: 1 / 3, large: 1 / 2 };

const STRIP =
	':is(.purplish-cards-peek-small, .purplish-cards-peek-medium, .purplish-cards-peek-large) .is-product-collection-layout-carousel';

/**
 * Works out a CSS length expression made of numbers, `calc()` and the
 * given values.
 *
 * @param {string} expression The expression.
 * @param {Object} values     Each token and the number it stands for.
 * @return {number} The result.
 */
function evaluate( expression, values ) {
	let sum = expression;
	Object.entries( values ).forEach( ( [ token, value ] ) => {
		sum = sum.split( token ).join( `(${ value })` );
	} );
	sum = sum.replaceAll( 'calc(', '(' );
	expect( sum ).toMatch( /^[\d\s.()+\-*/]+$/ );
	return Function( `return ${ sum };` )();
}

/**
 * The declarations of the first rule whose selector is exactly `selector`.
 *
 * @param {string} selector The selector.
 * @return {string} The declarations, or an empty string.
 */
function rule( selector ) {
	const at = css.indexOf( selector + ' {' );
	return -1 === at ? '' : css.slice( at, css.indexOf( '}', at ) );
}

/**
 * A custom property's value in some declarations.
 *
 * @param {string} body The declarations.
 * @param {string} name The property, with its dashes.
 * @return {string} The value, or an empty string.
 */
function prop( body, name ) {
	const found = body.match( new RegExp( `${ name }:\\s*([^;]+);` ) );
	return found ? found[ 1 ].trim() : '';
}

const card = rule( STRIP + ' .wc-block-product' );

// Whole cards for a carousel width: the base, then each container step.
const steps = [
	...css.matchAll(
		/@container carousel \(min-width: (\d+)px\) \{\s*([^{}]+)\{([^{}]*)\}/g
	),
]
	.filter(
		( [ , , selector ] ) => selector.trim() === STRIP + ' .wc-block-product'
	)
	.map( ( [ , width, , body ] ) => [
		Number( width ),
		Number( prop( body, '--purplish-peek-cards' ) ),
	] );

const wholeCards = ( width ) =>
	steps.reduce(
		( count, [ from, cards ] ) => ( width >= from ? cards : count ),
		Number( prop( card, '--purplish-peek-cards' ) )
	);

describe( 'carousel peek', () => {
	it( 'has a fifth, a third and half of a card as its sizes', () => {
		Object.entries( SIZES ).forEach( ( [ size, share ] ) => {
			const value = prop(
				rule( `.purplish-cards-peek-${ size }` ),
				'--purplish-peek'
			);
			expect( evaluate( value, {} ) ).toBeCloseTo( share, 5 );
		} );
	} );

	it( 'steps the whole cards as WooCommerce steps its card widths', () => {
		expect( wholeCards( 375 ) ).toBe( 2 );
		expect( steps ).toEqual( [
			[ 600, 3 ],
			[ 960, 4 ],
			[ 1280, 5 ],
			[ 1440, 6 ],
		] );
	} );

	it( 'fills the strip with whole cards and the share of the next', () => {
		const formula = prop( card, '--purplish-peek-card' );
		[ 335, 599, 600, 782, 959, 960, 1185, 1280, 1340, 1440, 1920 ].forEach(
			( width ) => {
				[ 0, 10, 19.1, 24 ].forEach( ( gap ) => {
					Object.values( SIZES ).forEach( ( share ) => {
						const cards = wholeCards( width );
						const each = evaluate( formula, {
							'100%': width,
							'var(--purplish-peek-cards)': cards,
							'var(--purplish-peek-gap)': gap,
							'var(--purplish-peek)': share,
						} );
						const shown = width - cards * ( each + gap );
						expect( shown / each ).toBeCloseTo( share, 5 );
					} );
				} );
			}
		);
	} );

	it( 'keeps cards within 15% of the size WooCommerce gives them', () => {
		// WooCommerce's card min-width for each carousel width step.
		const woo = [
			[ 0, 0.425 ],
			[ 600, 0.285 ],
			[ 782, 0.29 ],
			[ 960, 0.22 ],
			[ 1280, 0.18 ],
			[ 1440, 0.15 ],
		];
		const formula = prop( card, '--purplish-peek-card' );
		[ 335, 600, 782, 960, 1280, 1440 ].forEach( ( width ) => {
			const theirs =
				width * woo.filter( ( [ from ] ) => width >= from ).pop()[ 1 ];
			Object.values( SIZES ).forEach( ( share ) => {
				const each = evaluate( formula, {
					'100%': width,
					'var(--purplish-peek-cards)': wholeCards( width ),
					'var(--purplish-peek-gap)': 12,
					'var(--purplish-peek)': share,
				} );
				expect( Math.abs( each - theirs ) / theirs ).toBeLessThan(
					0.15
				);
			} );
		} );
	} );

	it( 'snaps cards to the start edge with the theme block gap', () => {
		const strip = rule( STRIP );
		expect( strip ).toMatch(
			/--purplish-peek-gap:\s*var\(--wp--style--block-gap,\s*0\.5em\)/
		);
		expect( strip ).toMatch( /column-gap:\s*var\(--purplish-peek-gap\)/ );
		expect( strip ).toMatch( /scroll-padding-inline:\s*0/ );
		expect( card ).toMatch( /scroll-snap-align:\s*start/ );
		expect( card ).toMatch( /flex:\s*0 0 var\(--purplish-peek-card\)/ );
		expect( card ).toMatch( /min-width:\s*var\(--purplish-peek-card\)/ );
		expect( card ).toMatch( /max-width:\s*none/ );
	} );

	it( 'gives way to the same carousel effects in the panel and on the server', () => {
		const php = readFileSync(
			join( __dirname, '../../src/Frontend/BlockEffects.php' ),
			'utf8'
		)
			.match( /const CAROUSEL_EFFECTS = array\(([^)]*)\)/ )[ 1 ]
			.match( /'([a-z]+)'/g )
			.map( ( name ) => name.replaceAll( "'", '' ) );
		const panel = readFileSync(
			join( __dirname, '../../assets/src/editor/cards.js' ),
			'utf8'
		);
		const group = panel.slice(
			panel.indexOf( "'In a carousel'" ),
			panel.indexOf( "'As cards scroll in'" )
		);
		const js = [ ...group.matchAll( /value: '([a-z]+)'/g ) ].map(
			( [ , name ] ) => name
		);
		expect( js ).toEqual( php );
		expect( panel ).toMatch(
			/const CAROUSEL_EFFECTS = HOVERS\[ 1 \]\.options/
		);
	} );
} );
