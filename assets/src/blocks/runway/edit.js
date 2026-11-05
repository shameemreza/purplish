/**
 * The Runway in the editor: the intro and the Product Collection as
 * visitors without JavaScript see them. The line of looks only shows on
 * the front end, where PHP prints the product photos.
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const LOOKS = [
	{ value: 'all', label: __( 'Featured and gallery photos', 'purplish' ) },
	{ value: 'featured', label: __( 'Featured photo only', 'purplish' ) },
];

const SHAPES = [
	{ value: 'framed', label: __( 'Framed', 'purplish' ) },
	{ value: 'cutout', label: __( 'Cutout', 'purplish' ) },
];

const TEMPLATE = [
	[
		'core/heading',
		{ placeholder: __( 'Add a heading for the runway', 'purplish' ) },
	],
	[ 'core/paragraph', { placeholder: __( 'Add a short line', 'purplish' ) } ],
	[ 'woocommerce/product-collection', {} ],
];

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { looks, shape, number, fly } = attributes;
	const blockProps = useBlockProps( {
		className: `purplish-runway-looks-${ looks } purplish-runway-shape-${ shape } purplish-runway-number-${ number } purplish-runway-fly-${ fly }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-runway-content' },
		{ allowedBlocks: metadata.allowedBlocks, template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Runway', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Looks from', 'purplish' ) }
						help={ __(
							'Each photo becomes one look in the line, taken from the products in the collection. The line shows on the published page; visitors walk it by dragging, swiping, the arrow keys or the buttons, and it never moves on its own.',
							'purplish'
						) }
						value={ looks }
						options={ LOOKS }
						onChange={ ( value ) =>
							setAttributes( { looks: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Photo shape', 'purplish' ) }
						help={ __(
							'Cutout drops the frame, for product photos with a transparent background.',
							'purplish'
						) }
						value={ shape }
						options={ SHAPES }
						onChange={ ( value ) =>
							setAttributes( { shape: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Look number', 'purplish' ) }
						help={ __(
							'The big look number next to the previous and next buttons.',
							'purplish'
						) }
						checked={ 'on' === number }
						onChange={ ( value ) =>
							setAttributes( { number: value ? 'on' : 'off' } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Fly to cart', 'purplish' ) }
						help={ __(
							'The product photo flies into the mini cart when a visitor adds it to the cart. It needs the Mini-Cart block on the page, usually in the header, and does nothing without it.',
							'purplish'
						) }
						checked={ 'on' === fly }
						onChange={ ( value ) =>
							setAttributes( { fly: value ? 'on' : 'off' } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
