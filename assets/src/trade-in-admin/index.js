/**
 * The device table on the Trade-in settings: Add device copies the row
 * template, Remove takes a row out, and on save the rows go into one JSON
 * field. The inputs have no names, so a long table never hits the server's
 * limit on posted fields.
 */

/**
 * Starts the device table.
 *
 * @param {Element} wrap The `[data-purplish-devices]` element.
 */
function start( wrap ) {
	const body = wrap.querySelector( 'tbody' );
	const template = wrap.querySelector( 'template' );
	const json = wrap.querySelector( 'textarea' );
	const form = wrap.closest( 'form' );

	wrap.addEventListener( 'click', ( event ) => {
		if ( event.target.closest( '.purplish-device-add' ) ) {
			body.append( template.content.cloneNode( true ) );
			body.lastElementChild.querySelector( 'input' )?.focus();
		}
		const remove = event.target.closest( '.purplish-device-remove' );
		if ( remove ) {
			remove.closest( 'tr' ).remove();
			wrap.querySelector( '.purplish-device-add' )?.focus();
			// WooCommerce keeps Save changes disabled until a field changes.
			json.dispatchEvent(
				new window.Event( 'change', { bubbles: true } )
			);
		}
	} );

	form?.addEventListener( 'submit', () => {
		json.value = JSON.stringify(
			Array.from( body.rows, ( row ) => {
				const device = {};
				row.querySelectorAll( 'input[data-key]' ).forEach(
					( input ) => {
						device[ input.dataset.key ] = input.value;
					}
				);
				return device;
			} )
		);
	} );
}

document.querySelectorAll( '[data-purplish-devices]' ).forEach( start );
