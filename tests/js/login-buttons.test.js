/*
 * Button looks on the My Account forms that need a script. A plain
 * comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it } from '@jest/globals';
import { bloom, ripple, roll } from '../../assets/src/login/buttons';

let group;
let button;
let reduced = false;

beforeEach( () => {
	window.matchMedia = () => ( { matches: reduced } );
	document.body.innerHTML =
		'<div class="purplish-account"><form><button type="submit" name="login">Log in</button></form></div>';
	group = document.body.firstChild;
	button = group.querySelector( 'button' );
	button.getBoundingClientRect = () => ( {
		left: 100,
		top: 50,
		width: 120,
		height: 40,
	} );
} );

describe( 'buttons', () => {
	it( 'Bloom starts the color where the pointer comes in', () => {
		bloom( group );
		button.dispatchEvent(
			new window.MouseEvent( 'pointerover', {
				bubbles: true,
				clientX: 130,
				clientY: 60,
			} )
		);
		expect( button.style.getPropertyValue( '--pa-bx' ) ).toBe( '30px' );
		expect( button.style.getPropertyValue( '--pa-by' ) ).toBe( '10px' );
	} );

	it( 'Roll keeps the words, once, with a copy for CSS', () => {
		roll( group );
		roll( group );
		const words = button.querySelectorAll( '.pa-roll' );
		expect( words ).toHaveLength( 1 );
		expect( words[ 0 ].dataset.text ).toBe( 'Log in' );
		expect( button.textContent ).toBe( 'Log in' );
		expect( button.name ).toBe( 'login' );
	} );

	it( 'Ripple spreads from the press, or the middle for the keyboard', () => {
		ripple( group );
		button.dispatchEvent(
			new window.MouseEvent( 'click', {
				bubbles: true,
				clientX: 110,
				clientY: 70,
				detail: 1,
			} )
		);
		button.dispatchEvent(
			new window.MouseEvent( 'click', { bubbles: true, detail: 0 } )
		);
		const inks = button.querySelectorAll( '.pa-ripple' );
		expect( inks ).toHaveLength( 2 );
		expect( [ inks[ 0 ].style.left, inks[ 0 ].style.top ] ).toEqual( [
			'10px',
			'20px',
		] );
		expect( [ inks[ 1 ].style.left, inks[ 1 ].style.top ] ).toEqual( [
			'60px',
			'20px',
		] );
		expect( inks[ 0 ].getAttribute( 'aria-hidden' ) ).toBe( 'true' );
	} );

	it( 'Ripple stays still under reduced motion', () => {
		reduced = true;
		ripple( group );
		button.click();
		reduced = false;
		expect( button.querySelector( '.pa-ripple' ) ).toBeNull();
	} );
} );
