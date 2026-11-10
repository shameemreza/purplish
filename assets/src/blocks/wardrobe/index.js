/**
 * The Wardrobe block, and the Navigation block's permission to hold it.
 * PHP prints the menu item, so the saved markup is only the panel's blocks.
 */
import { registerBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.css';
import './editor.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M4 5.5h5M11 5.5h3M16 5.5h4"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
		<rect
			x="4"
			y="9"
			width="16"
			height="10"
			rx="2"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path
			d="M7 12.5h3M7 15.5h3M13 12v4.5M16.5 12v4.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
	</svg>
);

// The editor registers Navigation from its own copy of block.json, so the
// PHP filter that adds Wardrobe to its child blocks doesn't reach it.
addFilter(
	'blocks.registerBlockType',
	'purplish/wardrobe/navigation',
	( settings, name ) =>
		'core/navigation' === name &&
		Array.isArray( settings.allowedBlocks ) &&
		! settings.allowedBlocks.includes( metadata.name )
			? {
					...settings,
					allowedBlocks: [ ...settings.allowedBlocks, metadata.name ],
				}
			: settings
);

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save,
	__experimentalLabel: ( { label } ) => label,
} );
