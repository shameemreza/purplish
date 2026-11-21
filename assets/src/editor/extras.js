/**
 * Matching extras panel on Add to Cart + Options: where the products come
 * from, the title, the style and what each shows.
 */
import {
	Button,
	ComboboxControl,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import {
	decodeEntities,
	extrasSettings,
	MAX,
	products,
} from './extras-preview';

/**
 * Search and order the picked products.
 *
 * @param {Object}                  props          Props.
 * @param {number[]}                props.ids      Picked product IDs, in order.
 * @param {(ids: number[]) => void} props.onChange Saves new IDs.
 */
function PickProducts( { ids, onChange } ) {
	const [ search, setSearch ] = useState( '' );
	const [ options, setOptions ] = useState( [] );
	const [ names, setNames ] = useState( {} );
	const find = useDebounce( setSearch, 300 );

	useEffect( () => {
		let live = true;
		products( {
			search,
			type: 'simple',
			stock_status: 'instock',
			per_page: 20,
		} ).then(
			( list ) =>
				live &&
				setOptions(
					list
						.filter( ( product ) => ! ids.includes( product.id ) )
						.map( ( product ) => ( {
							value: String( product.id ),
							label: decodeEntities( product.name ),
						} ) )
				)
		);
		return () => {
			live = false;
		};
	}, [ search, ids ] );

	const key = ids.join( ',' );
	useEffect( () => {
		if ( ! key ) {
			return;
		}
		let live = true;
		products( { include: key, per_page: MAX } ).then(
			( list ) =>
				live &&
				setNames(
					Object.fromEntries(
						list.map( ( product ) => [
							product.id,
							decodeEntities( product.name ),
						] )
					)
				)
		);
		return () => {
			live = false;
		};
	}, [ key ] );

	const move = ( from, to ) => {
		const next = [ ...ids ];
		next.splice( to, 0, next.splice( from, 1 )[ 0 ] );
		onChange( next );
	};

	return (
		<div className="purplish-extras-pick">
			{ ids.length < MAX && (
				<ComboboxControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Add a product', 'purplish' ) }
					help={ __(
						'Simple products in stock. Up to 6, in the order they show.',
						'purplish'
					) }
					value={ null }
					options={ options }
					onFilterValueChange={ find }
					onChange={ ( value ) =>
						value && onChange( [ ...ids, Number( value ) ] )
					}
				/>
			) }
			{ ids.length > 0 && (
				<ol className="purplish-extras-picked">
					{ ids.map( ( id, index ) => {
						const name =
							names[ id ] ||
							/* translators: %d: product ID. */
							sprintf( __( 'Product %d', 'purplish' ), id );
						return (
							<li key={ id }>
								<span>{ name }</span>
								<Button
									size="small"
									disabled={ 0 === index }
									label={ sprintf(
										/* translators: %s: product name. */
										__( 'Move %s up', 'purplish' ),
										name
									) }
									icon="arrow-up-alt2"
									onClick={ () => move( index, index - 1 ) }
								/>
								<Button
									size="small"
									disabled={ index === ids.length - 1 }
									label={ sprintf(
										/* translators: %s: product name. */
										__( 'Move %s down', 'purplish' ),
										name
									) }
									icon="arrow-down-alt2"
									onClick={ () => move( index, index + 1 ) }
								/>
								<Button
									size="small"
									label={ sprintf(
										/* translators: %s: product name. */
										__( 'Remove %s', 'purplish' ),
										name
									) }
									icon="no-alt"
									onClick={ () =>
										onChange(
											ids.filter(
												( picked ) => picked !== id
											)
										)
									}
								/>
							</li>
						);
					} ) }
				</ol>
			) }
		</div>
	);
}

const FROM_HELP = {
	pick: __(
		'The same list shows on every product that uses this template.',
		'purplish'
	),
	crosssells: __(
		'Each product’s own Cross-sells, set under Product data, Linked Products.',
		'purplish'
	),
	auto: __(
		'Products from the same category, then the same tags, best sellers first.',
		'purplish'
	),
};

/**
 * The Matching extras panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 * @param {boolean}                 [props.always] The Matching Extras block: no switch, the list always shows.
 */
export default function ExtrasPanel( { saved, setSaved, always = false } ) {
	const on = always || true === saved.extras;
	const settings = extrasSettings( saved );
	const save = ( changes ) => setSaved( { ...saved, ...changes } );

	return (
		<PanelBody
			title={ __( 'Matching extras', 'purplish' ) }
			initialOpen={ on }
		>
			{ ! always && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Matching extras under the button',
						'purplish'
					) }
					help={ __(
						'Other products with a checkbox under Add to cart. Ticked ones go into the cart with the product on the same click. Only simple products in stock show. The editor shows the picks, or the store’s best sellers.',
						'purplish'
					) }
					checked={ on }
					onChange={ ( extras ) => save( { extras } ) }
				/>
			) }
			{ on && (
				<>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Products', 'purplish' ) }
						help={ FROM_HELP[ settings.from ] }
						value={ settings.from }
						options={ [
							{
								value: 'pick',
								label: __( 'Pick them', 'purplish' ),
							},
							{
								value: 'crosssells',
								label: __( 'Cross-sells', 'purplish' ),
							},
							{
								value: 'auto',
								label: __( 'Auto match', 'purplish' ),
							},
						] }
						onChange={ ( extrasfrom ) => save( { extrasfrom } ) }
					/>
					{ 'pick' === settings.from && (
						<PickProducts
							ids={ settings.pick }
							onChange={ ( extraspick ) =>
								save( { extraspick } )
							}
						/>
					) }
					{ 'auto' !== settings.from && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Auto match when there are none',
								'purplish'
							) }
							checked={ settings.fallback }
							onChange={ ( extrasfallback ) =>
								save( { extrasfallback } )
							}
						/>
					) }
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Title', 'purplish' ) }
						placeholder={ __( 'Add matching extras?', 'purplish' ) }
						value={
							'string' === typeof saved.extrastitle
								? saved.extrastitle
								: ''
						}
						onChange={ ( extrastitle ) =>
							save( { extrastitle: extrastitle || undefined } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Hide the title', 'purplish' ) }
						help={ __(
							'Screen readers still read it.',
							'purplish'
						) }
						checked={ settings.notitle }
						onChange={ ( extrasnotitle ) =>
							save( { extrasnotitle } )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Style', 'purplish' ) }
						value={ settings.style }
						options={ [
							{
								value: 'cards',
								label: __( 'Cards', 'purplish' ),
							},
							{
								value: 'compact',
								label: __( 'Compact rows', 'purplish' ),
							},
							{
								value: 'tiles',
								label: __( 'Tiles', 'purplish' ),
							},
						] }
						onChange={ ( extrasstyle ) => save( { extrasstyle } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Photo', 'purplish' ) }
						checked={ settings.photo }
						onChange={ ( extrasphoto ) => save( { extrasphoto } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'See more details link', 'purplish' ) }
						help={ __(
							'Opens the product in a new tab.',
							'purplish'
						) }
						checked={ settings.link }
						onChange={ ( extraslink ) => save( { extraslink } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Short description', 'purplish' ) }
						help={ __(
							'The first words of each product’s short description. Compact rows leave it out.',
							'purplish'
						) }
						checked={ settings.desc }
						onChange={ ( extrasdesc ) => save( { extrasdesc } ) }
					/>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'How many', 'purplish' ) }
						min={ 1 }
						max={ MAX }
						value={ settings.count }
						onChange={ ( extrascount ) =>
							save( { extrascount: extrascount || 3 } )
						}
					/>
				</>
			) }
		</PanelBody>
	);
}
