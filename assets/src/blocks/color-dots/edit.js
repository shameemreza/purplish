/**
 * Color Dots in the editor. Each card reads its product's colors from the
 * `purplish_colors` field, the same list the server prints, so the dots in
 * the editor match the store. A product with one color shows its dot faded
 * when Show a single color is off, since the front end leaves it out.
 * With Swap the photo on, the first dot wears the chosen ring.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, _n, sprintf } from '@wordpress/i18n';

const LOOKS = [
	{ value: 'dots', label: __( 'Dots', 'purplish' ) },
	{ value: 'swatches', label: __( 'Swatches', 'purplish' ) },
];

const SWAPS = [
	{ value: 'off', label: __( 'Off', 'purplish' ) },
	{ value: 'hover', label: __( 'On hover', 'purplish' ) },
	{ value: 'tap', label: __( 'On tap', 'purplish' ) },
];

const MOTIONS = [
	{ value: 'fade', label: __( 'Fade', 'purplish' ) },
	{ value: 'wipe', label: __( 'Wipe', 'purplish' ) },
	{ value: 'slide', label: __( 'Slide', 'purplish' ) },
];

// Shown where there is no product yet, such as the inserter preview.
const SAMPLE = [
	{ name: __( 'Ink', 'purplish' ), color: '#2b2a33' },
	{ name: __( 'Sage', 'purplish' ), color: '#8a9a7b' },
	{ name: __( 'Sand', 'purplish' ), color: '#d8c3a5' },
];

/**
 * What a screen reader hears for the dots, as the server words it.
 *
 * @param {Array<{name: string, color: string}>} colors The product's colors.
 * @return {string} The label.
 */
export function label( colors ) {
	const names = colors.map( ( entry ) => entry.name ).filter( Boolean );
	if ( names.length === colors.length ) {
		/* translators: %s: color names, separated by commas. */
		return sprintf( __( 'Colors: %s', 'purplish' ), names.join( ', ' ) );
	}
	return sprintf(
		/* translators: %d: how many colors the product comes in. */
		_n( '%d color', '%d colors', colors.length, 'purplish' ),
		colors.length
	);
}

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {Object}                    props.context       Card context.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const { look, limit, single, swap, motion } = attributes;
	const swaps = swap && 'off' !== swap;
	const postId = context.postId;

	const colors = useSelect(
		( select ) => {
			if (
				! postId ||
				( context.postType && 'product' !== context.postType )
			) {
				return SAMPLE;
			}
			const record = select( 'core' ).getEntityRecord(
				'postType',
				'product',
				postId,
				{ _fields: 'id,purplish_colors' }
			);
			return record && Array.isArray( record.purplish_colors )
				? record.purplish_colors
				: null;
		},
		[ postId, context.postType ]
	);

	const max = Math.max( 1, Math.min( 12, limit || 4 ) );
	const list = colors || [];
	const hidden = ! list.length || ( 1 === list.length && ! single );
	const rest = list.length - max;

	const blockProps = useBlockProps( {
		className: [
			'purplish-color-dots-look-' + look,
			hidden ? 'is-purplish-hidden' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
		title: hidden
			? __(
					'Not shown on this product: it has fewer than two colors.',
					'purplish'
				)
			: undefined,
		role: list.length ? 'img' : undefined,
		'aria-label': list.length ? label( list ) : undefined,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Colors', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Look', 'purplish' ) }
						value={ look }
						options={ LOOKS }
						onChange={ ( value ) =>
							setAttributes( { look: value } )
						}
					/>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'How many to show', 'purplish' ) }
						help={ __(
							'The rest show as a count, such as +3.',
							'purplish'
						) }
						value={ limit }
						min={ 1 }
						max={ 12 }
						onChange={ ( value ) =>
							setAttributes( { limit: value || 4 } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show a single color', 'purplish' ) }
						help={ __(
							'Off: products that come in one color show no dots.',
							'purplish'
						) }
						checked={ !! single }
						onChange={ ( value ) =>
							setAttributes( { single: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Swap the photo', 'purplish' ) }
						help={
							'hover' === swap
								? __(
										'A dot shows its color on the photo while the pointer is over it. A click or tap keeps it.',
										'purplish'
									)
								: __(
										'A click or tap on a dot shows that color on the photo. Uses each variation photo.',
										'purplish'
									)
						}
						value={ swap || 'off' }
						options={ SWAPS }
						onChange={ ( value ) =>
							setAttributes( { swap: value } )
						}
					/>
					{ swaps && (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Swap motion', 'purplish' ) }
							value={ motion || 'fade' }
							options={ MOTIONS }
							onChange={ ( value ) =>
								setAttributes( { motion: value } )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ list.length ? (
					<>
						{ list.slice( 0, max ).map( ( entry, index ) => (
							<span
								key={ index }
								className={
									'purplish-color-dots-dot' +
									( swaps && ! hidden && 0 === index
										? ' is-purplish-chosen'
										: '' )
								}
								style={ { '--purplish-dot': entry.color } }
								title={ entry.name || undefined }
							/>
						) ) }
						{ rest > 0 && (
							<span className="purplish-color-dots-more">
								{ '+' + rest }
							</span>
						) }
					</>
				) : (
					<span className="purplish-color-dots-dot is-purplish-empty" />
				) }
			</div>
		</>
	);
}
