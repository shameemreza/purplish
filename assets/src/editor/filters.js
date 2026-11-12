/**
 * Purplish panel on the Product Filters block.
 */
import {
	Notice,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
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
	const bar = 'bar' === saved.layout;
	const always = 'always' === ( attributes && attributes.overlayMode );
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Layout', 'purplish' ) }
				help={
					bar
						? __(
								'The filters sit in one row and each opens as a dropdown under its label. Put the block above the products. Phones keep the overlay.',
								'purplish'
							)
						: __(
								'The filters stack in a column, for a sidebar.',
								'purplish'
							)
				}
				value={ bar ? 'bar' : '' }
				options={ [
					{ label: __( 'Column', 'purplish' ), value: '' },
					{ label: __( 'Bar', 'purplish' ), value: 'bar' },
				] }
				onChange={ ( value ) => {
					const next = { ...saved };
					if ( 'bar' === value ) {
						next.layout = 'bar';
					} else {
						delete next.layout;
					}
					setSaved( next );
				} }
			/>
			{ bar && always && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'The overlay is on for every screen, so the filters never sit in a row. Set the overlay to mobile only or off.',
						'purplish'
					) }
				</Notice>
			) }
			{ bar && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Open on hover', 'purplish' ) }
					help={ __(
						'A list opens when the mouse rests on its label and closes when the mouse leaves it. Clicks, the keyboard and touch screens work as before.',
						'purplish'
					) }
					checked={ true === saved.hover }
					onChange={ ( on ) => setSaved( { ...saved, hover: on } ) }
				/>
			) }
			{ bar && (
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Open style', 'purplish' ) }
					help={ __(
						'How a list moves as it opens. It closes with a quick fade.',
						'purplish'
					) }
					value={ saved.open || '' }
					options={ [
						{ label: __( 'Slide', 'purplish' ), value: '' },
						{ label: __( 'Grow', 'purplish' ), value: 'grow' },
						{ label: __( 'Fold', 'purplish' ), value: 'fold' },
						{
							label: __( 'Cascade', 'purplish' ),
							value: 'cascade',
						},
					] }
					onChange={ ( value ) => {
						const next = { ...saved };
						if ( value ) {
							next.open = value;
						} else {
							delete next.open;
						}
						setSaved( next );
					} }
				/>
			) }
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
				help={
					bar
						? __(
								'A bar has no sidebar to fold, so this is off while Layout is Bar.',
								'purplish'
							)
						: __(
								'A Hide filters button sits beside Sort by. The filters fold away and the products take the width. Needs the filters in a column beside the products. Phones keep the overlay.',
								'purplish'
							)
				}
				disabled={ bar }
				checked={ true === saved.hide && ! bar }
				onChange={ ( on ) => setSaved( { ...saved, hide: on } ) }
			/>
			{ true === saved.hide && ! bar && always && (
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
