/**
 * The Showfloor in the editor: the looks stand on the floor as they do on
 * the front end, with the selected look at the front. Clicking a look, a
 * name in the look list or the arrows turns the floor. Each Look is edited
 * in place and in its own panel; the floor's settings are here.
 */
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

import metadata from './block.json';
import {
	ADDS,
	CLOSES,
	CURSORS,
	DEPTHS,
	HOVERS,
	LISTS,
	MARKS,
	PAUSES,
	PHONES,
	SHAPES,
	STARTS,
	TOGGLES,
} from './choices';
import { FloorContext } from './context';
import { money, useProducts } from './products';

const LOOK = 'purplish/showfloor-look';
const TEMPLATE = [ [ LOOK ], [ LOOK ], [ LOOK ] ];

// The settings saved as option classes: every attribute with fixed values.
const OPTIONS = Object.keys( metadata.attributes ).filter(
	( key ) => metadata.attributes[ key ].enum
);

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {string}                    props.clientId      Block client ID.
 */
export default function Edit( { attributes, setAttributes, clientId } ) {
	const { looks, selected } = useSelect(
		( select ) => {
			const store = select( blockEditorStore );
			const blocks = store.getBlocks( clientId );
			const id = store.getSelectedBlockClientId();
			const parents = id ? store.getBlockParents( id ) : [];
			return {
				looks: blocks,
				selected: blocks.findIndex(
					( block ) =>
						block.clientId === id ||
						parents.includes( block.clientId )
				),
			};
		},
		[ clientId ]
	);
	const [ front, setFront ] = useState( 0 );
	useEffect( () => {
		if ( selected >= 0 ) {
			setFront( selected );
		}
	}, [ selected ] );
	const count = looks.length;
	const shown = count ? Math.min( front, count - 1 ) : 0;
	const look = count ? looks[ shown ].attributes : null;
	const products = useProducts( look ? look.products : [] );
	const { insertBlock, selectBlock } = useDispatch( blockEditorStore );

	const addLook = () => insertBlock( createBlock( LOOK ), count, clientId );
	const turn = ( n ) => {
		if ( count ) {
			selectBlock( looks[ ( shown + n + count ) % count ].clientId );
		}
	};

	const sum = products.reduce(
		( total, product ) => total + Number( product.prices.price ),
		0
	);
	const total = products.length ? money( products[ 0 ].prices, sum ) : '';
	const pieces = sprintf(
		/* translators: %s: number of pieces. */
		_n( '%s piece', '%s pieces', products.length, 'purplish' ),
		products.length
	);
	let meta = '';
	if ( products.length ) {
		meta =
			'on' === attributes.total
				? sprintf(
						/* translators: 1: number of pieces, like "3 pieces", 2: look total. */
						__( '%1$s, %2$s', 'purplish' ),
						pieces,
						total
					)
				: pieces;
	}

	const blockProps = useBlockProps( {
		className: [
			'is-ready',
			'is-editor',
			...OPTIONS.map(
				( key ) => `purplish-showfloor-${ key }-${ attributes[ key ] }`
			),
		].join( ' ' ),
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-showfloor-ring' },
		{
			allowedBlocks: metadata.allowedBlocks,
			template: TEMPLATE,
			renderAppender: false,
			orientation: 'horizontal',
		}
	);

	const select = ( key, label, options, help ) => (
		<SelectControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			value={ attributes[ key ] }
			options={ options }
			onChange={ ( value ) => setAttributes( { [ key ]: value } ) }
		/>
	);
	const toggle = ( key, label, help ) => (
		<ToggleControl
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			checked={ 'on' === attributes[ key ] }
			onChange={ ( value ) =>
				setAttributes( { [ key ]: value ? 'on' : 'off' } )
			}
		/>
	);

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton onClick={ addLook }>
						{ __( 'Add look', 'purplish' ) }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Floor', 'purplish' ) }>
					{ select(
						'shape',
						__( 'Floor shape', 'purplish' ),
						SHAPES
					) }
					{ select(
						'depth',
						__( 'Depth', 'purplish' ),
						DEPTHS,
						__(
							'How much smaller, fainter and blurred the looks at the back are.',
							'purplish'
						)
					) }
					{ select(
						'start',
						__( 'Start view', 'purplish' ),
						STARTS
					) }
					{ select(
						'phone',
						__( 'On phones', 'purplish' ),
						PHONES,
						__(
							'A swipe row with the next looks peeking in, or a smaller floor.',
							'purplish'
						)
					) }
				</PanelBody>
				<PanelBody title={ __( 'Autoplay', 'purplish' ) }>
					{ toggle(
						'autoplay',
						__( 'Autoplay', 'purplish' ),
						__(
							'Turns one look at a time. It holds while a visitor hovers, focuses or drags the floor, opens a look or views the grid, and never runs for visitors who ask for reduced motion.',
							'purplish'
						)
					) }
					{ 'on' === attributes.autoplay && (
						<>
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __(
									'Seconds for each look',
									'purplish'
								) }
								value={ attributes.speed }
								min={ 3 }
								max={ 8 }
								onChange={ ( value ) =>
									setAttributes( { speed: value } )
								}
							/>
							{ select(
								'pause',
								__( 'Pause button', 'purplish' ),
								PAUSES,
								'off' === attributes.pause
									? __(
											'Without the button, visitors can only hold the floor by hovering or focusing it, so the page may fall short of WCAG 2.2.2 (Pause, Stop, Hide).',
											'purplish'
										)
									: undefined
							) }
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Controls', 'purplish' ) }>
					{ select(
						'toggle',
						__( 'Floor and grid switch', 'purplish' ),
						TOGGLES
					) }
					{ toggle( 'arrows', __( 'Arrows', 'purplish' ) ) }
					{ select(
						'cursor',
						__( 'Drag cursor', 'purplish' ),
						CURSORS
					) }
					{ select( 'list', __( 'Look list', 'purplish' ), LISTS ) }
				</PanelBody>
				<PanelBody title={ __( 'Shopping', 'purplish' ) }>
					{ toggle( 'shop', __( 'Shop button', 'purplish' ) ) }
					{ 'on' === attributes.shop && (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Shop button text', 'purplish' ) }
							placeholder={ __( 'Shop this look', 'purplish' ) }
							value={ attributes.shopText }
							onChange={ ( value ) =>
								setAttributes( { shopText: value } )
							}
						/>
					) }
					{ select( 'add', __( 'Add buttons', 'purplish' ), ADDS ) }
					{ toggle( 'total', __( 'Look total', 'purplish' ) ) }
					{ toggle(
						'whole',
						__( 'Whole-look button', 'purplish' ),
						__(
							'One button that adds every piece in the look. It hides for a look with a product that needs options chosen first.',
							'purplish'
						)
					) }
					{ select(
						'marks',
						__( 'Tap a marker to', 'purplish' ),
						MARKS
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Grid and open look', 'purplish' ) }
					initialOpen={ false }
				>
					{ select(
						'hover',
						__( 'Grid hover', 'purplish' ),
						HOVERS
					) }
					{ select(
						'close',
						__( 'Close button', 'purplish' ),
						CLOSES
					) }
					{ toggle(
						'fly',
						__( 'Fly to cart', 'purplish' ),
						__(
							'The product photo flies into the mini cart when a visitor adds it. It needs the Mini-Cart block on the page and does nothing without it.',
							'purplish'
						)
					) }
				</PanelBody>
			</InspectorControls>
			<FloorContext.Provider
				value={ { front: shown, count, depth: attributes.depth } }
			>
				<section { ...blockProps }>
					<div className="purplish-showfloor-stage">
						<div
							className="purplish-showfloor-floor"
							aria-hidden="true"
						/>
						<div { ...innerBlocksProps } />
					</div>
					<div className="purplish-showfloor-copy">
						<p className="purplish-showfloor-count">
							{ sprintf(
								/* translators: 1: look number, 2: number of looks. */
								__( 'Look %1$s of %2$s', 'purplish' ),
								shown + 1,
								count
							) }
						</p>
						<h2 className="wp-block-heading purplish-showfloor-title">
							{ ( look && look.name ) ||
								__( 'Name this look', 'purplish' ) }
						</h2>
						{ meta && (
							<p className="purplish-showfloor-meta">{ meta }</p>
						) }
						<p className="purplish-showfloor-line">
							{ look && look.line }
						</p>
						{ 'on' === attributes.shop && (
							<span className="wp-element-button purplish-showfloor-open">
								{ attributes.shopText ||
									__( 'Shop this look', 'purplish' ) }
							</span>
						) }
					</div>
					{ 'off' !== attributes.list && (
						<ul className="purplish-showfloor-names">
							{ looks.map( ( item, index ) => (
								<li key={ item.clientId }>
									<button
										type="button"
										className="purplish-showfloor-name"
										aria-current={ index === shown }
										onClick={ () =>
											selectBlock( item.clientId )
										}
									>
										<span
											className="purplish-showfloor-name-face"
											aria-hidden="true"
											style={ {
												'--fx': item.attributes.face.x,
												'--fy': item.attributes.face.y,
											} }
										>
											{ item.attributes.url && (
												<img
													src={ item.attributes.url }
													alt=""
												/>
											) }
										</span>
										<span className="purplish-showfloor-name-n">
											{ String( index + 1 ).padStart(
												2,
												'0'
											) }
										</span>
										<span className="purplish-showfloor-name-t">
											{ item.attributes.name ||
												__( 'Look', 'purplish' ) }
										</span>
									</button>
								</li>
							) ) }
						</ul>
					) }
					{ 'off' !== attributes.toggle && (
						<div className="purplish-showfloor-view">
							<span
								className="purplish-showfloor-view-ring"
								aria-pressed="true"
							>
								<span className="purplish-showfloor-label">
									{ __( 'Floor', 'purplish' ) }
								</span>
							</span>
							<span
								className="purplish-showfloor-view-grid"
								aria-pressed="false"
							>
								<span className="purplish-showfloor-label">
									{ __( 'Grid', 'purplish' ) }
								</span>
							</span>
						</div>
					) }
					<div className="purplish-showfloor-turn">
						<button
							type="button"
							className="purplish-showfloor-prev"
							aria-label={ __( 'Previous look', 'purplish' ) }
							onClick={ () => turn( -1 ) }
						/>
						<button
							type="button"
							className="purplish-showfloor-add-look"
							onClick={ addLook }
						>
							{ __( 'Add look', 'purplish' ) }
						</button>
						<button
							type="button"
							className="purplish-showfloor-next"
							aria-label={ __( 'Next look', 'purplish' ) }
							onClick={ () => turn( 1 ) }
						/>
					</div>
					{ products.length > 0 && (
						<aside className="purplish-showfloor-panel">
							<p className="purplish-showfloor-panel-title">
								<span>
									{ __( 'In this look', 'purplish' ) }
								</span>
								<span className="purplish-showfloor-panel-total">
									{ total }
								</span>
							</p>
							<ul className="purplish-showfloor-pieces">
								{ products.map( ( product ) => (
									<li
										key={ product.id }
										className="purplish-showfloor-piece"
									>
										{ product.images[ 0 ] && (
											<img
												src={
													product.images[ 0 ]
														.thumbnail
												}
												alt=""
											/>
										) }
										<span className="purplish-showfloor-piece-name">
											<span>{ product.name }</span>
											<span className="purplish-showfloor-piece-price">
												{ money( product.prices ) }
											</span>
										</span>
										<span className="purplish-showfloor-add">
											<span className="purplish-showfloor-add-t">
												{ __(
													'Add to cart',
													'purplish'
												) }
											</span>
										</span>
									</li>
								) ) }
							</ul>
						</aside>
					) }
				</section>
			</FloorContext.Provider>
		</>
	);
}
