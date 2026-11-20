/*
 * The picked color's name beside the attribute name on the Variation
 * Selector follows the chosen swatch.
 * A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it } from '@jest/globals';
import { pickedName, watchPicked } from '../../assets/src/picked/picked';

const ROW = `<div class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute">
	<span class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name">Color<span class="purplish-photo-swatches__picked" aria-hidden="true"></span></span>
	<div class="wc-block-product-filter-chips is-style-swatch purplish-photo-swatches">
		<button class="wc-block-product-filter-chips__item" aria-label="Forest green" aria-checked="false"></button>
		<button class="wc-block-product-filter-chips__item" aria-label="Blush pink" aria-checked="false"></button>
	</div>
</div>`;

const flush = () => new Promise( ( done ) => setTimeout( done, 0 ) );

describe( 'picked name', () => {
	beforeEach( () => {
		document.body.innerHTML = ROW;
	} );

	it( 'reads the chosen option, or nothing', () => {
		const row = document.body.firstElementChild;
		expect( pickedName( row ) ).toBe( '' );
		row.querySelectorAll( 'button' )[ 1 ].setAttribute(
			'aria-checked',
			'true'
		);
		expect( pickedName( row ) ).toBe( 'Blush pink' );
	} );

	it( 'follows the chosen option and clears when none is chosen', async () => {
		const slot = document.querySelector(
			'.purplish-photo-swatches__picked'
		);
		const [ green, pink ] = document.querySelectorAll( 'button' );
		watchPicked( slot );

		green.setAttribute( 'aria-checked', 'true' );
		await flush();
		expect( slot.textContent ).toBe( 'Forest green' );

		green.setAttribute( 'aria-checked', 'false' );
		pink.setAttribute( 'aria-checked', 'true' );
		await flush();
		expect( slot.textContent ).toBe( 'Blush pink' );

		pink.setAttribute( 'aria-checked', 'false' );
		await flush();
		expect( slot.textContent ).toBe( '' );
	} );

	it( 'does nothing outside a row', () => {
		document.body.innerHTML =
			'<span class="purplish-photo-swatches__picked">Kept</span>';
		const slot = document.body.firstElementChild;
		expect( () => watchPicked( slot ) ).not.toThrow();
		expect( slot.textContent ).toBe( 'Kept' );
	} );
} );
