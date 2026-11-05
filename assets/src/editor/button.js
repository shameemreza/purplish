/**
 * Purplish panel on the Button block.
 */
import {
	BaseControl,
	ColorPalette,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

import { EYES, EYES_HELP } from './eyes';

const LOOKS = [
	{
		key: 'glow',
		label: __( 'Glow ring', 'purplish' ),
		help: __(
			'A soft ring of the effect color around the button.',
			'purplish'
		),
	},
	{
		key: 'sweep',
		label: __( 'Light sweep', 'purplish' ),
		help: __( 'A band of light crosses the button on hover.', 'purplish' ),
	},
	{
		key: 'glass',
		label: __( 'Liquid glass', 'purplish' ),
		help: __(
			'A frosted fill in the effect color with a highlight that follows the mouse. Visitors who ask for reduced motion see the plain button.',
			'purplish'
		),
	},
	{
		key: 'spectrum',
		label: __( 'Spectrum ring', 'purplish' ),
		help: __(
			'A rainbow ring travels once around the edge on hover.',
			'purplish'
		),
	},
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function ButtonPanel( { saved, setSaved } ) {
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( ButtonPanel, 'purplish-effect-color' );
	const save = ( changes ) => setSaved( { ...saved, ...changes } );

	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			{ LOOKS.map( ( look ) => (
				<ToggleControl
					key={ look.key }
					__nextHasNoMarginBottom
					label={ look.label }
					help={ look.help }
					checked={ true === saved[ look.key ] }
					onChange={ ( on ) => save( { [ look.key ]: on } ) }
				/>
			) ) }
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Googly eyes', 'purplish' ) }
				help={ EYES_HELP }
				value={ saved.eyes || '' }
				options={ EYES }
				onChange={ ( eyes ) => save( { eyes: eyes || undefined } ) }
			/>
			<BaseControl
				__nextHasNoMarginBottom
				id={ colorId }
				label={ __( 'Effect color', 'purplish' ) }
				help={ __(
					'Used by the glow ring and the liquid glass. On a product page with a variation color, that color is used instead.',
					'purplish'
				) }
			>
				<ColorPalette
					colors={ palette || [] }
					value={ saved.color }
					onChange={ ( color ) => save( { color } ) }
					clearable
				/>
			</BaseControl>
		</PanelBody>
	);
}
