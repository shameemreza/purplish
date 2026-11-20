/*
 * Photo swatch samples on the Variation Selector in the editor.
 * A plain comment, since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it } from '@jest/globals';
import {
	paint,
	photoRow,
	sampleColor,
	sweater,
} from '../../assets/src/editor/photo-swatches-preview';

const row = ( slug, label, options ) => `
	<div class="wp-block-group">
		<label class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name" for="${ slug }">${ label }</label>
		<div class="wc-block-product-filter-chips">
			${ options
				.map(
					( option ) =>
						`<div class="wc-block-product-filter-chips__item" aria-checked="false"><span class="wc-block-product-filter-chips__label"><span class="wc-block-product-filter-chips__swatch wc-block-product-filter-chips__swatch--no-color"></span><span class="wc-block-product-filter-chips__text">${ option }</span></span></div>`
				)
				.join( '' ) }
		</div>
	</div>`;

const block = () => {
	document.body.innerHTML = `<div class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute">${ row(
		'pa_size',
		'Size',
		[ 'Small', 'Medium' ]
	) }${ row( 'pa_color', 'Color', [ 'Blue', 'Red', 'Green' ] ) }</div>`;
	return document.body.firstElementChild;
};

describe( 'sampleColor', () => {
	it( 'uses the sample colors, then CSS color names, then gray', () => {
		const view = { CSS: { supports: ( p, v ) => 'forestgreen' === v } };
		expect( sampleColor( 'Blue', view ) ).toBe( '#3c5a8a' );
		expect( sampleColor( 'Forest green', view ) ).toBe( 'forestgreen' );
		expect( sampleColor( 'Heather', view ) ).toBe( '#8a8790' );
	} );
} );

describe( 'sweater', () => {
	it( 'draws an SVG image in the color', () => {
		const image = sweater( '#3c5a8a' );
		expect( image.startsWith( 'url("data:image/svg+xml,' ) ).toBe( true );
		expect( decodeURIComponent( image ) ).toContain( 'fill="#3c5a8a"' );
	} );
} );

describe( 'photoRow', () => {
	it( 'finds the color row on Auto and the picked one when set', () => {
		const el = block();
		expect( photoRow( el, '' ).querySelector( 'label' ).textContent ).toBe(
			'Color'
		);
		expect(
			photoRow( el, 'pa_size' ).querySelector( 'label' ).textContent
		).toBe( 'Size' );
		expect( photoRow( el, 'pattern' ) ).toBeNull();
	} );
} );

describe( 'paint', () => {
	it( 'gives each option a sample photo, picks the first, and names it', () => {
		const color = photoRow( block(), '' );
		paint( color, true );

		const chips = color.querySelector( '.wc-block-product-filter-chips' );
		expect( chips.classList.contains( 'purplish-photo-swatches' ) ).toBe(
			true
		);
		expect( chips.classList.contains( 'is-style-swatch' ) ).toBe( true );
		expect(
			color.querySelectorAll(
				'.wc-block-product-filter-chips__swatch--no-color'
			)
		).toHaveLength( 0 );
		const swatch = color.querySelector(
			'.wc-block-product-filter-chips__swatch'
		);
		expect( swatch.style.backgroundImage ).toContain(
			'data:image/svg+xml'
		);
		const items = color.querySelectorAll(
			'.wc-block-product-filter-chips__item'
		);
		expect( items[ 0 ].getAttribute( 'aria-checked' ) ).toBe( 'true' );
		expect( items[ 1 ].getAttribute( 'aria-checked' ) ).toBe( 'false' );
		expect(
			color.querySelector( '.purplish-photo-swatches__picked' )
				.textContent
		).toBe( 'Blue' );
	} );

	it( 'changes nothing when drawn again, so the watcher settles', () => {
		const color = photoRow( block(), '' );
		paint( color, true );
		const watch = new window.MutationObserver( () => {} );
		watch.observe( color, {
			childList: true,
			subtree: true,
			attributes: true,
		} );
		paint( color, true );

		expect( watch.takeRecords() ).toHaveLength( 0 );
		watch.disconnect();
	} );

	it( 'leaves the name out when it is off', () => {
		const color = photoRow( block(), '' );
		paint( color, true );
		paint( color, false );

		expect(
			color.querySelector( '.purplish-photo-swatches__picked' )
		).toBeNull();
	} );
} );
