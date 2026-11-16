/*
 * A Cart page meter set to the totals column moves to the top of that
 * column, and back in when WooCommerce draws the column again. A plain
 * comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it, jest } from '@jest/globals';

const tick = () => new Promise( ( done ) => setTimeout( done, 0 ) );

describe( 'meter in the totals column', () => {
	it( 'moves to the top of the column and follows a redraw', async () => {
		document.body.innerHTML =
			'<div class="purplish-meter purplish-meter-totals"><p class="purplish-meter-text"></p></div>' +
			'<div class="wp-block-woocommerce-cart"><div class="wc-block-cart__sidebar"><div class="summary"></div></div></div>';
		const meter = document.querySelector( '.purplish-meter' );

		await jest.isolateModulesAsync(
			() => import( '../../assets/src/meter/index.js' )
		);
		expect(
			document.querySelector( '.wc-block-cart__sidebar' )
				.firstElementChild
		).toBe( meter );

		const cart = document.querySelector( '.wp-block-woocommerce-cart' );
		cart.innerHTML =
			'<div class="wc-block-cart__sidebar"><div class="summary"></div></div>';
		await tick();
		expect(
			document.querySelector( '.wc-block-cart__sidebar' )
				.firstElementChild
		).toBe( meter );
	} );

	it( 'leaves a meter above the cart where it is', async () => {
		document.body.innerHTML =
			'<div class="purplish-meter purplish-meter-page"></div>' +
			'<div class="wp-block-woocommerce-cart"><div class="wc-block-cart__sidebar"></div></div>';
		await jest.isolateModulesAsync(
			() => import( '../../assets/src/meter/index.js' )
		);
		expect( document.body.firstElementChild.className ).toBe(
			'purplish-meter purplish-meter-page'
		);
	} );
} );
