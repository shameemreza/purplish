/**
 * Purplish panel on the Product Stock Indicator block, and its editor view
 * while a sold-out look or the dot is on.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const LOOKS = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'pill', label: __( 'Pill', 'purplish' ) },
	{ value: 'chip', label: __( 'Chip', 'purplish' ) },
	{ value: 'glass', label: __( 'Glass', 'purplish' ) },
];

const CORNERS = [
	{ value: 'top-left', label: __( 'Top left', 'purplish' ) },
	{ value: 'top-right', label: __( 'Top right', 'purplish' ) },
	{ value: 'bottom-left', label: __( 'Bottom left', 'purplish' ) },
	{ value: 'bottom-right', label: __( 'Bottom right', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function StockPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Only when sold out', 'purplish' ) }
				help={
					saved.dot
						? __(
								'Sold-out products show in this look, and other products keep their dot. On the photo, inside the Product Image block, only sold-out products show.',
								'purplish'
							)
						: __(
								'Shows only on sold-out products, in this look. Put the block inside the Product Image block to place it on the photo.',
								'purplish'
							)
				}
				value={ saved.soldout || '' }
				options={ LOOKS }
				onChange={ ( soldout ) =>
					setSaved( { ...saved, soldout: soldout || undefined } )
				}
			/>
			{ saved.soldout && (
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Corner', 'purplish' ) }
					help={ __(
						'Where it sits on the photo, inside the Product Image block.',
						'purplish'
					) }
					value={ saved.corner || 'top-left' }
					options={ CORNERS }
					onChange={ ( corner ) =>
						setSaved( {
							...saved,
							corner: 'top-left' === corner ? undefined : corner,
						} )
					}
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Dot', 'purplish' ) }
				help={ __(
					'A small colored dot before In stock, low stock and On backorder, a different color for each. The words stay.',
					'purplish'
				) }
				checked={ true === saved.dot }
				onChange={ ( dot ) =>
					setSaved( { ...saved, dot: dot || undefined } )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Grey out sold-out cards', 'purplish' ) }
				help={ __(
					'The photo of a sold-out product turns grey and soft, and comes back a little on hover.',
					'purplish'
				) }
				checked={ true === saved.grey }
				onChange={ ( grey ) =>
					setSaved( { ...saved, grey: grey || undefined } )
				}
			/>
		</PanelBody>
	);
}

/**
 * The block in the editor while a sold-out look or the dot is on.
 * WooCommerce's own view shows "In stock" on every card, so neither could
 * be seen; this one reads each card's real stock from the `purplish_shelf`
 * field and puts the stock classes on the wrapper, as the front end does.
 * Outside a product card it shows a sold-out sample, or an in-stock one
 * with the dot.
 *
 * @param {Object}                        props           Block edit props.
 * @param {import("react").ComponentType} props.BlockEdit WooCommerce's edit view.
 */
export function StockView( { BlockEdit, ...props } ) {
	const saved = props.attributes.purplish || {};
	const shown = !! saved.soldout || true === saved.dot;
	const postId = props.context && props.context.postId;

	const stock = useSelect(
		( select ) => {
			if ( ! shown || ! postId ) {
				return null;
			}
			const record = select( 'core' ).getEntityRecord(
				'postType',
				'product',
				postId,
				{ _fields: 'id,purplish_shelf' }
			);
			return record && record.purplish_shelf
				? record.purplish_shelf.stock
				: null;
		},
		[ shown, postId ]
	);

	if ( ! shown ) {
		return <BlockEdit { ...props } />;
	}
	return <Stock stock={ stock } dot={ true === saved.dot } />;
}

/**
 * The indicator with the card's stock, or a sample.
 *
 * @param {Object}      props       Props.
 * @param {Object|null} props.stock The product's stock text, class and low flag.
 * @param {boolean}     props.dot   Whether the dot is on.
 */
function Stock( { stock, dot } ) {
	const fallback = dot ? 'in-stock' : 'out-of-stock';
	const state = stock ? stock.class : fallback;
	const blockProps = useBlockProps( {
		className:
			'wc-block-components-product-stock-indicator wc-block-components-product-stock-indicator--' +
			state +
			( stock && stock.low ? ' purplish-stock-low' : '' ),
		title:
			'out-of-stock' === state || dot
				? undefined
				: __(
						'Not shown on this product: it isn’t sold out.',
						'purplish'
					),
	} );
	const text =
		( stock && stock.text ) ||
		( 'out-of-stock' === state
			? __( 'Out of stock', 'purplish' )
			: __( 'In stock', 'purplish' ) );
	return <div { ...blockProps }>{ text }</div>;
}
