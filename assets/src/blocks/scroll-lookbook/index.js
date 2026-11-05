/**
 * The Scroll Lookbook block: Cover blocks play as a film while visitors
 * scroll, and each stop brings in a product card. PHP prints the wrapper,
 * so the saved markup is only the inner blocks.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<rect
			x="2.75"
			y="4.75"
			width="18.5"
			height="14.5"
			rx="1.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path d="M10 9.5v5l4.25-2.5z" fill="currentColor" />
		<rect x="15.5" y="7" width="4" height="10" rx="1" fill="currentColor" />
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
