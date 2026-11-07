/**
 * Matching chips read a selected chip's look the same way on the front end
 * and in the editor.
 */
import { describe, expect, it } from '@jest/globals';
import { chipVars } from '../../assets/src/shared/chips';

const props = ( values ) => ( {
	getPropertyValue: ( name ) => values[ name ] || '',
} );

const look = {
	borderRadius: '999px',
	paddingTop: '4px',
	paddingRight: '12px',
	paddingBottom: '4px',
	paddingLeft: '12px',
	fontSize: '14px',
	letterSpacing: 'normal',
	textTransform: 'uppercase',
};

describe( 'chipVars', () => {
	it( 'takes the selected colors and the chip shape', () => {
		expect(
			chipVars(
				props( {
					'--wc-product-filter-chips-selected-background': ' #111 ',
					'--wc-product-filter-chips-selected-border': '#222',
					'--wc-product-filter-chips-selected-text': '#fff',
				} ),
				look
			)
		).toEqual( {
			background: '#111',
			border: '#222',
			text: '#fff',
			radius: '999px',
			padding: '4px 12px 4px 12px',
			size: '14px',
			spacing: 'normal',
			case: 'uppercase',
		} );
	} );

	it( 'falls back to the text and background colors, then the system ones', () => {
		const fallback = chipVars(
			props( {
				'--wc-product-filter-chips-text': '#333',
				'--wc-product-filter-chips-background': '#eee',
			} ),
			look
		);
		expect( fallback.background ).toBe( '#333' );
		expect( fallback.border ).toBe( '#333' );
		expect( fallback.text ).toBe( '#eee' );

		const none = chipVars( props( {} ), look );
		expect( none.background ).toBe( 'CanvasText' );
		expect( none.border ).toBe( 'CanvasText' );
		expect( none.text ).toBe( 'Canvas' );
	} );
} );
