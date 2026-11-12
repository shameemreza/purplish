/**
 * The Shelf Tag block: a tag on product cards that shows only on the
 * products its rule matches. PHP prints it, so nothing is saved.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M4 5.5A1.5 1.5 0 0 1 5.5 4h6.4l8.1 8.1-7.9 7.9L4 11.9z"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
		/>
		<circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" />
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save: () => null } );
