/**
 * Login and register options for My Account: the panel on the Account
 * Group and on the Shortcode block that holds `[woocommerce_my_account]`,
 * and sample forms in the canvas with the picked options on them, since
 * the editor shows the shortcode only as text.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { setup } from '../login/setup';
import { sampleForms } from './account-sample';

export const ACCOUNT_STYLE = 'is-style-purplish-account';
export const SHORTCODE = 'woocommerce_my_account';

/**
 * The choices for each option, in the order the server reads them (see
 * AccountForms::config()).
 *
 * @return {Object<string, Array<{value: string, label: string}>>} Choices per option.
 */
export function choices() {
	return {
		fields: [
			{ value: '', label: __( 'Theme default', 'purplish' ) },
			{ value: 'soft', label: __( 'Soft', 'purplish' ) },
			{ value: 'filled', label: __( 'Filled', 'purplish' ) },
			{ value: 'underline', label: __( 'Underline', 'purplish' ) },
			{ value: 'draw', label: __( 'Draw', 'purplish' ) },
			{ value: 'fill', label: __( 'Fill', 'purplish' ) },
			{ value: 'corners', label: __( 'Corners', 'purplish' ) },
		],
		labels: [
			{ value: '', label: __( 'Above', 'purplish' ) },
			{ value: 'inside', label: __( 'Inside', 'purplish' ) },
			{ value: 'floating', label: __( 'Floating', 'purplish' ) },
			{ value: 'flip', label: __( 'Flip', 'purplish' ) },
			{ value: 'wave', label: __( 'Wave', 'purplish' ) },
		],
		switch: [
			{ value: '', label: __( 'Side by side', 'purplish' ) },
			{ value: 'tabs', label: __( 'Tabs', 'purplish' ) },
			{ value: 'flip', label: __( 'Flip', 'purplish' ) },
			{ value: 'slide', label: __( 'Slide', 'purplish' ) },
		],
		tabs: [
			{ value: '', label: __( 'Pill', 'purplish' ) },
			{ value: 'line', label: __( 'Line', 'purplish' ) },
		],
		slide: [
			{ value: '', label: __( 'Push', 'purplish' ) },
			{ value: 'stretch', label: __( 'Stretch', 'purplish' ) },
			{ value: 'flood', label: __( 'Flood', 'purplish' ) },
			{ value: 'blinds', label: __( 'Blinds', 'purplish' ) },
			{ value: 'page', label: __( 'Page', 'purplish' ) },
			{ value: 'shear', label: __( 'Shear', 'purplish' ) },
			{ value: 'shutter', label: __( 'Shutter', 'purplish' ) },
			{ value: 'liquid', label: __( 'Liquid', 'purplish' ) },
			{ value: 'depth', label: __( 'Depth', 'purplish' ) },
			{ value: 'elastic', label: __( 'Elastic', 'purplish' ) },
			{ value: 'mosaic', label: __( 'Mosaic', 'purplish' ) },
			{ value: 'fold', label: __( 'Fold', 'purplish' ) },
			{ value: 'iris', label: __( 'Iris', 'purplish' ) },
		],
		panel: [
			{ value: '', label: __( 'Wash', 'purplish' ) },
			{ value: 'card', label: __( 'Card', 'purplish' ) },
			{ value: 'glass', label: __( 'Glass', 'purplish' ) },
		],
		button: [
			{ value: '', label: __( 'Theme button', 'purplish' ) },
			{ value: 'bloom', label: __( 'Bloom', 'purplish' ) },
			{ value: 'ripple', label: __( 'Ripple', 'purplish' ) },
			{ value: 'sweep', label: __( 'Sweep', 'purplish' ) },
			{ value: 'spectrum', label: __( 'Spectrum', 'purplish' ) },
			{ value: 'trace', label: __( 'Trace', 'purplish' ) },
			{ value: 'roll', label: __( 'Roll', 'purplish' ) },
			{ value: 'split', label: __( 'Split', 'purplish' ) },
			{ value: 'glass', label: __( 'Glass', 'purplish' ) },
		],
	};
}

/**
 * Options that only apply with another option set, as in
 * AccountForms::config(): the switch values each needs.
 */
const NEEDS = {
	tabs: [ 'tabs', 'slide' ],
	slide: [ 'slide' ],
};

/**
 * Whether an option applies with the other saved options.
 *
 * @param {string} option Option name.
 * @param {Object} saved  Saved account options.
 * @return {boolean} Whether it applies.
 */
export function applies( option, saved ) {
	return ! NEEDS[ option ] || NEEDS[ option ].includes( saved.switch );
}

const SWITCHES = [ 'focus', 'aurora', 'spotlight' ];

let samples = 0;

/**
 * The classes the server prints for saved options, `purplish-account`
 * first, or none with no option set.
 *
 * @param {Object} account Saved account options.
 * @return {string[]} Class names.
 */
export function accountClasses( account ) {
	const saved = account || {};
	const list = choices();
	const classes = [];
	Object.keys( list ).forEach( ( option ) => {
		if (
			applies( option, saved ) &&
			list[ option ].some(
				( c ) => c.value && c.value === saved[ option ]
			)
		) {
			classes.push(
				'purplish-account-' + option + '-' + saved[ option ]
			);
		}
	} );
	SWITCHES.forEach( ( option ) => {
		if ( true === saved[ option ] ) {
			classes.push( 'purplish-account-' + option );
		}
	} );
	return classes.length ? [ 'purplish-account', ...classes ] : [];
}

/**
 * Whether a Group carries the Account style.
 *
 * @param {Object} attributes Block attributes.
 * @return {boolean} Whether it does.
 */
export function isAccountGroup( attributes ) {
	return ( attributes.className || '' )
		.split( /\s+/ )
		.includes( ACCOUNT_STYLE );
}

/**
 * Whether a Shortcode block holds My Account.
 *
 * @param {Object} attributes Block attributes.
 * @return {boolean} Whether it does.
 */
export function holdsAccount( attributes ) {
	return ( attributes.text || '' ).includes( '[' + SHORTCODE );
}

/**
 * The closest Account Group around a block, or null.
 *
 * @param {string} clientId Block client ID.
 * @return {Object|null} The Group's block, or null.
 */
function useAccountGroup( clientId ) {
	return useSelect(
		( select ) => {
			const editor = select( blockEditorStore );
			const parents = editor.getBlockParents( clientId, true );
			for ( const id of parents ) {
				const block = editor.getBlock( id );
				if (
					block &&
					'core/group' === block.name &&
					isAccountGroup( block.attributes )
				) {
					return block;
				}
			}
			return null;
		},
		[ clientId ]
	);
}

/**
 * The options.
 *
 * @param {Object}                    props            Panel props.
 * @param {Object}                    props.account    Saved account options.
 * @param {(account: Object) => void} props.setAccount Saves new account options.
 */
export function AccountOptions( { account, setAccount } ) {
	const list = choices();
	const set = ( option, value ) => {
		const next = { ...account, [ option ]: value || undefined };
		Object.keys( next ).forEach(
			( key ) => undefined === next[ key ] && delete next[ key ]
		);
		setAccount( next );
	};
	return (
		<>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Fields', 'purplish' ) }
				help={ __(
					'Soft and Filled tint the fields, Underline keeps a line under each, Draw traces a line around a field when it takes focus, Fill washes the color in, and Corners draws four marks around it.',
					'purplish'
				) }
				value={ account.fields || '' }
				options={ list.fields }
				onChange={ ( value ) => set( 'fields', value ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Focus ring', 'purplish' ) }
				help={ __(
					"A ring in your theme's accent color around the field, box or link that has keyboard focus.",
					'purplish'
				) }
				checked={ true === account.focus }
				onChange={ ( on ) => set( 'focus', on || undefined ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Labels', 'purplish' ) }
				help={ __(
					'Inside puts the label in the field. Floating holds it in the empty field and moves it up as you type. Flip turns it over on the way up, and Wave ripples its letters up one after another.',
					'purplish'
				) }
				value={ account.labels || '' }
				options={ list.labels }
				onChange={ ( value ) => set( 'labels', value ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Log in and register', 'purplish' ) }
				help={ __(
					'Tabs shows one form at a time with a tab for each. Flip turns the card over to the other form. Slide puts both forms in one card and slides a colored panel over the closed one, with tabs on phones.',
					'purplish'
				) }
				value={ account.switch || '' }
				options={ list.switch }
				onChange={ ( value ) => set( 'switch', value ) }
			/>
			{ applies( 'tabs', account ) && (
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Tab look', 'purplish' ) }
					help={ __(
						'Pill slides a raised box to the picked tab. Line puts the tabs over a rule and stretches a line to the picked one.',
						'purplish'
					) }
					value={ account.tabs || '' }
					options={ list.tabs }
					onChange={ ( value ) => set( 'tabs', value ) }
				/>
			) }
			{ applies( 'slide', account ) && (
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Slide motion', 'purplish' ) }
					help={ __(
						'How the colored panel moves to the other form. Under reduced motion it moves without the effect.',
						'purplish'
					) }
					value={ account.slide || '' }
					options={ list.slide }
					onChange={ ( value ) => set( 'slide', value ) }
				/>
			) }
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Panel', 'purplish' ) }
				help={ __(
					'Wash keeps the soft tint of the Account style. Card puts each form in a raised white box, and Glass in a frosted one.',
					'purplish'
				) }
				value={ account.panel || '' }
				options={ list.panel }
				onChange={ ( value ) => set( 'panel', value ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Aurora', 'purplish' ) }
				help={ __(
					'A light in the wash behind the forms that follows the pointer and keyboard focus.',
					'purplish'
				) }
				checked={ true === account.aurora }
				onChange={ ( on ) => set( 'aurora', on || undefined ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Spotlight', 'purplish' ) }
				help={ __(
					'The edge of each form box lights up where the pointer is. Under reduced motion the light stays still.',
					'purplish'
				) }
				checked={ true === account.spotlight }
				onChange={ ( on ) => set( 'spotlight', on || undefined ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Button', 'purplish' ) }
				help={ __(
					'How Log in, Register and the other form buttons answer the pointer and keyboard focus. Bloom grows the effect color from where the pointer comes in, and Ripple spreads ink from where the button is pressed. Under reduced motion they keep the colors without the movement.',
					'purplish'
				) }
				value={ account.button || '' }
				options={ list.button }
				onChange={ ( value ) => set( 'button', value ) }
			/>
		</>
	);
}

/**
 * The Login and register panel, for the Account Group.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export function AccountPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Login and register', 'purplish' ) }>
			<p className="components-base-control__help">
				{ __(
					"How the log in and register forms look to visitors who aren't signed in.",
					'purplish'
				) }
			</p>
			<AccountOptions
				account={ saved.account || {} }
				setAccount={ ( account ) => setSaved( { ...saved, account } ) }
			/>
		</PanelBody>
	);
}

/**
 * The panel on the Shortcode block, only while it holds My Account. Inside
 * an Account Group the options live on the Group, so the panel says so.
 *
 * @param {Object}                  props            Panel props.
 * @param {Object}                  props.attributes Block attributes.
 * @param {string}                  props.clientId   Block client ID.
 * @param {Object}                  props.saved      The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved   Saves a new `purplish` attribute.
 */
export function ShortcodePanel( { attributes, clientId, saved, setSaved } ) {
	const group = useAccountGroup( clientId );
	if ( ! holdsAccount( attributes ) ) {
		return null;
	}
	if ( group && ! saved.account ) {
		return (
			<PanelBody title={ __( 'Login and register', 'purplish' ) }>
				<p className="components-base-control__help">
					{ __(
						'This block sits in an Account Group. Select the Group to change how the log in and register forms look.',
						'purplish'
					) }
				</p>
			</PanelBody>
		);
	}
	return <AccountPanel saved={ saved } setSaved={ setSaved } />;
}

/**
 * The block as it is, then sample forms with the options on them: the
 * block's own, or the Account Group's around it.
 *
 * @param {Object}                        props           Block edit props.
 * @param {import("react").ComponentType} props.BlockEdit The block's edit view.
 */
export function ShortcodeView( { BlockEdit, ...props } ) {
	const group = useAccountGroup( props.clientId );
	const own = ( props.attributes.purplish || {} ).account;
	const groupAccount = group
		? ( group.attributes.purplish || {} ).account
		: null;
	const account = own || groupAccount || {};

	if ( ! holdsAccount( props.attributes ) ) {
		return <BlockEdit { ...props } />;
	}
	return (
		<>
			<BlockEdit { ...props } />
			<SampleForms classes={ accountClasses( account ) } />
		</>
	);
}

/**
 * Sample log in and register forms in WooCommerce's markup. They are drawn
 * again from scratch whenever the options change, so the script can set
 * them up the way it does on the page.
 *
 * @param {Object}   props         Props.
 * @param {string[]} props.classes Option classes.
 */
function SampleForms( { classes } ) {
	const key = classes.join( ' ' );
	return <SampleFormsInner key={ key } classes={ classes } />;
}

/**
 * The sample, set up once.
 *
 * @param {Object}   props         Props.
 * @param {string[]} props.classes Option classes.
 */
function SampleFormsInner( { classes } ) {
	const ref = useRef();
	useEffect( () => {
		const node = ref.current;
		node.innerHTML = sampleForms();
		node.addEventListener( 'submit', ( e ) => e.preventDefault() );
		if ( classes.length ) {
			setup( node, ++samples );
		}
	}, [ classes ] );
	return (
		<div
			ref={ ref }
			className={ [ 'purplish-account-sample', ...classes ].join( ' ' ) }
			aria-label={ __( 'Sample log in and register forms', 'purplish' ) }
			role="group"
		/>
	);
}
