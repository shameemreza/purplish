/**
 * Purplish panel on the Product Results Count block.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __, _n, _x, sprintf } from '@wordpress/i18n';

const SHOWS = [ 'number', 'brackets', 'products' ];

// A sample total for the editor, which has no real count.
const SAMPLE = 24;

/**
 * The visible count, as the page shows it.
 *
 * @param {string} show  The Count option.
 * @param {number} total Number of products.
 * @return {string} The text.
 */
function countText( show, total ) {
	const number = total.toLocaleString();
	if ( 'brackets' === show ) {
		return sprintf(
			/* translators: %s: number of products. */
			_x( '(%s)', 'product count in brackets', 'purplish' ),
			number
		);
	}
	if ( 'products' === show ) {
		return sprintf(
			/* translators: %s: number of products. */
			_n( '%s product', '%s products', total, 'purplish' ),
			number
		);
	}
	return number;
}

/**
 * Shows a sample count while the Count option is set; WooCommerce's own
 * view otherwise.
 *
 * @param {Object}                        props           Block edit props.
 * @param {import("react").ComponentType} props.BlockEdit WooCommerce's edit view.
 */
export function CountView( { BlockEdit, ...props } ) {
	const show = ( props.attributes.purplish || {} ).show;
	if ( ! SHOWS.includes( show ) ) {
		return <BlockEdit { ...props } />;
	}
	return <SampleCount text={ countText( show, SAMPLE ) } />;
}

/**
 * The block wrapper with a sample count.
 *
 * @param {Object} props      Props.
 * @param {string} props.text The count text.
 */
function SampleCount( { text } ) {
	const blockProps = useBlockProps( {
		className: 'woocommerce wc-block-product-results-count',
	} );
	return (
		<div { ...blockProps }>
			<p className="woocommerce-result-count">
				<span className="purplish-count-number">{ text }</span>
			</p>
		</div>
	);
}

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function CountPanel( { saved, setSaved } ) {
	const show = SHOWS.includes( saved.show ) ? saved.show : '';
	const set = ( name ) => ( value ) =>
		setSaved( { ...saved, [ name ]: value || undefined } );
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Count', 'purplish' ) }
				help={ __(
					'Just the number sits well next to a title in a shop hero. Screen readers still hear the full sentence. The editor shows a sample number.',
					'purplish'
				) }
				value={ show }
				options={ [
					{ value: '', label: __( 'Full sentence', 'purplish' ) },
					{
						value: 'number',
						label: __( 'Just the number', 'purplish' ),
					},
					{
						value: 'brackets',
						label: __( 'In brackets', 'purplish' ),
					},
					{
						value: 'products',
						label: __( 'Number and “products”', 'purplish' ),
					},
				] }
				onChange={ set( 'show' ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Look', 'purplish' ) }
				help={ __(
					'Pill fills the count with a soft tint of the effect color. Outline draws a thin ring around it.',
					'purplish'
				) }
				value={
					[ 'pill', 'outline' ].includes( saved.look )
						? saved.look
						: ''
				}
				options={ [
					{ value: '', label: __( 'Plain', 'purplish' ) },
					{ value: 'pill', label: __( 'Pill', 'purplish' ) },
					{ value: 'outline', label: __( 'Outline', 'purplish' ) },
				] }
				onChange={ set( 'look' ) }
			/>
			{ show && (
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Motion', 'purplish' ) }
					help={ __(
						'Plays when the page opens and again when a filter changes the count. Roll brings the digits up one after another; Pop grows the number in. Visitors who ask for reduced motion see it as it is.',
						'purplish'
					) }
					value={
						[ 'roll', 'pop' ].includes( saved.motion )
							? saved.motion
							: ''
					}
					options={ [
						{ value: '', label: __( 'None', 'purplish' ) },
						{ value: 'roll', label: __( 'Roll', 'purplish' ) },
						{ value: 'pop', label: __( 'Pop', 'purplish' ) },
					] }
					onChange={ set( 'motion' ) }
				/>
			) }
		</PanelBody>
	);
}
