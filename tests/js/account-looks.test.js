/*
 * The ready-made looks in the Login and register panel. A plain comment,
 * since Jest reads the environment from it.
 *
 * @jest-environment jsdom
 */
import { describe, expect, it, jest } from '@jest/globals';
import { accountClasses, isLook, looks } from '../../assets/src/editor/account';

// The panel's components aren't needed to read the looks.
jest.mock( '@wordpress/components', () => ( {} ) );
jest.mock( '@wordpress/block-editor', () => ( { store: {} } ), {
	virtual: true,
} );
jest.mock( '@wordpress/data', () => ( {} ), { virtual: true } );

describe( 'looks', () => {
	it( 'gives a class for every option each look sets', () => {
		looks().forEach( ( look ) => {
			expect( accountClasses( look.account ) ).toHaveLength(
				Object.keys( look.account ).length + 1
			);
		} );
	} );

	it( 'knows saved options that are exactly a look', () => {
		const purple = looks()[ 0 ].account;
		expect( isLook( { ...purple }, purple ) ).toBe( true );
		expect( isLook( { ...purple, eye: true }, purple ) ).toBe( false );
		expect( isLook( { ...purple, labels: 'flip' }, purple ) ).toBe( false );
		expect( isLook( undefined, purple ) ).toBe( false );
	} );
} );
