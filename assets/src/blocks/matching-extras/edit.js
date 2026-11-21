/**
 * Matching Extras in the editor: the same sample list Add to Cart +
 * Options shows for its switch, and the same Matching extras panel. A
 * template has no product of its own, so Cross-sells and Auto match show
 * the store's best sellers.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import ExtrasPanel from '../../editor/extras';
import { build, extrasSettings, sample } from '../../editor/extras-preview';

/**
 * The editor view and the block panel.
 *
 * @param {Object}                    props               Block props.
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Saves attributes.
 */
export default function Edit( { attributes, setAttributes } ) {
	const options = attributes.options || {};
	const key = JSON.stringify( extrasSettings( options ) );
	const [ items, setItems ] = useState( null );
	const ref = useRef();
	const blockProps = useBlockProps();

	useEffect( () => {
		let live = true;
		sample( JSON.parse( key ) ).then(
			( list ) => live && setItems( list )
		);
		return () => {
			live = false;
		};
	}, [ key ] );

	useEffect( () => {
		const element = ref.current;
		if ( ! element || ! items?.length ) {
			return;
		}
		element.replaceChildren(
			build( element.ownerDocument, items, JSON.parse( key ) )
		);
		return () => element.replaceChildren();
	}, [ items, key ] );

	return (
		<>
			<InspectorControls>
				<ExtrasPanel
					saved={ options }
					setSaved={ ( next ) => setAttributes( { options: next } ) }
					always
				/>
			</InspectorControls>
			<div { ...blockProps }>
				{ /* The list is built outside React, as the server prints it. */ }
				<div ref={ ref } />
				{ items && ! items.length && (
					<p className="purplish-matching-extras__empty">
						{ __(
							'No simple products in stock to show yet.',
							'purplish'
						) }
					</p>
				) }
			</div>
		</>
	);
}
