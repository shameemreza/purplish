/**
 * Purplish panel on the Add to Cart + Options block: which parts follow the
 * picked color, and the photo transition.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Title and price rise', 'purplish' ) }
				help={ __(
					'The product title and price rise in after the photo on each color change.',
					'purplish'
				) }
				checked={ true === options.rise }
				onChange={ ( rise ) => save( { rise } ) }
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
