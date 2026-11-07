/**
 * Used by the Hover Tabs editor.
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Gives a group added after the block loaded the starting inner blocks, so
 * it doesn't open on the empty group's layout picker. Saved groups and
 * groups a merchant empties later are left alone.
 *
 * @param {string} clientId The parent block's client ID.
 * @param {Array}  template The inner blocks template for a new group.
 */
export function useFillNewGroups( clientId, template ) {
	const groups = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);
	const { replaceInnerBlocks, __unstableMarkNextChangeAsNotPersistent } =
		useDispatch( blockEditorStore );
	const known = useRef( null );

	useEffect( () => {
		const before = known.current;
		known.current = new Set( groups.map( ( group ) => group.clientId ) );
		if ( ! before ) {
			return;
		}
		groups
			.filter(
				( group ) =>
					! before.has( group.clientId ) &&
					'core/group' === group.name &&
					! group.innerBlocks.length
			)
			.forEach( ( group ) => {
				__unstableMarkNextChangeAsNotPersistent();
				replaceInnerBlocks(
					group.clientId,
					createBlocksFromInnerBlocksTemplate( template ),
					false
				);
			} );
	}, [
		groups,
		template,
		replaceInnerBlocks,
		__unstableMarkNextChangeAsNotPersistent,
	] );
}
