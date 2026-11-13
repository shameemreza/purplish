/**
 * Purplish panel on the Product Collection block.
 */
import {
	ColorPalette,
	PanelBody,
	SelectControl,
	ToggleControl,
	BaseControl,
} from '@wordpress/components';
import * as components from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { __, _n, sprintf } from '@wordpress/i18n';

// WordPress 7.1 ships the segmented control under its experimental name
// only; newer versions under the stable one.
const ToggleGroupControl =
	components.ToggleGroupControl ||
	components.__experimentalToggleGroupControl;
const ToggleGroupControlOption =
	components.ToggleGroupControlOption ||
	components.__experimentalToggleGroupControlOption;

const HOVERS = [
	{
		label: __( 'On hover', 'purplish' ),
		options: [
			{ value: 'magnetic', label: __( 'Magnetic panel', 'purplish' ) },
			{ value: 'spotlight', label: __( 'Spotlight', 'purplish' ) },
			{ value: 'reveal', label: __( 'Color reveal', 'purplish' ) },
			{ value: 'fan', label: __( 'Fan deck', 'purplish' ) },
			{ value: 'expand', label: __( 'Expanded cards', 'purplish' ) },
			{ value: 'folder', label: __( 'Card folder', 'purplish' ) },
			{ value: 'bookmark', label: __( 'Bookmark', 'purplish' ) },
			{ value: 'roster', label: __( 'Roster', 'purplish' ) },
		],
	},
	{
		label: __( 'In a carousel', 'purplish' ),
		options: [
			{ value: 'deck', label: __( 'Deck', 'purplish' ) },
			{ value: 'arc', label: __( 'Arc', 'purplish' ) },
			{ value: 'stage', label: __( 'Spotlight carousel', 'purplish' ) },
			{ value: 'grow', label: __( 'Grow', 'purplish' ) },
			{ value: 'tiltrow', label: __( 'Tilt', 'purplish' ) },
			{ value: 'depth', label: __( 'Depth', 'purplish' ) },
			{ value: 'overlap', label: __( 'Overlap', 'purplish' ) },
			{ value: 'momentum', label: __( 'Momentum', 'purplish' ) },
			{ value: 'wheel', label: __( 'Wheel', 'purplish' ) },
			{ value: 'helix', label: __( 'Helix', 'purplish' ) },
			{ value: 'reel', label: __( 'Reel', 'purplish' ) },
		],
	},
	{
		label: __( 'As cards scroll in', 'purplish' ),
		options: [
			{ value: 'clothesline', label: __( 'Clothes line', 'purplish' ) },
			{ value: 'parallax', label: __( 'Parallax', 'purplish' ) },
			{ value: 'zoomin', label: __( 'Zoom reveal', 'purplish' ) },
			{ value: 'unfold', label: __( 'Unfold', 'purplish' ) },
		],
	},
];

/**
 * Card effects that already move the whole card or change the photo on
 * hover, as `BlockEffects::CARD_MOVERS` and `PHOTO_CHANGERS`. The hover
 * look for that part is greyed out with them.
 */
const CARD_MOVERS = [
	'magnetic',
	'spotlight',
	'fan',
	'expand',
	'folder',
	'deck',
	'depth',
	'overlap',
	'clothesline',
	'roster',
];
const PHOTO_CHANGERS = [ 'reveal', 'bookmark', 'roster' ];

/**
 * The hover looks for each part of the card. The first is used when the
 * part is turned on.
 */
const LOOKS = {
	card: {
		key: 'hovercard',
		label: __( 'Card on hover', 'purplish' ),
		options: [
			{ value: 'lift', label: __( 'Lift', 'purplish' ) },
			{ value: 'tilt', label: __( 'Tilt', 'purplish' ) },
			{ value: 'ring', label: __( 'Ring', 'purplish' ) },
			{ value: 'torch', label: __( 'Torch', 'purplish' ) },
		],
	},
	photo: {
		key: 'hoverphoto',
		label: __( 'Photo on hover', 'purplish' ),
		options: [
			{ value: 'push', label: __( 'Push in', 'purplish' ) },
			{ value: 'drift', label: __( 'Drift', 'purplish' ) },
			{ value: 'glare', label: __( 'Glare', 'purplish' ) },
			{ value: 'bloom', label: __( 'Color bloom', 'purplish' ) },
			{ value: 'second', label: __( 'Second photo', 'purplish' ) },
		],
	},
	button: {
		key: 'hoverbutton',
		label: __( 'Button on hover', 'purplish' ),
		options: [
			{ value: 'bag', label: __( 'Bag and sweep', 'purplish' ) },
			{ value: 'glow', label: __( 'Glow ring', 'purplish' ) },
			{ value: 'sweep', label: __( 'Sweep', 'purplish' ) },
			{ value: 'spectrum', label: __( 'Spectrum ring', 'purplish' ) },
			{ value: 'glass', label: __( 'Liquid glass', 'purplish' ) },
		],
	},
};

/**
 * The parts a hover target covers.
 *
 * @param {string} target `card`, `photo`, `button`, `all` or ''.
 * @return {string[]} The parts.
 */
const partsOf = ( target ) =>
	'all' === target ? Object.keys( LOOKS ) : [ target ].filter( Boolean );

/**
 * The hover options as the panel shows them. A 1.1 block with Button
 * motion on and no hover target reads as the button's Bag and sweep look,
 * which is what it draws.
 *
 * @param {Object} saved The block's saved `purplish` attribute.
 * @return {Object} `hoveron` and the three looks.
 */
function hoverValues( saved ) {
	if ( true === saved.motion && ! saved.hoveron ) {
		return { ...saved, hoveron: 'button', hoverbutton: 'bag' };
	}
	return saved;
}

/**
 * The Hover section: where the hover plays, and one look per part.
 *
 * @param {Object}                    props       Section props.
 * @param {Object}                    props.saved The block's saved `purplish` attribute.
 * @param {(changes: Object) => void} props.save  Saves changed keys.
 */
function HoverControls( { saved, save } ) {
	const values = hoverValues( saved );
	const target = values.hoveron || '';
	const moves = CARD_MOVERS.includes( saved.hover );
	const changes = PHOTO_CHANGERS.includes( saved.hover );
	const blocked = { card: moves, photo: changes, button: false };

	// The first change here writes the hover keys out, so Button motion,
	// which they replace, goes.
	const update = ( next ) =>
		save( {
			hoveron: values.hoveron,
			hoverbutton: values.hoverbutton,
			...next,
			motion: undefined,
		} );

	const pick = ( next ) => {
		const looks = {};
		partsOf( next ).forEach( ( part ) => {
			const { key, options } = LOOKS[ part ];
			looks[ key ] = values[ key ] || options[ 0 ].value;
		} );
		update( { hoveron: next || undefined, ...looks } );
	};

	let help = __(
		'Plays while the mouse is on the card, alongside the card effect. Off on touch screens; looks that move are off for visitors who ask for reduced motion.',
		'purplish'
	);
	if ( moves ) {
		help = __(
			'This card effect already moves the card on hover, so the card look is off. The photo and button looks still play.',
			'purplish'
		);
	} else if ( changes ) {
		help = __(
			'This card effect already changes the photo on hover, so the photo look is off. The card and button looks still play.',
			'purplish'
		);
	}

	return (
		<>
			<ToggleGroupControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				isBlock
				label={ __( 'Hover plays on', 'purplish' ) }
				help={ help }
				value={ target }
				onChange={ pick }
			>
				<ToggleGroupControlOption
					value=""
					label={ __( 'Off', 'purplish' ) }
				/>
				<ToggleGroupControlOption
					value="card"
					label={ __( 'Card', 'purplish' ) }
					disabled={ moves }
				/>
				<ToggleGroupControlOption
					value="photo"
					label={ __( 'Photo', 'purplish' ) }
					disabled={ changes }
				/>
				<ToggleGroupControlOption
					value="button"
					label={ __( 'Button', 'purplish' ) }
				/>
				<ToggleGroupControlOption
					value="all"
					label={ __( 'All', 'purplish' ) }
					disabled={ moves }
				/>
			</ToggleGroupControl>
			{ partsOf( target )
				.filter( ( part ) => ! blocked[ part ] )
				.map( ( part ) => {
					const { key, label, options } = LOOKS[ part ];
					return (
						<SelectControl
							key={ key }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ label }
							value={ values[ key ] || options[ 0 ].value }
							options={ options }
							onChange={ ( look ) => update( { [ key ]: look } ) }
							help={
								'photo' === part && 'second' === values[ key ]
									? __(
											'Fades to the product’s first gallery photo on the store. Products without one keep their photo. The editor shows the main photo only.',
											'purplish'
										)
									: undefined
							}
						/>
					);
				} ) }
		</>
	);
}

/**
 * Grid switch: the second column count shoppers can switch to.
 *
 * @param {Object}                    props            Control props.
 * @param {Object}                    props.saved      The block's saved `purplish` attribute.
 * @param {(changes: Object) => void} props.save       Saves changes to it.
 * @param {Object}                    props.attributes The block's attributes.
 */
function GridSwitch( { saved, save, attributes } ) {
	const layout = ( attributes && attributes.displayLayout ) || {};
	const own = Number( layout.columns ) || 0;
	const grid = 'flex' === layout.type && own > 0;
	const roster = 'roster' === saved.hover;
	let help = sprintf(
		/* translators: %d: the grid's own column count. */
		__(
			'Two buttons beside Sort by switch the grid between its %d columns and these. The choice lasts for the visit. Phones keep their grid.',
			'purplish'
		),
		own
	);
	if ( ! grid ) {
		help = __( 'Needs the Grid layout.', 'purplish' );
	} else if ( roster ) {
		help = __( 'Roster lays the cards out as its own list.', 'purplish' );
	}
	const counts = [ '2', '3', '4', '5', '6' ].filter(
		( count ) => Number( count ) !== own
	);
	return (
		<SelectControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ __( 'Grid switch', 'purplish' ) }
			help={ help }
			value={
				grid && ! roster && counts.includes( saved.grid )
					? saved.grid
					: ''
			}
			disabled={ ! grid || roster }
			options={ [
				{ label: __( 'Off', 'purplish' ), value: '' },
				...counts.map( ( count ) => ( {
					label: sprintf(
						/* translators: %s: a column count. */
						_n(
							'%s column',
							'%s columns',
							Number( count ),
							'purplish'
						),
						count
					),
					value: count,
				} ) ),
			] }
			onChange={ ( value ) => save( { grid: value || undefined } ) }
		/>
	);
}

/**
 * How the cards move when filters, sorting or a page change swap the
 * results. WooCommerce's Force page reload loads a new page instead, so
 * there's nothing to move.
 *
 * @param {Object}                    props            Control props.
 * @param {Object}                    props.saved      The block's saved `purplish` attribute.
 * @param {(changes: Object) => void} props.save       Saves changes to it.
 * @param {Object}                    props.attributes The block's attributes.
 */
function ResultsChange( { saved, save, attributes } ) {
	const reload = !! ( attributes && attributes.forcePageReload );
	const looks = {
		fade: __( 'The new results fade in.', 'purplish' ),
		rise: __( 'The cards come up one after another.', 'purplish' ),
		move: __(
			'Cards that stay glide to their new places, and new ones fade in.',
			'purplish'
		),
	};
	const look = Object.keys( looks ).includes( saved.results )
		? saved.results
		: '';
	let help = look
		? looks[ look ]
		: __(
				'How the cards change when shoppers filter, sort or change page. Visitors who ask for reduced motion see the swap without motion.',
				'purplish'
			);
	if ( reload ) {
		help = __(
			'Force page reload is on, so each change loads a new page.',
			'purplish'
		);
	}
	return (
		<SelectControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ __( 'Results change', 'purplish' ) }
			help={ help }
			value={ reload ? '' : look }
			disabled={ reload }
			options={ [
				{ label: __( 'Instant', 'purplish' ), value: '' },
				{ label: __( 'Fade', 'purplish' ), value: 'fade' },
				{ label: __( 'Rise', 'purplish' ), value: 'rise' },
				{ label: __( 'Move', 'purplish' ), value: 'move' },
			] }
			onChange={ ( value ) => save( { results: value || undefined } ) }
		/>
	);
}

/**
 * The panel.
 *
 * @param {Object}                  props            Panel props.
 * @param {Object}                  props.saved      The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved   Saves a new `purplish` attribute.
 * @param {Object}                  props.attributes The block's attributes.
 */
export default function CardsPanel( { saved, setSaved, attributes } ) {
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( CardsPanel, 'purplish-effect-color' );
	const save = ( changes ) => setSaved( { ...saved, ...changes } );

	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Button on the photo', 'purplish' ) }
				help={
					true === saved.details
						? __(
								'Details on the photo already puts the button on the photo.',
								'purplish'
							)
						: __(
								'Title and price on one line, and add to cart on the photo. It shows on hover, and always on touch screens.',
								'purplish'
							)
				}
				checked={ true === saved.overlay && true !== saved.details }
				disabled={ true === saved.details }
				onChange={ ( overlay ) => save( { overlay } ) }
			/>
			{ true === saved.overlay && true !== saved.details && (
				<ToggleGroupControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					isBlock
					label={ __( 'Button look', 'purplish' ) }
					help={
						'bar' === saved.buttonlook
							? __(
									'A full-width bar across the bottom of the photo that slides up on hover.',
									'purplish'
								)
							: __(
									'A small frosted button on the bottom right of the photo.',
									'purplish'
								)
					}
					value={ 'bar' === saved.buttonlook ? 'bar' : 'pill' }
					onChange={ ( look ) =>
						save( {
							buttonlook: 'bar' === look ? 'bar' : undefined,
						} )
					}
				>
					<ToggleGroupControlOption
						value="pill"
						label={ __( 'Pill', 'purplish' ) }
					/>
					<ToggleGroupControlOption
						value="bar"
						label={ __( 'Bar', 'purplish' ) }
					/>
				</ToggleGroupControl>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Details on the photo', 'purplish' ) }
				help={
					'roster' === saved.hover
						? __(
								'Roster shows the names as its own list, so it keeps the details where they are.',
								'purplish'
							)
						: __(
								'Title, price and add to cart sit on the bottom of the photo over a soft shade. They show on hover, and always on touch screens.',
								'purplish'
							)
				}
				checked={ true === saved.details }
				onChange={ ( details ) =>
					save( { details: details || undefined } )
				}
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Card effect', 'purplish' ) }
				help={ __(
					'Carousel effects need the Carousel layout and leave a grid as it is. Roster and Unfold need the Grid layout, and Roster shows the plain grid on phones and touch screens. Momentum, Helix and Reel can also be dragged with the mouse. Visitors who ask for reduced motion see no movement.',
					'purplish'
				) }
				value={ saved.hover || '' }
				onChange={ ( hover ) => save( { hover: hover || undefined } ) }
			>
				<option value="">{ __( 'None', 'purplish' ) }</option>
				{ HOVERS.map( ( group ) => (
					<optgroup key={ group.label } label={ group.label }>
						{ group.options.map( ( option ) => (
							<option key={ option.value } value={ option.value }>
								{ option.label }
							</option>
						) ) }
					</optgroup>
				) ) }
			</SelectControl>
			{ 'roster' !== saved.hover && (
				<HoverControls saved={ saved } save={ save } />
			) }
			<BaseControl
				__nextHasNoMarginBottom
				id={ colorId }
				label={ __( 'Effect color', 'purplish' ) }
				help={ __(
					'Used by the button fill, the magnetic panel, the color reveal, the clothes line pegs, the card folder, the bookmark and the hover looks. Without one, the hover looks use the theme’s text color. On a product page with a variation color, that color is used instead.',
					'purplish'
				) }
			>
				<ColorPalette
					colors={ palette || [] }
					value={ saved.color }
					onChange={ ( color ) => save( { color } ) }
					clearable
				/>
			</BaseControl>
			<GridSwitch
				saved={ saved }
				save={ save }
				attributes={ attributes }
			/>
			<ResultsChange
				saved={ saved }
				save={ save }
				attributes={ attributes }
			/>
		</PanelBody>
	);
}
