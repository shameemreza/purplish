/**
 * Instant results on the core Search block: the panel, and a sample
 * results panel under the field while the block is selected.
 */
import {
	PanelBody,
	Popover,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * The block's element in the editor canvas, which may be an iframe.
 *
 * @param {string} clientId Block client ID.
 * @return {Element|null} The element.
 */
function blockElement( clientId ) {
	const frame = document.querySelector( 'iframe[name="editor-canvas"]' );
	const doc = ( frame && frame.contentDocument ) || document;
	return doc.getElementById( 'block-' + clientId );
}

/**
 * The editor view: the block as it is, plus sample results while it's
 * selected and the option is on. The samples are three real products the
 * server hands over, with their prices.
 *
 * @param {Object}                        props           Block edit props.
 * @param {import("react").ComponentType} props.BlockEdit The block's edit view.
 */
export function SearchView( { BlockEdit, ...props } ) {
	const saved = props.attributes.purplish || {};
	const on = !! saved.instant && props.isSelected;
	const [ anchor, setAnchor ] = useState( null );

	useEffect( () => {
		setAnchor( on ? blockElement( props.clientId ) : null );
	}, [ on, props.clientId ] );

	return (
		<>
			<BlockEdit { ...props } />
			{ on && anchor && (
				<Popover
					anchor={ anchor }
					placement="bottom-start"
					focusOnMount={ false }
					animate={ false }
					variant="unstyled"
				>
					<SamplePanel saved={ saved } />
				</Popover>
			) }
		</>
	);
}

/**
 * Sample rows in the page's markup.
 *
 * @param {Object} props       Props.
 * @param {Object} props.saved The block's `purplish` attribute.
 */
function SamplePanel( { saved } ) {
	const sample = window.purplishSearch || { cats: [], items: [] };
	const count = saved.count || 6;
	return (
		<div className="purplish-search purplish-search--preview">
			<div className="purplish-search__list">
				{ false !== saved.cats && sample.cats.length > 0 && (
					<div className="purplish-search__cats">
						{ sample.cats.map( ( cat ) => (
							<span className="purplish-search__cat" key={ cat }>
								{ cat }
							</span>
						) ) }
					</div>
				) }
				{ sample.items.slice( 0, count ).map( ( item ) => (
					<div className="purplish-search__item" key={ item.url }>
						<img src={ item.img } alt="" width="48" height="48" />
						<span className="purplish-search__text">
							<span className="purplish-search__name">
								{ item.name }
							</span>
							{ false !== saved.price && (
								<span className="purplish-search__price">
									{ item.priceText }
								</span>
							) }
						</span>
						{ item.sold && (
							<span className="purplish-search__sold">
								{ __( 'Sold out', 'purplish' ) }
							</span>
						) }
					</div>
				) ) }
				<div className="purplish-search__all">
					{ __( 'See all results', 'purplish' ) }
				</div>
			</div>
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
export default function SearchPanel( { saved, setSaved } ) {
	const set = ( name, off ) => ( value ) =>
		setSaved( { ...saved, [ name ]: value === off ? undefined : value } );
	return (
		<PanelBody title={ __( 'Instant results', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show products while typing', 'purplish' ) }
				help={ __(
					'Matches product names and SKUs, variation SKUs included, from the second letter. Works on cached pages. Prices are the ones a shopper who is not signed in sees.',
					'purplish'
				) }
				checked={ !! saved.instant }
				onChange={ set( 'instant', false ) }
			/>
			{ saved.instant && (
				<>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Products', 'purplish' ) }
						min={ 3 }
						max={ 10 }
						value={ saved.count || 6 }
						onChange={ ( count ) =>
							setSaved( {
								...saved,
								count:
									6 === count || ! count ? undefined : count,
							} )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Matching categories', 'purplish' ) }
						checked={ false !== saved.cats }
						onChange={ set( 'cats', true ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Price', 'purplish' ) }
						checked={ false !== saved.price }
						onChange={ set( 'price', true ) }
					/>
				</>
			) }
		</PanelBody>
	);
}
