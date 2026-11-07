/**
 * Purplish panel on the Mini-Cart drawer (the Mini-Cart Contents block in
 * the Mini-Cart template part).
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
		key: 'spring',
		label: __( 'Spring slide', 'purplish' ),
		help: __(
			'The drawer opens with a soft settle, then the items and the footer follow one after another. Visitors who ask for reduced motion see the plain drawer. It plays on the store, not in the editor.',
			'purplish'
		),
	},
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
			'Item photos grow from 64 to 96 pixels, or to 80 on phones, with rounded corners.',
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
export default function DrawerPanel( { saved, setSaved } ) {
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( DrawerPanel, 'purplish-effect-color' );
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
