/**
 * What each Piece needs from the Changing Room in the editor to stand in
 * its place: its ring, its place in that ring, which piece each ring has
 * turned to and whether far pieces show.
 */
import { createContext } from '@wordpress/element';

export const RoomContext = createContext( {
	order: {},
	front: { top: 0, bottom: 0 },
	far: true,
} );
