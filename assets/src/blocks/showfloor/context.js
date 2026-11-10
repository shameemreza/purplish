/**
 * What each Look needs from the Showfloor in the editor to stand in its
 * place on the floor: the front look, how many looks there are and the
 * depth setting.
 */
import { createContext } from '@wordpress/element';

export const FloorContext = createContext( {
	front: 0,
	count: 1,
	depth: 'subtle',
} );
