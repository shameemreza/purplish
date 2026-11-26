/*
 * Aurora and Spotlight on the My Account forms. A plain comment, since
 * Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it } from '@jest/globals';
import { light } from '../../assets/src/login/light';

let group;
let reduced = false;

beforeEach( () => {
	window.matchMedia = () => ( { matches: reduced } );
	window.innerWidth = 1000;
	window.innerHeight = 800;
	window.requestAnimationFrame = ( fn ) => setTimeout( fn, 0 );
	document.body.innerHTML =
		'<div class="purplish-account"><div class="woocommerce"><form class="login"><input></form></div></div>';
	group = document.body.firstChild;
} );

describe( 'light', () => {
	it( 'rests in the middle, and Spotlight gives each panel its point', () => {
		light( group, true );
		expect( group.style.getPropertyValue( '--pa-mx' ) ).toBe( '500.0px' );
		expect( group.style.getPropertyValue( '--pa-my' ) ).toBe( '320.0px' );
		expect( group.style.getPropertyValue( '--pa-hue' ) ).toBe( '0.0' );
		expect(
			group.querySelector( 'form' ).style.getPropertyValue( '--pa-px' )
		).toBe( '500.0px' );
	} );

	it( 'lights while the pointer is over the forms', () => {
		light( group, false );
		group.dispatchEvent( new window.Event( 'pointerenter' ) );
		expect( group.classList.contains( 'pa-lit' ) ).toBe( true );
		group.dispatchEvent( new window.Event( 'pointerleave' ) );
		expect( group.classList.contains( 'pa-lit' ) ).toBe( false );
	} );

	it( 'stays still under reduced motion', () => {
		reduced = true;
		light( group, false );
		reduced = false;
		group.dispatchEvent( new window.Event( 'pointerenter' ) );
		expect( group.classList.contains( 'pa-lit' ) ).toBe( false );
		expect( group.style.getPropertyValue( '--pa-mx' ) ).toBe( '500.0px' );
	} );
} );
