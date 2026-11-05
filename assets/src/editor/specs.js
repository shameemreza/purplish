/**
 * Purplish panel on the Product Specifications block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const STYLES = [
	{ value: '', label: __( 'WooCommerce default', 'purplish' ) },
	{ value: 'striped', label: __( 'Striped', 'purplish' ) },
	{ value: 'cards', label: __( 'Cards', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function SpecsPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Table style', 'purplish' ) }
				help={ __(
					'Striped shades every other row. Cards gives each row its own soft card.',
					'purplish'
				) }
				value={ saved.style || '' }
				options={ STYLES }
				onChange={ ( style ) =>
					setSaved( { ...saved, style: style || undefined } )
				}
			/>
		</PanelBody>
	);
}
