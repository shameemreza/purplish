/**
 * The choices in the Showfloor's block panel, value first.
 */
import { __ } from '@wordpress/i18n';

const choices = ( list ) =>
	list.map( ( [ value, label ] ) => ( { value, label } ) );

export const SHAPES = choices( [
	[ 'oval', __( 'Oval', 'purplish' ) ],
	[ 'flat', __( 'Flat row', 'purplish' ) ],
] );
export const DEPTHS = choices( [
	[ 'subtle', __( 'Subtle', 'purplish' ) ],
	[ 'strong', __( 'Strong', 'purplish' ) ],
	[ 'off', __( 'Off', 'purplish' ) ],
] );
export const STARTS = choices( [
	[ 'floor', __( 'Floor', 'purplish' ) ],
	[ 'grid', __( 'Grid', 'purplish' ) ],
] );
export const PHONES = choices( [
	[ 'row', __( 'Swipe row', 'purplish' ) ],
	[ 'ring', __( 'Small floor', 'purplish' ) ],
] );
export const PAUSES = choices( [
	[ 'ring', __( 'With timer ring', 'purplish' ) ],
	[ 'plain', __( 'Plain', 'purplish' ) ],
	[ 'off', __( 'Hidden', 'purplish' ) ],
] );
export const TOGGLES = choices( [
	[ 'both', __( 'Icon and text', 'purplish' ) ],
	[ 'icons', __( 'Icons', 'purplish' ) ],
	[ 'text', __( 'Text', 'purplish' ) ],
	[ 'off', __( 'Hidden', 'purplish' ) ],
] );
export const CURSORS = choices( [
	[ 'label', __( 'Label (Drag, Open, Turn)', 'purplish' ) ],
	[ 'ring', __( 'Ring', 'purplish' ) ],
	[ 'off', __( 'System cursor', 'purplish' ) ],
] );
export const LISTS = choices( [
	[ 'both', __( 'Numbers and names', 'purplish' ) ],
	[ 'faces', __( 'Faces', 'purplish' ) ],
	[ 'names', __( 'Names', 'purplish' ) ],
	[ 'numbers', __( 'Numbers', 'purplish' ) ],
	[ 'off', __( 'Off', 'purplish' ) ],
] );
export const ADDS = choices( [
	[ 'text', __( 'Text', 'purplish' ) ],
	[ 'icon', __( 'Icon', 'purplish' ) ],
] );
export const HOVERS = choices( [
	[ 'lift', __( 'Lift', 'purplish' ) ],
	[ 'spotlight', __( 'Spotlight', 'purplish' ) ],
	[ 'peek', __( 'Peek pieces', 'purplish' ) ],
	[ 'none', __( 'None', 'purplish' ) ],
] );
export const CLOSES = choices( [
	[ 'both', __( 'Icon and text', 'purplish' ) ],
	[ 'icon', __( 'Icon', 'purplish' ) ],
	[ 'text', __( 'Text', 'purplish' ) ],
] );
export const MARKS = choices( [
	[ 'highlight', __( 'Highlight the piece', 'purplish' ) ],
	[ 'add', __( 'Add it to the cart', 'purplish' ) ],
] );
