/**
 * The Scroll Lookbook in the editor: the shots, the cards and the closing
 * line stacked as plain blocks, as visitors without JavaScript see them.
 * The film only plays on the front end.
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const LENGTHS = [
	{ value: 'short', label: __( 'Short', 'purplish' ) },
	{ value: 'medium', label: __( 'Medium', 'purplish' ) },
	{ value: 'long', label: __( 'Long', 'purplish' ) },
];

const FFMPEG =
	'ffmpeg -i in.mp4 -an -vf scale=1920:-2 -r 25 -g 5 -keyint_min 5 -bf 0 -crf 24 -movflags +faststart out.mp4';

const shot = ( chapter ) => [
	'core/cover',
	{ dimRatio: 0, minHeight: 100, minHeightUnit: 'svh' },
	[ [ 'core/paragraph', { className: 'is-chapter', placeholder: chapter } ] ],
];

const TEMPLATE = [
	[
		'core/cover',
		{ dimRatio: 40, minHeight: 100, minHeightUnit: 'svh' },
		[
			[
				'core/heading',
				{
					level: 1,
					placeholder: __( 'Add a heading for the film', 'purplish' ),
				},
			],
			[
				'core/paragraph',
				{ placeholder: __( 'Add a short line', 'purplish' ) },
			],
		],
	],
	shot( __( 'Add a chapter name', 'purplish' ) ),
	[ 'woocommerce/product-collection', {} ],
	[
		'core/group',
		{},
		[
			[
				'core/heading',
				{ placeholder: __( 'Add a closing line', 'purplish' ) },
			],
		],
	],
];

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { chapters, timecode, length } = attributes;
	const blockProps = useBlockProps( {
		className: `purplish-lookbook-chapters-${ chapters } purplish-lookbook-timecode-${ timecode } purplish-lookbook-length-${ length }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-lookbook-stage' },
		{ allowedBlocks: metadata.allowedBlocks, template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Scroll Lookbook', 'purplish' ) }>
					<p>
						{ __(
							'Each Cover is a shot, the first one opens the film. The first Product Collection gives the cards: card 1 comes in at the second shot, card 2 at the third, and so on. A Group placed last is the closing line. The film plays on the published page.',
							'purplish'
						) }
					</p>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Chapter list', 'purplish' ) }
						help={ __(
							'Names each stop beside the film, from a paragraph with the class is-chapter in each Cover, or the product name.',
							'purplish'
						) }
						checked={ 'on' === chapters }
						onChange={ ( value ) =>
							setAttributes( { chapters: value ? 'on' : 'off' } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Timecode', 'purplish' ) }
						help={ __(
							'A running timecode in the corner while the film plays.',
							'purplish'
						) }
						checked={ 'on' === timecode }
						onChange={ ( value ) =>
							setAttributes( { timecode: value ? 'on' : 'off' } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Scroll length', 'purplish' ) }
						help={ __(
							'How much scrolling each stop takes.',
							'purplish'
						) }
						value={ length }
						options={ LENGTHS }
						onChange={ ( value ) =>
							setAttributes( { length: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Video files', 'purplish' ) }
					initialOpen={ false }
				>
					<p>
						{ __(
							'Videos follow the scroll smoothly when they have a keyframe every few frames. Most phone and stock clips have one every second or two, so scrolling back stutters. This ffmpeg line makes a clip that scrubs well, without sound:',
							'purplish'
						) }
					</p>
					<p>
						<code>{ FFMPEG }</code>
					</p>
					<p>
						{ __(
							'Photos work in every shot: they zoom slowly as the page scrolls.',
							'purplish'
						) }
					</p>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
