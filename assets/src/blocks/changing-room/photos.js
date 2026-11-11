/**
 * The pair photos in the Changing Room's block panel: a grid with a top
 * in each row and a bottom in each column, one photo in each cell.
 */
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const IMAGE = [ 'image' ];

/**
 * The key of a pair's photo in the photos attribute.
 *
 * @param {number} top    Top product ID.
 * @param {number} bottom Bottom product ID.
 * @return {string} The key, like "12|34".
 */
export const pairKey = ( top, bottom ) => `${ top }|${ bottom }`;

/**
 * The grid.
 *
 * @param {Object}                                props          Props.
 * @param {Object[]}                              props.tops     Top Piece blocks with a product.
 * @param {Object[]}                              props.bottoms  Bottom Piece blocks with a product.
 * @param {Object}                                props.photos   The photos attribute.
 * @param {(block: Object) => string}             props.nameOf   A Piece block's product name.
 * @param {(key: string, media?: Object) => void} props.setPhoto Sets or, with no media, clears a photo.
 */
export default function Photos( { tops, bottoms, photos, nameOf, setPhoto } ) {
	return tops.length > 0 && bottoms.length > 0 ? (
		<table className="purplish-changing-room-photos">
			<thead>
				<tr>
					<td />
					{ bottoms.map( ( bottom ) => (
						<th key={ bottom.clientId } scope="col">
							{ nameOf( bottom ) }
						</th>
					) ) }
				</tr>
			</thead>
			<tbody>
				{ tops.map( ( top ) => (
					<tr key={ top.clientId }>
						<th scope="row">{ nameOf( top ) }</th>
						{ bottoms.map( ( bottom ) => {
							const key = pairKey(
								top.attributes.product,
								bottom.attributes.product
							);
							const set = photos[ key ];
							const pair = sprintf(
								/* translators: 1: top piece, 2: bottom piece. */
								__( '%1$s and %2$s', 'purplish' ),
								nameOf( top ),
								nameOf( bottom )
							);
							return (
								<td key={ bottom.clientId }>
									<MediaUploadCheck>
										<MediaUpload
											allowedTypes={ IMAGE }
											value={ set && set.id }
											onSelect={ ( media ) =>
												setPhoto( key, media )
											}
											render={ ( { open } ) => (
												<Button
													className="purplish-changing-room-photo-pick"
													onClick={ open }
													label={ sprintf(
														/* translators: %s: the pair, like "Linen jacket and Chinos". */
														__(
															'Photo of %s',
															'purplish'
														),
														pair
													) }
												>
													{ set ? (
														<img
															src={ set.url }
															alt=""
														/>
													) : (
														'+'
													) }
												</Button>
											) }
										/>
									</MediaUploadCheck>
									{ set && (
										<Button
											variant="link"
											isDestructive
											onClick={ () => setPhoto( key ) }
											label={ sprintf(
												/* translators: %s: the pair, like "Linen jacket and Chinos". */
												__(
													'Remove the photo of %s',
													'purplish'
												),
												pair
											) }
										>
											{ __( 'Remove', 'purplish' ) }
										</Button>
									) }
								</td>
							);
						} ) }
					</tr>
				) ) }
			</tbody>
		</table>
	) : (
		<p className="components-base-control__help">
			{ __(
				'Pick a product for at least one top and one bottom piece first.',
				'purplish'
			) }
		</p>
	);
}
