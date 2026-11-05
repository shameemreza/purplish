/**
 * The Story Deck block: customer stories on a deck of cards. PHP prints the
 * wrapper and the buttons, so the saved markup is only the inner blocks.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<rect
			x="5"
			y="5"
			width="8"
			height="12"
			rx="1.5"
			transform="rotate(-10 9 17)"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<rect
			x="7"
			y="4"
			width="8"
			height="12"
			rx="1.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path
			d="M17.5 8h3M17.5 11h3M17.5 14h2"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
