/**
 * The Story Deck in the editor: the stories as a plain list of cards, the
 * way visitors without JavaScript see them. The deck only works on the
 * front end.
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const SIDES = [
	{ value: 'left', label: __( 'Left', 'purplish' ) },
	{ value: 'right', label: __( 'Right', 'purplish' ) },
];

const SHAPES = [
	{ value: 'rounded', label: __( 'Rounded', 'purplish' ) },
	{ value: 'square', label: __( 'Square', 'purplish' ) },
];

const FANS = [
	{ value: 'fanned', label: __( 'Fanned', 'purplish' ) },
	{ value: 'flat', label: __( 'Flat', 'purplish' ) },
];

const STORY = [
	'core/group',
	{ layout: { type: 'default' } },
	[
		[ 'core/image', {} ],
		[
			'core/quote',
			{},
			[
				[
					'core/paragraph',
					{
						placeholder: __(
							'Write what the customer said',
							'purplish'
						),
					},
				],
			],
		],
		[
			'core/paragraph',
			{ placeholder: __( 'Add their name', 'purplish' ) },
		],
	],
];

const TEMPLATE = [ STORY, STORY ];

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { side, shape, fan } = attributes;
	const blockProps = useBlockProps( {
		className: `purplish-deck-side-${ side } purplish-deck-shape-${ shape } purplish-deck-fan-${ fan }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-deck-stories' },
		{ allowedBlocks: metadata.allowedBlocks, template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Deck', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Deck side', 'purplish' ) }
						help={ __(
							'Which side the cards sit on, with the story beside them. On phones the story sits under the cards.',
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
						label={ __( 'Card shape', 'purplish' ) }
						value={ shape }
						options={ SHAPES }
						onChange={ ( value ) =>
							setAttributes( { shape: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Fan', 'purplish' ) }
						help={ __(
							'Fanned cards under the top one lean out like a hand of cards. The deck never moves on its own: visitors flick the top card away or use the buttons and arrow keys.',
							'purplish'
						) }
						value={ fan }
						options={ FANS }
						onChange={ ( value ) =>
							setAttributes( { fan: value } )
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
