/**
 * Purplish panel on the Pagination block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function PaginationPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Page numbers', 'purplish' ) }
				help={ __(
					'Boxed: each number and Previous and Next in a small box, the current page filled. Underline: the current page underlined, the others lighter.',
					'purplish'
				) }
				value={ saved.look || '' }
				options={ [
					{ value: '', label: __( 'Theme default', 'purplish' ) },
					{ value: 'boxed', label: __( 'Boxed', 'purplish' ) },
					{
						value: 'underline',
						label: __( 'Underline', 'purplish' ),
					},
				] }
				onChange={ ( look ) =>
					setSaved( { ...saved, look: look || undefined } )
				}
			/>
		</PanelBody>
	);
}
