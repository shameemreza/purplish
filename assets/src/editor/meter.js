/**
 * The Free shipping meter switch and its wording, shared by the drawer and
 * Cart page panels.
 */
import { SelectControl, TextControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

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

/**
 * The meter's wording and, on the Cart page, where it sits. Empty fields
 * keep the store's own sentences, which name the Free shipping method by
 * its title.
 *
 * @param {Object}                    props           Panel props.
 * @param {Object}                    props.saved     The block's saved `purplish` attribute.
 * @param {(changes: Object) => void} props.save      Saves changed options.
 * @param {boolean}                   props.placement Whether to offer the placement choice.
 */
export function MeterSettings( { saved, save, placement = false } ) {
	const sample = window.purplishMeter || {};
	return (
		<>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Meter text', 'purplish' ) }
				help={ sprintf(
					/* translators: %s: the placeholder to type, %s. */
					__(
						'Use %s where the amount left goes. Leave it empty to use the name of your Free shipping method.',
						'purplish'
					),
					'%s'
				) }
				placeholder={ sample.left || '' }
				value={ saved.metertext || '' }
				onChange={ ( text ) =>
					save( { metertext: text || undefined } )
				}
			/>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Text once it ships free', 'purplish' ) }
				placeholder={ __( 'Your order ships free.', 'purplish' ) }
				value={ saved.meterdone || '' }
				onChange={ ( text ) =>
					save( { meterdone: text || undefined } )
				}
			/>
			{ placement && (
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Meter placement', 'purplish' ) }
					value={ saved.meterplace || '' }
					options={ [
						{
							label: __( 'Above the cart', 'purplish' ),
							value: '',
						},
						{
							label: __( 'Top of the totals column', 'purplish' ),
							value: 'totals',
						},
					] }
					onChange={ ( place ) =>
						save( { meterplace: place || undefined } )
					}
				/>
			) }
		</>
	);
}
