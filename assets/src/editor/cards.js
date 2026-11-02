/**
 * Purplish panel on the Product Collection block.
 */
import {
	ColorPalette,
	PanelBody,
	SelectControl,
	ToggleControl,
	BaseControl,
} from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

const HOVERS = [
	{
		label: __( 'On hover', 'purplish' ),
		options: [
			{ value: 'magnetic', label: __( 'Magnetic panel', 'purplish' ) },
			{ value: 'spotlight', label: __( 'Spotlight', 'purplish' ) },
			{ value: 'reveal', label: __( 'Color reveal', 'purplish' ) },
			{ value: 'fan', label: __( 'Fan deck', 'purplish' ) },
			{ value: 'expand', label: __( 'Expanded cards', 'purplish' ) },
			{ value: 'folder', label: __( 'Card folder', 'purplish' ) },
		],
	},
	{
		label: __( 'In a carousel', 'purplish' ),
		options: [
			{ value: 'deck', label: __( 'Deck', 'purplish' ) },
			{ value: 'arc', label: __( 'Arc', 'purplish' ) },
			{ value: 'stage', label: __( 'Spotlight carousel', 'purplish' ) },
			{ value: 'grow', label: __( 'Grow', 'purplish' ) },
			{ value: 'tiltrow', label: __( 'Tilt', 'purplish' ) },
		],
	},
	{
		label: __( 'As cards scroll in', 'purplish' ),
		options: [
			{ value: 'clothesline', label: __( 'Clothes line', 'purplish' ) },
			{ value: 'parallax', label: __( 'Parallax', 'purplish' ) },
			{ value: 'zoomin', label: __( 'Zoom reveal', 'purplish' ) },
		],
	},
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function CardsPanel( { saved, setSaved } ) {
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( CardsPanel, 'purplish-effect-color' );
	const save = ( changes ) => setSaved( { ...saved, ...changes } );

	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Button on the photo', 'purplish' ) }
				help={ __(
					'Title and price on one line, and add to cart as a small frosted button on the photo. It shows on hover, and always on touch screens.',
					'purplish'
				) }
				checked={ true === saved.overlay }
				onChange={ ( overlay ) => save( { overlay } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Button motion', 'purplish' ) }
				help={ __(
					'A bag icon slides in and a light sweeps across on hover; the button fills and pops after adding to cart.',
					'purplish'
				) }
				checked={ true === saved.motion }
				onChange={ ( motion ) => save( { motion } ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Card effect', 'purplish' ) }
				help={ __(
					'Carousel effects need the Carousel layout and leave a grid as it is. Visitors who ask for reduced motion see no movement.',
					'purplish'
				) }
				value={ saved.hover || '' }
				onChange={ ( hover ) => save( { hover: hover || undefined } ) }
			>
				<option value="">{ __( 'None', 'purplish' ) }</option>
				{ HOVERS.map( ( group ) => (
					<optgroup key={ group.label } label={ group.label }>
						{ group.options.map( ( option ) => (
							<option key={ option.value } value={ option.value }>
								{ option.label }
							</option>
						) ) }
					</optgroup>
				) ) }
			</SelectControl>
			<BaseControl
				__nextHasNoMarginBottom
				id={ colorId }
				label={ __( 'Effect color', 'purplish' ) }
				help={ __(
					'Used by the button fill, the magnetic panel, the color reveal, the clothes line pegs and the card folder. On a product page with a variation color, that color is used instead.',
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
