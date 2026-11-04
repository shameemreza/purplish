/**
 * Purplish panels on WooCommerce's blocks. Each block saves its options in
 * its own `purplish` attribute, so each template keeps its own settings.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';

import AddToCartPanel from './add-to-cart';
import GalleryPanel from './gallery';
import CardsPanel from './cards';
import AccordionPanel from './accordion';
import GroupPanel from './group';
import ChipsPanel from './chips';
import BadgePanel from './badge';
import ThumbsPanel from './thumbs';

const PANELS = {
	'woocommerce/add-to-cart-with-options': AddToCartPanel,
	'woocommerce/product-gallery': GalleryPanel,
	'woocommerce/product-collection': CardsPanel,
	'core/accordion': AccordionPanel,
	'core/group': GroupPanel,
	'woocommerce/add-to-cart-with-options-variation-selector-attribute':
		ChipsPanel,
	'woocommerce/product-sale-badge': BadgePanel,
	'woocommerce/product-gallery-thumbnails': ThumbsPanel,
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
