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
import { __ } from '@wordpress/i18n';

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
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function CardsPanel( { saved, setSaved } ) {
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
								'Title and price on one line, and add to cart as a small frosted button on the photo. It shows on hover, and always on touch screens.',
								'purplish'
							)
				}
				checked={ true === saved.overlay && true !== saved.details }
				disabled={ true === saved.details }
				onChange={ ( overlay ) => save( { overlay } ) }
			/>
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
		</PanelBody>
	);
}
