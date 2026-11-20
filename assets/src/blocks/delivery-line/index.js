/**
 * The Delivery Line block: delivery date, returns and pickup lines next to
 * the buy button. PHP prints it, so nothing is saved.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M2.75 6.75h10.5v9.5H2.75zM13.25 9.75h4.1l3.9 3.6v2.9h-8"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
		/>
		<circle
			cx="6.75"
			cy="17.25"
			r="1.75"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<circle
			cx="17.25"
			cy="17.25"
			r="1.75"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save: () => null } );
