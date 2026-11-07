/**
 * The Step Path in the editor: the steps with the line drawn, the way it
 * looks once it has scrolled into view. The line only draws on the front
 * end; the hover look shows here too.
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useFillNewGroups } from '../../shared/fill-new-groups';
import metadata from './block.json';

const DIRECTIONS = [
	{ value: 'across', label: __( 'Across', 'purplish' ) },
	{ value: 'down', label: __( 'Down', 'purplish' ) },
];

const MARKERS = [
	{ value: 'numbers', label: __( 'Numbers', 'purplish' ) },
	{ value: 'dots', label: __( 'Dots', 'purplish' ) },
];

const LINES = [
	{ value: 'plain', label: __( 'Plain', 'purplish' ) },
	{ value: 'dashed', label: __( 'Dashed', 'purplish' ) },
];

const STEP = [
	'core/group',
	{ layout: { type: 'default' } },
	[
		[
			'core/heading',
			{ level: 3, placeholder: __( 'Name this step', 'purplish' ) },
		],
		[
			'core/paragraph',
			{
				placeholder: __( 'Say what happens in this step', 'purplish' ),
			},
		],
	],
];

const TEMPLATE = [ STEP, STEP, STEP ];

// The add button inserts a group straight away; useFillNewGroups fills it.
const DEFAULT_BLOCK = {
	name: 'core/group',
	attributes: { layout: { type: 'default' } },
};

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {string}                    props.clientId      Block client ID.
 */
export default function Edit( { attributes, setAttributes, clientId } ) {
	const { direction, marker, line } = attributes;
	useFillNewGroups( clientId, STEP[ 2 ] );
	const blockProps = useBlockProps( {
		className: `purplish-path-direction-${ direction } purplish-path-marker-${ marker } purplish-path-line-${ line }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-path-steps' },
		{
			allowedBlocks: metadata.allowedBlocks,
			template: TEMPLATE,
			defaultBlock: DEFAULT_BLOCK,
			directInsert: true,
			orientation: 'across' === direction ? 'horizontal' : 'vertical',
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Steps', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Direction', 'purplish' ) }
						help={ __(
							'Across puts the steps side by side. On narrow screens they always run down.',
							'purplish'
						) }
						value={ direction }
						options={ DIRECTIONS }
						onChange={ ( value ) =>
							setAttributes( { direction: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Marker', 'purplish' ) }
						value={ marker }
						options={ MARKERS }
						onChange={ ( value ) =>
							setAttributes( { marker: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Line', 'purplish' ) }
						help={ __(
							'The line draws from step to step as the block scrolls into view, in the block text color. Visitors who ask for reduced motion see it drawn.',
							'purplish'
						) }
						value={ line }
						options={ LINES }
						onChange={ ( value ) =>
							setAttributes( { line: value } )
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
