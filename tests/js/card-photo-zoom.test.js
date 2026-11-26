/**
 * The overlay and details cards grow the photo a little on hover, only
 * when the shopper hasn't asked for reduced motion.
 */
import { describe, expect, it } from '@jest/globals';
import { effectPart } from './effect-styles';

const css = effectPart( 'cards' );

const ZOOM =
	':is(.purplish-cards-overlay, .purplish-cards-details):not([class*="purplish-cards-hoverphoto-"], [class*="purplish-cards-hovercard-"]) .wc-block-product:hover .wc-block-components-product-image img';

/**
 * The media query wrapping the first rule whose selector is `selector`.
 *
 * @param {string} selector The selector.
 * @return {string} The query, or an empty string.
 */
function query( selector ) {
	const at = css.indexOf( selector + ' {' );
	if ( -1 === at ) {
		return '';
	}
	const open = css.lastIndexOf( '@media', at );
	return -1 === open ? '' : css.slice( open, css.indexOf( '{', open ) );
}

describe( 'card photo zoom', () => {
	it( 'grows the photo on hover', () => {
		const at = css.indexOf( ZOOM + ' {' );
		expect( at ).toBeGreaterThan( -1 );
		expect( css.slice( at, css.indexOf( '}', at ) ) ).toMatch(
			/transform:\s*scale\(1\.06\)/
		);
	} );

	it( 'only zooms when motion is allowed', () => {
		expect( query( ZOOM ) ).toMatch(
			/prefers-reduced-motion:\s*no-preference/
		);
	} );
} );
