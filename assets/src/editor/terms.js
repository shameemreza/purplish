/**
 * Purplish panel on the Terms Query block.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { ENTRANCES } from './group';

/**
 * A select that saves one option, or drops it for the empty value.
 *
 * @param {Object}                  props          Control props.
 * @param {string}                  props.name     The option's key.
 * @param {string}                  props.label    Label.
 * @param {string}                  props.help     Help text.
 * @param {Object[]}                props.options  Choices.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
function Choice( { name, label, help, options, saved, setSaved } ) {
	const known = options.some( ( option ) => option.value === saved[ name ] );
	return (
		<SelectControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			value={ known ? saved[ name ] : '' }
			options={ options }
			onChange={ ( value ) =>
				setSaved( { ...saved, [ name ]: value || undefined } )
			}
		/>
	);
}

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function TermsPanel( { saved, setSaved } ) {
	const look = [ 'tabs', 'photos' ].includes( saved.look ) ? saved.look : '';
	const props = { saved, setSaved };
	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<Choice
				{ ...props }
				name="look"
				label={ __( 'Look', 'purplish' ) }
				help={ __(
					'Tabs: the terms in a row that marks the current one. Category photos: add a Featured Category block to the term template for a photo above each name.',
					'purplish'
				) }
				options={ [
					{ value: '', label: __( 'Theme default', 'purplish' ) },
					{ value: 'tabs', label: __( 'Tabs', 'purplish' ) },
					{
						value: 'photos',
						label: __( 'Category photos', 'purplish' ),
					},
				] }
			/>
			{ 'tabs' === look && (
				<Choice
					{ ...props }
					name="tab"
					label={ __( 'Tab style', 'purplish' ) }
					help={ __(
						'Line draws in under a tab on hover and stays under the current one. Pill fills the current tab. Dot puts a small dot under it.',
						'purplish'
					) }
					options={ [
						{ value: '', label: __( 'Line', 'purplish' ) },
						{ value: 'pill', label: __( 'Pill', 'purplish' ) },
						{ value: 'dot', label: __( 'Dot', 'purplish' ) },
					] }
				/>
			) }
			{ 'photos' === look && (
				<>
					<Choice
						{ ...props }
						name="shape"
						label={ __( 'Shape', 'purplish' ) }
						help={ __(
							'The current term gets a ring in the effect color, in any shape.',
							'purplish'
						) }
						options={ [
							{ value: '', label: __( 'Circle', 'purplish' ) },
							{
								value: 'rounded',
								label: __( 'Rounded square', 'purplish' ),
							},
							{
								value: 'square',
								label: __( 'Square', 'purplish' ),
							},
							{ value: 'arch', label: __( 'Arch', 'purplish' ) },
						] }
					/>
					<Choice
						{ ...props }
						name="hover"
						label={ __( 'Hover', 'purplish' ) }
						help={ __(
							'Zoom grows the photo inside its shape. Ring closes in around it. Morph turns the shape into a circle, or a circle into a rounded square. Turn grows and turns the photo. The Featured Category’s own hover looks work here too.',
							'purplish'
						) }
						options={ [
							{ value: '', label: __( 'Zoom', 'purplish' ) },
							{ value: 'ring', label: __( 'Ring', 'purplish' ) },
							{
								value: 'morph',
								label: __( 'Morph', 'purplish' ),
							},
							{ value: 'turn', label: __( 'Turn', 'purplish' ) },
							{ value: 'still', label: __( 'None', 'purplish' ) },
						] }
					/>
				</>
			) }
			<Choice
				{ ...props }
				name="entrance"
				label={ __( 'Entrance on scroll', 'purplish' ) }
				help={ __(
					'The terms come in one after another as the list scrolls into view. Visitors who ask for reduced motion see it as it is.',
					'purplish'
				) }
				options={ ENTRANCES }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Mark the current term', 'purplish' ) }
				help={
					look
						? __(
								'Tabs and Category photos always mark it.',
								'purplish'
							)
						: __(
								'On a category or tag page, that term is shown as the current one, and its parent when only parents are listed.',
								'purplish'
							)
				}
				disabled={ !! look }
				checked={ !! look || true === saved.current }
				onChange={ ( on ) =>
					setSaved( { ...saved, current: on || undefined } )
				}
			/>
		</PanelBody>
	);
}
