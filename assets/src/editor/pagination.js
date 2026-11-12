/**
 * Purplish panel on the Pagination block.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { ButtonLooks } from './button';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 * @param {Object}                  props.context  The block's context.
 */
export default function PaginationPanel( { saved, setSaved, context } ) {
	const products = !! context?.query?.isProductCollectionBlock;
	const more = products && [ 'button', 'fade' ].includes( saved.more );
	let help = __(
		'Replaces the page numbers with a button that adds the next page under the products. Over a fade, the last row fades out under it.',
		'purplish'
	);
	if ( ! products ) {
		help = __( 'Needs a Product Collection block.', 'purplish' );
	}
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
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Load more', 'purplish' ) }
				help={ help }
				disabled={ ! products }
				value={ more ? saved.more : '' }
				options={ [
					{ value: '', label: __( 'Off', 'purplish' ) },
					{ value: 'button', label: __( 'Button', 'purplish' ) },
					{
						value: 'fade',
						label: __( 'Button over a fade', 'purplish' ),
					},
				] }
				onChange={ ( value ) =>
					setSaved( { ...saved, more: value || undefined } )
				}
			/>
			{ more && (
				<>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Outline button', 'purplish' ) }
						help={ __(
							'The theme’s outline button style, as on the Button block.',
							'purplish'
						) }
						checked={ true === saved.outline }
						onChange={ ( outline ) =>
							setSaved( {
								...saved,
								outline: outline || undefined,
							} )
						}
					/>
					<ButtonLooks
						looks={ saved.button || {} }
						change={ ( changes ) =>
							setSaved( {
								...saved,
								button: {
									...( saved.button || {} ),
									...changes,
								},
							} )
						}
					/>
				</>
			) }
		</PanelBody>
	);
}
