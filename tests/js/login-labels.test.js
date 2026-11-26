/*
 * Floating and Wave labels on the My Account forms. A plain comment, since
 * Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it } from '@jest/globals';
import { setup } from '../../assets/src/login/setup';

const FORM = `<form><p class="form-row"><label for="u">Email <span class="required">*</span></label><input class="input-text" id="u" value=""></p></form>`;

let group;

beforeEach( () => {
	document.body.innerHTML = `<div class="purplish-account">${ FORM }</div>`;
	group = document.body.firstChild;
} );

describe( 'setup', () => {
	it( 'leaves labels alone without a moving label option', () => {
		group.classList.add( 'purplish-account-labels-inside' );
		setup( group );
		expect( group.classList.contains( 'pa-ready' ) ).toBe( false );
		expect( group.querySelector( 'label' ).innerHTML ).toBe(
			'Email <span class="required">*</span>'
		);
	} );

	it( 'marks a row filled while its field has a value, and touched after focus', () => {
		group.classList.add( 'purplish-account-labels-floating' );
		setup( group );
		const input = group.querySelector( 'input' );
		const row = group.querySelector( '.form-row' );
		expect( group.classList.contains( 'pa-ready' ) ).toBe( true );

		input.value = 'ann';
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		expect( row.classList.contains( 'pa-filled' ) ).toBe( true );

		input.value = '';
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		expect( row.classList.contains( 'pa-filled' ) ).toBe( false );

		input.dispatchEvent(
			new window.FocusEvent( 'focusin', { bubbles: true } )
		);
		expect( row.classList.contains( 'pa-touched' ) ).toBe( true );
	} );

	it( 'splits Wave labels into letters, with the words once for screen readers', () => {
		group.classList.add( 'purplish-account-labels-wave' );
		setup( group );
		const label = group.querySelector( 'label' );
		const letters = label.querySelectorAll( '.pa-ch' );
		expect(
			Array.from( letters )
				.map( ( l ) => l.textContent )
				.join( '' )
		).toBe( 'Email' );
		expect( letters[ 2 ].style.getPropertyValue( '--i' ) ).toBe( '2' );
		expect( label.querySelector( '.pa-sr' ).textContent ).toBe( 'Email' );
		expect( label.querySelector( '[aria-hidden="true"]' ) ).not.toBeNull();
		expect( label.querySelector( '.required' ) ).not.toBeNull();
		expect( group.classList.contains( 'pa-ready' ) ).toBe( true );
	} );
} );
