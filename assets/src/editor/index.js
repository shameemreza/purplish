/**
 * Purplish panel on WooCommerce's Add to Cart + Options block: which parts
 * follow the picked color, and the photo transition. Saved as the block's
 * `purplish` attribute, so each template keeps its own settings.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const BLOCK = 'woocommerce/add-to-cart-with-options';

const DEFAULTS = {
	parts: [ 'button', 'chip', 'badge' ],
	transition: 'blur',
};

const PARTS = [
	{ key: 'button', label: __( 'Add to cart button', 'purplish' ) },
	{ key: 'chip', label: __( 'Selected option', 'purplish' ) },
	{ key: 'badge', label: __( 'Sale badge', 'purplish' ) },
	{ key: 'price', label: __( 'Sale price', 'purplish' ) },
	{ key: 'tint', label: __( 'Page background tint', 'purplish' ) },
];

addFilter(
	'blocks.registerBlockType',
	'purplish/attribute',
	( settings, name ) => {
		if ( BLOCK !== name ) {
			return settings;
		}
		return {
			...settings,
			attributes: {
				...settings.attributes,
				purplish: { type: 'object', default: {} },
			},
		};
	}
);

const withPurplishPanel = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( BLOCK !== props.name ) {
			return <BlockEdit { ...props } />;
		}

		const saved = props.attributes.purplish || {};
		const options = { ...DEFAULTS, ...saved };
		const save = ( changes ) =>
			props.setAttributes( {
				purplish: { ...options, ...changes },
			} );

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title={ __( 'Purplish', 'purplish' ) }>
						<p>
							{ __(
								'Parts of the product page that take the color of the variation a shopper picks.',
								'purplish'
							) }
						</p>
						{ PARTS.map( ( part ) => (
							<ToggleControl
								key={ part.key }
								__nextHasNoMarginBottom
								label={ part.label }
								checked={ options.parts.includes( part.key ) }
								onChange={ ( on ) =>
									save( {
										parts: on
											? [ ...options.parts, part.key ]
											: options.parts.filter(
													( key ) => key !== part.key
												),
									} )
								}
							/>
						) ) }
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Photo transition', 'purplish' ) }
							help={ __(
								'Plays when the photo changes with the color. Visitors who ask for reduced motion see no effect.',
								'purplish'
							) }
							value={ options.transition }
							options={ [
								{
									value: 'none',
									label: __( 'None', 'purplish' ),
								},
								{
									value: 'fade',
									label: __( 'Fade', 'purplish' ),
								},
								{
									value: 'blur',
									label: __( 'Blur', 'purplish' ),
								},
							] }
							onChange={ ( transition ) =>
								save( { transition } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			</>
		);
	},
	'withPurplishPanel'
);

addFilter( 'editor.BlockEdit', 'purplish/panel', withPurplishPanel );
