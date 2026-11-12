/**
 * Purplish panel on the Accordion block.
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The panel.
 *
 * @param {Object}                  props          Panel props.
 * @param {Object}                  props.saved    The block's saved `purplish` attribute.
 * @param {(saved: Object) => void} props.setSaved Saves a new `purplish` attribute.
 */
export default function AccordionPanel( { saved, setSaved } ) {
	const soft = true === saved.soft;
	const save = ( changes ) => setSaved( { ...saved, ...changes } );

	return (
		<PanelBody title={ __( 'Purplish', 'purplish' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Soft style', 'purplish' ) }
				help={ __(
					'Each item becomes a soft card, and the plus turns into an x that fills with the color when open.',
					'purplish'
				) }
				checked={ soft }
				onChange={ ( on ) => save( { soft: on } ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Heading line', 'purplish' ) }
				help={
					soft
						? __(
								'The soft style draws its own cards, so the line is off with it.',
								'purplish'
							)
						: __(
								'Drawn: a hairline under each item, with a line that draws across on hover and stays while the item is open.',
								'purplish'
							)
				}
				disabled={ soft }
				value={ saved.line || '' }
				options={ [
					{ value: '', label: __( 'Theme default', 'purplish' ) },
					{ value: 'draw', label: __( 'Drawn', 'purplish' ) },
				] }
				onChange={ ( line ) => save( { line: line || undefined } ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Icon', 'purplish' ) }
				help={
					soft
						? __(
								'The soft style has its own round button.',
								'purplish'
							)
						: __(
								'Chevron: a small arrow that turns over when the item opens.',
								'purplish'
							)
				}
				disabled={ soft }
				value={ saved.icon || '' }
				options={ [
					{ value: '', label: __( 'Plus', 'purplish' ) },
					{ value: 'chevron', label: __( 'Chevron', 'purplish' ) },
				] }
				onChange={ ( icon ) => save( { icon: icon || undefined } ) }
			/>
		</PanelBody>
	);
}
