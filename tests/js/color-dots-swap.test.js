/*
 * Swap the photo on Color Dots: a dot shows its color's photo on its own
 * card, the old photo leaves with the block's motion, a second press lets
 * the color go, and the main photo comes back.
 * A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { beforeEach, describe, expect, it } from '@jest/globals';
import {
	altFor,
	cardOf,
	chosen,
	photoOf,
	pick,
	preview,
	settle,
	show,
	wanted,
	warm,
} from '../../assets/src/swap/swap';

const card = ( id ) => `<li class="wc-block-product" id="${ id }">
	<div class="wc-block-components-product-image"><a href="#">
		<img src="main.jpg" srcset="main.jpg 480w, main-s.jpg 240w" sizes="300px" loading="lazy" data-testid="product-image" alt="Coat">
		<img class="purplish-second-photo" src="second.jpg" alt="">
		<div class="wc-block-components-product-image__inner-container"></div>
	</a></div>
	<div class="wp-block-purplish-color-dots purplish-color-dots-swap purplish-color-dots-swap-hover" data-motion="wipe" role="group">
		<button type="button" class="purplish-color-dots-dot" aria-pressed="false" aria-label="Navy" data-src="navy.jpg" data-srcset="navy.jpg 480w"></button>
		<button type="button" class="purplish-color-dots-dot" aria-pressed="false" aria-label="Olive"></button>
	</div>
</li>`;

const dot = ( id, index ) =>
	document.querySelectorAll( `#${ id } .purplish-color-dots-dot` )[ index ];
const photo = ( id ) => photoOf( document.getElementById( id ) );
const layers = ( id ) =>
	document.querySelectorAll( `#${ id } .purplish-swap-old` );

beforeEach( () => {
	document.body.innerHTML = `<ul>${ card( 'a' ) }${ card( 'b' ) }</ul>`;
} );

describe( 'finding the photo', () => {
	it( 'finds the card and its main photo, not the second photo', () => {
		expect( cardOf( dot( 'a', 0 ) ).id ).toBe( 'a' );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'main.jpg' );
		expect( photoOf( null ) ).toBeNull();
	} );

	it( 'wants the dot photo, or the photo the card started with', () => {
		const img = photo( 'a' );
		expect( wanted( img, dot( 'a', 0 ) ) ).toEqual( {
			src: 'navy.jpg',
			srcset: 'navy.jpg 480w',
		} );
		show( img, dot( 'a', 0 ), 'fade', true );
		expect( wanted( img, dot( 'a', 1 ) ) ).toEqual( {
			src: 'main.jpg',
			srcset: 'main.jpg 480w, main-s.jpg 240w',
		} );
	} );
} );

describe( 'show', () => {
	it( 'swaps at once with reduced motion, with no layer', () => {
		expect( show( photo( 'a' ), dot( 'a', 0 ), 'wipe', true ) ).toBe(
			true
		);
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'navy.jpg' );
		expect( photo( 'a' ).getAttribute( 'srcset' ) ).toBe( 'navy.jpg 480w' );
		expect( photo( 'a' ).getAttribute( 'sizes' ) ).toBe( '300px' );
		expect( layers( 'a' ) ).toHaveLength( 0 );
	} );

	it( 'leaves the old photo on top, hidden from screen readers, with the motion', () => {
		show( photo( 'a' ), dot( 'a', 0 ), 'wipe', false );
		const [ layer ] = layers( 'a' );
		expect( layer.getAttribute( 'src' ) ).toBe( 'main.jpg' );
		expect( layer.className ).toBe( 'purplish-swap-old is-wipe is-going' );
		expect( layer.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( layer.alt ).toBe( '' );
		expect( layer.hasAttribute( 'data-testid' ) ).toBe( false );
		expect( layer.previousElementSibling ).toBe( photo( 'a' ) );

		layer.dispatchEvent( new window.Event( 'animationend' ) );
		expect( layers( 'a' ) ).toHaveLength( 0 );
	} );

	it( 'slides the new photo in with slide', () => {
		show( photo( 'a' ), dot( 'a', 0 ), 'slide', false );
		expect( photo( 'a' ).classList ).toContain( 'is-purplish-slide-in' );
	} );

	it( 'keeps one layer when swaps come fast, and skips the photo already shown', () => {
		show( photo( 'a' ), dot( 'a', 0 ), 'fade', false );
		show( photo( 'a' ), dot( 'a', 1 ), 'fade', false );
		expect( layers( 'a' ) ).toHaveLength( 1 );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'main.jpg' );
		expect( show( photo( 'a' ), dot( 'a', 1 ), 'fade', false ) ).toBe(
			false
		);
	} );
} );

describe( 'picking', () => {
	it( 'keeps the picked color on its own card only', () => {
		expect( pick( dot( 'a', 0 ), true ) ).toBe( dot( 'a', 0 ) );
		expect( dot( 'a', 0 ).getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( dot( 'a', 1 ).getAttribute( 'aria-pressed' ) ).toBe( 'false' );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'navy.jpg' );
		expect( photo( 'b' ).getAttribute( 'src' ) ).toBe( 'main.jpg' );
	} );

	it( 'moves the ring, and a second press lets the color go', () => {
		pick( dot( 'a', 0 ), true );
		pick( dot( 'a', 1 ), true );
		expect( chosen( dot( 'a', 0 ).parentElement ) ).toBe( dot( 'a', 1 ) );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'main.jpg' );

		pick( dot( 'a', 0 ), true );
		expect( pick( dot( 'a', 0 ), true ) ).toBeNull();
		expect( chosen( dot( 'a', 0 ).parentElement ) ).toBeNull();
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'main.jpg' );
	} );

	it( 'previews on hover and settles back on the chosen color', () => {
		const dots = dot( 'a', 0 ).parentElement;
		preview( dot( 'a', 0 ), true );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'navy.jpg' );
		settle( dots, true );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'main.jpg' );

		pick( dot( 'a', 0 ), true );
		preview( dot( 'a', 1 ), true );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'main.jpg' );
		settle( dots, true );
		expect( photo( 'a' ).getAttribute( 'src' ) ).toBe( 'navy.jpg' );
	} );

	it( 'warms each block once', () => {
		const dots = dot( 'a', 0 ).parentElement;
		warm( dots );
		expect( dots.dataset.purplishWarm ).toBe( '1' );
	} );
} );

describe( 'alt text', () => {
	it( 'works out the alt without touching the photo', () => {
		expect( altFor( photo( 'a' ), dot( 'a', 0 ) ) ).toBe( 'Coat, Navy' );
		expect( altFor( photo( 'a' ), dot( 'a', 1 ) ) ).toBe( 'Coat' );
		expect( altFor( photo( 'a' ), null ) ).toBe( 'Coat' );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat' );
	} );

	it( 'names the color while its photo shows, then puts the alt back', () => {
		show( photo( 'a' ), dot( 'a', 0 ), 'fade', true );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat, Navy' );

		show( photo( 'a' ), null, 'fade', true );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat' );
	} );

	it( 'follows a press from the keyboard, a click or a tap', () => {
		pick( dot( 'a', 0 ), true );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat, Navy' );

		pick( dot( 'a', 0 ), true );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat' );
	} );

	it( 'follows a hover preview and settles back', () => {
		preview( dot( 'a', 0 ), false );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat, Navy' );

		settle( dot( 'a', 0 ).parentElement, false );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat' );
	} );

	it( 'uses the translated pattern from the block', () => {
		dot( 'a', 0 ).parentElement.dataset.alt = '%2$s: %1$s';
		show( photo( 'a' ), dot( 'a', 0 ), 'fade', true );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Navy: Coat' );
	} );

	it( 'leaves an empty alt empty, since the photo is decorative', () => {
		photo( 'a' ).setAttribute( 'alt', '' );
		show( photo( 'a' ), dot( 'a', 0 ), 'fade', true );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( '' );
	} );

	it( 'leaves the alt alone for a dot without its own photo', () => {
		show( photo( 'a' ), dot( 'a', 1 ), 'fade', true );
		expect( photo( 'a' ).getAttribute( 'alt' ) ).toBe( 'Coat' );
	} );

	it( 'keeps the other cards as they were', () => {
		show( photo( 'a' ), dot( 'a', 0 ), 'fade', true );
		expect( photo( 'b' ).getAttribute( 'alt' ) ).toBe( 'Coat' );
	} );
} );
