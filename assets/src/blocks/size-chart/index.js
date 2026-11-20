/**
 * The Size Chart block: a chart's own sizes, or the product's chart on the
 * product page. PHP prints it, so nothing is saved.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.css';
import './editor.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M3.5 15.5 15.5 3.5l5 5-12 12z"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
		/>
		<path
			d="M7 12l1.6 1.6M9.5 9.5l2.2 2.2M12 7l1.6 1.6M14.5 4.5l2.2 2.2"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save: () => null } );
