/**
 * Purplish panels on WooCommerce's blocks. Each block saves its options in
 * its own `purplish` attribute, so each template keeps its own settings.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';

import AddToCartPanel from './add-to-cart';
import GalleryPanel from './gallery';

const PANELS = {
	'woocommerce/add-to-cart-with-options': AddToCartPanel,
	'woocommerce/product-gallery': GalleryPanel,
};

addFilter(
	'blocks.registerBlockType',
	'purplish/attribute',
	( settings, name ) => {
		if ( ! PANELS[ name ] ) {
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
		const Panel = PANELS[ props.name ];
		if ( ! Panel ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<Panel
						saved={ props.attributes.purplish || {} }
						setSaved={ ( purplish ) =>
							props.setAttributes( { purplish } )
						}
					/>
				</InspectorControls>
			</>
		);
	},
	'withPurplishPanel'
);

addFilter( 'editor.BlockEdit', 'purplish/panel', withPurplishPanel );
