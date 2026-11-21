/**
 * Purplish panel on the Column block.
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
export default function ColumnPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Keep in view while the photos scroll',
					'purplish'
				) }
				help={ __(
					'Made for the product details beside a photo grid. On wide screens this column stays below the header while the columns beside it scroll, then leaves with them. A column taller than the window scrolls until its end shows first. Phones keep the columns stacked.',
					'purplish'
				) }
				checked={ true === saved.keep }
				onChange={ ( on ) =>
					setSaved( { ...saved, keep: on || undefined } )
				}
			/>
		</PanelBody>
	);
}
