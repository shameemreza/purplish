/**
 * Purplish panel on the Mini-Cart block.
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
export default function MiniCartPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Count pop', 'purplish' ) }
				help={ __(
					'The item count pops and the bag nudges when something is added to the cart.',
					'purplish'
				) }
				checked={ true === saved.pop }
				onChange={ ( on ) => setSaved( { ...saved, pop: on } ) }
			/>
		</PanelBody>
	);
}
