/**
 * Purplish panels on the Order confirmation page: the thank-you moment on
 * the Order Status block and the receipt card on the Order Summary block.
 */
import {
	BaseControl,
	ColorPalette,
	PanelBody,
	ToggleControl,
} from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

/**
 * The Order Status panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export function OrderStatusPanel( { saved, setSaved } ) {
	const [ palette ] = useSettings( 'color.palette' );
	const colorId = useInstanceId( OrderStatusPanel, 'purplish-effect-color' );
	const save = ( changes ) => setSaved( { ...saved, ...changes } );

	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Thank-you moment', 'purplish' ) }
				help={ __(
					'The heading rises in and one burst plays behind it when the page opens. It plays once and never loops. Visitors who ask for reduced motion see the plain heading.',
					'purplish'
				) }
				checked={ true === saved.moment }
				onChange={ ( moment ) => save( { moment } ) }
			/>
			<BaseControl
				__nextHasNoMarginBottom
				id={ colorId }
				label={ __( 'Effect color', 'purplish' ) }
				help={ __(
					"Used by the burst. Without one, the burst takes the theme's text color.",
					'purplish'
				) }
			>
				<ColorPalette
					colors={ palette || [] }
					value={ saved.color }
					onChange={ ( color ) => save( { color } ) }
					clearable
				/>
			</BaseControl>
		</PanelBody>
	);
}

/**
 * The Order Summary panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export function OrderSummaryPanel( { saved, setSaved } ) {
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Receipt card', 'purplish' ) }
				help={ __(
					'Shows the order number, date, total, email and payment as a printed receipt, one line each, with a torn edge.',
					'purplish'
				) }
				checked={ true === saved.receipt }
				onChange={ ( receipt ) => setSaved( { ...saved, receipt } ) }
			/>
		</PanelBody>
	);
}
