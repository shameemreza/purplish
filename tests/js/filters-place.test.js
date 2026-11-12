/*
 * The Hide filters button finds the filters' column and sits beside Sort
 * by, or at the top of the products' column. A plain comment, since Jest
 * reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it } from '@jest/globals';
import { placeToggle, sides } from '../../assets/src/filters/place';

const page = ( main ) => {
	document.body.innerHTML = `<div class="wp-block-columns">
		<div class="wp-block-column" id="side"><h3>Filter by</h3><button class="purplish-filters-toggle"></button><div class="wc-block-product-filters"></div></div>
		<div class="wp-block-column" id="main">${ main }</div>
	</div>`;
	return document.querySelector( '.purplish-filters-toggle' );
};

describe( 'sides', () => {
	it( 'finds the filters, the row and the products', () => {
		const found = sides( page( '' ) );
		expect( found.side.id ).toBe( 'side' );
		expect( found.main.id ).toBe( 'main' );
		expect( found.row.classList.contains( 'wp-block-columns' ) ).toBe(
			true
		);
	} );

	it( 'gives up outside a columns row', () => {
		document.body.innerHTML =
			'<div class="wp-block-group"><button class="purplish-filters-toggle"></button></div>';
		expect(
			sides( document.querySelector( '.purplish-filters-toggle' ) )
		).toBeNull();
	} );

	it( 'gives up in a column with no neighbor', () => {
		document.body.innerHTML =
			'<div class="wp-block-columns"><div class="wp-block-column"><button class="purplish-filters-toggle"></button></div></div>';
		expect(
			sides( document.querySelector( '.purplish-filters-toggle' ) )
		).toBeNull();
	} );
} );

describe( 'placeToggle', () => {
	it( 'sits just before Sort by', () => {
		const toggle = page(
			'<div class="row"><p>Results</p><div class="wp-block-woocommerce-catalog-sorting"></div></div><ul></ul>'
		);
		placeToggle( toggle, document.getElementById( 'main' ) );
		expect( toggle.nextElementSibling.className ).toBe(
			'wp-block-woocommerce-catalog-sorting'
		);
		expect( toggle.classList.contains( 'is-beside' ) ).toBe( true );
	} );

	it( 'goes to the top of the products without Sort by', () => {
		const toggle = page( '<ul></ul>' );
		const main = document.getElementById( 'main' );
		placeToggle( toggle, main );
		expect( main.firstElementChild ).toBe( toggle );
		expect( toggle.classList.contains( 'is-beside' ) ).toBe( false );
	} );
} );
