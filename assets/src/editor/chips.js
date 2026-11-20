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

const SWATCH_ATTRIBUTES = [
	{ value: '', label: __( 'Auto, the color attribute', 'purplish' ) },
	...( window.purplishSizeAttributes || [] ),
];

const SWATCHES = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'photos', label: __( 'Photo swatches', 'purplish' ) },
];

const SHAPES = [
	{ value: '', label: __( 'Square', 'purplish' ) },
	{ value: 'round', label: __( 'Round', 'purplish' ) },
];

const SIZES = [
	{ value: '', label: __( 'Small, 44px', 'purplish' ) },
	{ value: 'large', label: __( 'Large, 64px', 'purplish' ) },
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
	const photos = 'photos' === saved.swatch;
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
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Color options', 'purplish' ) }
				help={ __(
					'Photo swatches show each color as its variation photo. A color with no photo shows its swatch color, then its name. The Drop-down style shows no photos.',
					'purplish'
				) }
				value={ saved.swatch || '' }
				options={ SWATCHES }
				onChange={ ( value ) => set( 'swatch', value ) }
			/>
			{ photos && (
				<>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Show photos on', 'purplish' ) }
						value={ saved.swatchattr || '' }
						options={ SWATCH_ATTRIBUTES }
						onChange={ ( value ) => set( 'swatchattr', value ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Swatch shape', 'purplish' ) }
						value={ saved.swatchshape || '' }
						options={ SHAPES }
						onChange={ ( value ) => set( 'swatchshape', value ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Swatch size', 'purplish' ) }
						value={ saved.swatchsize || '' }
						options={ SIZES }
						onChange={ ( value ) => set( 'swatchsize', value ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Picked color name', 'purplish' ) }
						help={ __(
							'Shows the chosen color beside the attribute name.',
							'purplish'
						) }
						checked={ false !== saved.swatchname }
						onChange={ ( on ) =>
							setSaved( {
								...saved,
								swatchname: on ? undefined : false,
							} )
						}
					/>
				</>
			) }
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
