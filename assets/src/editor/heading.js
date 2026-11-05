/**
 * Purplish panel on the Heading block.
 */
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const REVEALS = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'rise', label: __( 'Rise', 'purplish' ) },
	{ value: 'focus', label: __( 'Focus', 'purplish' ) },
];

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function HeadingPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Reveal on scroll', 'purplish' ) }
				help={ __(
					'Plays once as the heading scrolls into view: it rises into place, or comes into focus. Visitors who ask for reduced motion see the heading as it is.',
					'purplish'
				) }
				value={ saved.reveal || '' }
				options={ REVEALS }
				onChange={ ( reveal ) =>
					setSaved( { ...saved, reveal: reveal || undefined } )
				}
			/>
		</PanelBody>
	);
}
