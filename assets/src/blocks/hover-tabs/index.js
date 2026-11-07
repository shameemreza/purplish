/**
 * The Hover Tabs block: names beside one big photo that changes as
 * visitors hover over or tab to each name. PHP prints the wrapper, so the
 * saved markup is only the inner blocks.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M3 6.5h6M3 12h7.5M3 17.5h6"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<rect
			x="13"
			y="4"
			width="8"
			height="16"
			rx="1.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
