/**
 * The Showfloor block and its Look block: cut-out looks on a turning
 * floor. Both register here, in one script, so each Look shares the
 * floor's place in the editor. PHP prints the floor, so the saved markup
 * is only the Look blocks, and a Look saves nothing.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import lookMetadata from '../showfloor-look/block.json';
import Edit from './edit';
import LookEdit from '../showfloor-look/edit';
import save from './save';
import './style.css';
import './editor.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<ellipse
			cx="12"
			cy="18"
			rx="9"
			ry="3"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<circle cx="12" cy="5" r="1.75" fill="currentColor" />
		<path
			d="M12 8v8M7 10.5v5M17 10.5v5"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
	</svg>
);

const lookIcon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<circle cx="12" cy="4.5" r="2" fill="currentColor" />
		<path
			d="M8.5 9.5h7l-1 6h-5zM10 15.5l-.5 6M14 15.5l.5 6"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
registerBlockType( lookMetadata.name, {
	icon: lookIcon,
	edit: LookEdit,
	save: () => null,
	__experimentalLabel: ( { name } ) => name,
} );
