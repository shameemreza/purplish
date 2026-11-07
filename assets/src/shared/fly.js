/**
 * Flies a copy of a product photo into the Mini-Cart, then bumps the cart.
 * Shared by the Runway block and the Mini-Cart's fly to cart option.
 */

const FLY = { duration: 800, easing: 'cubic-bezier(0.5, 0, 0.3, 1)' };
const BUMP = { duration: 450, easing: 'ease-out' };

/**
 * Flies the copy.
 *
 * @param {HTMLImageElement} photo     The product photo.
 * @param {HTMLElement}      cart      The Mini-Cart button.
 * @param {number}           way       1 left to right, -1 right to left.
 * @param {string}           className Class of the copy, which styles it.
 */
export function flyToCart( photo, cart, way, className ) {
	const from = photo.getBoundingClientRect();
	if ( ! from.width ) {
		return;
	}
	const to = cart.getBoundingClientRect();
	const copy = document.createElement( 'img' );
	copy.src = photo.currentSrc || photo.src;
	copy.alt = '';
	copy.className = className;
	copy.setAttribute( 'aria-hidden', 'true' );
	Object.assign( copy.style, {
		left: from.left + 'px',
		top: from.top + 'px',
		width: from.width + 'px',
		height: from.height + 'px',
	} );
	document.body.append( copy );

	const x = to.left + to.width / 2 - ( from.left + from.width / 2 );
	const y = to.top + to.height / 2 - ( from.top + from.height / 2 );
	const flight = copy.animate(
		[
			{ transform: 'translate(0, 0) scale(1)', opacity: 1 },
			{
				transform: `translate(${ x * 0.45 }px, ${
					y * 0.45 - 80
				}px) scale(0.45) rotate(${ -6 * way }deg)`,
				opacity: 1,
				offset: 0.45,
			},
			{
				transform: `translate(${ x }px, ${ y }px) scale(${ Math.min(
					1,
					28 / from.width
				) })`,
				opacity: 0.2,
			},
		],
		FLY
	);
	flight.onfinish = () => {
		copy.remove();
		cart.animate(
			[
				{ transform: 'scale(1)' },
				{ transform: 'scale(1.25)' },
				{ transform: 'scale(0.94)' },
				{ transform: 'scale(1)' },
			],
			BUMP
		);
	};
	flight.oncancel = () => copy.remove();
}
