/**
 * Purplish panel on the Group block.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const ENTRANCES = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'fadeup', label: __( 'Fade up', 'purplish' ) },
	{ value: 'zoomin', label: __( 'Zoom in', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function GroupPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Take the page color', 'purplish' ) }
				help={ __(
					"On product pages, this section's background becomes a soft version of the color the shopper picks. The editor shows it in a sample color on product templates.",
					'purplish'
				) }
				checked={ true === saved.tint }
				onChange={ ( on ) => setSaved( { ...saved, tint: on } ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Entrance on scroll', 'purplish' ) }
				help={ __(
					'Plays once as the section scrolls into view. Visitors who ask for reduced motion see it as it is.',
					'purplish'
				) }
				value={ saved.entrance || '' }
				options={ ENTRANCES }
				onChange={ ( entrance ) =>
					setSaved( { ...saved, entrance: entrance || undefined } )
				}
			/>
		</PanelBody>
	);
}
