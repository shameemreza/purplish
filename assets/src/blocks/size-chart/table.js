/**
 * Size Chart table helpers, shared by the editor and Jest. The server does
 * the same math in Purplish\SizeCharts, so both print the same inches.
 */
import { __ } from '@wordpress/i18n';

const NUMBER = '(\\d+(?:[.,]\\d+)?)';
const VALUE = new RegExp(
	`^\\s*${ NUMBER }\\s*(?:[-–—]\\s*${ NUMBER })?\\s*$`
);

/**
 * Column names for sizing systems, which stay labels even when every value
 * is a number.
 */
const SYSTEMS = /^(eu|us|uk|it|fr|de|jp|au|mx|br|cn|kr|int|size)\b/i;

/**
 * The tape lines a column can draw, each with the English words that pick
 * it on its own.
 */
export const LINES = {
	chest: /chest|bust/i,
	waist: /waist/i,
	hips: /hip|seat/i,
	sleeve: /sleeve|arm/i,
	inseam: /inseam|inside leg|leg/i,
};

/**
 * A number, or a range like 86-91. Commas or dots read as decimals.
 *
 * @param {string} value Typed value.
 * @return {{min: number, max: number}|null} The range, or null for text.
 */
export function parseValue( value ) {
	const match = VALUE.exec( String( value ?? '' ) );
	if ( ! match ) {
		return null;
	}
	const min = parseFloat( match[ 1 ].replace( ',', '.' ) );
	const max = match[ 2 ] ? parseFloat( match[ 2 ].replace( ',', '.' ) ) : min;
	return { min, max };
}

/**
 * Centimeters to inches, to the nearest half inch.
 *
 * @param {number} cm Centimeters.
 * @return {number} Inches.
 */
export function toInches( cm ) {
	return Math.round( ( cm / 2.54 ) * 2 ) / 2;
}

/**
 * A number with at most one decimal, without a trailing zero.
 *
 * @param {number} n Number.
 * @return {string} Formatted number.
 */
export function formatNumber( n ) {
	return String( Math.round( n * 10 ) / 10 );
}

/**
 * A measurement in both units.
 *
 * @param {string} value Typed value.
 * @return {{cm: string, in: string}|null} Both units, or null for text.
 */
export function cell( value ) {
	const range = parseValue( value );
	if ( ! range ) {
		return null;
	}
	const both = ( fn ) =>
		range.min === range.max
			? fn( range.min )
			: `${ fn( range.min ) }-${ fn( range.max ) }`;
	return {
		cm: both( formatNumber ),
		in: both( ( n ) => formatNumber( toInches( n ) ) ),
	};
}

/**
 * Splits one line of pasted text, keeping quoted values whole.
 *
 * @param {string} line      Line.
 * @param {string} separator Tab, semicolon or comma.
 * @return {string[]} Values.
 */
function split( line, separator ) {
	const values = [];
	let value = '';
	let quoted = false;
	for ( const char of line ) {
		if ( '"' === char ) {
			quoted = ! quoted;
		} else if ( char === separator && ! quoted ) {
			values.push( value.trim() );
			value = '';
		} else {
			value += char;
		}
	}
	values.push( value.trim() );
	return values;
}

/**
 * Rows copied from a spreadsheet. The first row names the columns; the
 * first column holds the sizes. A column reads as a measurement when every
 * value in it is a number or a range, unless it is named for a sizing
 * system like EU or US.
 *
 * @param {string} text Pasted text.
 * @return {{columns: Object[], rows: string[][]}} Columns and rows.
 */
export function parsePasted( text ) {
	const lines = String( text ?? '' )
		.split( /\r?\n/ )
		.filter( ( line ) => line.trim() );
	if ( ! lines.length ) {
		return { columns: [], rows: [] };
	}
	let separator = ',';
	if ( lines[ 0 ].includes( '\t' ) ) {
		separator = '\t';
	} else if ( lines[ 0 ].includes( ';' ) ) {
		separator = ';';
	}
	const [ head, ...body ] = lines.map( ( line ) => split( line, separator ) );
	const width = head.length;
	const rows = body.map( ( row ) =>
		Array.from( { length: width }, ( _, i ) => row[ i ] ?? '' )
	);
	const columns = head.slice( 1 ).map( ( name, i ) => {
		const values = rows.map( ( row ) => row[ i + 1 ] ).filter( Boolean );
		const measure =
			! SYSTEMS.test( name ) &&
			values.length > 0 &&
			values.every( ( value ) => null !== parseValue( value ) );
		return {
			name,
			kind: measure ? 'measure' : 'label',
			line: 'auto',
			note: '',
		};
	} );
	return { columns, rows };
}

/**
 * The tape line a column draws: its pick, or a guess from its name.
 *
 * @param {Object} column Column.
 * @return {string} A key of LINES, or 'none'.
 */
export function lineFor( column ) {
	if ( 'measure' !== ( column.kind ?? 'measure' ) ) {
		return 'none';
	}
	const line = column.line || 'auto';
	if ( 'auto' !== line ) {
		return LINES[ line ] ? line : 'none';
	}
	const found = Object.keys( LINES ).find( ( key ) =>
		LINES[ key ].test( column.name ?? '' )
	);
	return found || 'none';
}

/**
 * The figure a chart shows. Auto shows the bottom for an inseam, the top
 * for any other line, and none when no column draws a line.
 *
 * @param {string}   figure  Saved figure.
 * @param {Object[]} columns Columns.
 * @return {string} 'top', 'bottom' or 'none'.
 */
export function figureFor( figure, columns ) {
	if ( 'auto' !== ( figure || 'auto' ) ) {
		return figure;
	}
	const lines = ( columns || [] ).map( lineFor );
	if ( lines.includes( 'inseam' ) ) {
		return 'bottom';
	}
	return lines.some( ( line ) => 'none' !== line ) ? 'top' : 'none';
}

/**
 * Sample charts for the placeholder and the product page preview. The tops
 * sample is the server's starter chart (SizeCharts::sample).
 */
export const SAMPLES = {
	tops: {
		columns: [
			{
				name: __( 'Chest', 'purplish' ),
				kind: 'measure',
				line: 'auto',
				note: __(
					'Around the fullest part of your chest, under your arms, with the tape level.',
					'purplish'
				),
			},
			{
				name: __( 'Waist', 'purplish' ),
				kind: 'measure',
				line: 'auto',
				note: __(
					'Around your natural waist, the narrowest part above your hips.',
					'purplish'
				),
			},
			{
				name: __( 'Hips', 'purplish' ),
				kind: 'measure',
				line: 'auto',
				note: __(
					'Around the fullest part of your hips, with your feet together.',
					'purplish'
				),
			},
		],
		rows: [
			[ 'XS', '80-85', '64-69', '86-91' ],
			[ 'S', '86-91', '70-75', '92-97' ],
			[ 'M', '92-97', '76-81', '98-103' ],
			[ 'L', '98-103', '82-87', '104-109' ],
			[ 'XL', '104-110', '88-94', '110-116' ],
		],
	},
	shoes: {
		columns: [
			{ name: 'US', kind: 'label', line: 'auto', note: '' },
			{ name: 'UK', kind: 'label', line: 'auto', note: '' },
			{
				name: __( 'Foot length', 'purplish' ),
				kind: 'measure',
				line: 'none',
				note: __(
					'Stand on a sheet of paper, mark your heel and your longest toe, and measure between the marks.',
					'purplish'
				),
			},
		],
		rows: [
			[ '38', '6', '5', '24' ],
			[ '39', '7', '6', '24.7' ],
			[ '40', '7.5', '6.5', '25.4' ],
			[ '41', '8.5', '7.5', '26' ],
			[ '42', '9', '8', '26.7' ],
			[ '43', '10', '9', '27.3' ],
			[ '44', '11', '10', '28' ],
		],
	},
};
