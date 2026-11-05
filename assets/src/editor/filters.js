/**
 * Purplish panel on the Product Filters block.
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
export default function FiltersPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Matching chips', 'purplish' ) }
				help={ __(
					'Active filters look like a selected chip, and chips fade into their selected color.',
					'purplish'
				) }
				checked={ true === saved.chips }
				onChange={ ( on ) => setSaved( { ...saved, chips: on } ) }
			/>
		</PanelBody>
	);
}
