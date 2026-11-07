/**
 * The Step Path block: numbered steps on a line that draws as the block
 * scrolls into view. PHP prints the wrapper, so the saved markup is only
 * the inner blocks.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M7 12h3.5M13.5 12H17"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<circle
			cx="4.5"
			cy="12"
			r="2.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<circle
			cx="12"
			cy="12"
			r="2.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<circle cx="19.5" cy="12" r="2.5" fill="currentColor" />
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
