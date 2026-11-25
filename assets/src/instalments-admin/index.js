/**
 * The price band rows on the Instalments settings, the product editor and
 * the category screens: Add band copies the row template with the next
 * free index, Remove takes a row out. The product and category select
 * shows the rows only for Own bands. The logo field opens the Media
 * Library.
 */

/**
 * Starts one band table.
 *
 * @param {Element} wrap The `[data-purplish-bands]` element.
 */
function startBands( wrap ) {
	const body = wrap.querySelector( 'tbody' );
	const template = wrap.querySelector( 'template' );
	let next = body.rows.length;

	wrap.addEventListener( 'click', ( event ) => {
		if ( event.target.closest( '.purplish-band-add' ) ) {
			const html = template.innerHTML.replace( /__i__/g, String( next ) );
			next += 1;
			body.insertAdjacentHTML( 'beforeend', html );
			body.lastElementChild.querySelector( 'input' )?.focus();
		}
		const remove = event.target.closest( '.purplish-band-remove' );
		if ( remove ) {
			remove.closest( 'tr' ).remove();
			wrap.querySelector( '.purplish-band-add' )?.focus();
		}
	} );
}

/**
 * Shows the own bands while Own bands is picked.
 *
 * @param {HTMLSelectElement} select The mode select.
 */
function startMode( select ) {
	const own = select.parentElement.querySelector( '.purplish-own-bands' );
	if ( ! own ) {
		return;
	}
	const show = () => {
		own.hidden = 'own' !== select.value;
	};
	select.addEventListener( 'change', show );
	show();
}

/**
 * Starts the logo picker.
 *
 * @param {Element} wrap The `[data-purplish-logo]` element.
 */
function startLogo( wrap ) {
	const input = wrap.querySelector( 'input' );
	const preview = wrap.querySelector( '.purplish-logo-preview' );
	const clear = wrap.querySelector( '.purplish-logo-clear' );
	let frame = null;

	wrap.querySelector( '.purplish-logo-pick' ).addEventListener(
		'click',
		() => {
			if ( ! window.wp?.media ) {
				return;
			}
			frame =
				frame ||
				window.wp.media( {
					title: wrap.dataset.title,
					button: { text: wrap.dataset.button },
					library: { type: 'image' },
					multiple: false,
				} );
			frame.off( 'select' ).on( 'select', () => {
				const image = frame.state().get( 'selection' ).first().toJSON();
				const size = image.sizes?.thumbnail || image;
				const img = document.createElement( 'img' );
				img.src = size.url;
				img.alt = '';
				img.style.maxHeight = '40px';
				img.style.width = 'auto';
				preview.replaceChildren( img );
				input.value = String( image.id );
				clear.hidden = false;
			} );
			frame.open();
		}
	);

	clear.addEventListener( 'click', () => {
		input.value = '';
		preview.replaceChildren();
		clear.hidden = true;
	} );
}

document.querySelectorAll( '[data-purplish-bands]' ).forEach( startBands );
document
	.querySelectorAll( '[data-purplish-instalments-mode]' )
	.forEach( startMode );
document.querySelectorAll( '[data-purplish-logo]' ).forEach( startLogo );
