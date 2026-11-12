/**
 * Load more, on Query Pagination inside Product Collection. WooCommerce's
 * own navigation fetches the grid with every page so far and puts it in
 * place, then focuses the first card, which scrolls to the top of the grid.
 * This keeps the page where it was, moves focus to the first new card, and
 * brings the new cards in one after another.
 */

/**
 * The shortest wait from the click to the new cards, so a fast answer still
 * shows the button working instead of the grid jumping.
 */
export const SHORTEST = 650;

/**
 * The cards in a collection's grid.
 *
 * @param {Element} region The collection's router region.
 * @return {Element[]} The cards, in order.
 */
export const cards = ( region ) =>
	Array.from(
		region.querySelectorAll(
			'.wc-block-product-template > .wc-block-product'
		)
	);

/**
 * Whether a click goes to WooCommerce's navigation, not a new tab.
 *
 * @param {MouseEvent} event The click.
 * @return {boolean} True for a plain left click.
 */
const plain = ( event ) =>
	0 === event.button &&
	! event.metaKey &&
	! event.ctrlKey &&
	! event.altKey &&
	! event.shiftKey;

/**
 * Starts watching Load more links.
 *
 * @param {Document} doc     The document.
 * @param {Window}   win     The window.
 * @param {boolean}  reduced Whether motion is reduced.
 * @return {void}
 */
export function track( doc, win, reduced ) {
	let pending = null;
	let timer = 0;
	const now = () => ( win.performance ? win.performance.now() : Date.now() );

	/**
	 * Frees every Load more link and forgets the click.
	 */
	const free = () => {
		pending = null;
		win.clearTimeout( timer );
		doc.querySelectorAll( 'a[data-purplish-more]' ).forEach( ( link ) => {
			link.classList.remove( 'is-loading' );
			link.removeAttribute( 'aria-disabled' );
		} );
	};

	// Capture, so the click is noted before WooCommerce's handler runs: with
	// the page already fetched on hover, WooCommerce draws it straight away.
	doc.addEventListener(
		'click',
		( event ) => {
			const link =
				event.target instanceof win.Element &&
				event.target.closest( 'a[data-purplish-more]' );
			const region = link && link.closest( '[data-wp-router-region]' );
			if ( ! region || ! plain( event ) ) {
				return;
			}
			pending = {
				region: region.getAttribute( 'data-wp-router-region' ),
				count: cards( region ).length,
				y: win.scrollY,
				at: now(),
				shown: false,
			};
			link.classList.add( 'is-loading' );
			link.setAttribute( 'aria-disabled', 'true' );
			win.clearTimeout( timer );
			// WooCommerce gives up after 10 seconds and loads the page in full.
			timer = win.setTimeout( free, 12000 );
		},
		true
	);

	// Back to a page kept in the browser's cache, frozen mid-load.
	win.addEventListener( 'pageshow', ( event ) => {
		if ( event.persisted ) {
			free();
		}
	} );

	/**
	 * Shows the new cards, frees the button and glides the first new row
	 * up into view when it sits low on the screen.
	 *
	 * @param {Element}   region The collection's router region.
	 * @param {Element[]} fresh  The new cards.
	 */
	const reveal = ( region, fresh ) => {
		// The router keeps the link, with the classes added by hand.
		region
			.querySelectorAll( 'a[data-purplish-more]' )
			.forEach( ( link ) => {
				link.classList.remove( 'is-loading' );
				link.removeAttribute( 'aria-disabled' );
			} );
		const first = () =>
			( fresh[ 0 ].querySelector( 'a' ) || fresh[ 0 ] ).focus( {
				preventScroll: true,
			} );
		if ( reduced ) {
			first();
			return;
		}
		fresh.forEach( ( card, index ) => {
			const done = ( event ) => {
				if ( event.target !== card ) {
					return;
				}
				card.classList.remove( 'is-purplish-new' );
				card.style.removeProperty( '--purplish-i' );
				card.removeEventListener( 'animationend', done );
			};
			card.addEventListener( 'animationend', done );
			card.style.setProperty(
				'--purplish-i',
				String( Math.min( index, 10 ) )
			);
			card.classList.remove( 'is-purplish-wait' );
			card.classList.add( 'is-purplish-new' );
		} );
		// After the wait class goes, since a hidden card can't take focus.
		first();
		const top = fresh[ 0 ].getBoundingClientRect().top;
		if ( top > win.innerHeight * 0.6 ) {
			win.scrollBy( {
				top: top - win.innerHeight * 0.3,
				behavior: 'smooth',
			} );
		}
	};

	// WooCommerce sends this after it focuses the first card, and on renders.
	doc.addEventListener( 'wc-blocks_product_list_rendered', () => {
		if ( ! pending ) {
			return;
		}
		const region = Array.from(
			doc.querySelectorAll( '[data-wp-router-region]' )
		).find(
			( element ) =>
				pending.region ===
				element.getAttribute( 'data-wp-router-region' )
		);
		const list = region ? cards( region ) : [];
		if ( list.length <= pending.count ) {
			return;
		}

		const done = list[ 0 ].contains( doc.activeElement );
		win.scrollTo( win.scrollX, pending.y );

		if ( ! pending.shown ) {
			pending.shown = true;
			const fresh = list.slice( pending.count );
			const wait = reduced
				? 0
				: Math.max( 0, SHORTEST - ( now() - pending.at ) );
			if ( wait ) {
				// Out of the grid until the wait ends, so the button stays put.
				fresh.forEach( ( card ) =>
					card.classList.add( 'is-purplish-wait' )
				);
				win.setTimeout( () => reveal( region, fresh ), wait );
			} else {
				reveal( region, fresh );
			}
		}

		if ( done ) {
			pending = null;
			win.clearTimeout( timer );
		}
	} );
}
