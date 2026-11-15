/**
 * The outline card button: a thin edge in the text color at rest, the
 * effect color on hover and keyboard focus.
 */
import { describe, expect, it } from '@jest/globals';
import fs from 'fs';
import path from 'path';

const css = fs.readFileSync(
	path.join( __dirname, '../../assets/src/effects/style.css' ),
	'utf8'
);

const BUTTON =
	'.purplish-cards-buttonstyle-outline .wc-block-product .wc-block-components-product-button__button';

/**
 * The declarations of the first rule whose selector is exactly `selector`.
 *
 * @param {string} selector The selector.
 * @return {string} The declarations, or an empty string.
 */
function rule( selector ) {
	const at = css.indexOf( selector + ' {' );
	if ( -1 === at ) {
		return '';
	}
	return css.slice( at, css.indexOf( '}', at ) );
}

describe( 'outline card button', () => {
	it( 'has a thin edge in the text color and no fill', () => {
		const rest = rule( BUTTON );
		expect( rest ).toMatch( /border:\s*1px solid currentcolor/ );
		expect( rest ).toMatch( /background-color:\s*transparent/ );
		expect( rest ).toMatch( /width:\s*100%/ );
		expect( rest ).toMatch( /min-height:\s*44px/ );
	} );

	it( 'fills with the effect color on hover and keyboard focus', () => {
		const on = rule(
			BUTTON + ':is(:hover, :focus-visible, .purplish-hover)'
		);
		expect( on ).toMatch( /background-color:\s*var\(--purplish-color\)/ );
		expect( on ).toMatch( /color:\s*var\(--purplish-color-on\)/ );
	} );

	it( 'only moves its colors when motion is allowed', () => {
		expect( css ).toMatch(
			/prefers-reduced-motion:\s*no-preference\)\s*{\s*\.purplish-cards-buttonstyle-outline/
		);
	} );
} );
