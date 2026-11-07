/**
 * The Step Path in the editor: the steps with the line drawn, the way it
 * looks once it has scrolled into view. The line only draws on the front
 * end; the hover look shows here too.
 */
import {
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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

// The add button inserts a group straight away; the hook below fills it.
const DEFAULT_BLOCK = {
	name: 'core/group',
	attributes: { layout: { type: 'default' } },
};

/**
 * Gives a step added after the block loaded a heading and a paragraph, so
 * it doesn't open on the empty group's layout picker. Saved steps and
 * steps a merchant empties later are left alone.
 *
 * @param {string} clientId The Step Path block's client ID.
 */
function useFillNewSteps( clientId ) {
	const steps = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);
	const { replaceInnerBlocks, __unstableMarkNextChangeAsNotPersistent } =
		useDispatch( blockEditorStore );
	const known = useRef( null );

	useEffect( () => {
		const before = known.current;
		known.current = new Set( steps.map( ( step ) => step.clientId ) );
		if ( ! before ) {
			return;
		}
		steps
			.filter(
				( step ) =>
					! before.has( step.clientId ) &&
					'core/group' === step.name &&
					! step.innerBlocks.length
			)
			.forEach( ( step ) => {
				__unstableMarkNextChangeAsNotPersistent();
				replaceInnerBlocks(
					step.clientId,
					createBlocksFromInnerBlocksTemplate( STEP[ 2 ] ),
					false
				);
			} );
	}, [ steps, replaceInnerBlocks, __unstableMarkNextChangeAsNotPersistent ] );
}

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
	useFillNewSteps( clientId );
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
