/**
 * Purplish panel on the Product Price block, and its editor view with the
 * instalment line under the price on cards that show one.
 */
import { PanelBody, ToggleControl, ExternalLink } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function PricePanel( { saved, setSaved } ) {
	const settings = window.purplishInstalments?.settings;
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Instalment line', 'purplish' ) }
				help={
					<>
						{ __(
							'A line under the price with the monthly amount, from your price bands.',
							'purplish'
						) }{ ' ' }
						{ settings && (
							<ExternalLink href={ settings }>
								{ __( 'Instalment settings', 'purplish' ) }
							</ExternalLink>
						) }
					</>
				}
				checked={ true === saved.instalments }
				onChange={ ( instalments ) =>
					setSaved( {
						...saved,
						instalments: instalments || undefined,
					} )
				}
			/>
		</PanelBody>
	);
}

/**
 * The block in the editor with the line the store prints for the card's
 * product, from the `purplish_instalments` field. Outside a card, or for
 * a product that shows no line, it's WooCommerce's own view.
 *
 * @param {Object}                        props           Block edit props.
 * @param {import("react").ComponentType} props.BlockEdit WooCommerce's edit view.
 */
export function PriceView( { BlockEdit, ...props } ) {
	const on = true === ( props.attributes.purplish || {} ).instalments;
	const postId = props.context && props.context.postId;

	const line = useSelect(
		( select ) => {
			if ( ! on || ! postId ) {
				return '';
			}
			const record = select( 'core' ).getEntityRecord(
				'postType',
				'product',
				postId,
				{ _fields: 'id,purplish_instalments' }
			);
			return ( record && record.purplish_instalments ) || '';
		},
		[ on, postId ]
	);

	return (
		<>
			<BlockEdit { ...props } />
			{ line && (
				<div
					className="purplish-instalments-preview"
					// The server's own markup, escaped where it's built.
					dangerouslySetInnerHTML={ { __html: line } }
				/>
			) }
		</>
	);
}
