/**
 * The editor preview works out the same classes and color as the server.
 */
import { describe, expect, it } from '@jest/globals';
import {
	classesFor,
	colorStyle,
	eyesIn,
	glassSelector,
	HOVER_BUTTONS,
	onColor,
	pageClasses,
	pageStyle,
} from '../../assets/src/editor/preview-classes';

const buyButton = {
	prefix: 'buybutton',
	options: { glow: true, sweep: true, glass: true, spectrum: true },
	legacy: {},
};

describe( 'classesFor', () => {
	it( 'adds the prefix and one class per switch that is on', () => {
		expect(
			classesFor( buyButton, { glow: true, glass: true, sweep: false } )
		).toEqual( [
			'purplish-buybutton',
			'purplish-buybutton-glow',
			'purplish-buybutton-glass',
		] );
	} );

	it( 'gives nothing when no option is on', () => {
		expect( classesFor( buyButton, { color: '#7b2cbf' } ) ).toEqual( [] );
		expect( classesFor( buyButton, {} ) ).toEqual( [] );
		expect( classesFor( buyButton, undefined ) ).toEqual( [] );
		expect( classesFor( undefined, { glow: true } ) ).toEqual( [] );
	} );

	it( 'ignores unknown values', () => {
		expect( classesFor( buyButton, { glow: 'yes', eyes: 'two' } ) ).toEqual(
			[]
		);
	} );

	it( 'takes listed values and reads legacy switches', () => {
		const gallery = {
			prefix: 'gallery',
			options: { hover: [ 'tilt', 'zoom' ] },
			legacy: { tilt: [ 'hover', 'tilt' ] },
		};
		expect( classesFor( gallery, { hover: 'zoom' } ) ).toEqual( [
			'purplish-gallery',
			'purplish-gallery-hover-zoom',
		] );
		expect( classesFor( gallery, { tilt: true } ) ).toEqual( [
			'purplish-gallery',
			'purplish-gallery-hover-tilt',
		] );
		expect( classesFor( gallery, { tilt: true, hover: 'zoom' } ) ).toEqual(
			[ 'purplish-gallery', 'purplish-gallery-hover-zoom' ]
		);
		expect( classesFor( gallery, { hover: 'spin' } ) ).toEqual( [] );
	} );
} );

describe( 'onColor', () => {
	it( 'matches the server: white on dark colors, black on light ones', () => {
		expect( onColor( '#7b2cbf' ) ).toBe( '#ffffff' );
		expect( onColor( '#ffeb3b' ) ).toBe( '#000000' );
		expect( onColor( '#000' ) ).toBe( '#ffffff' );
		expect( onColor( '#FFF' ) ).toBe( '#000000' );
		expect( onColor( 'purple' ) ).toBe( '#000000' );
	} );
} );

describe( 'colorStyle', () => {
	it( 'gives both custom properties for a valid color only', () => {
		expect( colorStyle( { color: '#7b2cbf' } ) ).toEqual( {
			'--purplish-effect': '#7b2cbf',
			'--purplish-effect-on': '#ffffff',
		} );
		expect( colorStyle( { color: 'red;background:x' } ) ).toEqual( {} );
		expect( colorStyle( {} ) ).toEqual( {} );
	} );
} );

describe( 'glassSelector', () => {
	it( 'names the checkout buttons only when glass is on', () => {
		expect(
			glassSelector( [
				'purplish-buybutton',
				'purplish-buybutton-glass',
			] )
		).toBe(
			'.wc-block-cart__submit-button, .wc-block-components-checkout-place-order-button, .wc-block-mini-cart__footer-checkout'
		);
		expect(
			glassSelector( [ 'purplish-buybutton', 'purplish-buybutton-glow' ] )
		).toBe( '' );
	} );

	it( 'names the add to cart button when its glass is on', () => {
		expect(
			glassSelector( [ 'purplish-cart', 'purplish-cart-glass' ] )
		).toBe( '.wc-block-components-product-button__button' );
	} );
} );

describe( 'eyesIn', () => {
	it( 'counts the eyes the classes ask for', () => {
		expect(
			eyesIn( [ 'purplish-cart', 'purplish-cart-eyes-two' ], 'cart' )
		).toBe( 2 );
		expect(
			eyesIn( [ 'purplish-cart', 'purplish-cart-eyes-one' ], 'cart' )
		).toBe( 1 );
		expect(
			eyesIn( [ 'purplish-cart', 'purplish-cart-glass' ], 'cart' )
		).toBe( 0 );
		expect( eyesIn( [ 'purplish-badge-eyes-two' ], 'cart' ) ).toBe( 0 );
	} );
} );

const page = {
	parts: { button: true, chip: true, badge: true, price: false, tint: false },
	switches: [ 'glow', 'mesh' ],
	sample: {
		color: '#8f43ee',
		on: '#ffffff',
		ink: '#4f2583',
		tint: '#f2edff',
	},
};

describe( 'pageClasses', () => {
	it( 'takes the default parts when none are saved', () => {
		expect( pageClasses( page, undefined ) ).toEqual( [
			'purplish',
			'purplish-active',
			'purplish-part-button',
			'purplish-part-chip',
			'purplish-part-badge',
		] );
	} );

	it( 'takes the saved parts and the switches the editor shows', () => {
		expect(
			pageClasses( page, {
				parts: [ 'tint', 'button', 'nope' ],
				mesh: true,
				glow: 'yes',
				halo: true,
				dots: true,
			} )
		).toEqual( [
			'purplish',
			'purplish-active',
			'purplish-part-button',
			'purplish-part-tint',
			'purplish-glow',
			'purplish-mesh',
		] );
	} );

	it( 'keeps the base classes with no part on', () => {
		expect( pageClasses( page, { parts: [] } ) ).toEqual( [
			'purplish',
			'purplish-active',
		] );
	} );

	it( 'gives nothing without page data', () => {
		expect( pageClasses( {}, { parts: [ 'button' ] } ) ).toEqual( [] );
		expect( pageClasses( undefined, undefined ) ).toEqual( [] );
	} );
} );

describe( 'pageStyle', () => {
	it( 'gives the sample colors as custom properties', () => {
		expect( pageStyle( page ) ).toEqual( {
			'--purplish-accent': '#8f43ee',
			'--purplish-on': '#ffffff',
			'--purplish-ink': '#4f2583',
			'--purplish-tint': '#f2edff',
		} );
	} );

	it( 'gives nothing for a missing or bad sample', () => {
		expect( pageStyle( { sample: null } ) ).toEqual( {} );
		expect(
			pageStyle( { sample: { ...page.sample, tint: 'red' } } )
		).toEqual( {} );
		expect( pageStyle( undefined ) ).toEqual( {} );
	} );
} );

describe( 'HOVER_BUTTONS', () => {
	it( 'marks the add to cart buttons on product cards', () => {
		expect( HOVER_BUTTONS[ 'woocommerce/product-collection' ] ).toBe(
			'.wc-block-components-product-button__button'
		);
	} );

	it( 'marks the product rows in the drawer and on the Cart page', () => {
		expect( HOVER_BUTTONS[ 'woocommerce/mini-cart-contents' ] ).toBe(
			'.wc-block-cart-items__row'
		);
		expect( HOVER_BUTTONS[ 'woocommerce/cart' ] ).toBe(
			'.wc-block-cart-items__row'
		);
	} );

	it( 'gives the Cart page classes the server prints', () => {
		const cartPage = {
			prefix: 'cartpage',
			options: { rows: true, photos: true, sticky: true },
			legacy: {},
		};
		expect(
			classesFor( cartPage, { rows: true, sticky: true, spring: true } )
		).toEqual( [
			'purplish-cartpage',
			'purplish-cartpage-rows',
			'purplish-cartpage-sticky',
		] );
	} );

	it( 'gives the Checkout page classes the server prints', () => {
		const checkoutPage = {
			prefix: 'checkoutpage',
			options: {
				fields: [ 'soft', 'filled', 'underline' ],
				focus: true,
				summary: true,
				photos: true,
			},
			legacy: {},
		};
		expect(
			classesFor( checkoutPage, {
				fields: 'underline',
				focus: true,
				photos: true,
				sticky: true,
			} )
		).toEqual( [
			'purplish-checkoutpage',
			'purplish-checkoutpage-fields-underline',
			'purplish-checkoutpage-focus',
			'purplish-checkoutpage-photos',
		] );
		expect( classesFor( checkoutPage, { fields: 'rounded' } ) ).toEqual(
			[]
		);
	} );

	it( 'gives the Checkout layout classes the server prints', () => {
		const checkoutPage = {
			prefix: 'checkoutpage',
			options: {
				fields: [ 'soft', 'filled', 'underline' ],
				focus: true,
				summary: true,
				photos: true,
				choices: [ 'cards', 'list' ],
				side: true,
				cards: true,
			},
			legacy: {},
		};
		expect(
			classesFor( checkoutPage, {
				summary: true,
				choices: 'cards',
				side: true,
				cards: true,
			} )
		).toEqual( [
			'purplish-checkoutpage',
			'purplish-checkoutpage-summary',
			'purplish-checkoutpage-choices-cards',
			'purplish-checkoutpage-side',
			'purplish-checkoutpage-cards',
		] );
		expect( classesFor( checkoutPage, { choices: 'tiles' } ) ).toEqual(
			[]
		);
		expect( classesFor( checkoutPage, { side: 'tint' } ) ).toEqual( [] );
	} );

	it( 'gives the card classes the server prints', () => {
		const cards = {
			prefix: 'cards',
			options: { overlay: true, motion: true, hover: [ 'roster' ] },
			legacy: {},
		};
		expect(
			classesFor( cards, { overlay: true, hover: 'roster' } )
		).toEqual( [
			'purplish-cards',
			'purplish-cards-overlay',
			'purplish-cards-hover-roster',
		] );
	} );
} );

describe( 'card hover looks', () => {
	const cards = {
		prefix: 'cards',
		options: {
			hover: [ 'deck', 'arc', 'reveal' ],
			hoveron: [ 'card', 'photo', 'button', 'all' ],
			hovercard: [ 'lift' ],
			hoverphoto: [ 'push' ],
			hoverbutton: [ 'glow' ],
		},
		legacy: {},
		needs: {
			hovercard: [
				[ 'hoveron', [ 'card', 'all' ], true ],
				[ 'hover', [ 'deck' ], false ],
			],
			hoverphoto: [
				[ 'hoveron', [ 'photo', 'all' ], true ],
				[ 'hover', [ 'reveal' ], false ],
			],
			hoverbutton: [ [ 'hoveron', [ 'button', 'all' ], true ] ],
		},
	};
	const looks = {
		hovercard: 'lift',
		hoverphoto: 'push',
		hoverbutton: 'glow',
	};

	it( 'prints only the looks the target reaches', () => {
		expect( classesFor( cards, { hoveron: 'photo', ...looks } ) ).toEqual( [
			'purplish-cards',
			'purplish-cards-hoveron-photo',
			'purplish-cards-hoverphoto-push',
		] );
		expect( classesFor( cards, looks ) ).toEqual( [] );
	} );

	it( 'leaves out the part the card effect already moves', () => {
		expect(
			classesFor( cards, { hover: 'deck', hoveron: 'all', ...looks } )
		).toEqual( [
			'purplish-cards',
			'purplish-cards-hover-deck',
			'purplish-cards-hoveron-all',
			'purplish-cards-hoverphoto-push',
			'purplish-cards-hoverbutton-glow',
		] );
		expect(
			classesFor( cards, { hover: 'arc', hoveron: 'all', ...looks } )
		).toHaveLength( 6 );
	} );
} );

describe( 'details on the photo', () => {
	const cards = {
		prefix: 'cards',
		options: { overlay: true, details: true, hover: [ 'reel', 'roster' ] },
		legacy: {},
		needs: {
			overlay: [ [ 'details', [ true ], false ] ],
			details: [ [ 'hover', [ 'roster' ], false ] ],
		},
	};

	it( 'takes the place of the button on the photo', () => {
		expect(
			classesFor( cards, { overlay: true, details: true, hover: 'reel' } )
		).toEqual( [
			'purplish-cards',
			'purplish-cards-details',
			'purplish-cards-hover-reel',
		] );
		expect( classesFor( cards, { overlay: true } ) ).toEqual( [
			'purplish-cards',
			'purplish-cards-overlay',
		] );
	} );

	it( 'stays off with Roster', () => {
		expect(
			classesFor( cards, { details: true, hover: 'roster' } )
		).toEqual( [ 'purplish-cards', 'purplish-cards-hover-roster' ] );
	} );
} );
