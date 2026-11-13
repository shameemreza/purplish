/**
 * Purplish panel on the Chips filter block.
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
export default function FilterChipsPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Names under swatches', 'purplish' ) }
				help={ __(
					'When the attribute has color swatches, each one shows its name under it, and the chosen ones get a check.',
					'purplish'
				) }
				checked={ true === saved.names }
				onChange={ ( on ) =>
					setSaved( { ...saved, names: on || undefined } )
				}
			/>
		</PanelBody>
	);
}
