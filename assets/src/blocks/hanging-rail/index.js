/**
 * The Hanging Rail block: products on a rope that slide along it as the
 * page scrolls. PHP prints the wrapper, so the saved markup is only the
 * inner blocks.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M2 5q5 3 10 0t10 0"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path d="M6 6h1v2H6zM17 6h1v2h-1z" fill="currentColor" />
		<rect
			x="3.5"
			y="8"
			width="6"
			height="9"
			rx="1"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<rect
			x="14.5"
			y="8"
			width="6"
			height="11"
			rx="1"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
