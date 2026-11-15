/*
 * Ruled cards: hairlines between the cards and none on the outer edges, at
 * every column count and for any number of cards. The test reads the
 * edge selectors from the stylesheet and works out which cards they reach.
 */
import { describe, expect, it } from '@jest/globals';
import { readFileSync } from 'fs';
import { join } from 'path';

const css = readFileSync(
	join( __dirname, '../../assets/src/effects/shop/style.css' ),
	'utf8'
).replace( /\/\*[\s\S]*?\*\//g, '' );

const rules = [ ...css.matchAll( /([^{}]+)\{([^{}]*)\}/g ) ].map(
	( [ , selector, body ] ) => ( {
		selectors: selector.split( ',' ).map( ( one ) => one.trim() ),
		body,
	} )
);

// The editor keeps a hidden copy of the card being edited, which the
// counts skip. On the store every card is shown.
const SHOWN = ' of :not([style*="display: none"])';

/**
 * The selectors that clear a border for one column count.
 *
 * @param {string} scope    `columns-N` or `purplish-cards-grid-N`.
 * @param {string} property The border that's cleared.
 * @return {string[]} The selectors.
 */
const clearing = ( scope, property ) =>
	rules
		.filter( ( rule ) => rule.body.includes( `${ property }: 0` ) )
		.flatMap( ( rule ) => rule.selectors )
		.filter(
			( selector ) =>
				selector.includes( 'purplish-cards-ruled' ) &&
				selector.includes( scope )
		);

/**
 * Whether a card at `index` (1-based) of `count` matches an edge selector.
 * Reads the `nth-child(An)`, `nth-child(An+1):nth-last-child(-n+A)` and
 * `... ~ li` forms the stylesheet uses.
 *
 * @param {string} given The selector.
 * @param {number} index The card's place.
 * @param {number} count How many cards.
 * @return {boolean} Whether it matches.
 */
const matches = ( given, index, count ) => {
	const selector = given.replaceAll( SHOWN, '' );
	const firstOfLast = ( a ) => ( at ) =>
		at % a === 1 % a && count - at + 1 <= a;
	let found = selector.match(
		/li:nth-child\((\d+)n\+1\):nth-last-child\(-n\+(\d+)\)( ~ li)?$/
	);
	if ( found ) {
		const test = firstOfLast( Number( found[ 1 ] ) );
		if ( ! found[ 3 ] ) {
			return test( index );
		}
		for ( let at = 1; at < index; at++ ) {
			if ( test( at ) ) {
				return true;
			}
		}
		return false;
	}
	found = selector.match( /li:nth-child\((\d+)n\)$/ );
	return !! found && index % Number( found[ 1 ] ) === 0;
};

const scopes = [ 2, 3, 4, 5, 6 ].flatMap( ( n ) => [
	[ `columns-${ n }`, n ],
	[ `purplish-cards-grid-${ n }`, n ],
] );

describe( 'Ruled cards', () => {
	it.each( scopes )( '%s sets its own column count', ( scope, columns ) => {
		const rule = rules.find(
			( one ) =>
				one.selectors.some(
					( selector ) =>
						selector.includes( 'purplish-cards-ruled' ) &&
						selector.endsWith(
							scope.startsWith( 'columns' )
								? `.${ scope }`
								: ':not(.is-horizontal)'
						) &&
						selector.includes( scope )
				) && one.body.includes( 'grid-template-columns' )
		);
		expect( rule.body ).toContain( `repeat(${ columns }, minmax(0, 1fr))` );
	} );

	it.each( scopes )(
		'%s leaves no rule on the outer edges',
		( scope, columns ) => {
			const side = clearing( scope, 'border-inline-end' );
			const foot = clearing( scope, 'border-block-end' );
			expect( side.length ).toBeGreaterThan( 0 );
			expect( foot.length ).toBeGreaterThan( 0 );

			for ( let count = 1; count <= 13; count++ ) {
				const rows = Math.ceil( count / columns );
				for ( let index = 1; index <= count; index++ ) {
					const lastColumn = index % columns === 0;
					const lastRow = Math.ceil( index / columns ) === rows;
					expect(
						side.some( ( one ) => matches( one, index, count ) )
					).toBe( lastColumn );
					expect(
						foot.some( ( one ) => matches( one, index, count ) )
					).toBe( lastRow );
				}
			}
		}
	);

	it( 'skips the editor’s hidden card in every count', () => {
		const counts = rules
			.flatMap( ( rule ) => rule.selectors )
			.filter( ( one ) => one.includes( 'purplish-cards-ruled' ) )
			.flatMap( ( one ) => one.split( /(?=:nth-)/ ).slice( 1 ) );
		expect( counts.length ).toBeGreaterThan( 0 );
		counts.forEach( ( one ) => expect( one ).toContain( SHOWN ) );
	} );

	it( 'puts the hairlines back for the grid switch count', () => {
		const back = rules.filter(
			( rule ) =>
				rule.selectors.some( ( one ) =>
					one.includes( 'data-purplish-grid="other"' )
				) && rule.body.includes( 'border-inline-end: 1px solid' )
		);
		expect( back ).toHaveLength( 5 );
	} );

	it( 'keeps the cells square, so a rounded card look can’t bend the lines', () => {
		const cell = rules.find(
			( rule ) =>
				rule.selectors.includes(
					'.purplish-cards-ruled .wc-block-product-template:not(.is-horizontal) > li'
				) && rule.body.includes( 'border-inline-end: 1px solid' )
		);
		expect( cell.body ).toContain( 'border-radius: 0' );
	} );

	it( 'draws the Ring card look inside the cell, at rest and on hover', () => {
		const effects = readFileSync(
			join( __dirname, '../../assets/src/effects/style.css' ),
			'utf8'
		).replace( /\/\*[\s\S]*?\*\//g, '' );
		const inside = [
			...effects.matchAll( /([^{}]+)\{([^{}]*)\}/g ),
		].filter(
			( [ , selector, body ] ) =>
				selector.includes(
					'.purplish-cards-ruled.purplish-cards-hovercard-ring'
				) && /outline-offset: -\d+px/.test( body )
		);
		expect( inside ).toHaveLength( 2 );
		expect( inside[ 1 ][ 1 ] ).toContain( ':hover' );
	} );
} );
