/**
 * The photo hover styles that follow the mouse, shared by the front script
 * and the editor preview. Each sets CSS variables on the frame; the
 * stylesheet does the rest.
 */

export const LOUPE_BOX = 180;
export const LOUPE_ZOOM = 2.5;

/**
 * The largest file the page already names for a photo.
 *
 * @param {HTMLImageElement} img The photo.
 * @return {string} Its address.
 */
export function largest( img ) {
	if ( img.dataset.large_image ) {
		return img.dataset.large_image;
	}
	let best = img.currentSrc || img.src;
	let width = 0;
	( img.getAttribute( 'srcset' ) || '' ).split( ',' ).forEach( ( entry ) => {
		const [ url, size = '' ] = entry.trim().split( ' ' );
		const w = parseInt( size, 10 );
		if ( url && w > width ) {
			best = url;
			width = w;
		}
	} );
	return best;
}

/**
 * Where the picture sits inside its box, after `object-fit`.
 *
 * @param {HTMLImageElement} img The photo.
 * @return {{left: number, top: number, width: number, height: number}} The picture's box on screen.
 */
export function pictureBox( img ) {
	const box = img.getBoundingClientRect();
	const fit = img.ownerDocument.defaultView.getComputedStyle( img ).objectFit;
	if ( ! img.naturalWidth || ( 'cover' !== fit && 'contain' !== fit ) ) {
		return box;
	}
	const pick = 'cover' === fit ? Math.max : Math.min;
	const ratio = pick(
		box.width / img.naturalWidth,
		box.height / img.naturalHeight
	);
	const width = img.naturalWidth * ratio;
	const height = img.naturalHeight * ratio;
	return {
		left: box.left + ( box.width - width ) / 2,
		top: box.top + ( box.height - height ) / 2,
		width,
		height,
	};
}

/**
 * Gets the frame ready as the mouse comes in: the loupe needs the photo.
 *
 * @param {HTMLElement}      frame The element that gets the mouse variables.
 * @param {string}           style The hover style.
 * @param {HTMLImageElement} img   The photo, if any.
 */
export function enterFrame( frame, style, img ) {
	if ( 'loupe' === style && img ) {
		frame.style.setProperty(
			'--purplish-loupe',
			'url("' + largest( img ).split( '"' ).join( '%22' ) + '")'
		);
		frame.style.setProperty( '--purplish-loupe-box', LOUPE_BOX + 'px' );
	}
}

/**
 * Sets the mouse variables for a move over the area.
 *
 * @param {HTMLElement}      frame The element that gets the mouse variables.
 * @param {string}           style The hover style.
 * @param {PointerEvent}     event The pointer move.
 * @param {HTMLElement}      area  The element the mouse moves over.
 * @param {HTMLImageElement} img   The photo, if any.
 */
export function moveFrame( frame, style, event, area, img ) {
	const box = area.getBoundingClientRect();
	const x = ( event.clientX - box.left ) / box.width;
	const y = ( event.clientY - box.top ) / box.height;
	frame.style.setProperty( '--purplish-ry', ( x - 0.5 ) * 22 + 'deg' );
	frame.style.setProperty( '--purplish-rx', ( 0.5 - y ) * 16 + 'deg' );
	frame.style.setProperty( '--purplish-mx', x * 100 + '%' );
	frame.style.setProperty( '--purplish-my', y * 100 + '%' );
	frame.style.setProperty( '--purplish-dx', String( x - 0.5 ) );
	frame.style.setProperty( '--purplish-dy', String( y - 0.5 ) );

	if ( 'loupe' === style && img ) {
		const picture = pictureBox( img );
		const px = ( event.clientX - picture.left ) * LOUPE_ZOOM;
		const py = ( event.clientY - picture.top ) * LOUPE_ZOOM;
		frame.style.setProperty(
			'--purplish-loupe-size',
			picture.width * LOUPE_ZOOM +
				'px ' +
				picture.height * LOUPE_ZOOM +
				'px'
		);
		frame.style.setProperty(
			'--purplish-loupe-pos',
			LOUPE_BOX / 2 - px + 'px ' + ( LOUPE_BOX / 2 - py ) + 'px'
		);
	}
}

/**
 * Lets the frame settle as the mouse leaves. The tilt resets fully; the
 * others keep the mouse position so the photo settles back where it was
 * instead of jumping to the center.
 *
 * @param {HTMLElement} frame The element that gets the mouse variables.
 * @param {string}      style The hover style.
 */
export function leaveFrame( frame, style ) {
	( 'tilt' === style
		? [ '--purplish-rx', '--purplish-ry', '--purplish-mx', '--purplish-my' ]
		: [ '--purplish-rx', '--purplish-ry', '--purplish-dx', '--purplish-dy' ]
	).forEach( ( name ) => frame.style.removeProperty( name ) );
}
