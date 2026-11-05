/**
 * The Hanging Rail in the editor: the inner blocks with the rope and clips
 * drawn, as a plain strip. The row only travels on the front end.
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const CLIPS = [
	{ value: 'peg', label: __( 'Peg', 'purplish' ) },
	{ value: 'binder', label: __( 'Binder clip', 'purplish' ) },
	{ value: 'tape', label: __( 'Tape', 'purplish' ) },
];

const SAGS = [
	{ value: 'little', label: __( 'A little', 'purplish' ) },
	{ value: 'more', label: __( 'More', 'purplish' ) },
];

const LENGTHS = [
	{ value: 'short', label: __( 'Short', 'purplish' ) },
	{ value: 'medium', label: __( 'Medium', 'purplish' ) },
	{ value: 'long', label: __( 'Long', 'purplish' ) },
];

const TEMPLATE = [
	[
		'core/heading',
		{ placeholder: __( 'Add a heading for the rail', 'purplish' ) },
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
	const { clip, sag, length } = attributes;
	const blockProps = useBlockProps( {
		className: `purplish-rail-clip-${ clip } purplish-rail-sag-${ sag } purplish-rail-length-${ length }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className:
				'purplish-rail-stage is-layout-constrained has-global-padding',
		},
		{ allowedBlocks: metadata.allowedBlocks, template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Rail', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Clip', 'purplish' ) }
						value={ clip }
						options={ CLIPS }
						onChange={ ( value ) =>
							setAttributes( { clip: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Rope sag', 'purplish' ) }
						value={ sag }
						options={ SAGS }
						onChange={ ( value ) =>
							setAttributes( { sag: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Scroll length', 'purplish' ) }
						help={ __(
							'How much scrolling the rail takes to show every product. The section holds still while the products slide along the rope. Visitors who ask for reduced motion see a plain row they can scroll sideways.',
							'purplish'
						) }
						value={ length }
						options={ LENGTHS }
						onChange={ ( value ) =>
							setAttributes( { length: value } )
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
