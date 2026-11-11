/**
 * Purplish panel on the Cover block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SCROLLS = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'slowzoom', label: __( 'Slow zoom', 'purplish' ) },
	{ value: 'parallax', label: __( 'Parallax', 'purplish' ) },
	{ value: 'pill', label: __( 'Pill reveal', 'purplish' ) },
];

const HOVERS = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'push', label: __( 'Push in', 'purplish' ) },
	{ value: 'tilt', label: __( 'Tilt', 'purplish' ) },
	{ value: 'lift', label: __( 'Lift', 'purplish' ) },
	{ value: 'glare', label: __( 'Glare', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function CoverPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Scroll effect', 'purplish' ) }
				help={ __(
					'Plays as the page scrolls. Slow zoom and parallax move the background image or video; pill reveal opens the cover from a small rounded pill. Visitors who ask for reduced motion see the plain cover.',
					'purplish'
				) }
				value={ saved.scroll || '' }
				options={ SCROLLS }
				onChange={ ( scroll ) =>
					setSaved( { ...saved, scroll: scroll || undefined } )
				}
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Hover', 'purplish' ) }
				help={ __(
					'Push in moves the background in slowly. Tilt turns the whole cover toward the mouse with a sheen, Lift raises it with a soft shadow, and Glare runs a light after the mouse. Hover the cover here to see it; the sheen and glare follow the mouse on the page. Touch screens and reduced motion show the plain cover.',
					'purplish'
				) }
				value={ saved.hover || '' }
				options={ HOVERS }
				onChange={ ( hover ) =>
					setSaved( { ...saved, hover: hover || undefined } )
				}
			/>
		</PanelBody>
	);
}
