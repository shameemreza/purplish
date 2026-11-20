/**
 * The Delivery Line in the editor: the server's own line for the product
 * being edited, so the date is today's real date, and the block panel.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	ExternalLink,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

const SETTINGS =
	'admin.php?page=wc-settings&tab=shipping&section=purplish_delivery';

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {Object}                    props.context       Post context.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const { date, range, countdown, returns, link, pickup, format } =
		attributes;
	const blockProps = useBlockProps();
	const toggle = ( key, label, help ) => (
		<ToggleControl
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			checked={ !! attributes[ key ] }
			onChange={ ( value ) => setAttributes( { [ key ]: value } ) }
		/>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Lines', 'purplish' ) }>
					{ toggle( 'date', __( 'Delivery date', 'purplish' ) ) }
					{ date && (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Date', 'purplish' ) }
							value={ range }
							options={ [
								{
									value: 'latest',
									label: __(
										'Latest date (arrives by)',
										'purplish'
									),
								},
								{
									value: 'range',
									label: __( 'Date range', 'purplish' ),
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { range: value } )
							}
						/>
					) }
					{ date &&
						toggle(
							'countdown',
							__( 'Time left before the cut-off', 'purplish' ),
							countdown
								? __(
										'Shows “Order within …” while today’s cut-off still counts.',
										'purplish'
									)
								: undefined
						) }
					{ toggle( 'returns', __( 'Returns line', 'purplish' ) ) }
					{ returns &&
						toggle(
							'link',
							__( 'Link to the returns policy', 'purplish' ),
							link
								? __(
										'Links to WooCommerce’s Refund and Returns page when it’s published.',
										'purplish'
									)
								: undefined
						) }
					{ toggle(
						'pickup',
						__( 'Pickup line', 'purplish' ),
						pickup
							? __(
									'Shows when Local pickup is on in WooCommerce, with a location.',
									'purplish'
								)
							: undefined
					) }
				</PanelBody>
				<PanelBody title={ __( 'Look', 'purplish' ) }>
					{ toggle( 'icons', __( 'Icons', 'purplish' ) ) }
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Date format', 'purplish' ) }
						value={ format }
						options={ [
							{
								value: 'short',
								label: __(
									'Short, with the weekday',
									'purplish'
								),
							},
							{
								value: 'site',
								label: __(
									'The site’s date format',
									'purplish'
								),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { format: value } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Delivery dates', 'purplish' ) }>
					<p>
						{ __(
							'Processing days, the cut-off time, working days, holidays, delivery days and the returns window are set once for the whole store.',
							'purplish'
						) }
					</p>
					<ExternalLink href={ SETTINGS }>
						{ __( 'Delivery dates settings', 'purplish' ) }
					</ExternalLink>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block={ metadata.name }
					attributes={ attributes }
					skipBlockSupportAttributes
					urlQueryArgs={
						context.postId && 'product' === context.postType
							? { post_id: context.postId }
							: {}
					}
					EmptyResponsePlaceholder={ () => (
						<p className="purplish-delivery-line-empty">
							{ __(
								'Nothing to show: every line is off, or this product doesn’t ship.',
								'purplish'
							) }
						</p>
					) }
				/>
			</div>
		</>
	);
}
