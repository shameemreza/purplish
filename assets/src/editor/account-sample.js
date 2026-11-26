/**
 * WooCommerce's log in and register forms, as My Account prints them for a
 * logged-out visitor with registration on, for the editor's sample. The
 * password fields come wrapped with the show-password button, as
 * WooCommerce's script leaves them.
 */
import { __ } from '@wordpress/i18n';
import { escapeHTML, escapeAttribute } from '@wordpress/escape-html';

let count = 0;

/**
 * One text field row.
 *
 * @param {string} id    Field ID.
 * @param {string} type  Input type.
 * @param {string} label Label text.
 * @return {string} Markup.
 */
function row( id, type, label ) {
	const required = `&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">${ escapeHTML(
		__( 'Required', 'purplish' )
	) }</span>`;
	const input = `<input type="${ type }" class="woocommerce-Input woocommerce-Input--text input-text" id="${ id }" value="" />`;
	const field =
		'password' === type
			? `<span class="password-input">${ input }<button type="button" class="show-password-input" aria-label="${ escapeAttribute(
					__( 'Show password', 'purplish' )
				) }"></button></span>`
			: input;
	return `<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label for="${ id }">${ escapeHTML(
		label
	) }${ required }</label>${ field }</p>`;
}

/**
 * The sample forms' markup, with IDs of their own.
 *
 * @return {string} Markup.
 */
export function sampleForms() {
	count++;
	const id = ( name ) => `purplish-sample-${ count }-${ name }`;
	const button = ( text, cls ) =>
		`<button type="submit" class="woocommerce-button button wp-element-button ${ cls }">${ escapeHTML(
			text
		) }</button>`;

	return `<div class="woocommerce"><div class="u-columns col2-set">
<div class="u-column1 col-1"><h2>${ escapeHTML( __( 'Login', 'purplish' ) ) }</h2>
<form class="woocommerce-form woocommerce-form-login login" novalidate>
${ row( id( 'username' ), 'text', __( 'Username or email address', 'purplish' ) ) }
${ row( id( 'password' ), 'password', __( 'Password', 'purplish' ) ) }
<p class="form-row"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme"><input class="woocommerce-form__input woocommerce-form__input-checkbox" type="checkbox" id="${ id(
		'rememberme'
	) }" /> <span>${ escapeHTML( __( 'Remember me', 'purplish' ) ) }</span></label>
${ button( __( 'Log in', 'purplish' ), 'woocommerce-form-login__submit' ) }</p>
<p class="woocommerce-LostPassword lost_password"><a href="#lost-password">${ escapeHTML(
		__( 'Lost your password?', 'purplish' )
	) }</a></p>
</form></div>
<div class="u-column2 col-2"><h2>${ escapeHTML( __( 'Register', 'purplish' ) ) }</h2>
<form class="woocommerce-form woocommerce-form-register register">
${ row( id( 'email' ), 'email', __( 'Email address', 'purplish' ) ) }
${ row( id( 'reg-password' ), 'password', __( 'Password', 'purplish' ) ) }
<div class="woocommerce-privacy-policy-text"><p>${ escapeHTML(
		__(
			'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our privacy policy.',
			'purplish'
		)
	) }</p></div>
<p class="woocommerce-form-row form-row">${ button(
		__( 'Register', 'purplish' ),
		'woocommerce-form-register__submit'
	) }</p>
</form></div>
</div></div>`;
}
