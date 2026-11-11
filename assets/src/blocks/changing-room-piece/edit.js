/**
 * One Piece in the editor: its cut-out standing in its place in its ring,
 * and a panel for its ring, product and cut-out. The piece the ring has
 * turned to sits on the model, faint, as he wears it.
 */
import {
	BlockControls,
	InspectorControls,
	MediaReplaceFlow,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useContext, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { SLOTS } from '../changing-room/choices';
import { RoomContext } from '../changing-room/context';
import { offset, spot, vars } from '../changing-room/ring';
import { money, searchProducts, useProducts } from '../showfloor/products';

const IMAGE = [ 'image' ];

/**
 * The piece's editor view and panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {string}                    props.clientId      Block client ID.
 */
export default function PieceEdit( { attributes, setAttributes, clientId } ) {
	const { slot, product, id, url } = attributes;
	const { order, front, far } = useContext( RoomContext );
	const [ chosen ] = useProducts( product ? [ product ] : [] );
	const [ search, setSearch ] = useState( '' );
	const [ found, setFound ] = useState( [] );

	useEffect( () => {
		const words = search.trim();
		if ( words.length < 2 ) {
			setFound( [] );
			return;
		}
		let live = true;
		const timer = setTimeout( () => {
			searchProducts( words )
				.then( ( list ) => live && setFound( list ) )
				.catch( () => {} );
		}, 300 );
		return () => {
			live = false;
			clearTimeout( timer );
		};
	}, [ search ] );

	const me = order[ clientId ] || { slot, index: 0, count: 1 };
	const worn = me.index === front[ me.slot ];
	const at = spot(
		offset( me.index, front[ me.slot ], me.count ),
		me.count,
		me.slot,
		far
	);
	const image =
		url || ( chosen && chosen.images.length ? chosen.images[ 0 ].src : '' );

	const blockProps = useBlockProps( {
		className: [
			'purplish-changing-room-item',
			`purplish-changing-room-item-${ me.slot }`,
			worn && 'is-worn',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: {
			...vars( at ),
			opacity: worn ? 0.4 : Math.max( at.o, 0.12 ).toFixed( 3 ),
			zIndex: worn ? 30 : at.z,
		},
	} );

	const pick = ( media ) =>
		setAttributes( {
			id: media.id,
			url:
				( media.sizes && media.sizes.large && media.sizes.large.url ) ||
				media.url,
		} );

	return (
		<>
			{ url && (
				<BlockControls group="other">
					<MediaReplaceFlow
						mediaId={ id }
						mediaURL={ url }
						allowedTypes={ IMAGE }
						accept="image/*"
						onSelect={ pick }
						name={ __( 'Replace cut-out', 'purplish' ) }
					/>
				</BlockControls>
			) }
			<InspectorControls>
				<PanelBody title={ __( 'Piece', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Ring', 'purplish' ) }
						value={ slot }
						options={ SLOTS }
						onChange={ ( value ) =>
							setAttributes( { slot: value } )
						}
					/>
					{ chosen && (
						<p className="purplish-changing-room-chosen">
							{ chosen.name }
							<small>{ money( chosen.prices ) }</small>
						</p>
					) }
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={
							product
								? __( 'Change the product', 'purplish' )
								: __( 'Product', 'purplish' )
						}
						placeholder={ __( 'Search products', 'purplish' ) }
						value={ search }
						onChange={ setSearch }
					/>
					<ul className="purplish-changing-room-found">
						{ found.map( ( item ) => (
							<li key={ item.id }>
								<Button
									variant="tertiary"
									onClick={ () => {
										setAttributes( { product: item.id } );
										setSearch( '' );
									} }
								>
									{ item.name }
								</Button>
							</li>
						) ) }
					</ul>
				</PanelBody>
				<PanelBody title={ __( 'Cut-out', 'purplish' ) }>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ pick }
							allowedTypes={ IMAGE }
							value={ id }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ url
										? __( 'Replace cut-out', 'purplish' )
										: __( 'Choose cut-out', 'purplish' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ url && (
						<Button
							variant="link"
							isDestructive
							onClick={ () =>
								setAttributes( { id: 0, url: '' } )
							}
						>
							{ __( 'Use the product image', 'purplish' ) }
						</Button>
					) }
					<p className="components-base-control__help">
						{ __(
							'A flat cut-out of the piece with a transparent background (PNG or WebP). Empty, the product image shows.',
							'purplish'
						) }
					</p>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ image ? (
					<img src={ image } alt="" draggable="false" />
				) : (
					<span className="purplish-changing-room-blank">
						{ __( 'Pick a product', 'purplish' ) }
					</span>
				) }
			</div>
		</>
	);
}
