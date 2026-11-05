/**
 * Purplish panel on WooCommerce's Accordion block.
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
export default function AccordionGroupPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Soft style', 'purplish' ) }
				help={ __(
					'Each item becomes a soft card, and the icon sits in a round button that fills with the color when open.',
					'purplish'
				) }
				checked={ true === saved.soft }
				onChange={ ( on ) => setSaved( { ...saved, soft: on } ) }
			/>
		</PanelBody>
	);
}
