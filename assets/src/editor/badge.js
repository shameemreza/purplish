/**
 * Purplish panel on the On-Sale Badge block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SHAPES = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'pill', label: __( 'Pill', 'purplish' ) },
	{ value: 'circle', label: __( 'Circle', 'purplish' ) },
	{ value: 'tag', label: __( 'Price tag', 'purplish' ) },
];

const CORNERS = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'top-left', label: __( 'Top left', 'purplish' ) },
	{ value: 'top-right', label: __( 'Top right', 'purplish' ) },
	{ value: 'bottom-left', label: __( 'Bottom left', 'purplish' ) },
	{ value: 'bottom-right', label: __( 'Bottom right', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function BadgePanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Shape', 'purplish' ) }
				help={ __(
					'A border radius set in Styles takes the place of the pill and circle corners.',
					'purplish'
				) }
				value={ saved.shape || '' }
				options={ SHAPES }
				onChange={ ( shape ) =>
					setSaved( { ...saved, shape: shape || undefined } )
				}
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Corner', 'purplish' ) }
				help={ __(
					"Where the badge sits on the photo. Works when the badge is inside the Product Gallery's large image.",
					'purplish'
				) }
				value={ saved.corner || '' }
				options={ CORNERS }
				onChange={ ( corner ) =>
					setSaved( { ...saved, corner: corner || undefined } )
				}
			/>
		</PanelBody>
	);
}
