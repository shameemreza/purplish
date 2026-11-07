/**
 * Purplish panel on the Cart block.
 */
import {
	BaseControl,
	ColorPalette,
	PanelBody,
	ToggleControl,
} from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

const LOOKS = [
	{
		key: 'rows',
		label: __( 'Soft rows', 'purplish' ),
		help: __(
			'Each item sits on its own rounded card in a light tint of the effect color.',
			'purplish'
		),
	},
	{
		key: 'photos',
		label: __( 'Bigger photos', 'purplish' ),
		help: __(
			'Item photos grow from 80 to 120 pixels, or 64 to 80 on phones, with rounded corners.',
			'purplish'
		),
	},
	{
		key: 'sticky',
		label: __( 'Sticky totals', 'purplish' ),
		help: __(
			'The totals and the checkout button stay in view while a long cart scrolls, when they sit beside the items.',
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
export default function CartPagePanel( { saved, setSaved } ) {
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( CartPagePanel, 'purplish-effect-color' );
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
			<BaseControl
				__nextHasNoMarginBottom
				id={ colorId }
				label={ __( 'Effect color', 'purplish' ) }
				help={ __(
					"Tints the soft rows. Without one, they use a light tint of the theme's text color.",
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
