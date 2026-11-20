/**
 * The Size Chart in the editor. Its own chart is a table edited in place,
 * with the column settings in the sidebar. The product's chart shows a
 * sample, since a template has no product.
 */
import {
	BlockControls,
	BlockIcon,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	Placeholder,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import Inspector from './inspector';
import { Bar, Fit, Table } from './preview';
import { SAMPLES } from './table';

const SAMPLE_FIT = __(
	'Runs small. Between sizes, take the larger one.',
	'purplish'
);

/**
 * The table, edited in place.
 *
 * @param {Object}                                        props          Props.
 * @param {Object[]}                                      props.columns  Columns.
 * @param {string[][]}                                    props.rows     Rows.
 * @param {(index: number, name: string) => void}         props.setName  Sets a column's name.
 * @param {(row: number, col: number, v: string) => void} props.setValue Sets one value.
 * @param {(row: number) => void}                         props.remove   Removes a row.
 */
function EditableTable( { columns, rows, setName, setValue, remove } ) {
	return (
		<div className="purplish-size-chart__scroll">
			<table className="purplish-size-chart__table purplish-size-chart__edit">
				<thead>
					<tr>
						<th scope="col">{ __( 'Size', 'purplish' ) }</th>
						{ columns.map( ( column, i ) => (
							<th key={ i } scope="col">
								<input
									type="text"
									value={ column.name || '' }
									aria-label={ sprintf(
										/* translators: %d: column number. */
										__( 'Column %d name', 'purplish' ),
										i + 1
									) }
									onChange={ ( event ) =>
										setName( i, event.target.value )
									}
								/>
								{ 'label' !== column.kind && (
									<span className="purplish-size-chart__u">
										cm
									</span>
								) }
							</th>
						) ) }
						<th scope="col">
							<span className="screen-reader-text">
								{ __( 'Remove', 'purplish' ) }
							</span>
						</th>
					</tr>
				</thead>
				<tbody>
					{ rows.map( ( row, r ) => (
						<tr key={ r }>
							{ [ 0, ...columns.map( ( _, i ) => i + 1 ) ].map(
								( c ) => {
									const Cell = 0 === c ? 'th' : 'td';
									return (
										<Cell
											key={ c }
											scope={
												0 === c ? 'row' : undefined
											}
										>
											<input
												type="text"
												value={ row[ c ] ?? '' }
												aria-label={ sprintf(
													/* translators: 1: row number, 2: column name. */
													__(
														'Row %1$d, %2$s',
														'purplish'
													),
													r + 1,
													0 === c
														? __(
																'Size',
																'purplish'
															)
														: columns[ c - 1 ].name
												) }
												onChange={ ( event ) =>
													setValue(
														r,
														c,
														event.target.value
													)
												}
											/>
										</Cell>
									);
								}
							) }
							<td>
								<Button
									size="small"
									icon="no-alt"
									label={ sprintf(
										/* translators: %d: row number. */
										__( 'Remove row %d', 'purplish' ),
										r + 1
									) }
									onClick={ () => remove( r ) }
								/>
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
}

/**
 * The editor view.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { source, columns = [], rows = [], unit, fitnote } = attributes;
	const product = 'product' === source;
	const blockProps = useBlockProps( {
		'data-unit': 'in' === unit ? 'in' : 'cm',
	} );
	const width = columns.length + 1;

	const removeColumn = ( index ) =>
		setAttributes( {
			columns: columns.filter( ( _, i ) => i !== index ),
			rows: rows.map( ( row ) =>
				row.filter( ( _, i ) => i !== index + 1 )
			),
		} );
	const addRow = () =>
		setAttributes( { rows: [ ...rows, Array( width ).fill( '' ) ] } );
	const addColumn = () =>
		setAttributes( {
			columns: [
				...columns,
				{ name: '', kind: 'measure', line: 'auto', note: '' },
			],
			rows: rows.map( ( row ) => [ ...row, '' ] ),
		} );
	const setName = ( index, name ) =>
		setAttributes( {
			columns: columns.map( ( column, i ) =>
				i === index ? { ...column, name } : column
			),
		} );
	const setValue = ( r, c, value ) =>
		setAttributes( {
			rows: rows.map( ( row, i ) =>
				i === r
					? Array.from( { length: width }, ( _, j ) =>
							j === c ? value : ( row[ j ] ?? '' )
						)
					: row
			),
		} );
	const remove = ( r ) =>
		setAttributes( { rows: rows.filter( ( _, i ) => i !== r ) } );

	const inspector = (
		<Inspector
			attributes={ attributes }
			setAttributes={ setAttributes }
			removeColumn={ removeColumn }
		/>
	);

	if ( product ) {
		return (
			<div { ...blockProps }>
				{ inspector }
				{ false !== fitnote && <Fit note={ SAMPLE_FIT } /> }
				<Bar unit={ unit } showSwitch={ false !== attributes.switch } />
				<Table
					columns={ SAMPLES.tops.columns }
					rows={ SAMPLES.tops.rows }
				/>
			</div>
		);
	}

	if ( ! rows.length ) {
		return (
			<div { ...blockProps }>
				{ inspector }
				<Placeholder
					icon={ <BlockIcon icon="editor-table" /> }
					label={ __( 'Size Chart', 'purplish' ) }
					instructions={ __(
						'Start from a sample and change the sizes, or paste rows from a spreadsheet in the sidebar.',
						'purplish'
					) }
				>
					<Button
						variant="primary"
						onClick={ () => setAttributes( SAMPLES.tops ) }
					>
						{ __( 'Start with a tops sample', 'purplish' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ () => setAttributes( SAMPLES.shoes ) }
					>
						{ __( 'Start with a shoes sample', 'purplish' ) }
					</Button>
				</Placeholder>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ inspector }
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon="table-row-after"
						label={ __( 'Add row', 'purplish' ) }
						onClick={ addRow }
					/>
					<ToolbarButton
						icon="table-col-after"
						label={ __( 'Add column', 'purplish' ) }
						onClick={ addColumn }
					/>
				</ToolbarGroup>
			</BlockControls>
			<Bar unit={ unit } showSwitch={ false !== attributes.switch } />
			<EditableTable
				columns={ columns }
				rows={ rows }
				setName={ setName }
				setValue={ setValue }
				remove={ remove }
			/>
		</div>
	);
}
