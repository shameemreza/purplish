/*
 * The public `purplish:colorchange` event on document. A plain comment,
 * since Jest reads the environment from it and ESLint's JSDoc rules don't
 * know the tag.
 *
 * @jest-environment jsdom
 */
import {
	afterEach,
	beforeEach,
	describe,
	expect,
	jest,
	test,
} from '@jest/globals';

jest.mock( '../../assets/src/frontend/style.css', () => ( {} ) );

const RED = {
	color: '#cc2200',
	on: '#ffffff',
	ink: '#aa1c00',
	tint: '#fbeee9',
};
const BLUE = {
	color: '#2244cc',
	on: '#ffffff',
	ink: '#1c38aa',
	tint: '#eaeefb',
};
const GREEN = {
	color: '#22aa44',
	on: '#ffffff',
	ink: '#1c8a38',
	tint: '#eaf8ee',
};

const variations = [
	{ attributes: { attribute_pa_color: 'red' }, ...RED },
	{ attributes: { attribute_pa_color: 'blue' }, ...BLUE },
];

let events;
let changes;
const onColor = ( event ) => events.push( event );
const onChange = ( event ) => changes.push( event );

/**
 * Builds an Add to Cart + Options block and loads the front-end script.
 *
 * @param {Object|null} fallback The page's default color set.
 * @return {HTMLInputElement} The color input WooCommerce keeps up to date.
 */
function load( fallback ) {
	document.body.className = '';
	document.body.removeAttribute( 'style' );
	document.body.innerHTML = `
		<div class="wp-block-woocommerce-add-to-cart-with-options">
			<input type="hidden" name="attribute_pa_color" value="">
		</div>`;
	window.purplishData = { default: fallback, variations };
	jest.isolateModules( () => {
		require( '../../assets/src/frontend/index.js' );
	} );
	return document.querySelector( 'input[name="attribute_pa_color"]' );
}

/**
 * Picks a value the way WooCommerce does, then lets the 0 ms read run.
 *
 * @param {HTMLInputElement} input The color input.
 * @param {string}           value The value, or '' for the reset link.
 */
function pick( input, value ) {
	input.value = value;
	input.dispatchEvent( new window.Event( 'change', { bubbles: true } ) );
	jest.runAllTimers();
}

beforeEach( () => {
	jest.useFakeTimers();
	events = [];
	changes = [];
	document.addEventListener( 'purplish:colorchange', onColor );
	document.body.addEventListener( 'purplish:change', onChange );
} );

afterEach( () => {
	document.removeEventListener( 'purplish:colorchange', onColor );
	document.body.removeEventListener( 'purplish:change', onChange );
	jest.useRealTimers();
} );

describe( 'purplish:colorchange', () => {
	test( 'fires on document with the color set when a variation is picked', () => {
		const input = load( null );
		pick( input, 'red' );

		expect( events ).toHaveLength( 1 );
		expect( events[ 0 ].target ).toBe( document );
		expect( events[ 0 ].detail ).toEqual( RED );
	} );

	test( 'fires with the new set on each change of color', () => {
		const input = load( null );
		pick( input, 'red' );
		pick( input, 'blue' );

		expect( events.map( ( event ) => event.detail ) ).toEqual( [
			RED,
			BLUE,
		] );
	} );

	test( 'fires with null when a reset goes back to the theme colors', () => {
		const input = load( null );
		pick( input, 'red' );
		pick( input, '' );

		expect( events ).toHaveLength( 2 );
		expect( events[ 1 ].detail ).toBeNull();
	} );

	test( 'fires with the default set when a reset goes back to it', () => {
		const input = load( GREEN );
		pick( input, 'blue' );
		pick( input, '' );

		expect( events.map( ( event ) => event.detail ) ).toEqual( [
			BLUE,
			GREEN,
		] );
	} );

	test( 'stays quiet when the color does not change', () => {
		const input = load( null );
		pick( input, 'red' );
		pick( input, 'red' );

		expect( events ).toHaveLength( 1 );
	} );

	test( 'hands listeners a copy, so changing it leaves the page color alone', () => {
		const input = load( null );
		pick( input, 'red' );
		events[ 0 ].detail.color = '#000000';
		pick( input, 'red' );

		expect( events ).toHaveLength( 1 );
		expect(
			document.body.style.getPropertyValue( '--purplish-accent' )
		).toBe( RED.color );
	} );

	test( 'leaves the 1.0 purplish:change event on body as it was', () => {
		const input = load( null );
		pick( input, 'red' );
		pick( input, '' );

		expect( changes.map( ( event ) => event.detail ) ).toEqual( [
			{ set: RED },
			{ set: null },
		] );
		expect( changes[ 0 ].target ).toBe( document.body );
	} );
} );
