/**
 * Purplish panel on the Accordion block.
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
export default function AccordionPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Soft style', 'purplish' ) }
				help={ __(
					'Each item becomes a soft card, and the plus turns into an x that fills with the color when open.',
					'purplish'
				) }
				checked={ true === saved.soft }
				onChange={ ( on ) => setSaved( { ...saved, soft: on } ) }
			/>
		</PanelBody>
	);
}
