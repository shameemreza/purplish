/*
 * Ruled cards: hairlines between the cards and none on the outer edges, at
 * every column count and for any number of cards. The test reads the
 * edge selectors from the stylesheet and works out which cards they reach.
 */
import { describe, expect, it } from '@jest/globals';
import { effectPart } from './effect-styles';

const css = effectPart( 'cards' ).replace( /\/\*[\s\S]*?\*\//g, '' );

const rules = [ ...css.matchAll( /([^{}]+)\{([^{}]*)\}/g ) ].map(
	( [ , selector, body ] ) => ( {
		selectors: selector.split( ',' ).map( ( one ) => one.trim() ),
		body,
	} )
);

// The editor keeps a hidden copy of the card being edited, which the
// counts skip. On the store every card is shown.
const SHOWN = ' of :not([style*="display: none"])';

// The last row's rule goes to 0 unless Closing rule sets the width.
const CLEARS = {
	'border-inline-end': 'border-inline-end: 0',
	'border-block-end': 'border-block-end-width: var(--purplish-ruled-foot, 0)',
};

/**
 * The selectors that clear a border for one column count.
 *
 * @param {string} scope    `columns-N`, `purplish-cards-grid-N` or `purplish-cards-tablet-N`.
 * @param {string} property The border that's cleared.
 * @return {string[]} The selectors.
 */
const clearing = ( scope, property ) =>
	rules
		.filter( ( rule ) => rule.body.includes( CLEARS[ property ] ) )
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

const tablets = [ 2, 3, 4, 5 ].map( ( n ) => [
	`purplish-cards-tablet-${ n }`,
	n,
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

	it.each( tablets )(
		'%s sets its count from 600px to 1023px',
		( scope, columns ) => {
			const at = css.indexOf(
				'@media (width >= 600px) and (width < 1024px)'
			);
			const block = css.slice( at, css.indexOf( '@media', at + 1 ) );
			expect( at ).toBeGreaterThan( -1 );
			expect( block ).toContain(
				`${ scope } .wc-block-product-template:not(.is-horizontal)[class*="columns-"] {\n\t\tgrid-template-columns: repeat(${ columns }, minmax(0, 1fr));`
			);
		}
	);

	it( 'puts both rules back on every card before the tablet count clears them', () => {
		const back = rules.filter(
			( rule ) =>
				rule.selectors.some(
					( one ) =>
						one.includes( 'purplish-cards-tablet-' ) &&
						one.endsWith( `li:nth-child(n${ SHOWN })` )
				) &&
				rule.body.includes( 'border-inline-end: 1px solid' ) &&
				rule.body.includes( 'border-block-end: 1px solid' )
		);
		expect( back ).toHaveLength( 4 );
	} );

	it( 'keeps the last row’s rule with Closing rule', () => {
		const close = rules.find( ( rule ) =>
			rule.selectors.includes(
				'.purplish-cards-ruled.purplish-cards-ruledclose'
			)
		);
		expect( close.body ).toMatch( /--purplish-ruled-foot:\s*1px/ );
		expect( css ).not.toMatch(
			/purplish-cards-ruled[^{}]*\{[^{}]*border-block-end: 0/
		);
	} );

	it.each( [ ...scopes, ...tablets ] )(
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
					one.includes( ':root[data-purplish-grid="other"]' )
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
		const inside = [ ...css.matchAll( /([^{}]+)\{([^{}]*)\}/g ) ].filter(
			( [ , selector, body ] ) =>
				selector.includes(
					'.purplish-cards-ruled.purplish-cards-hovercard-ring'
				) && /outline-offset: -\d+px/.test( body )
		);
		expect( inside ).toHaveLength( 2 );
		expect( inside[ 1 ][ 1 ] ).toContain( ':hover' );
	} );
} );
