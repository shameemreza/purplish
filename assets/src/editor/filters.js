/**
 * Purplish panel on the Product Filters block.
 */
import { Notice, PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The panel.
 *
 * @param {Object}                  props            Panel props.
 * @param {Object}                  props.saved      The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved   Saves a new `purplish` attribute.
 * @param {Object}                  props.attributes The block's attributes.
 */
export default function FiltersPanel( { saved, setSaved, attributes } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Matching chips', 'purplish' ) }
				help={ __(
					'Active filters look like a selected chip, and chips fade into their selected color.',
					'purplish'
				) }
				checked={ true === saved.chips }
				onChange={ ( on ) => setSaved( { ...saved, chips: on } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Sidebar can hide', 'purplish' ) }
				help={ __(
					'A Hide filters button sits beside Sort by. The filters fold away and the products take the width. Needs the filters in a column beside the products. Phones keep the overlay.',
					'purplish'
				) }
				checked={ true === saved.hide }
				onChange={ ( on ) => setSaved( { ...saved, hide: on } ) }
			/>
			{ true === saved.hide &&
				'always' === ( attributes && attributes.overlayMode ) && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'The overlay is on for every screen, so there is no sidebar to hide. Set the overlay to mobile only or off.',
							'purplish'
						) }
					</Notice>
				) }
		</PanelBody>
	);
}
