/*
 * Feedback on the My Account forms. A plain comment, since Jest reads the
 * environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it } from '@jest/globals';
import { feedback } from '../../assets/src/login/feedback';

const FORMS = `<div class="woocommerce">
	<form class="login"><p class="form-row"><input name="username" required></p><button type="submit" name="login">Log in</button></form>
	<form class="register"><p class="form-row"><input type="email" class="input-text" name="email"></p><button type="submit" name="register">Register</button></form>
</div>`;

let group;

beforeEach( () => {
	window.requestAnimationFrame = ( fn ) => fn();
	document.body.innerHTML = `<div class="purplish-account">${ FORMS }</div>`;
	group = document.body.firstChild;
} );

describe( 'feedback', () => {
	it( 'wraps each email field with a tick', () => {
		feedback( group );
		const wrap = group.querySelector( '.pa-field' );
		expect( wrap.querySelector( 'input[type=email]' ) ).not.toBeNull();
		expect( wrap.querySelector( 'svg.pa-tick' ) ).not.toBeNull();
	} );

	it( 'shakes the row of a refused field', () => {
		feedback( group );
		group
			.querySelector( '[name=username]' )
			.dispatchEvent( new window.Event( 'invalid' ) );
		expect(
			group.querySelector( '.form-row' ).classList.contains( 'pa-shake' )
		).toBe( true );
	} );

	it( 'shows busy dots once while a form sends', () => {
		feedback( group );
		const form = group.querySelector( 'form.login' );
		const send = () =>
			form.dispatchEvent(
				new window.Event( 'submit', {
					bubbles: true,
					cancelable: true,
				} )
			);
		send();
		send();
		const button = form.querySelector( 'button' );
		expect( button.getAttribute( 'aria-busy' ) ).toBe( 'true' );
		expect( button.querySelectorAll( '.pa-dots' ) ).toHaveLength( 1 );
		expect( button.querySelectorAll( '.pa-dots i' ) ).toHaveLength( 3 );
	} );

	it( 'shakes the register form after a failed sign up', () => {
		group.classList.add( 'purplish-account-open-register' );
		group.insertAdjacentHTML(
			'afterbegin',
			'<ul class="woocommerce-error"><li>Error</li></ul>'
		);
		feedback( group );
		expect(
			group
				.querySelector( 'form.register' )
				.classList.contains( 'pa-shake' )
		).toBe( true );
		expect(
			group.querySelector( 'form.login' ).classList.contains( 'pa-shake' )
		).toBe( false );
	} );
} );
