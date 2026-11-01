/**
 * Purplish panel on the Group block.
 */
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
					"On product pages, this section's background becomes a soft version of the color the shopper picks.",
					'purplish'
				) }
				checked={ true === saved.tint }
				onChange={ ( on ) => setSaved( { ...saved, tint: on } ) }
			/>
		</PanelBody>
	);
}
