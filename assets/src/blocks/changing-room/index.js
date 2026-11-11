/**
 * The Changing Room block and its Piece block: tops and bottoms turning in
 * rings round a model. Both register here, in one script, so each Piece
 * shares the room's place in the editor. PHP prints the room, so the saved
 * markup is only the Piece blocks, and a Piece saves nothing.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import pieceMetadata from '../changing-room-piece/block.json';
import Edit from './edit';
import PieceEdit from '../changing-room-piece/edit';
import save from './save';
import './style.css';
import './editor.css';

const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<circle cx="12" cy="3.5" r="1.75" fill="currentColor" />
		<path
			d="M9.5 7h5l-.5 6h-4zM10 13l-.5 8M14 13l.5 8"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
		/>
		<path
			d="M3 9.5c2-1 4-1.5 5.5-1.5M21 9.5c-2-1-4-1.5-5.5-1.5M4 17c1.5-.7 3.2-1 4.8-1M20 17c-1.5-.7-3.2-1-4.8-1"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
	</svg>
);

const pieceIcon = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden>
		<path
			d="M8 3.5 4 6v5h3v9.5h10V11h3V6l-4-2.5c-.5 1.5-2 2.5-4 2.5S8.5 5 8 3.5z"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
		/>
	</svg>
);

registerBlockType( metadata.name, { icon, edit: Edit, save } );
registerBlockType( pieceMetadata.name, {
	icon: pieceIcon,
	edit: PieceEdit,
	save: () => null,
} );
