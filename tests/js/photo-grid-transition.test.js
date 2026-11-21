/*
 * The photo transition on a Product Gallery with a photo grid: from 600px
 * each tile plays it, below that the slider's strip does. A plain comment,
 * since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, jest, test } from '@jest/globals';

jest.mock( '../../assets/src/frontend/style.css', () => ( {} ) );

const RED = {
	color: '#cc2200',
	on: '#ffffff',
	ink: '#aa1c00',
	tint: '#fbeee9',
};

/**
 * Builds a grid gallery with one hidden variation photo and an Add to
 * Cart + Options block, loads the front-end script and picks red.
 *
 * @param {boolean} wide Whether the window is 600px or wider.
 * @return {HTMLElement} The gallery's strip.
 */
function pickRed( wide ) {
	window.matchMedia = ( query ) => ( {
		matches: wide && query.includes( '600px' ),
		addEventListener: () => {},
	} );
	document.body.className = '';
	document.body.innerHTML = `
		<div class="wp-block-woocommerce-product-gallery purplish-gallery purplish-gallery-layout-mosaic">
			<div class="wc-block-product-gallery-large-image">
				<ul class="wc-block-product-gallery-large-image__container">
					<li class="wc-block-product-gallery-large-image__wrapper"><img data-image-id="1" alt=""></li>
					<li class="wc-block-product-gallery-large-image__wrapper"><img data-image-id="2" alt=""></li>
					<li class="wc-block-product-gallery-large-image__wrapper" hidden><img data-image-id="3" alt=""></li>
				</ul>
			</div>
		</div>
		<div class="wp-block-woocommerce-add-to-cart-with-options">
			<input type="hidden" name="attribute_pa_color" value="">
		</div>`;
	window.purplishData = {
		default: null,
		variations: [ { attributes: { attribute_pa_color: 'red' }, ...RED } ],
	};
	jest.useFakeTimers();
	jest.isolateModules( () => {
		require( '../../assets/src/frontend/index.js' );
	} );
	document.body.classList.add( 'purplish-transition-wipe' );
	const input = document.querySelector( 'input' );
	input.value = 'red';
	input.dispatchEvent( new window.Event( 'change', { bubbles: true } ) );
	jest.runAllTimers();
	jest.useRealTimers();
	return document.querySelector(
		'.wc-block-product-gallery-large-image__container'
	);
}

const swapping = ( element ) =>
	element.matches( '.purplish-swap-a, .purplish-swap-b' );

describe( 'the photo transition on a photo grid', () => {
	test( 'plays on every tile, the hidden variation photo too', () => {
		const strip = pickRed( true );
		expect( swapping( strip ) ).toBe( false );
		[ ...strip.children ].forEach( ( slide ) => {
			expect( swapping( slide ) ).toBe( true );
			expect( slide.style.getPropertyValue( '--purplish-wash' ) ).toBe(
				RED.color
			);
		} );
	} );

	test( 'plays on the strip on phones, where the slider is', () => {
		const strip = pickRed( false );
		expect( swapping( strip ) ).toBe( true );
		expect( [ ...strip.children ].some( swapping ) ).toBe( false );
	} );
} );
