/**
 * Wardrobe in the editor: the label sits in the menu like any item, and the
 * panel opens under the header while the item or anything inside it is
 * selected, with the same width and look as the front end. A new Wardrobe
 * starts with the layout from the block's example: links, three photo
 * tiles and a look card.
 */
import {
	InspectorControls,
	RichText,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { getBlockType } from '@wordpress/blocks';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { place } from './place';

/**
 * Block example blocks as an InnerBlocks template.
 *
 * @param {Object[]} blocks Blocks as name, attributes and innerBlocks.
 * @return {Array[]} Template.
 */
const toTemplate = ( blocks = [] ) =>
	blocks.map( ( block ) => [
		block.name,
		block.attributes || {},
		toTemplate( block.innerBlocks ),
	] );

export const chevron = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path d="M6 9.5l6 6 6-6" />
	</svg>
);

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {string}                    props.clientId      Block client ID.
 * @param {boolean}                   props.isSelected    Whether it's selected.
 * @param {Object}                    props.context       Navigation context.
 */
export default function Edit( {
	attributes,
	setAttributes,
	clientId,
	isSelected,
	context,
} ) {
	const { label, url, width, look } = attributes;
	const { open, preview, empty } = useSelect(
		( select ) => {
			const store = select( blockEditorStore );
			return {
				open: store.hasSelectedInnerBlock( clientId, true ),
				preview: !! store.getSettings().isPreviewMode,
				empty: ! store.getBlockCount( clientId ),
			};
		},
		[ clientId ]
	);
	const shown = preview || isSelected || open;
	const itemRef = useRef();
	const panelRef = useRef();

	// A new item gets a label to start from.
	useEffect( () => {
		if ( ! label && empty && ! preview ) {
			setAttributes( { label: __( 'Shop', 'purplish' ) } );
		}
		// Only when it's added.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useLayoutEffect( () => {
		const item = itemRef.current;
		const panel = panelRef.current;
		if ( ! shown || preview || ! item || ! panel ) {
			return;
		}
		const view = item.ownerDocument.defaultView;
		const update = () => place( item, panel );
		update();
		view.addEventListener( 'resize', update );
		return () => view.removeEventListener( 'resize', update );
	}, [ shown, preview, width, look ] );

	const blockProps = useBlockProps( {
		ref: itemRef,
		className: [
			'wp-block-navigation-item',
			'purplish-wardrobe-width-' + width,
			'purplish-wardrobe-look-' + look,
			shown ? 'is-open' : '',
			preview ? 'is-preview' : '',
		].join( ' ' ),
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-wardrobe-inner' },
		{
			template: toTemplate(
				getBlockType( metadata.name )?.example?.innerBlocks
			),
			templateLock: false,
		}
	);
	const showIcon = false !== context.showSubmenuIcon;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'purplish' ) }>
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Link', 'purplish' ) }
						help={ __(
							'Phones and the Navigation overlay show the label as a link to this page. Leave it empty for the shop page.',
							'purplish'
						) }
						type="url"
						value={ url }
						onChange={ ( value ) =>
							setAttributes( { url: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Panel width', 'purplish' ) }
						value={ width }
						options={ [
							{ label: __( 'Wide', 'purplish' ), value: 'wide' },
							{
								label: __( 'Content', 'purplish' ),
								value: 'content',
							},
							{ label: __( 'Full', 'purplish' ), value: 'full' },
						] }
						onChange={ ( value ) =>
							setAttributes( { width: value } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Panel look', 'purplish' ) }
						help={
							'flat' === look
								? __(
										'A flat sheet straight under the header.',
										'purplish'
									)
								: __(
										'A card with rounded corners and a soft shadow.',
										'purplish'
									)
						}
						value={ look }
						options={ [
							{ label: __( 'Card', 'purplish' ), value: 'card' },
							{ label: __( 'Flat', 'purplish' ), value: 'flat' },
						] }
						onChange={ ( value ) =>
							setAttributes( { look: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<li { ...blockProps }>
				<span className="wp-block-navigation-item__content purplish-wardrobe-toggle">
					<RichText
						tagName="span"
						className="wp-block-navigation-item__label"
						identifier="label"
						aria-label={ __( 'Menu label', 'purplish' ) }
						placeholder={ __( 'Add label…', 'purplish' ) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
						allowedFormats={ [] }
						withoutInteractiveFormatting
					/>
					{ showIcon && chevron }
				</span>
				<div className="purplish-wardrobe-panel" ref={ panelRef }>
					<div { ...innerBlocksProps } />
				</div>
			</li>
		</>
	);
}
