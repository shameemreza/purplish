/**
 * The Escalator in the editor: the server's own markup, so the editor shows
 * the same tiles, moving, as the published page. Photos are picked from the
 * Media Library; products come from the store.
 */
import {
	InspectorControls,
	MediaPlaceholder,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	useSettings,
} from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	ColorPalette,
	Disabled,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { __, _n, sprintf } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

const SOURCES = [
	{ value: 'products', label: __( 'Products', 'purplish' ) },
	{ value: 'photos', label: __( 'Photos', 'purplish' ) },
];

const QUERIES = [
	{ value: 'newest', label: __( 'Newest', 'purplish' ) },
	{ value: 'best', label: __( 'Best sellers', 'purplish' ) },
	{ value: 'sale', label: __( 'On sale', 'purplish' ) },
	{ value: 'featured', label: __( 'Featured', 'purplish' ) },
	{ value: 'category', label: __( 'One category', 'purplish' ) },
];

const SPEEDS = [
	{ value: 'slow', label: __( 'Slow', 'purplish' ) },
	{ value: 'medium', label: __( 'Medium', 'purplish' ) },
	{ value: 'fast', label: __( 'Fast', 'purplish' ) },
];

const HOVERS = [
	{ value: 'none', label: __( 'None', 'purplish' ) },
	{ value: 'ring', label: __( 'Ring', 'purplish' ) },
	{ value: 'lift', label: __( 'Lift', 'purplish' ) },
	{ value: 'sweep', label: __( 'Light sweep', 'purplish' ) },
	{ value: 'push', label: __( 'Push in', 'purplish' ) },
];

const CARTS = [
	{ value: 'off', label: __( 'None', 'purplish' ) },
	{ value: 'filled', label: __( 'Filled', 'purplish' ) },
	{ value: 'glass', label: __( 'Liquid glass', 'purplish' ) },
	{ value: 'sweep', label: __( 'Light sweep', 'purplish' ) },
	{ value: 'spectrum', label: __( 'Spectrum ring', 'purplish' ) },
];

const COLUMNS = [
	{ value: '3', label: __( 'Three', 'purplish' ) },
	{ value: '2', label: __( 'Two', 'purplish' ) },
];

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { source, query, category, images, count, columns, speed, height } =
		attributes;
	const photos = 'photos' === source;
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( Edit, 'purplish-escalator-color' );
	// The server markup brings its own wrapper and height.
	const blockProps = useBlockProps( { style: { height: 'auto' } } );

	const categories = useSelect(
		( select ) =>
			'category' === query && ! photos
				? select( 'core' ).getEntityRecords(
						'taxonomy',
						'product_cat',
						{ per_page: -1, hide_empty: true, _fields: 'id,name' }
					)
				: null,
		[ query, photos ]
	);

	const pickPhotos = ( media ) =>
		setAttributes( { images: media.map( ( item ) => item.id ) } );

	let body;
	if ( photos && ! images.length ) {
		body = (
			<MediaPlaceholder
				icon="format-gallery"
				labels={ {
					title: __( 'Escalator photos', 'purplish' ),
					instructions: __(
						'Pick six or more photos. They ride the columns in the order you pick them.',
						'purplish'
					),
				} }
				accept="image/*"
				allowedTypes={ [ 'image' ] }
				multiple
				onSelect={ pickPhotos }
			/>
		);
	} else {
		body = (
			<Disabled>
				<ServerSideRender
					block={ metadata.name }
					attributes={ attributes }
					skipBlockSupportAttributes
					EmptyResponsePlaceholder={ () => (
						<p className="purplish-escalator-empty">
							{ __(
								'No products with a photo match. Pick other products or switch to photos.',
								'purplish'
							) }
						</p>
					) }
				/>
			</Disabled>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Escalator', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Tiles', 'purplish' ) }
						help={ __(
							'Product tiles link to the product with its name and price. Photo tiles show your photos only.',
							'purplish'
						) }
						value={ source }
						options={ SOURCES }
						onChange={ ( value ) =>
							setAttributes( { source: value } )
						}
					/>
					{ ! photos && (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Products', 'purplish' ) }
							value={ query }
							options={ QUERIES }
							onChange={ ( value ) =>
								setAttributes( { query: value } )
							}
						/>
					) }
					{ ! photos && 'category' === query && (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Category', 'purplish' ) }
							value={ String( category ) }
							options={ [
								{
									value: '0',
									label: __( 'Pick a category', 'purplish' ),
								},
								...( categories || [] ).map( ( term ) => ( {
									value: String( term.id ),
									label: term.name,
								} ) ),
							] }
							onChange={ ( value ) =>
								setAttributes( {
									category: parseInt( value, 10 ) || 0,
								} )
							}
						/>
					) }
					{ ! photos && (
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Number of products', 'purplish' ) }
							value={ count }
							min={ 4 }
							max={ 18 }
							onChange={ ( value ) =>
								setAttributes( { count: value || 9 } )
							}
						/>
					) }
					{ ! photos && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Name and price', 'purplish' ) }
							help={ __(
								'Under each photo. Screen readers hear the name either way.',
								'purplish'
							) }
							checked={ 'off' !== attributes.details }
							onChange={ ( on ) =>
								setAttributes( { details: on ? 'on' : 'off' } )
							}
						/>
					) }
					{ ! photos && (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Add to cart button', 'purplish' ) }
							help={ __(
								'On the photo, shown when a visitor points at a tile or tabs to it, and always on touch screens. Filled fills it with the effect color, Liquid glass frosts the photo behind it, Light sweep passes a band of light over it, and Spectrum ring runs a rainbow round its edge. Products that need a choice first link to their page.',
								'purplish'
							) }
							value={ attributes.cart || 'off' }
							options={ CARTS }
							onChange={ ( value ) =>
								setAttributes( { cart: value } )
							}
						/>
					) }
					{ photos && (
						<MediaUploadCheck>
							<MediaUpload
								multiple
								gallery
								allowedTypes={ [ 'image' ] }
								value={ images }
								onSelect={ pickPhotos }
								render={ ( { open } ) => (
									<Button
										__next40pxDefaultSize
										variant="secondary"
										onClick={ open }
									>
										{ images.length
											? sprintf(
													/* translators: %d: number of photos picked. */
													_n(
														'Change photos (%d picked)',
														'Change photos (%d picked)',
														images.length,
														'purplish'
													),
													images.length
												)
											: __( 'Pick photos', 'purplish' ) }
									</Button>
								) }
							/>
						</MediaUploadCheck>
					) }
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Columns', 'purplish' ) }
						help={ __(
							'Next to each other, the columns move in opposite directions. Phones show two.',
							'purplish'
						) }
						value={ String( columns ) }
						options={ COLUMNS }
						onChange={ ( value ) =>
							setAttributes( { columns: parseInt( value, 10 ) } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Speed', 'purplish' ) }
						help={ __(
							'The columns stop while a visitor points at them or tabs to a tile, and the pause button stops them for good. Visitors who ask for reduced motion see them standing still.',
							'purplish'
						) }
						value={ speed }
						options={ SPEEDS }
						onChange={ ( value ) =>
							setAttributes( { speed: value } )
						}
					/>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Height in pixels', 'purplish' ) }
						help={ __(
							'Phones cap it at 440 pixels.',
							'purplish'
						) }
						value={ height }
						min={ 320 }
						max={ 900 }
						step={ 20 }
						onChange={ ( value ) =>
							setAttributes( { height: value || 560 } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Tile look', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Hover', 'purplish' ) }
						help={ __(
							'What a tile does when a visitor points at it or tabs to it. Ring draws a line in the effect color inside the photo, Lift raises it with a shadow in the effect color, Light sweep passes a band of light over it, and Push in moves in slowly. Ring stays for visitors who ask for reduced motion; the others stand still.',
							'purplish'
						) }
						value={ attributes.hover || 'none' }
						options={ HOVERS }
						onChange={ ( value ) =>
							setAttributes( { hover: value } )
						}
					/>
					<BaseControl
						__nextHasNoMarginBottom
						id={ colorId }
						label={ __( 'Effect color', 'purplish' ) }
						help={ __(
							'Used by the ring and the lift shadow. Without one, the text color is used.',
							'purplish'
						) }
					>
						<ColorPalette
							colors={ palette || [] }
							value={ attributes.color }
							onChange={ ( color ) => setAttributes( { color } ) }
							clearable
						/>
					</BaseControl>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Corner radius in pixels', 'purplish' ) }
						help={ __(
							'Without one, tiles take the theme’s Purplish radius.',
							'purplish'
						) }
						value={ attributes.radius }
						min={ 0 }
						max={ 48 }
						allowReset
						onChange={ ( value ) =>
							setAttributes( { radius: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ body }</div>
		</>
	);
}
