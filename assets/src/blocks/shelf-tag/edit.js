/**
 * The Shelf Tag in the editor. On each card it checks the product against
 * the rule, the same way the server does, and shows the tag faded on the
 * cards it won't print on, so the look shows everywhere and the rule stays
 * honest.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const DAY = 86400;

export const LABELS = {
	bestseller: __( 'Best seller', 'purplish' ),
	new: __( 'New arrival', 'purplish' ),
	featured: __( 'Featured', 'purplish' ),
};

const RULES = [
	{ value: 'bestseller', label: __( 'Best sellers', 'purplish' ) },
	{ value: 'new', label: __( 'New products', 'purplish' ) },
	{ value: 'featured', label: __( 'Featured products', 'purplish' ) },
];

const LOOKS = [
	{ value: 'chip', label: __( 'Chip', 'purplish' ) },
	{ value: 'pill', label: __( 'Pill', 'purplish' ) },
	{ value: 'glass', label: __( 'Glass', 'purplish' ) },
];

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
 * @param {Object} shelf      The product's rank, featured flag and creation time.
 * @param {number} now        The time now, in seconds.
 * @return {boolean} Whether the tag prints on the product.
 */
export function matches( attributes, shelf, now ) {
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
	const { rule, top, days, label, look, corner, shine } = attributes;
	const postId = context.postId;

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

	const shows =
		! shelf ||
		matches( attributes, shelf, Math.floor( Date.now() / 1000 ) );

	const blockProps = useBlockProps( {
		className: [
			'purplish-shelf-tag-look-' + look,
			'purplish-shelf-tag-corner-' + corner,
			'purplish-shelf-tag-rule-' + rule,
			shine ? 'purplish-shelf-tag-shine' : '',
			shows ? '' : 'is-purplish-hidden',
		]
			.filter( Boolean )
			.join( ' ' ),
		title: shows
			? undefined
			: __(
					'Not shown on this product: the rule doesn’t match.',
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
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Text', 'purplish' ) }
						placeholder={ LABELS[ rule ] }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
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
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<span className="purplish-shelf-tag-text">
					{ label || LABELS[ rule ] }
				</span>
			</div>
		</>
	);
}
