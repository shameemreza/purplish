/*
 * The log in and register switch on the My Account forms. A plain comment,
 * since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeAll, beforeEach, describe, expect, it } from '@jest/globals';
import { switcher } from '../../assets/src/login/switch';

const FORMS = `<div class="woocommerce"><div class="u-columns col2-set">
	<div class="u-column1 col-1"><h2>Login</h2><form><input name="username"></form></div>
	<div class="u-column2 col-2"><h2>Register</h2><form><input type="hidden" name="n"><input name="email"></form></div>
</div></div>`;

let group;

const panels = () => [ ...group.querySelectorAll( '[role=tabpanel]' ) ];
const tabs = () => [ ...group.querySelectorAll( '[role=tab]' ) ];
const selected = () =>
	tabs().map( ( tab ) => tab.getAttribute( 'aria-selected' ) );

beforeAll( () => {
	window.matchMedia = () => ( { matches: true } );
	window.HTMLElement.prototype.animate = () => ( {} );
} );

beforeEach( () => {
	window.location.hash = '';
	document.body.innerHTML = `<div class="purplish-account">${ FORMS }</div>`;
	group = document.body.firstChild;
} );

describe( 'switcher', () => {
	it( 'turns both forms into tabs with the login form open', () => {
		group.classList.add( 'purplish-account-switch-tabs' );
		switcher( group, 2 );

		expect( tabs().map( ( tab ) => tab.textContent ) ).toEqual( [
			'Login',
			'Register',
		] );
		expect( tabs()[ 1 ].getAttribute( 'aria-controls' ) ).toBe(
			'pa-panel-2-register'
		);
		expect( panels()[ 1 ].getAttribute( 'aria-labelledby' ) ).toBe(
			'pa-tab-2-register'
		);
		expect( selected() ).toEqual( [ 'true', 'false' ] );
		expect( panels().map( ( panel ) => panel.inert ) ).toEqual( [
			false,
			true,
		] );
		expect( group.querySelector( '.pa-slide' ) ).toBeNull();
	} );

	it( 'switches with a click and with the arrow keys', () => {
		group.classList.add( 'purplish-account-switch-tabs' );
		switcher( group, 0 );

		tabs()[ 1 ].click();
		expect( selected() ).toEqual( [ 'false', 'true' ] );
		expect( panels()[ 0 ].classList.contains( 'pa-off' ) ).toBe( true );

		group.querySelector( '.pa-tabs' ).dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'ArrowLeft',
				bubbles: true,
			} )
		);
		expect( selected() ).toEqual( [ 'true', 'false' ] );
	} );

	it( 'opens on the register form after a failed sign up', () => {
		group.classList.add(
			'purplish-account-switch-flip',
			'purplish-account-open-register'
		);
		switcher( group, 0 );
		expect( selected() ).toEqual( [ 'false', 'true' ] );
	} );

	it( 'adds the Slide panel and its pieces, and its button switches', () => {
		group.classList.add(
			'purplish-account-switch-slide',
			'purplish-account-slide-mosaic'
		);
		switcher( group, 0 );

		const slide = group.querySelector( '.pa-slide' );
		expect( slide.querySelectorAll( '.pa-tiles i' ) ).toHaveLength( 48 );
		expect( slide.dataset.active ).toBe( 'login' );

		const side = slide.querySelector( '.pa-slide-side:not([hidden])' );
		expect( side.querySelector( 'h3' ).textContent ).toBe( 'New here?' );
		side.querySelector( 'button' ).click();

		expect( slide.dataset.active ).toBe( 'register' );
		expect( document.activeElement.name ).toBe( 'email' );
	} );

	it( 'leaves the page alone without both forms', () => {
		group.classList.add( 'purplish-account-switch-tabs' );
		group.querySelector( '.u-column2' ).remove();
		switcher( group, 0 );
		expect( group.querySelector( '.pa-tabs' ) ).toBeNull();
	} );
} );
