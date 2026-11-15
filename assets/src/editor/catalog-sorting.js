/**
 * Purplish panel on the Catalog Sorting block: which orderings show, and a
 * select big enough to tap.
 */
import { useBlockProps } from '@wordpress/block-editor';
import {
	BaseControl,
	CheckboxControl,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { ORDERINGS, shownOrderings } from './catalog-sorting-orderings';

/**
 * Shows the select with the orderings the shop will list while an
 * ordering is hidden; WooCommerce's own view otherwise.
 *
 * @param {Object}                        props           Block edit props.
 * @param {import("react").ComponentType} props.BlockEdit WooCommerce's edit view.
 */
export function CatalogSortingView( { BlockEdit, ...props } ) {
	const saved = props.attributes.purplish || {};
	const hides =
		ORDERINGS.some( ( { option } ) => saved[ option ] ) || saved.rated;
	if ( ! hides ) {
		return <BlockEdit { ...props } />;
	}
	return (
		<SampleSorting
			useLabel={ !! props.attributes.useLabel }
			shown={ shownOrderings( saved, !! window.purplishRated ) }
		/>
	);
}

/**
 * The block wrapper with the trimmed select.
 *
 * @param {Object}   props          Props.
 * @param {boolean}  props.useLabel Whether the block shows its "Sort by" label.
 * @param {Object[]} props.shown    The shown orderings.
 */
function SampleSorting( { useLabel, shown } ) {
	const blockProps = useBlockProps( {
		className: 'woocommerce wc-block-catalog-sorting',
	} );
	const select = (
		<select
			className="orderby"
			id={ useLabel ? 'purplish-orderby-preview' : undefined }
		>
			<option>
				{ useLabel
					? __( 'Default', 'purplish' )
					: __( 'Default sorting', 'purplish' ) }
			</option>
			{ shown.map( ( { option, label, long } ) => (
				<option key={ option }>{ useLabel ? label : long }</option>
			) ) }
		</select>
	);
	return (
		<div { ...blockProps }>
			<form className="woocommerce-ordering">
				{ useLabel && (
					<label htmlFor="purplish-orderby-preview">
						{ __( 'Sort by', 'purplish' ) }
					</label>
				) }
				{ select }
			</form>
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
export default function CatalogSortingPanel( { saved, setSaved } ) {
	const set = ( name ) => ( value ) =>
		setSaved( { ...saved, [ name ]: value || undefined } );
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<BaseControl
				__nextHasNoMarginBottom
				id="purplish-sorting-orderings"
				label={ __( 'Orderings', 'purplish' ) }
				help={ __(
					'Default sorting always shows. An ordering a shopper already sorted by stays in the list.',
					'purplish'
				) }
			>
				{ ORDERINGS.map( ( { option, label } ) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={ option }
						label={ label }
						checked={ ! saved[ option ] }
						onChange={ ( checked ) => set( option )( ! checked ) }
					/>
				) ) }
			</BaseControl>
			{ ! saved.hiderating && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Average rating only once a product has one',
						'purplish'
					) }
					help={ __(
						'Leaves out average rating until at least one product in the store has a rating, so the list never offers an order that changes nothing.',
						'purplish'
					) }
					checked={ !! saved.rated }
					onChange={ set( 'rated' ) }
				/>
			) }
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Size', 'purplish' ) }
				help={ __(
					'Large makes the select at least 44px tall, as easy to tap as the other shop controls.',
					'purplish'
				) }
				value={ saved.size || '' }
				options={ [
					{ value: '', label: __( 'Theme default', 'purplish' ) },
					{ value: 'large', label: __( 'Large', 'purplish' ) },
				] }
				onChange={ set( 'size' ) }
			/>
		</PanelBody>
	);
}
