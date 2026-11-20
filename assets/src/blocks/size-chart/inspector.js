/**
 * The Size Chart's sidebar: where the chart comes from, its columns, a
 * box to paste rows from a spreadsheet, and how it shows.
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { parsePasted } from './table';

const SOURCES = [
	{ value: 'own', label: __( 'This chart', 'purplish' ) },
	{ value: 'product', label: __( "This product's chart", 'purplish' ) },
];

const KINDS = [
	{ value: 'measure', label: __( 'Measurement (cm)', 'purplish' ) },
	{ value: 'label', label: __( 'Label, like EU or US sizes', 'purplish' ) },
];

const LINE_CHOICES = [
	{ value: 'auto', label: __( 'Auto, from the name', 'purplish' ) },
	{ value: 'none', label: __( 'None', 'purplish' ) },
	{ value: 'chest', label: __( 'Chest', 'purplish' ) },
	{ value: 'waist', label: __( 'Waist', 'purplish' ) },
	{ value: 'hips', label: __( 'Hips', 'purplish' ) },
	{ value: 'sleeve', label: __( 'Sleeve', 'purplish' ) },
	{ value: 'inseam', label: __( 'Inseam', 'purplish' ) },
];

const UNITS = [
	{ value: 'cm', label: __( 'cm', 'purplish' ) },
	{ value: 'in', label: __( 'in', 'purplish' ) },
];

const FIGURES = [
	{ value: 'auto', label: __( 'Auto, from the lines', 'purplish' ) },
	{ value: 'none', label: __( 'None', 'purplish' ) },
	{ value: 'top', label: __( 'Top', 'purplish' ) },
	{ value: 'bottom', label: __( 'Bottom', 'purplish' ) },
];

/**
 * One column's settings.
 *
 * @param {Object}                    props          Props.
 * @param {Object}                    props.column   Column.
 * @param {number}                    props.index    Column index.
 * @param {(changes: Object) => void} props.onChange Changes the column.
 * @param {() => void}                props.onRemove Removes the column.
 */
function Column( { column, index, onChange, onRemove } ) {
	const measure = 'label' !== column.kind;
	return (
		<PanelBody
			title={
				column.name ||
				sprintf(
					/* translators: %d: column number. */
					__( 'Column %d', 'purplish' ),
					index + 1
				)
			}
			initialOpen={ false }
		>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Name', 'purplish' ) }
				value={ column.name || '' }
				onChange={ ( name ) => onChange( { name } ) }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Kind', 'purplish' ) }
				value={ measure ? 'measure' : 'label' }
				options={ KINDS }
				onChange={ ( kind ) => onChange( { kind } ) }
			/>
			{ measure && (
				<>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Line on the figure', 'purplish' ) }
						value={ column.line || 'auto' }
						options={ LINE_CHOICES }
						onChange={ ( line ) => onChange( { line } ) }
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'How to measure', 'purplish' ) }
						value={ column.note || '' }
						onChange={ ( note ) => onChange( { note } ) }
					/>
				</>
			) }
			<Button variant="secondary" isDestructive onClick={ onRemove }>
				{ __( 'Remove column', 'purplish' ) }
			</Button>
		</PanelBody>
	);
}

/**
 * The sidebar.
 *
 * @param {Object}                    props               Props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 * @param {(index: number) => void}   props.removeColumn  Removes a column and its values.
 */
export default function Inspector( {
	attributes,
	setAttributes,
	removeColumn,
} ) {
	const { source, columns, unit, figure, fitnote, pick } = attributes;
	const product = 'product' === source;
	const [ pasted, setPasted ] = useState( '' );

	const changeColumn = ( index, changes ) =>
		setAttributes( {
			columns: columns.map( ( column, i ) =>
				i === index ? { ...column, ...changes } : column
			),
		} );

	const fill = () => {
		const table = parsePasted( pasted );
		if ( table.rows.length ) {
			setAttributes( table );
			setPasted( '' );
		}
	};

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Chart', 'purplish' ) }>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Show', 'purplish' ) }
					help={
						product
							? __(
									'Shows the chart picked for each product or its category, with its fit note. Put it in the Single Product template.',
									'purplish'
								)
							: __(
									'Holds its own sizes. Save it as a synced pattern to pick it for products and categories.',
									'purplish'
								)
					}
					value={ product ? 'product' : 'own' }
					options={ SOURCES }
					onChange={ ( value ) => setAttributes( { source: value } ) }
				/>
			</PanelBody>
			{ ! product && (
				<>
					<PanelBody title={ __( 'Columns', 'purplish' ) }>
						{ columns.map( ( column, i ) => (
							<Column
								key={ i }
								column={ column }
								index={ i }
								onChange={ ( changes ) =>
									changeColumn( i, changes )
								}
								onRemove={ () => removeColumn( i ) }
							/>
						) ) }
					</PanelBody>
					<PanelBody
						title={ __( 'Paste from a spreadsheet', 'purplish' ) }
						initialOpen={ false }
					>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Rows', 'purplish' ) }
							help={ __(
								'Copy the cells from a spreadsheet, with the column names in the first row and the sizes in the first column. This replaces the table.',
								'purplish'
							) }
							value={ pasted }
							onChange={ setPasted }
							rows={ 6 }
						/>
						<Button
							variant="secondary"
							onClick={ fill }
							disabled={ ! pasted.trim() }
						>
							{ __( 'Fill the table', 'purplish' ) }
						</Button>
					</PanelBody>
				</>
			) }
			<PanelBody title={ __( 'Display', 'purplish' ) }>
				{ product && (
					<p className="purplish-size-chart__help">
						{ __(
							"Settings changed here win over the chart's own.",
							'purplish'
						) }
					</p>
				) }
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Show first', 'purplish' ) }
					value={ unit }
					options={ UNITS }
					onChange={ ( value ) => setAttributes( { unit: value } ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show the cm/in switch', 'purplish' ) }
					checked={ false !== attributes.switch }
					onChange={ ( on ) => setAttributes( { switch: on } ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Pick a size from the chart', 'purplish' ) }
					help={ __(
						'On a product, each size is a button that picks the matching size option.',
						'purplish'
					) }
					checked={ false !== pick }
					onChange={ ( on ) => setAttributes( { pick: on } ) }
				/>
				{ ! product && (
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'How to measure figure', 'purplish' ) }
						value={ figure || 'auto' }
						options={ FIGURES }
						onChange={ ( value ) =>
							setAttributes( { figure: value } )
						}
					/>
				) }
				{ product && (
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show the fit note', 'purplish' ) }
						checked={ false !== fitnote }
						onChange={ ( on ) => setAttributes( { fitnote: on } ) }
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
}
