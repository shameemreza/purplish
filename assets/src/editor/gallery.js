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
	{
		key: 'tilt',
		label: __( '3D tilt', 'purplish' ),
		help: __(
			'The photo tilts toward the mouse with a light sheen. Off for touch screens and reduced motion.',
			'purplish'
		),
	},
	{
		key: 'lightbox',
		label: __( 'Styled full-screen view', 'purplish' ),
		help: __(
			'Tinted to the page color, one photo per screen that snaps into place.',
			'purplish'
		),
	},
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
