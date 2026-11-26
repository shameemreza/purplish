/**
 * Trade-in panel on Add to Cart + Options: whether the form asks about a
 * trade-in, and whether it shows the price after the estimate. The device
 * table, the notes and the terms box live in the store settings.
 */
import { ExternalLink, PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function TradeInPanel( { saved, setSaved } ) {
	const settings = window.purplishTradeIn?.settings;
	const on = true === saved.tradeIn;

	return (
		<PanelBody title={ __( 'Trade-in', 'purplish' ) } initialOpen={ on }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Ask about a trade-in', 'purplish' ) }
				help={
					<>
						{ __(
							'Shoppers say whether they’re trading in an old device, pick it, and see your estimate. It shows on products that take trade-in, once the device table has a device.',
							'purplish'
						) }{ ' ' }
						{ settings && (
							<ExternalLink href={ settings }>
								{ __( 'Trade-in settings', 'purplish' ) }
							</ExternalLink>
						) }
					</>
				}
				checked={ on }
				onChange={ ( tradeIn ) =>
					setSaved( {
						...saved,
						tradeIn: tradeIn || undefined,
						tradeInNet: tradeIn ? saved.tradeInNet : undefined,
					} )
				}
			/>
			{ on && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show price after trade-in', 'purplish' ) }
					help={ __(
						'Adds the product price minus the highest estimate under the estimate. The cart price doesn’t change.',
						'purplish'
					) }
					checked={ true === saved.tradeInNet }
					onChange={ ( tradeInNet ) =>
						setSaved( {
							...saved,
							tradeInNet: tradeInNet || undefined,
						} )
					}
				/>
			) }
		</PanelBody>
	);
}
