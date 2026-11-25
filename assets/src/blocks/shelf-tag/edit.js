/**
 * The Shelf Tag in the editor. On each card it checks the product against
 * the rule, the same way the server does, and shows the tag faded on the
 * cards it won't print on, so the look shows everywhere and the rule stays
 * honest.
 */
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	ComboboxControl,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { date } from '@wordpress/date';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const DAY = 86400;

export const LABELS = {
	bestseller: __( 'Best seller', 'purplish' ),
	new: __( 'New arrival', 'purplish' ),
	featured: __( 'Featured', 'purplish' ),
	sale: __( 'Deal', 'purplish' ),
};

const RULES = [
	{ value: 'bestseller', label: __( 'Best sellers', 'purplish' ) },
	{ value: 'new', label: __( 'New products', 'purplish' ) },
	{ value: 'featured', label: __( 'Featured products', 'purplish' ) },
	{ value: 'tag', label: __( 'Products with a tag', 'purplish' ) },
	{ value: 'category', label: __( 'Products in a category', 'purplish' ) },
	{ value: 'sale', label: __( 'Products on sale', 'purplish' ) },
];

const TAXONOMIES = { tag: 'product_tag', category: 'product_cat' };

const LOOKS = [
	{ value: 'chip', label: __( 'Chip', 'purplish' ) },
	{ value: 'pill', label: __( 'Pill', 'purplish' ) },
	{ value: 'glass', label: __( 'Glass', 'purplish' ) },
	{ value: 'ribbon', label: __( 'Ribbon', 'purplish' ) },
	{ value: 'image', label: __( 'Image', 'purplish' ) },
];

const SIZES = [
	{ value: 's', label: __( 'Small', 'purplish' ) },
	{ value: 'm', label: __( 'Medium', 'purplish' ) },
	{ value: 'l', label: __( 'Large', 'purplish' ) },
];

const DATE = /^\d{4}-\d{2}-\d{2}$/;

const CORNERS = [
	{ value: 'top-right', label: __( 'Top right', 'purplish' ) },
	{ value: 'top-left', label: __( 'Top left', 'purplish' ) },
	{ value: 'bottom-left', label: __( 'Bottom left', 'purplish' ) },
	{ value: 'bottom-right', label: __( 'Bottom right', 'purplish' ) },
];

/**
 * Whether the rule matches a product, from the `purplish_shelf` field.
 *
 * @param {Object} attributes Block attributes.
 * @param {Object} shelf      The product's rank, flags, terms and creation time.
 * @param {number} now        The time now, in seconds.
 * @param {string} today      Today in the store's timezone, as Y-m-d.
 * @return {boolean} Whether the tag prints on the product.
 */
export function matches( attributes, shelf, now, today ) {
	const { from, until } = attributes;
	if (
		( from && DATE.test( from ) && today < from ) ||
		( until && DATE.test( until ) && today > until )
	) {
		return false;
	}
	if ( TAXONOMIES[ attributes.rule ] ) {
		const terms =
			( 'tag' === attributes.rule ? shelf.tags : shelf.cats ) || [];
		return attributes.term > 0 && terms.includes( attributes.term );
	}
	if ( 'sale' === attributes.rule ) {
		return !! shelf.sale;
	}
	if ( 'featured' === attributes.rule ) {
		return !! shelf.featured;
	}
	if ( 'new' === attributes.rule ) {
		const days = Math.max( 1, Math.min( 365, attributes.days || 30 ) );
		return shelf.created >= now - days * DAY;
	}
	const top = Math.max( 1, Math.min( 50, attributes.top || 8 ) );
	return shelf.rank > 0 && shelf.rank <= top;
}

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {Object}                    props.context       Card context.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const {
		rule,
		top,
		days,
		term,
		from,
		until,
		label,
		look,
		corner,
		shine,
		imageId,
		imageAlt,
		size,
	} = attributes;
	const postId = context.postId;
	const taxonomy = TAXONOMIES[ rule ];
	const [ search, setSearch ] = useState( '' );

	const { terms, picked } = useSelect(
		( select ) => {
			if ( ! taxonomy ) {
				return { terms: [], picked: null };
			}
			const store = select( 'core' );
			return {
				terms:
					store.getEntityRecords( 'taxonomy', taxonomy, {
						search,
						per_page: 20,
						orderby: 'count',
						order: 'desc',
						_fields: 'id,name',
					} ) || [],
				picked: term
					? store.getEntityRecord( 'taxonomy', taxonomy, term, {
							_fields: 'id,name',
						} )
					: null,
			};
		},
		[ taxonomy, search, term ]
	);

	const media = useSelect(
		( select ) =>
			'image' === look && imageId
				? select( 'core' ).getEntityRecord(
						'postType',
						'attachment',
						imageId
					)
				: null,
		[ look, imageId ]
	);

	const shelf = useSelect(
		( select ) => {
			if (
				! postId ||
				( context.postType && 'product' !== context.postType )
			) {
				return null;
			}
			const record = select( 'core' ).getEntityRecord(
				'postType',
				'product',
				postId,
				{ _fields: 'id,purplish_shelf' }
			);
			return record && record.purplish_shelf
				? record.purplish_shelf
				: null;
		},
		[ postId, context.postType ]
	);

	const today = date( 'Y-m-d' );
	const shows = shelf
		? matches( attributes, shelf, Math.floor( Date.now() / 1000 ), today )
		: ! (
				( from && DATE.test( from ) && today < from ) ||
				( until && DATE.test( until ) && today > until )
			);

	let placeholder = LABELS[ rule ];
	if ( taxonomy ) {
		placeholder = picked ? picked.name : '';
	}
	const words = label || placeholder;

	const sizes = media && media.media_details && media.media_details.sizes;
	let source = media ? media.source_url : '';
	if ( sizes ) {
		const fit = 'l' === size ? sizes.medium : sizes.thumbnail;
		source = fit ? fit.source_url : source;
	}
	const pictured = 'image' === look && !! source;

	const options = terms.map( ( item ) => ( {
		value: item.id,
		label: item.name,
	} ) );
	if ( picked && ! options.some( ( item ) => item.value === picked.id ) ) {
		options.unshift( { value: picked.id, label: picked.name } );
	}

	const blockProps = useBlockProps( {
		className: [
			'purplish-shelf-tag-look-' +
				( pictured || 'image' !== look ? look : 'chip' ),
			pictured ? 'purplish-shelf-tag-size-' + size : '',
			'purplish-shelf-tag-corner-' + corner,
			'purplish-shelf-tag-rule-' + rule,
			shine && ! pictured ? 'purplish-shelf-tag-shine' : '',
			shows ? '' : 'is-purplish-hidden',
		]
			.filter( Boolean )
			.join( ' ' ),
		title: shows
			? undefined
			: __(
					'Not shown on this product: the rule or the dates don’t match.',
					'purplish'
				),
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tag', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Show on', 'purplish' ) }
						value={ rule }
						options={ RULES }
						onChange={ ( value ) =>
							setAttributes( { rule: value } )
						}
					/>
					{ 'bestseller' === rule && (
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'How many best sellers', 'purplish' ) }
							help={ __(
								'The products that sold the most, counted from orders.',
								'purplish'
							) }
							value={ top }
							min={ 1 }
							max={ 50 }
							onChange={ ( value ) =>
								setAttributes( { top: value || 8 } )
							}
						/>
					) }
					{ 'new' === rule && (
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'New for how many days', 'purplish' ) }
							help={ __(
								'Counted from the day the product was published.',
								'purplish'
							) }
							value={ days }
							min={ 1 }
							max={ 365 }
							onChange={ ( value ) =>
								setAttributes( { days: value || 30 } )
							}
						/>
					) }
					{ taxonomy && (
						<ComboboxControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={
								'tag' === rule
									? __( 'Product tag', 'purplish' )
									: __( 'Category', 'purplish' )
							}
							help={
								'tag' === rule
									? __(
											'Add the tag to products in Products > Tags, or many at once with Bulk edit.',
											'purplish'
										)
									: __(
											'Products in its subcategories get the tag too.',
											'purplish'
										)
							}
							value={ term || null }
							options={ options }
							onFilterValueChange={ setSearch }
							onChange={ ( value ) =>
								setAttributes( { term: Number( value ) || 0 } )
							}
						/>
					) }
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Text', 'purplish' ) }
						help={
							taxonomy
								? __(
										'Leave it empty to use the name of the tag or category.',
										'purplish'
									)
								: undefined
						}
						placeholder={ placeholder }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Dates', 'purplish' ) }
					initialOpen={ !! ( from || until ) }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="date"
						label={ __( 'Show from', 'purplish' ) }
						value={ from }
						max={ until || undefined }
						onChange={ ( value ) =>
							setAttributes( { from: value || '' } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="date"
						label={ __( 'Show until', 'purplish' ) }
						help={ __(
							'Both days count, in the store’s timezone. Leave them empty to show the tag all the time. A cached page shows the tag until the cache refreshes.',
							'purplish'
						) }
						value={ until }
						min={ from || undefined }
						onChange={ ( value ) =>
							setAttributes( { until: value || '' } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Look', 'purplish' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Look', 'purplish' ) }
						value={ look }
						options={ LOOKS }
						onChange={ ( value ) =>
							setAttributes( { look: value } )
						}
					/>
					{ 'image' === look && (
						<>
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									value={ imageId }
									onSelect={ ( picture ) =>
										setAttributes( {
											imageId: picture.id,
										} )
									}
									render={ ( { open } ) => (
										<Button
											__next40pxDefaultSize
											variant="secondary"
											onClick={ open }
										>
											{ imageId
												? __(
														'Change image',
														'purplish'
													)
												: __(
														'Pick image',
														'purplish'
													) }
										</Button>
									) }
								/>
							</MediaUploadCheck>
							{ !! imageId && (
								<Button
									variant="link"
									isDestructive
									onClick={ () =>
										setAttributes( { imageId: 0 } )
									}
								>
									{ __( 'Remove image', 'purplish' ) }
								</Button>
							) }
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Alternative text', 'purplish' ) }
								help={ __(
									'What the image says, for people who can’t see it. Empty uses the image’s own alt text, then the tag’s text.',
									'purplish'
								) }
								value={ imageAlt }
								onChange={ ( value ) =>
									setAttributes( { imageAlt: value } )
								}
							/>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Image width', 'purplish' ) }
								value={ size }
								options={ SIZES }
								onChange={ ( value ) =>
									setAttributes( { size: value } )
								}
							/>
						</>
					) }
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Corner', 'purplish' ) }
						help={ __(
							'Where the tag sits on the photo, when it is inside the Product Image block.',
							'purplish'
						) }
						value={ corner }
						options={ CORNERS }
						onChange={ ( value ) =>
							setAttributes( { corner: value } )
						}
					/>
					{ 'image' !== look && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Shine on hover', 'purplish' ) }
							help={ __(
								'A light passes over the tag once when the card is hovered.',
								'purplish'
							) }
							checked={ !! shine }
							onChange={ ( value ) =>
								setAttributes( { shine: value } )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ pictured ? (
					<img
						className="purplish-shelf-tag-image"
						src={ source }
						alt={ imageAlt || ( media && media.alt_text ) || words }
					/>
				) : (
					<span className="purplish-shelf-tag-text">
						{ words ||
							( taxonomy
								? __( 'Pick a tag or category', 'purplish' )
								: '' ) }
					</span>
				) }
			</div>
		</>
	);
}
