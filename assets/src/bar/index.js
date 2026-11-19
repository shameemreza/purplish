/**
 * Sticky bar on phones for Add to Cart + Options. The server prints the bar
 * hidden after the block, with the product's name and price. This adds a
 * copy of the block's button, moves the bar to the end of the page and
 * shows it while the block sits above the screen. Its price follows the
 * page's, which changes with the picked variation. The copy presses the
 * real button, so WooCommerce's own add flow and the drawer run as usual.
 * Until a variable product has a full pick, it scrolls up to the picker
 * instead.
 */

const BLOCK = '.wp-block-woocommerce-add-to-cart-with-options';
const PHONE = window.matchMedia( '(max-width: 599px)' );
const STILL = window.matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * The page's background, for the bar to sit on. A transparent body falls
 * back to the root's background, then to white.
 *
 * @return {string} A CSS color.
 */
function ground() {
	const clear = ( color ) =>
		! color || 'transparent' === color || /,\s*0\)$/.test( color );
	return (
		[ document.body, document.documentElement ]
			.map(
				( element ) =>
					window.getComputedStyle( element ).backgroundColor
			)
			.find( ( color ) => ! clear( color ) ) || '#fff'
	);
}

/**
 * A copy of the real button with WooCommerce's wiring taken off, so only
 * the bar's own click handler runs.
 *
 * @param {HTMLElement} real The block's button.
 * @return {HTMLButtonElement} The copy.
 */
function copyButton( real ) {
	const button = real.cloneNode( true );
	[ button, ...button.querySelectorAll( '*' ) ].forEach( ( element ) => {
		[ ...element.attributes ].forEach( ( { name } ) => {
			if (
				name.startsWith( 'data-' ) ||
				[ 'id', 'name', 'value', 'hidden', 'disabled' ].includes( name )
			) {
				element.removeAttribute( name );
			}
		} );
	} );
	button.classList.remove(
		'ajax_add_to_cart',
		'add_to_cart_button',
		'wc-interactive',
		'disabled'
	);
	if ( ! button.getAttribute( 'aria-label' ) ) {
		button.removeAttribute( 'aria-label' );
	}
	button.type = 'button';
	return button;
}

/**
 * The product's own price on the page, which WooCommerce changes to the
 * picked variation's: the one nearest the block that isn't on a card.
 *
 * @param {HTMLElement} form The block.
 * @return {HTMLElement|null} The price's inner element.
 */
function pagePrice( form ) {
	for (
		let around = form.parentElement;
		around;
		around = around.parentElement
	) {
		const price = [
			...around.querySelectorAll( '.wp-block-woocommerce-product-price' ),
		].find( ( element ) => ! element.closest( '.wc-block-product' ) );
		if ( price ) {
			return (
				price.querySelector( '.wc-block-components-product-price' ) ||
				price
			);
		}
	}
	return null;
}

/**
 * Sets up one bar.
 *
 * @param {HTMLElement} bar The bar the server printed.
 */
function setUp( bar ) {
	// WooCommerce may wrap the block's form in its notices area.
	const before = bar.previousElementSibling;
	const form = before?.matches( BLOCK )
		? before
		: before?.querySelector( BLOCK );
	const real = form?.querySelector( '.single_add_to_cart_button' );
	if ( ! real ) {
		return;
	}

	const button = copyButton( real );
	// The googly eyes are a span too; the words are the other one.
	const words = ':scope > span:not(.purplish-eyes)';
	const label = button.querySelector( words );
	const picking = () => form.classList.contains( 'is-invalid' );

	// The button says what it does: pick first, or the block's own words.
	const write = () => {
		const text = picking()
			? bar.dataset.pick
			: ( real.querySelector( words ) || real ).textContent.trim();
		( label || button ).textContent = text;
	};

	button.addEventListener( 'click', () => {
		if ( ! picking() ) {
			real.click();
			return;
		}
		form.scrollIntoView( {
			block: 'center',
			behavior: STILL.matches ? 'auto' : 'smooth',
		} );
		form.querySelector(
			'[role="radio"][aria-checked="false"]:not([disabled]), select'
		)?.focus( { preventScroll: true } );
	} );

	bar.append( button );
	bar.style.setProperty( '--purplish-bar-ground', ground() );
	bar.inert = true;
	bar.hidden = false;
	document.body.append( bar );
	document.body.classList.add( 'purplish-has-bar' );
	write();

	new window.MutationObserver( write ).observe( form, {
		attributes: true,
		attributeFilter: [ 'class' ],
		subtree: true,
		characterData: true,
		childList: true,
	} );

	const price = pagePrice( form );
	const shown = bar.querySelector( '.purplish-bar-price' );
	if ( price && shown ) {
		new window.MutationObserver( () =>
			shown.replaceChildren( ...price.cloneNode( true ).childNodes )
		).observe( price, {
			childList: true,
			subtree: true,
			characterData: true,
		} );
	}

	// Shown only once the block has gone up past the top of the screen.
	let past = false;
	const show = () => {
		const on = past && PHONE.matches;
		bar.classList.toggle( 'is-shown', on );
		bar.inert = ! on;
		document.body.style.setProperty(
			'--purplish-bar-height',
			bar.offsetHeight + 'px'
		);
	};
	new window.IntersectionObserver( ( [ entry ] ) => {
		past = ! entry.isIntersecting && entry.boundingClientRect.bottom < 0;
		show();
	} ).observe( form );
	PHONE.addEventListener( 'change', show );
}

document.querySelectorAll( '.purplish-bar' ).forEach( setUp );
