/**
 * Purplish panel on the Social Icons block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const LOOKS = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'ring', label: __( 'Ring', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function SocialPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Look', 'purplish' ) }
				help={ __(
					'Ring draws each icon in a thin round ring in the icon color, and fills it on hover. Set the icon color to match the section.',
					'purplish'
				) }
				value={ saved.look || '' }
				options={ LOOKS }
				onChange={ ( look ) =>
					setSaved( { ...saved, look: look || undefined } )
				}
			/>
		</PanelBody>
	);
}
