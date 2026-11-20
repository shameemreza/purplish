/**
 * Keeps a Delivery Line current on cached pages: picks the state for the
 * shopper's clock from the ones the page carries, and hides the dated lines
 * once every state is in the past. The words change only when the page
 * loads, when it's shown again, and when a cut-off or midnight passes, so
 * nothing updates on its own while the shopper reads (WCAG 2.2.2).
 */
import { dateLine, parts, pick } from './states';

/**
 * Writes parts into a line's text, only when the words changed.
 *
 * @param {Element}                                text  The text element.
 * @param {Array<{text: string, strong: boolean}>} items The parts.
 */
function write( text, items ) {
	if ( items.map( ( item ) => item.text ).join( '' ) === text.textContent ) {
		return;
	}
	text.replaceChildren(
		...items.map( ( item ) => {
			if ( ! item.strong ) {
				return document.createTextNode( item.text );
			}
			const strong = document.createElement( 'strong' );
			strong.textContent = item.text;
			return strong;
		} )
	);
}

/**
 * Starts one block.
 *
 * @param {HTMLElement} block The block.
 */
function start( block ) {
	let data;
	try {
		data = JSON.parse( block.dataset.purplishDelivery );
	} catch {
		return;
	}
	const row = ( kind ) =>
		block.querySelector( `[data-purplish-delivery-row="${ kind }"]` );
	const date = row( 'date' );
	const pickup = row( 'pickup' );
	let timer = 0;

	const tick = () => {
		clearTimeout( timer );
		const now = Date.now() / 1000;
		if ( now >= data.end ) {
			[ date, pickup ].forEach(
				( line ) => line && ( line.hidden = true )
			);
			return;
		}
		const state = pick( data.states, now );
		if ( ! state ) {
			return;
		}
		if ( date ) {
			const line = dateLine( data, state, now );
			write(
				date.querySelector( '.purplish-delivery-line__text' ),
				parts( line.template, line.values )
			);
		}
		if ( pickup && data.texts.pickup ) {
			const values = data.place ? [ data.place, state.p ] : [ state.p ];
			write(
				pickup.querySelector( '.purplish-delivery-line__text' ),
				parts( data.texts.pickup, values, data.place ? [ 1 ] : [] )
			);
		}
		const next = data.states.find( ( item ) => item.t > now );
		const wait = ( next ? next.t : data.end ) - now;
		// Timers past about 24 days overflow; the next visit catches up.
		if ( wait < 86400 * 8 ) {
			timer = setTimeout( tick, Math.max( 1, wait ) * 1000 );
		}
	};

	tick();
	document.addEventListener( 'visibilitychange', () => {
		if ( ! document.hidden ) {
			tick();
		}
	} );
}

document
	.querySelectorAll( '[data-purplish-delivery]' )
	.forEach( ( block ) => start( block ) );
