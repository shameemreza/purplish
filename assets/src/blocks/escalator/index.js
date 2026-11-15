/**
 * The Escalator block: photos or products in columns that drift in
 * opposite directions. PHP prints everything, so nothing is saved.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<rect
			x="3"
			y="2"
			width="7.5"
			height="9"
			rx="1"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<rect x="3" y="13" width="7.5" height="9" rx="1" fill="currentColor" />
		<rect
			x="13.5"
			y="1"
			width="7.5"
			height="5"
			rx="1"
			fill="currentColor"
		/>
		<rect
			x="13.5"
			y="8"
			width="7.5"
			height="9"
			rx="1"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<rect
			x="13.5"
			y="19"
			width="7.5"
			height="4"
			rx="1"
			fill="currentColor"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save: () => null } );
