/**
 * Purplish panel on the Add to Cart + Options block: which parts follow the
 * picked color, and the photo transition.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { EYES, EYES_HELP } from './eyes';

const DEFAULTS = {
	parts: [ 'button', 'chip', 'badge' ],
	transition: 'blur',
};

const PARTS = [
	{ key: 'button', label: __( 'Add to cart button', 'purplish' ) },
	{ key: 'chip', label: __( 'Selected option', 'purplish' ) },
	{ key: 'badge', label: __( 'Sale badge', 'purplish' ) },
	{ key: 'price', label: __( 'Sale price', 'purplish' ) },
	{ key: 'tint', label: __( 'Page background tint', 'purplish' ) },
];

/**
 * Which title effect is saved: rise, focus or none.
 *
 * @param {Object} options The saved options.
 * @return {string} `rise`, `focus` or an empty string.
 */
function titleEffect( options ) {
	if ( true === options.rise ) {
		return 'rise';
	}
	return true === options.focus ? 'focus' : '';
}

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function AddToCartPanel( { saved, setSaved } ) {
	const options = { ...DEFAULTS, ...saved };
	const save = ( changes ) => setSaved( { ...options, ...changes } );

	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<p>
				{ __(
					'Parts of the product page that take the color of the variation a shopper picks.',
					'purplish'
				) }
			</p>
			{ PARTS.map( ( part ) => (
				<ToggleControl
					key={ part.key }
					__nextHasNoMarginBottom
					label={ part.label }
					checked={ options.parts.includes( part.key ) }
					onChange={ ( on ) =>
						save( {
							parts: on
								? [ ...options.parts, part.key ]
								: options.parts.filter(
										( key ) => key !== part.key
									),
						} )
					}
				/>
			) ) }
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Photo transition', 'purplish' ) }
				help={ __(
					'Plays when the photo changes with the color. Visitors who ask for reduced motion see no effect.',
					'purplish'
				) }
				value={ options.transition }
				options={ [
					{
						value: 'none',
						label: __( 'None', 'purplish' ),
					},
					{
						value: 'fade',
						label: __( 'Fade', 'purplish' ),
					},
					{
						value: 'blur',
						label: __( 'Blur', 'purplish' ),
					},
					{
						value: 'zoom',
						label: __( 'Zoom', 'purplish' ),
					},
					{
						value: 'slide',
						label: __( 'Slide', 'purplish' ),
					},
					{
						value: 'flip',
						label: __( 'Flip', 'purplish' ),
					},
					{
						value: 'wipe',
						label: __( 'Wipe', 'purplish' ),
					},
					{
						value: 'circle',
						label: __( 'Circle reveal', 'purplish' ),
					},
					{
						value: 'wash',
						label: __( 'Color wash', 'purplish' ),
					},
				] }
				onChange={ ( transition ) => save( { transition } ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Title on a color change', 'purplish' ) }
				value={ titleEffect( options ) }
				options={ [
					{ value: '', label: __( 'None', 'purplish' ) },
					{
						value: 'rise',
						label: __( 'Title and price rise', 'purplish' ),
					},
					{
						value: 'focus',
						label: __( 'Title comes into focus', 'purplish' ),
					},
				] }
				onChange={ ( effect ) =>
					save( {
						rise: 'rise' === effect,
						focus: 'focus' === effect,
					} )
				}
			/>
			{ options.parts.includes( 'tint' ) && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Mesh background', 'purplish' ) }
					help={ __(
						'Soft blobs of the color over the tint, which move to a new place on each color change.',
						'purplish'
					) }
					checked={ true === options.mesh }
					onChange={ ( mesh ) => save( { mesh } ) }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Glow ring on the button', 'purplish' ) }
				help={ __(
					'A soft ring of the color around the add to cart button.',
					'purplish'
				) }
				checked={ true === options.glow }
				onChange={ ( glow ) => save( { glow } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Liquid glass button', 'purplish' ) }
				help={ __(
					'A frosted fill with a highlight that follows the mouse. Visitors who ask for reduced motion see the plain button.',
					'purplish'
				) }
				checked={ true === options.glass }
				onChange={ ( glass ) => save( { glass } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Spectrum ring on the button', 'purplish' ) }
				help={ __(
					'A rainbow ring travels once around the button on hover.',
					'purplish'
				) }
				checked={ true === options.spectrum }
				onChange={ ( spectrum ) => save( { spectrum } ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Googly eyes on the button', 'purplish' ) }
				help={ EYES_HELP }
				value={ options.eyes || '' }
				options={ EYES }
				onChange={ ( eyes ) => save( { eyes: eyes || undefined } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Halo after adding to cart', 'purplish' ) }
				help={ __(
					'A ring leaves the button and fades each time the product goes into the cart.',
					'purplish'
				) }
				checked={ true === options.halo }
				onChange={ ( halo ) => save( { halo } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Color dots on options', 'purplish' ) }
				help={ __(
					'A dot in each color before its name on the option buttons.',
					'purplish'
				) }
				checked={ true === options.dots }
				onChange={ ( dots ) => save( { dots } ) }
			/>
		</PanelBody>
	);
}
