/**
 * The Matching Extras block: the matching extras list as a block of its
 * own, for an Add to Cart part. PHP prints it, so nothing is saved.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<rect
			x="3.75"
			y="4.75"
			width="6.5"
			height="6.5"
			rx="1.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path
			d="m5.5 8 1.25 1.25L8.75 7"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<rect
			x="3.75"
			y="12.75"
			width="6.5"
			height="6.5"
			rx="1.5"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path
			d="M13 8h7.25M13 16h7.25"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save: () => null } );
