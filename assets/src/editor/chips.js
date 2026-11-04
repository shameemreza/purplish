/**
 * Purplish panel on the Variation Selector template block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const LOOKS = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'cross', label: __( 'Crossed out', 'purplish' ) },
	{ value: 'blur', label: __( 'Blurred', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function ChipsPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Grayed-out look', 'purplish' ) }
				help={ __(
					"How chips for options that can't be bought look. The crossed-out line also shows on swatches. To hide them instead, set Invalid options to Hidden.",
					'purplish'
				) }
				value={ saved.soldout || '' }
				options={ LOOKS }
				onChange={ ( soldout ) =>
					setSaved( { ...saved, soldout: soldout || undefined } )
				}
			/>
		</PanelBody>
	);
}
