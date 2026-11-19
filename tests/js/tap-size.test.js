/*
 * Tap size and the large buy row: the stylesheet gives each control its
 * height. The test reads the rules from the source stylesheet.
 */
import { describe, expect, it } from '@jest/globals';
import { allEffects } from './effect-styles';

const css = allEffects().replace( /\/\*[\s\S]*?\*\//g, '' );

const rules = [ ...css.matchAll( /([^{}]+)\{([^{}]*)\}/g ) ].map(
	( [ , selector, body ] ) => ( { selector: selector.trim(), body } )
);

/**
 * The declarations of every rule whose selector has all the given parts.
 *
 * @param {...string} parts Parts the selector holds.
 * @return {string} The declarations, joined.
 */
const bodyFor = ( ...parts ) =>
	rules
		.filter( ( rule ) =>
			parts.every( ( part ) => rule.selector.includes( part ) )
		)
		.map( ( rule ) => rule.body )
		.join( '\n' );

describe( 'Tap size', () => {
	it.each( [
		'wc-block-product-filter-chips__item',
		'wc-block-product-filter-removable-chips__item',
		'wc-block-product-filters__open-overlay',
	] )( 'gives %s a 44px floor', ( part ) => {
		expect( bodyFor( '.purplish-filters-tap', part ) ).toMatch(
			/min-height:\s*44px/
		);
	} );

	it( 'centers a chip label in the taller chip', () => {
		expect(
			bodyFor(
				'.purplish-filters-tap',
				'wc-block-product-filter-chips__item'
			)
		).toMatch( /align-items:\s*center/ );
	} );
} );

describe( 'Large buy row', () => {
	it( 'counts the edge in the quantity height', () => {
		const body = bodyFor(
			'.purplish-cart-large',
			'wc-block-add-to-cart-with-options__quantity-selector'
		);
		expect( body ).toMatch( /min-height:\s*52px/ );
		expect( body ).toMatch( /box-sizing:\s*border-box/ );
	} );

	it( 'gives the button the same height', () => {
		expect(
			bodyFor( '.purplish-cart-large', 'single_add_to_cart_button' )
		).toMatch( /min-height:\s*52px/ );
	} );
} );
