/**
 * Purplish panel on the Product Gallery Thumbnails block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SHAPES = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'rounded', label: __( 'Rounded', 'purplish' ) },
	{ value: 'circle', label: __( 'Circle', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function ThumbsPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Thumbnail shape', 'purplish' ) }
				help={ __(
					'Circle is round with the square aspect ratio, and oval with the others.',
					'purplish'
				) }
				value={ saved.shape || '' }
				options={ SHAPES }
				onChange={ ( shape ) =>
					setSaved( { ...saved, shape: shape || undefined } )
				}
			/>
		</PanelBody>
	);
}
