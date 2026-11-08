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
import AccordionGroupPanel from './accordion-group';
import DetailsPanel from './details';
import ReviewsPanel from './reviews';
import SpecsPanel from './specs';
import FiltersPanel from './filters';
import MiniCartPanel from './mini-cart';
import ImagePanel from './image';
import ButtonPanel from './button';
import HeadingPanel from './heading';
import ColumnsPanel from './columns';
import CoverPanel from './cover';
import BuyButtonPanel from './buy-button';
import DrawerPanel from './drawer';
import CartPagePanel from './cart-page';
import CheckoutPagePanel from './checkout-page';
import { OrderStatusPanel, OrderSummaryPanel } from './order-confirmation';
import './preview';

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
	'woocommerce/accordion-group': AccordionGroupPanel,
	'core/details': DetailsPanel,
	'woocommerce/product-reviews': ReviewsPanel,
	'woocommerce/product-specifications': SpecsPanel,
	'woocommerce/product-filters': FiltersPanel,
	'woocommerce/mini-cart': MiniCartPanel,
	'core/image': ImagePanel,
	'core/button': ButtonPanel,
	'core/heading': HeadingPanel,
	'core/columns': ColumnsPanel,
	'core/cover': CoverPanel,
	'woocommerce/order-confirmation-status': OrderStatusPanel,
	'woocommerce/order-confirmation-summary': OrderSummaryPanel,
	'woocommerce/proceed-to-checkout-block': BuyButtonPanel,
	'woocommerce/checkout-actions-block': BuyButtonPanel,
	'woocommerce/mini-cart-contents': DrawerPanel,
	'woocommerce/mini-cart-checkout-button-block': BuyButtonPanel,
	'woocommerce/cart': CartPagePanel,
	'woocommerce/checkout': CheckoutPagePanel,
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
