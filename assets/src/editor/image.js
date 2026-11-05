/**
 * Purplish panel on the Image block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { HOVERS } from './gallery';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function ImagePanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Hover style', 'purplish' ) }
				help={ __(
					'Plays when the mouse is over the photo. Touch screens and visitors who ask for reduced motion see the plain photo.',
					'purplish'
				) }
				value={ saved.hover || '' }
				options={ HOVERS }
				onChange={ ( hover ) =>
					setSaved( { ...saved, hover: hover || undefined } )
				}
			/>
		</PanelBody>
	);
}
