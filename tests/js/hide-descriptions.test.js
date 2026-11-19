/**
 * Hide short descriptions hides only the short description, so variation
 * details stay on each item.
 */
import { describe, expect, it } from '@jest/globals';
import { effectPart } from './effect-styles';

const css = effectPart( 'cartpage' ) + effectPart( 'drawer' );

describe( 'hide short descriptions', () => {
	it.each( [ 'cartpage', 'drawer' ] )(
		'hides the description in the %s styles',
		( part ) => {
			expect( effectPart( part ) ).toMatch(
				/:is\(\.purplish-cartpage-hidedesc, \.purplish-drawer-hidedesc\) \.wc-block-components-product-metadata__description \{\s*display: none;\s*\}/
			);
		}
	);

	it( 'leaves variation details alone', () => {
		const rules = css
			.split( '}' )
			.filter( ( rule ) => rule.includes( 'hidedesc' ) );
		expect( rules.join( '}' ) ).not.toMatch(
			/product-details|variation-data/
		);
	} );
} );
