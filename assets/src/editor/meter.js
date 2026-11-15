/**
 * The Free shipping meter switch, shared by the drawer and Cart page
 * panels.
 */
import { __ } from '@wordpress/i18n';

/**
 * The switch, for the panels' list of looks.
 *
 * @param {string} where Where the meter shows, for the help text.
 * @return {{key: string, label: string, help: string}} The look.
 */
export default function meterLook( where ) {
	const ready = ( window.purplishMeter || {} ).ready;
	return {
		key: 'meter',
		label: __( 'Free shipping meter', 'purplish' ),
		help: [
			where,
			ready
				? __(
						'It counts down to the minimum on your Free shipping method, as WooCommerce counts the cart, and updates as the cart changes. The editor shows a sample cart.',
						'purplish'
					)
				: __(
						'Your store has no Free shipping method with a minimum order amount, so the store shows no meter. Add one in WooCommerce, Settings, Shipping.',
						'purplish'
					),
		].join( ' ' ),
	};
}
