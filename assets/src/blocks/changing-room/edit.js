/**
 * The Changing Room in the editor: the figure in the photo of the front
 * pair, with both rings standing round him as they do on the front end.
 * Selecting a piece turns its ring to it. Each Piece is edited in its own
 * panel; the room's settings and the pair photos are here.
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
	SelectControl,
	TextControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import metadata from './block.json';
import { AREAS, COLORS, LOOKS, SWAPS } from './choices';
import { RoomContext } from './context';
import Photos, { pairKey } from './photos';
import { money, useProducts } from '../showfloor/products';

const PIECE = 'purplish/changing-room-piece';
const TOP = [ PIECE, { slot: 'top' } ];
const BOTTOM = [ PIECE, { slot: 'bottom' } ];
const TEMPLATE = [ TOP, TOP, TOP, BOTTOM, BOTTOM, BOTTOM ];
const SLOTS = [ 'top', 'bottom' ];

// The settings saved as option classes: every attribute with fixed values.
const OPTIONS = Object.keys( metadata.attributes ).filter(
	( key ) => metadata.attributes[ key ].enum
);

const slotOf = ( block ) =>
	'bottom' === block.attributes.slot ? 'bottom' : 'top';

/**
 * The hint line, naming only the moves that are on. ChangingRoom::hint()
 * prints the same line on the front end.
 *
 * @param {Object} a Block attributes.
 * @return {string} The hint.
 */
function hint( a ) {
	const parts = [];
	if ( 'on' === a.wheel ) {
		parts.push( __( 'scroll a row', 'purplish' ) );
	} else if ( 'on' === a.swipe ) {
		parts.push( __( 'swipe a row', 'purplish' ) );
	}
	if ( 'on' === a.drag ) {
		parts.push( __( 'drag a piece onto the model', 'purplish' ) );
	}
	parts.push(
		'on' === a.drawer
			? __( 'tap one for details', 'purplish' )
			: __( 'tap one to try it on', 'purplish' )
	);
	let line = parts[ 0 ];
	if ( 3 === parts.length ) {
		/* translators: 1: a way to use the block, like "scroll a row", 2: another, like "drag a piece onto the model", 3: the last, like "tap one for details". */
		line = sprintf( __( '%1$s, %2$s or %3$s', 'purplish' ), ...parts );
	} else if ( 2 === parts.length ) {
		/* translators: 1: a way to use the block, like "scroll a row", 2: another, like "tap one for details". */
		line = sprintf( __( '%1$s or %2$s', 'purplish' ), ...parts );
	}
	return line.charAt( 0 ).toUpperCase() + line.slice( 1 );
}

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {string}                    props.clientId      Block client ID.
 */
export default function Edit( { attributes, setAttributes, clientId } ) {
	const { pieces, selectedId, parents } = useSelect(
		( select ) => {
			const store = select( blockEditorStore );
			const id = store.getSelectedBlockClientId();
			return {
				pieces: store.getBlocks( clientId ),
				selectedId: id,
				parents: id ? store.getBlockParents( id ) : [],
			};
		},
		[ clientId ]
	);
	const { insertBlock } = useDispatch( blockEditorStore );

	const groups = { top: [], bottom: [] };
	const order = {};
	pieces.forEach( ( block ) => groups[ slotOf( block ) ].push( block ) );
	SLOTS.forEach( ( slot ) =>
		groups[ slot ].forEach( ( block, index ) => {
			order[ block.clientId ] = {
				slot,
				index,
				count: groups[ slot ].length,
			};
		} )
	);

	const [ turned, setTurned ] = useState( { top: 0, bottom: 0 } );
	const picked = pieces.find(
		( block ) =>
			block.clientId === selectedId || parents.includes( block.clientId )
	);
	const pickedAt = picked && order[ picked.clientId ];
	useEffect( () => {
		if ( pickedAt ) {
			setTurned( ( now ) => ( {
				...now,
				[ pickedAt.slot ]: pickedAt.index,
			} ) );
		}
	}, [ pickedAt && pickedAt.slot, pickedAt && pickedAt.index ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const front = {};
	const worn = {};
	SLOTS.forEach( ( slot ) => {
		const count = groups[ slot ].length;
		front[ slot ] = count ? Math.min( turned[ slot ], count - 1 ) : 0;
		worn[ slot ] = count
			? groups[ slot ][ front[ slot ] ].attributes
			: null;
	} );

	const ids = pieces.map( ( block ) => block.attributes.product );
	const products = useProducts( ids );
	const productOf = ( id ) => products.find( ( item ) => item.id === id );
	const wornProducts = SLOTS.map(
		( slot ) => worn[ slot ] && productOf( worn[ slot ].product )
	);
	const photo =
		worn.top &&
		worn.bottom &&
		attributes.photos[ pairKey( worn.top.product, worn.bottom.product ) ];

	const labels = {
		top: attributes.topLabel || __( 'Jacket', 'purplish' ),
		bottom: attributes.bottomLabel || __( 'Trousers', 'purplish' ),
	};
	const both = wornProducts[ 0 ] && wornProducts[ 1 ];
	const total = both
		? money(
				wornProducts[ 0 ].prices,
				Number( wornProducts[ 0 ].prices.price ) +
					Number( wornProducts[ 1 ].prices.price )
			)
		: '';

	const setPhoto = ( key, media ) => {
		const photos = { ...attributes.photos };
		if ( media ) {
			photos[ key ] = {
				id: media.id,
				url:
					( media.sizes &&
						media.sizes.large &&
						media.sizes.large.url ) ||
					media.url,
			};
		} else {
			delete photos[ key ];
		}
		setAttributes( { photos } );
	};

	const blockProps = useBlockProps( {
		className: [
			'is-editor',
			...OPTIONS.map(
				( key ) =>
					`purplish-changing-room-${ key }-${ attributes[ key ] }`
			),
			'theme' !== attributes.look &&
				`purplish-button purplish-button-${ attributes.look }`,
		]
			.filter( Boolean )
			.join( ' ' ),
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'purplish-changing-room-rings' },
		{
			allowedBlocks: metadata.allowedBlocks,
			template: TEMPLATE,
			renderAppender: false,
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
	const text = ( key, label, placeholder ) => (
		<TextControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			placeholder={ placeholder }
			value={ attributes[ key ] || '' }
			onChange={ ( value ) => setAttributes( { [ key ]: value } ) }
		/>
	);
	const tag = ( slot, product ) => (
		<span
			className={ `purplish-changing-room-tag purplish-changing-room-tag-${ slot }` }
		>
			<span className="purplish-changing-room-dot" aria-hidden="true" />
			<span className="purplish-changing-room-tag-name">
				{ product ? product.name : labels[ slot ] }
			</span>
			<span className="purplish-changing-room-tag-price">
				{ product ? money( product.prices ) : '' }
			</span>
		</span>
	);

	const tops = groups.top.filter( ( block ) => block.attributes.product );
	const bottoms = groups.bottom.filter(
		( block ) => block.attributes.product
	);
	const nameOf = ( block ) => {
		const product = productOf( block.attributes.product );
		return product ? product.name : '';
	};

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						onClick={ () =>
							insertBlock(
								createBlock( PIECE, { slot: 'top' } ),
								pieces.length,
								clientId
							)
						}
					>
						{ __( 'Add top piece', 'purplish' ) }
					</ToolbarButton>
					<ToolbarButton
						onClick={ () =>
							insertBlock(
								createBlock( PIECE, { slot: 'bottom' } ),
								pieces.length,
								clientId
							)
						}
					>
						{ __( 'Add bottom piece', 'purplish' ) }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Outfit photos', 'purplish' ) }>
					<p className="components-base-control__help">
						{ __(
							'One photo of the model in each pair, all shot from the same spot and framed the same way. The first top and first bottom show first.',
							'purplish'
						) }
					</p>
					<Photos
						tops={ tops }
						bottoms={ bottoms }
						photos={ attributes.photos }
						nameOf={ nameOf }
						setPhoto={ setPhoto }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Rings', 'purplish' ) }>
					{ text(
						'topLabel',
						__( 'Top ring name', 'purplish' ),
						__( 'Jacket', 'purplish' )
					) }
					{ text(
						'bottomLabel',
						__( 'Bottom ring name', 'purplish' ),
						__( 'Trousers', 'purplish' )
					) }
					{ toggle(
						'far',
						__( 'Far pieces', 'purplish' ),
						__(
							'Pieces two steps round show smaller and faded. Phones show the near pieces only.',
							'purplish'
						)
					) }
				</PanelBody>
				<PanelBody title={ __( 'Show', 'purplish' ) }>
					{ toggle( 'heading', __( 'Heading', 'purplish' ) ) }
					{ 'on' === attributes.heading &&
						text(
							'headingText',
							__( 'Heading text', 'purplish' ),
							__( 'Put a look together', 'purplish' )
						) }
					{ toggle( 'hint', __( 'Hint', 'purplish' ) ) }
					{ toggle(
						'tags',
						__( 'What he wears', 'purplish' ),
						__(
							'The name and price of each piece under the model.',
							'purplish'
						)
					) }
					{ toggle( 'outfit', __( 'Add both button', 'purplish' ) ) }
					{ toggle(
						'drawer',
						__( 'Details panel', 'purplish' ),
						__(
							'Tapping a piece opens its panel. Off, a tap tries it on.',
							'purplish'
						)
					) }
					{ 'on' === attributes.drawer && (
						<>
							{ toggle(
								'specs',
								__( 'Details tab', 'purplish' )
							) }
							{ toggle( 'about', __( 'About tab', 'purplish' ) ) }
							{ toggle(
								'wear',
								__( 'Try it on button', 'purplish' )
							) }
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Moves', 'purplish' ) }>
					{ toggle(
						'drag',
						__( 'Drag onto the model', 'purplish' )
					) }
					{ toggle(
						'wheel',
						__( 'Scroll to turn', 'purplish' ),
						__(
							'A mouse wheel or trackpad over a ring turns it.',
							'purplish'
						)
					) }
					{ toggle( 'swipe', __( 'Swipe to turn', 'purplish' ) ) }
					{ select(
						'swap',
						__( 'Photo change', 'purplish' ),
						SWAPS
					) }
				</PanelBody>
				<PanelBody title={ __( 'Look and color', 'purplish' ) }>
					{ select( 'look', __( 'Button look', 'purplish' ), LOOKS ) }
					{ select(
						'color',
						__( 'Page color from', 'purplish' ),
						COLORS,
						__(
							'The Purplish page color of the piece he wears, or of its picked size.',
							'purplish'
						)
					) }
					{ 'off' !== attributes.color &&
						select(
							'area',
							__( 'Color area', 'purplish' ),
							AREAS
						) }
				</PanelBody>
				<PanelBody title={ __( 'After adding', 'purplish' ) }>
					{ toggle(
						'notice',
						__( 'Added notice', 'purplish' ),
						__(
							'The photo, name and size with a View cart link, above everything.',
							'purplish'
						)
					) }
					{ toggle(
						'fly',
						__( 'Fly to cart', 'purplish' ),
						__(
							'The photo flies into the mini cart. It needs the Mini-Cart block on the page and does nothing without it.',
							'purplish'
						)
					) }
				</PanelBody>
			</InspectorControls>
			<RoomContext.Provider
				value={ { order, front, far: 'on' === attributes.far } }
			>
				<section { ...blockProps }>
					{ ( 'on' === attributes.heading ||
						'on' === attributes.hint ) && (
						<header className="purplish-changing-room-head">
							{ 'on' === attributes.heading && (
								<h2 className="wp-block-heading purplish-changing-room-heading">
									{ attributes.headingText ||
										__(
											'Put a look together',
											'purplish'
										) }
								</h2>
							) }
							{ 'on' === attributes.hint && (
								<p className="purplish-changing-room-hint">
									{ hint( attributes ) }
								</p>
							) }
						</header>
					) }
					<div className="purplish-changing-room-stage">
						<div className="purplish-changing-room-figure">
							{ photo ? (
								<img
									className="purplish-changing-room-photo"
									src={ photo.url }
									alt=""
									draggable="false"
								/>
							) : (
								<p className="purplish-changing-room-missing">
									{ __(
										'Add the photo of this pair in the block panel',
										'purplish'
									) }
								</p>
							) }
						</div>
						<div { ...innerBlocksProps } />
					</div>
					{ ( 'on' === attributes.tags ||
						'on' === attributes.outfit ) && (
						<div className="purplish-changing-room-wearing">
							{ 'on' === attributes.tags &&
								tag( 'top', wornProducts[ 0 ] ) }
							{ 'on' === attributes.outfit && (
								<div className="wp-block-button purplish-changing-room-outfit">
									<span className="wp-block-button__link wp-element-button purplish-changing-room-outfit-add">
										<span>
											{ __( 'Add both', 'purplish' ) }
										</span>
										<span className="purplish-changing-room-total">
											{ total }
										</span>
									</span>
								</div>
							) }
							{ 'on' === attributes.tags &&
								tag( 'bottom', wornProducts[ 1 ] ) }
						</div>
					) }
				</section>
			</RoomContext.Provider>
		</>
	);
}
