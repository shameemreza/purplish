/**
 * The Hover Tabs in the editor: the same stylesheet as the front end, so
 * hovering a name changes the photo here too, and the tab being edited
 * shows its photo.
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

const SIDES = [
	{ value: 'end', label: __( 'End', 'purplish' ) },
	{ value: 'start', label: __( 'Start', 'purplish' ) },
];

const CHANGES = [
	{ value: 'fade', label: __( 'Fade', 'purplish' ) },
	{ value: 'wipe', label: __( 'Wipe', 'purplish' ) },
];

const SHAPES = [
	{ value: 'rounded', label: __( 'Rounded', 'purplish' ) },
	{ value: 'square', label: __( 'Square', 'purplish' ) },
];

const TAB = [
	'core/group',
	{ layout: { type: 'default' } },
	[
		[
			'core/heading',
			{ level: 3, placeholder: __( 'Name this tab', 'purplish' ) },
		],
		[ 'core/image', {} ],
	],
];

const TEMPLATE = [ TAB, TAB, TAB ];

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
	const { side, change, shape } = attributes;
	useFillNewGroups( clientId, TAB[ 2 ] );
	const blockProps = useBlockProps( {
		className: `purplish-tabs-side-${ side } purplish-tabs-change-${ change } purplish-tabs-shape-${ shape }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-tabs-list' },
		{
			allowedBlocks: metadata.allowedBlocks,
			template: TEMPLATE,
			defaultBlock: DEFAULT_BLOCK,
			directInsert: true,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tabs', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Photo side', 'purplish' ) }
						help={ __(
							'End puts the photo after the names in the reading direction. On phones and touch screens each tab shows its photo above the name.',
							'purplish'
						) }
						value={ side }
						options={ SIDES }
						onChange={ ( value ) =>
							setAttributes( { side: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Photo change', 'purplish' ) }
						help={ __(
							'How the next photo comes in. Visitors who ask for reduced motion see it change at once.',
							'purplish'
						) }
						value={ change }
						options={ CHANGES }
						onChange={ ( value ) =>
							setAttributes( { change: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Photo shape', 'purplish' ) }
						value={ shape }
						options={ SHAPES }
						onChange={ ( value ) =>
							setAttributes( { shape: value } )
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
