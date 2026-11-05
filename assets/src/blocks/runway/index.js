/**
 * The Runway block: product photos in a line that fades into the distance,
 * walked look by look beside the product cards. PHP prints the wrapper and
 * the looks, so the saved markup is only the inner blocks.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<rect x="2" y="10" width="3" height="5" rx="0.5" fill="currentColor" />
		<rect
			x="6"
			y="8"
			width="4.5"
			height="7.5"
			rx="0.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<rect
			x="12"
			y="4"
			width="7.5"
			height="12"
			rx="1"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path d="M3 20h18" stroke="currentColor" strokeWidth="1.5" />
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
