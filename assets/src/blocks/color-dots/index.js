/**
 * The Color Dots block: the colors a product comes in, on product cards.
 * PHP prints it, so nothing is saved.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<circle cx="5.5" cy="12" r="3" fill="currentColor" />
		<circle
			cx="12"
			cy="12"
			r="2.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<circle cx="18.5" cy="12" r="3" fill="currentColor" opacity="0.45" />
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save: () => null } );
