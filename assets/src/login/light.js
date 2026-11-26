/**
 * Aurora and Spotlight: the light follows the pointer, and keyboard focus,
 * with a short ease. Without the script, or under reduced motion, it
 * rests still.
 */

const PANELS =
	'.woocommerce > form.login, .woocommerce > form.lost_reset_password, .woocommerce > .u-columns > .u-column1, .woocommerce > .u-columns > .u-column2';

/**
 * @param {HTMLElement} group The element with the option classes.
 * @param {boolean}     spot  Whether Spotlight is on, so the panels need
 *                            their own pointer values.
 */
export function light( group, spot ) {
	const doc = group.ownerDocument;
	const win = doc.defaultView;
	let tx = win.innerWidth / 2;
	let ty = win.innerHeight * 0.4;
	let x = tx;
	let y = ty;
	let raf = 0;
	const panels = group.querySelectorAll( PANELS );

	function paint() {
		group.style.setProperty( '--pa-mx', x.toFixed( 1 ) + 'px' );
		group.style.setProperty( '--pa-my', y.toFixed( 1 ) + 'px' );
		group.style.setProperty(
			'--pa-hue',
			( ( x / win.innerWidth - 0.5 ) * 70 ).toFixed( 1 )
		);
		if ( spot ) {
			panels.forEach( ( panel ) => {
				const r = panel.getBoundingClientRect();
				panel.style.setProperty(
					'--pa-px',
					( x - r.left ).toFixed( 1 ) + 'px'
				);
				panel.style.setProperty(
					'--pa-py',
					( y - r.top ).toFixed( 1 ) + 'px'
				);
			} );
		}
	}

	function frame() {
		x += ( tx - x ) * 0.14;
		y += ( ty - y ) * 0.14;
		paint();
		raf =
			Math.abs( tx - x ) > 0.4 || Math.abs( ty - y ) > 0.4
				? win.requestAnimationFrame( frame )
				: 0;
	}

	function aim( nx, ny ) {
		tx = nx;
		ty = ny;
		if ( ! raf ) {
			raf = win.requestAnimationFrame( frame );
		}
	}

	paint();
	if ( win.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}
	win.addEventListener( 'pointermove', ( e ) => aim( e.clientX, e.clientY ), {
		passive: true,
	} );
	win.addEventListener( 'scroll', () => aim( tx, ty ), { passive: true } );
	group.addEventListener( 'focusin', ( e ) => {
		if ( e.target.matches( 'input, select, textarea, button' ) ) {
			const r = e.target.getBoundingClientRect();
			aim( r.left + r.width / 2, r.top + r.height / 2 );
		}
	} );
	group.addEventListener( 'pointerenter', () =>
		group.classList.add( 'pa-lit' )
	);
	group.addEventListener( 'pointerleave', () =>
		group.classList.remove( 'pa-lit' )
	);
	doc.documentElement.addEventListener( 'pointerleave', () =>
		group.classList.remove( 'pa-lit' )
	);
}
