/**
 * A chart drawn the way the server prints it, for places with no product:
 * the Single Product template and the inserter. Same classes, so the
 * block's styles apply.
 */
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

import { cell } from './table';

/**
 * The fit note.
 *
 * @param {Object} props      Props.
 * @param {string} props.note Fit note.
 */
export function Fit( { note } ) {
	return (
		<p className="purplish-size-chart__fit">
			<span className="purplish-size-chart__fit-label">
				{ __( 'Fit', 'purplish' ) }
			</span>{ ' ' }
			{ note }
		</p>
	);
}

/**
 * The cm/in switch, shown but not used in the editor.
 *
 * @param {Object} props      Props.
 * @param {string} props.unit Unit shown first.
 */
export function Units( { unit } ) {
	const id = useInstanceId( Units, 'purplish-size-chart-unit' );
	return (
		<fieldset className="purplish-size-chart__units" disabled>
			<legend className="screen-reader-text">
				{ __( 'Units', 'purplish' ) }
			</legend>
			{ [ 'cm', 'in' ].map( ( value ) => (
				<label
					key={ value }
					className="purplish-size-chart__unit"
					htmlFor={ id + '-' + value }
				>
					<input
						id={ id + '-' + value }
						type="radio"
						value={ value }
						checked={ value === unit }
						readOnly
					/>
					<span>{ value }</span>
				</label>
			) ) }
		</fieldset>
	);
}

/**
 * The table with both units in each measurement cell.
 *
 * @param {Object}     props         Props.
 * @param {Object[]}   props.columns Columns.
 * @param {string[][]} props.rows    Rows.
 */
export function Table( { columns, rows } ) {
	const units = (
		<span className="purplish-size-chart__u">
			<span className="purplish-size-chart__cm">cm</span>
			<span className="purplish-size-chart__in">in</span>
		</span>
	);
	return (
		<div className="purplish-size-chart__scroll">
			<table className="purplish-size-chart__table">
				<thead>
					<tr>
						<th scope="col">{ __( 'Size', 'purplish' ) }</th>
						{ columns.map( ( column, i ) => (
							<th
								key={ i }
								scope="col"
								data-m={ 'c' + ( i + 1 ) }
							>
								{ column.name }
								{ 'measure' === column.kind && units }
							</th>
						) ) }
					</tr>
				</thead>
				<tbody>
					{ rows.map( ( row, r ) => (
						<tr key={ r } data-size={ row[ 0 ] }>
							<th scope="row">{ row[ 0 ] }</th>
							{ columns.map( ( column, i ) => {
								const both =
									'measure' === column.kind
										? cell( row[ i + 1 ] )
										: null;
								return (
									<td key={ i } data-m={ 'c' + ( i + 1 ) }>
										{ both ? (
											<>
												<span className="purplish-size-chart__cm">
													{ both.cm }
												</span>
												<span className="purplish-size-chart__in">
													{ both.in }
												</span>
											</>
										) : (
											row[ i + 1 ]
										) }
									</td>
								);
							} ) }
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
}

/**
 * The caption bar with the switch.
 *
 * @param {Object}  props            Props.
 * @param {string}  props.unit       Unit shown first.
 * @param {boolean} props.showSwitch Whether the switch shows.
 */
export function Bar( { unit, showSwitch } ) {
	return (
		<div className="purplish-size-chart__bar">
			<p className="purplish-size-chart__caption">
				{ __( 'Body measurements', 'purplish' ) }
			</p>
			{ showSwitch && <Units unit={ unit } /> }
		</div>
	);
}
