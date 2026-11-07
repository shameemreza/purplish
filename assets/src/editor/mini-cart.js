/**
 * Purplish panel on the Mini-Cart block.
 */
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function MiniCartPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Count pop', 'purplish' ) }
				help={ __(
					'The item count pops and the bag nudges when something is added to the cart.',
					'purplish'
				) }
				checked={ true === saved.pop }
				onChange={ ( on ) => setSaved( { ...saved, pop: on } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Fly to cart', 'purplish' ) }
				help={ __(
					'The product photo flies into the cart icon when a shopper adds it, from product cards and the product page. Not used when the drawer opens on add, since the drawer covers the icon. Visitors who ask for reduced motion see no flight.',
					'purplish'
				) }
				checked={ true === saved.fly }
				onChange={ ( on ) => setSaved( { ...saved, fly: on } ) }
			/>
		</PanelBody>
	);
}
