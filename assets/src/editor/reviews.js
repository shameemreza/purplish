/**
 * Purplish panel on the Product Reviews block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const STYLES = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'cards', label: __( 'Soft cards', 'purplish' ) },
	{ value: 'bubbles', label: __( 'Chat bubbles', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function ReviewsPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Review style', 'purplish' ) }
				help={ __(
					'Soft cards put each review on its own card. Chat bubbles put the review text in a bubble.',
					'purplish'
				) }
				value={ saved.style || '' }
				options={ STYLES }
				onChange={ ( style ) =>
					setSaved( { ...saved, style: style || undefined } )
				}
			/>
		</PanelBody>
	);
}
