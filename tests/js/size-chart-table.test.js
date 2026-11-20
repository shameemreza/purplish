/**
 * Size Chart table helpers: reading values, centimeters to inches, pasted
 * rows, and which tape line a column draws. The PHP side has the same cases
 * in SizeChartsTest, so the editor and the page agree.
 */
import { describe, expect, it } from '@jest/globals';
import {
	cell,
	figureFor,
	lineFor,
	parsePasted,
	parseValue,
	SAMPLES,
	toInches,
} from '../../assets/src/blocks/size-chart/table';

describe( 'parseValue', () => {
	it( 'reads a range', () => {
		expect( parseValue( '86-91' ) ).toEqual( { min: 86, max: 91 } );
	} );

	it( 'reads a comma as a decimal point', () => {
		expect( parseValue( '64,5' ) ).toEqual( { min: 64.5, max: 64.5 } );
	} );

	it( 'reads a dash with spaces around it', () => {
		expect( parseValue( '86 – 91' ) ).toEqual( { min: 86, max: 91 } );
	} );

	it( 'gives null for text', () => {
		expect( parseValue( 'XL' ) ).toBeNull();
		expect( parseValue( '' ) ).toBeNull();
	} );
} );

describe( 'toInches', () => {
	it( 'rounds to the nearest half inch', () => {
		expect( toInches( 86 ) ).toBe( 34 );
		expect( toInches( 91 ) ).toBe( 36 );
		expect( toInches( 64.5 ) ).toBe( 25.5 );
	} );
} );

describe( 'cell', () => {
	it( 'gives a value in both units', () => {
		expect( cell( '86-91' ) ).toEqual( { cm: '86-91', in: '34-36' } );
		expect( cell( '64,5' ) ).toEqual( { cm: '64.5', in: '25.5' } );
	} );

	it( 'gives null for text', () => {
		expect( cell( 'n/a' ) ).toBeNull();
	} );
} );

describe( 'parsePasted', () => {
	it( 'reads tab separated rows, first row as column names', () => {
		expect( parsePasted( 'Size\tChest\tEU\nS\t86-91\t46' ) ).toEqual( {
			columns: [
				{ name: 'Chest', kind: 'measure', line: 'auto', note: '' },
				{ name: 'EU', kind: 'label', line: 'auto', note: '' },
			],
			rows: [ [ 'S', '86-91', '46' ] ],
		} );
	} );

	it( 'reads comma separated rows with quoted values', () => {
		expect( parsePasted( 'Size,Waist\nM,"70,5"' ).rows ).toEqual( [
			[ 'M', '70,5' ],
		] );
	} );

	it( 'keeps a column of sizes like US 8 as labels', () => {
		const { columns } = parsePasted( 'Size\tUS\nS\t4-6\nM\tUS 8' );
		expect( columns[ 0 ].kind ).toBe( 'label' );
	} );

	it( 'gives nothing for empty text', () => {
		expect( parsePasted( '  \n ' ) ).toEqual( { columns: [], rows: [] } );
	} );
} );

describe( 'lineFor', () => {
	it( 'guesses the line from an English column name', () => {
		expect(
			lineFor( { name: 'Bust', kind: 'measure', line: 'auto' } )
		).toBe( 'chest' );
		expect(
			lineFor( { name: 'Inside leg', kind: 'measure', line: 'auto' } )
		).toBe( 'inseam' );
		expect(
			lineFor( { name: 'Hips', kind: 'measure', line: 'auto' } )
		).toBe( 'hips' );
	} );

	it( 'keeps a picked line and draws none for labels', () => {
		expect(
			lineFor( { name: 'Length', kind: 'measure', line: 'sleeve' } )
		).toBe( 'sleeve' );
		expect( lineFor( { name: 'EU', kind: 'label', line: 'auto' } ) ).toBe(
			'none'
		);
	} );
} );

describe( 'figureFor', () => {
	const tops = [ { name: 'Chest', kind: 'measure', line: 'auto' } ];
	const bottoms = [
		{ name: 'Waist', kind: 'measure', line: 'auto' },
		{ name: 'Inseam', kind: 'measure', line: 'auto' },
	];

	it( 'picks Bottom for an inseam, Top for other lines', () => {
		expect( figureFor( 'auto', bottoms ) ).toBe( 'bottom' );
		expect( figureFor( 'auto', tops ) ).toBe( 'top' );
	} );

	it( 'shows none when no column draws a line', () => {
		expect(
			figureFor( 'auto', [ { name: 'EU', kind: 'label', line: 'auto' } ] )
		).toBe( 'none' );
	} );

	it( 'keeps a picked figure', () => {
		expect( figureFor( 'bottom', tops ) ).toBe( 'bottom' );
		expect( figureFor( 'none', tops ) ).toBe( 'none' );
	} );
} );

describe( 'SAMPLES', () => {
	it( 'match the starter chart the server makes (SizeCharts::sample)', () => {
		expect( SAMPLES.tops.columns.map( ( column ) => column.name ) ).toEqual(
			[ 'Chest', 'Waist', 'Hips' ]
		);
		expect( SAMPLES.tops.rows ).toEqual( [
			[ 'XS', '80-85', '64-69', '86-91' ],
			[ 'S', '86-91', '70-75', '92-97' ],
			[ 'M', '92-97', '76-81', '98-103' ],
			[ 'L', '98-103', '82-87', '104-109' ],
			[ 'XL', '104-110', '88-94', '110-116' ],
		] );
		expect( figureFor( 'auto', SAMPLES.tops.columns ) ).toBe( 'top' );
	} );

	it( 'give shoes labels and a foot length with no figure', () => {
		const { columns, rows } = SAMPLES.shoes;
		expect( columns.map( ( column ) => column.kind ) ).toEqual( [
			'label',
			'label',
			'measure',
		] );
		expect( figureFor( 'auto', columns ) ).toBe( 'none' );
		rows.forEach( ( row ) => {
			expect( row ).toHaveLength( columns.length + 1 );
			expect( cell( row[ 3 ] ) ).not.toBeNull();
		} );
	} );
} );
