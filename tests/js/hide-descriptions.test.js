/**
 * Hide short descriptions hides only the short description, so variation
 * details stay on each item.
 */
import { describe, expect, it } from '@jest/globals';
import { readFileSync } from 'fs';
import { join } from 'path';

const css = readFileSync(
	join( __dirname, '../../assets/src/effects/style.css' ),
	'utf8'
);

describe( 'hide short descriptions', () => {
	it( 'hides the description on the Cart page and in the drawer', () => {
		expect( css ).toMatch(
			/:is\(\.purplish-cartpage-hidedesc, \.purplish-drawer-hidedesc\) \.wc-block-components-product-metadata__description \{\s*display: none;\s*\}/
		);
	} );

	it( 'leaves variation details alone', () => {
		const rules = css
			.split( '}' )
			.filter( ( rule ) => rule.includes( 'hidedesc' ) );
		expect( rules.join( '}' ) ).not.toMatch(
			/product-details|variation-data/
		);
	} );
} );
