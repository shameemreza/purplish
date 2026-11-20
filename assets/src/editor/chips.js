/**
 * Purplish panel on the Variation Selector template block.
 */
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ATTRIBUTES = [
	{ value: '', label: __( 'Auto, the one named like a size', 'purplish' ) },
	...( window.purplishSizeAttributes || [] ),
];

const SHEETS = [
	{ value: '', label: __( 'Sheet from the bottom', 'purplish' ) },
	{ value: 'card', label: __( 'Centered card', 'purplish' ) },
];

const LOOKS = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'cross', label: __( 'Crossed out', 'purplish' ) },
	{ value: 'blur', label: __( 'Blurred', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function ChipsPanel( { saved, setSaved } ) {
	const guide = false !== saved.sizeguide;
	const set = ( key, value ) =>
		setSaved( { ...saved, [ key ]: value || undefined } );
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Grayed-out look', 'purplish' ) }
				help={ __(
					"How chips for options that can't be bought look. The crossed-out line also shows on swatches. To hide them instead, set Invalid options to Hidden.",
					'purplish'
				) }
				value={ saved.soldout || '' }
				options={ LOOKS }
				onChange={ ( soldout ) =>
					setSaved( { ...saved, soldout: soldout || undefined } )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Size guide link', 'purplish' ) }
				help={ __(
					'Shows on products with a size chart, picked on the product or its category. It opens the chart in a dialog.',
					'purplish'
				) }
				checked={ guide }
				onChange={ ( on ) =>
					setSaved( { ...saved, sizeguide: on ? undefined : false } )
				}
			/>
			{ guide && (
				<>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Link text', 'purplish' ) }
						placeholder={ __( 'Size guide', 'purplish' ) }
						value={ saved.sizelabel || '' }
						onChange={ ( value ) => set( 'sizelabel', value ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Beside', 'purplish' ) }
						value={ saved.sizeattr || '' }
						options={ ATTRIBUTES }
						onChange={ ( value ) => set( 'sizeattr', value ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Dialog on phones', 'purplish' ) }
						value={ saved.sizesheet || '' }
						options={ SHEETS }
						onChange={ ( value ) => set( 'sizesheet', value ) }
					/>
				</>
			) }
		</PanelBody>
	);
}
