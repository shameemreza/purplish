/**
 * Purplish panel on the Checkout block.
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

const FIELDS = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'soft', label: __( 'Soft', 'purplish' ) },
	{ value: 'filled', label: __( 'Filled', 'purplish' ) },
	{ value: 'underline', label: __( 'Underline', 'purplish' ) },
];

const CHOICES = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'cards', label: __( 'Cards', 'purplish' ) },
	{ value: 'list', label: __( 'List', 'purplish' ) },
];

const LOOKS = [
	{
		key: 'focus',
		label: __( 'Focus ring', 'purplish' ),
		help: __(
			'The field a shopper is typing in gets a ring in the effect color.',
			'purplish'
		),
	},
	{
		key: 'summary',
		label: __( 'Soft summary', 'purplish' ),
		help: __(
			'The order summary sits on a rounded card in a light tint of the effect color, with a bigger, bolder total.',
			'purplish'
		),
	},
	{
		key: 'side',
		label: __( 'Side panel', 'purplish' ),
		help: __(
			'On wide screens, the order summary column sits on a rounded panel in a light tint of the effect color.',
			'purplish'
		),
	},
	{
		key: 'cards',
		label: __( 'Raised card', 'purplish' ),
		help: __(
			'The whole checkout sits on a raised card in the page color. Looks best when the checkout is placed on a tinted section.',
			'purplish'
		),
	},
	{
		key: 'photos',
		label: __( 'Bigger photos', 'purplish' ),
		help: __(
			'Photos in the order summary grow from 48 to 64 pixels, with rounded corners.',
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
export default function CheckoutPagePanel( { saved, setSaved } ) {
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( CheckoutPagePanel, 'purplish-effect-color' );
	const save = ( changes ) => setSaved( { ...saved, ...changes } );

	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Field style', 'purplish' ) }
				help={ __(
					'Soft rounds the fields, filled gives them a light tint of the effect color, underline keeps only the bottom line. Labels still float as before.',
					'purplish'
				) }
				value={ saved.fields || '' }
				options={ FIELDS }
				onChange={ ( fields ) =>
					save( { fields: fields || undefined } )
				}
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Shipping and payment choices', 'purplish' ) }
				help={ __(
					'Cards puts each option in its own box, list joins them in one box with a line between each. The picked option gets a light tint of the effect color.',
					'purplish'
				) }
				value={ saved.choices || '' }
				options={ CHOICES }
				onChange={ ( choices ) =>
					save( { choices: choices || undefined } )
				}
			/>
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
			<BaseControl
				__nextHasNoMarginBottom
				id={ colorId }
				label={ __( 'Effect color', 'purplish' ) }
				help={ __(
					"Colors the focus ring, the picked choice and the total line, and tints the filled fields, the summary and the side panel. Without one, they use the theme's text color.",
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
