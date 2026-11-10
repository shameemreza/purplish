/**
 * One Look in the editor: the cut-out standing in its place on the floor,
 * and a panel for its name, line, products, marker spots and face crop.
 */
import {
	BlockControls,
	InspectorControls,
	MediaReplaceFlow,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	FocalPointPicker,
	PanelBody,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useContext, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { FloorContext } from '../showfloor/context';
import { spot } from '../showfloor/depth';
import { money, searchProducts, useProducts } from '../showfloor/products';

const IMAGE = [ 'image' ];
const round = ( value ) => Math.round( value * 1000 ) / 1000;

/**
 * The look's editor view and panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {string}                    props.clientId      Block client ID.
 */
export default function LookEdit( { attributes, setAttributes, clientId } ) {
	const { id, url, name, line, products, marks, face } = attributes;
	const { front, count, depth } = useContext( FloorContext );
	const index = useSelect(
		( select ) => select( blockEditorStore ).getBlockIndex( clientId ),
		[ clientId ]
	);
	const chosen = useProducts( products );
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

	const here = spot( index - front, count, depth, true );
	const blockProps = useBlockProps( {
		className: 'purplish-showfloor-look',
		style: {
			'--x': `calc(${ here.x.toFixed( 3 ) } * var(--purplish-showfloor-rx))`,
			'--y': `calc(${ here.y.toFixed( 3 ) } * var(--purplish-showfloor-ry))`,
			'--s': here.s.toFixed( 3 ),
			'--o': here.o.toFixed( 3 ),
			'--b': here.b.toFixed( 2 ) + 'px',
			'--z': here.z,
		},
	} );

	const pick = ( media ) =>
		setAttributes( {
			id: media.id,
			url:
				( media.sizes && media.sizes.large && media.sizes.large.url ) ||
				media.url,
		} );
	const addProduct = ( product ) => {
		setAttributes( { products: [ ...products, product.id ] } );
		setSearch( '' );
	};
	const removeProduct = ( productId ) => {
		const rest = { ...marks };
		delete rest[ productId ];
		setAttributes( {
			products: products.filter( ( item ) => item !== productId ),
			marks: rest,
		} );
	};
	const markOf = ( productId, n ) =>
		marks[ productId ] || {
			x: 0.5,
			y: round( ( n + 1 ) / ( products.length + 1 ) ),
		};

	const chooser = ( variant, text ) => (
		<MediaUploadCheck>
			<MediaUpload
				onSelect={ pick }
				allowedTypes={ IMAGE }
				value={ id }
				render={ ( { open } ) => (
					<Button variant={ variant } onClick={ open }>
						{ text }
					</Button>
				) }
			/>
		</MediaUploadCheck>
	);

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
				<PanelBody title={ __( 'Look', 'purplish' ) }>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Name', 'purplish' ) }
						value={ name }
						onChange={ ( value ) =>
							setAttributes( { name: value } )
						}
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Short line', 'purplish' ) }
						rows={ 2 }
						value={ line }
						onChange={ ( value ) =>
							setAttributes( { line: value } )
						}
					/>
					<div className="purplish-showfloor-look-image">
						{ chooser(
							'secondary',
							url
								? __( 'Replace cut-out', 'purplish' )
								: __( 'Choose cut-out', 'purplish' )
						) }
						<p className="components-base-control__help">
							{ __(
								'A full-length cut-out with a transparent background (PNG or WebP), shot from the same angle as the other looks.',
								'purplish'
							) }
						</p>
					</div>
				</PanelBody>
				<PanelBody title={ __( 'Products', 'purplish' ) }>
					{ ! products.length && (
						<p className="components-base-control__help">
							{ __(
								'Pick the products in this look. Until then, visitors see the figure and name only.',
								'purplish'
							) }
						</p>
					) }
					<ul className="purplish-showfloor-look-products">
						{ chosen.map( ( product ) => (
							<li key={ product.id }>
								<span>
									{ product.name }
									<small>{ money( product.prices ) }</small>
								</span>
								<Button
									variant="link"
									isDestructive
									onClick={ () =>
										removeProduct( product.id )
									}
									label={ sprintf(
										/* translators: %s: product name. */
										__( 'Remove %s', 'purplish' ),
										product.name
									) }
								>
									{ __( 'Remove', 'purplish' ) }
								</Button>
							</li>
						) ) }
					</ul>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Add a product', 'purplish' ) }
						placeholder={ __( 'Search products', 'purplish' ) }
						value={ search }
						onChange={ setSearch }
					/>
					<ul className="purplish-showfloor-look-found">
						{ found
							.filter(
								( product ) => ! products.includes( product.id )
							)
							.map( ( product ) => (
								<li key={ product.id }>
									<Button
										variant="tertiary"
										onClick={ () => addProduct( product ) }
									>
										{ product.name }
									</Button>
								</li>
							) ) }
					</ul>
				</PanelBody>
				{ url && chosen.length > 0 && (
					<PanelBody
						title={ __( 'Markers', 'purplish' ) }
						initialOpen={ false }
					>
						<p className="components-base-control__help">
							{ __(
								'Place each number on its piece. Visitors see the markers when they open the look.',
								'purplish'
							) }
						</p>
						{ products.map( ( productId, n ) => {
							const product = chosen.find(
								( item ) => item.id === productId
							);
							return (
								product && (
									<FocalPointPicker
										__nextHasNoMarginBottom
										key={ productId }
										label={ sprintf(
											/* translators: 1: marker number, 2: product name. */
											__( '%1$s. %2$s', 'purplish' ),
											n + 1,
											product.name
										) }
										url={ url }
										value={ markOf( productId, n ) }
										onChange={ ( value ) =>
											setAttributes( {
												marks: {
													...marks,
													[ productId ]: {
														x: round( value.x ),
														y: round( value.y ),
													},
												},
											} )
										}
									/>
								)
							);
						} ) }
					</PanelBody>
				) }
				{ url && (
					<PanelBody
						title={ __( 'Face crop', 'purplish' ) }
						initialOpen={ false }
					>
						<FocalPointPicker
							__nextHasNoMarginBottom
							label={ __( 'Face center', 'purplish' ) }
							help={ __(
								'Where the round crop sits when the look list shows faces.',
								'purplish'
							) }
							url={ url }
							value={ face }
							onChange={ ( value ) =>
								setAttributes( {
									face: {
										x: round( value.x ),
										y: round( value.y ),
									},
								} )
							}
						/>
					</PanelBody>
				) }
			</InspectorControls>
			<div { ...blockProps }>
				<div className="purplish-showfloor-figure">
					{ url ? (
						<img src={ url } alt="" draggable="false" />
					) : (
						<div className="purplish-showfloor-empty">
							{ chooser(
								'primary',
								__( 'Choose cut-out', 'purplish' )
							) }
						</div>
					) }
				</div>
				<span
					className="purplish-showfloor-shadow"
					aria-hidden="true"
				/>
			</div>
		</>
	);
}
