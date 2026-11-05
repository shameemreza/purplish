/**
 * Purplish panel on the Product Gallery block.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const OPTIONS = [
	{
		key: 'nothumbs',
		label: __( 'Hide thumbnails', 'purplish' ),
		help: __( 'One big photo, no thumbnail strip.', 'purplish' ),
	},
	{ key: 'rounded', label: __( 'Rounded corners', 'purplish' ) },
	{
		key: 'arrows',
		label: __( 'Frosted arrows on hover', 'purplish' ),
		help: __(
			'Round arrows that show when the shopper hovers the photo.',
			'purplish'
		),
	},
];

export const HOVERS = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'tilt', label: __( '3D tilt', 'purplish' ) },
	{ value: 'zoom', label: __( 'Zoom', 'purplish' ) },
	{ value: 'lift', label: __( 'Lift', 'purplish' ) },
	{ value: 'parallax', label: __( 'Parallax', 'purplish' ) },
	{ value: 'glare', label: __( 'Glare', 'purplish' ) },
	{ value: 'loupe', label: __( 'Loupe', 'purplish' ) },
	{ value: 'slowzoom', label: __( 'Slow zoom', 'purplish' ) },
];

const FULLSCREENS = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'snap', label: __( 'Snap stack', 'purplish' ) },
	{ value: 'fade', label: __( 'Classic fade', 'purplish' ) },
	{ value: 'zoom', label: __( 'Zoom from photo', 'purplish' ) },
	{ value: 'filmstrip', label: __( 'Filmstrip', 'purplish' ) },
	{ value: 'cinema', label: __( 'Cinema', 'purplish' ) },
];

const SLIDERS = [
	{ value: '', label: __( 'Default', 'purplish' ) },
	{ value: 'cube', label: __( '3D media gallery', 'purplish' ) },
	{ value: 'circle', label: __( 'Circular slider', 'purplish' ) },
	{ value: 'reel', label: __( 'Reel slider', 'purplish' ) },
	{ value: 'tunnel', label: __( 'Image tunnel', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function GalleryPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			{ OPTIONS.map( ( option ) => (
				<ToggleControl
					key={ option.key }
					__nextHasNoMarginBottom
					label={ option.label }
					help={ option.help }
					checked={ true === saved[ option.key ] }
					onChange={ ( on ) =>
						setSaved( { ...saved, [ option.key ]: on } )
					}
				/>
			) ) }
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Hover style', 'purplish' ) }
				help={ __(
					'Plays when the mouse is over the photo. Touch screens and visitors who ask for reduced motion see the plain photo.',
					'purplish'
				) }
				value={ saved.hover || ( true === saved.tilt ? 'tilt' : '' ) }
				options={ HOVERS }
				onChange={ ( hover ) =>
					setSaved( {
						...saved,
						hover: hover || undefined,
						tilt: undefined,
					} )
				}
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Full-screen style', 'purplish' ) }
				help={ __(
					'The view that opens when the shopper clicks the photo. Needs the gallery\'s "Open pop-up when clicked" setting.',
					'purplish'
				) }
				value={
					saved.fullscreen ||
					( true === saved.lightbox ? 'snap' : '' )
				}
				options={ FULLSCREENS }
				onChange={ ( fullscreen ) =>
					setSaved( {
						...saved,
						fullscreen: fullscreen || undefined,
						lightbox: undefined,
					} )
				}
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Slide style', 'purplish' ) }
				help={ __(
					'How the photo moves when the gallery slides to the next one. Visitors who ask for reduced motion see the default slide.',
					'purplish'
				) }
				value={ saved.slider || '' }
				options={ SLIDERS }
				onChange={ ( slider ) =>
					setSaved( { ...saved, slider: slider || undefined } )
				}
			/>
		</PanelBody>
	);
}
