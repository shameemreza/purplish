/**
 * Purplish panel on the Columns block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { ENTRANCES } from './group';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function ColumnsPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Entrance on scroll', 'purplish' ) }
				help={ __(
					'Each column plays once as it scrolls into view, one after another. Visitors who ask for reduced motion see the columns as they are.',
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
