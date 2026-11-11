/**
 * The choices in the Changing Room's block panel, value first.
 */
import { __ } from '@wordpress/i18n';

const choices = ( list ) =>
	list.map( ( [ value, label ] ) => ( { value, label } ) );

export const LOOKS = choices( [
	[ 'theme', __( 'Theme button', 'purplish' ) ],
	[ 'glow', __( 'Glow', 'purplish' ) ],
	[ 'sweep', __( 'Sweep', 'purplish' ) ],
	[ 'glass', __( 'Glass', 'purplish' ) ],
	[ 'spectrum', __( 'Spectrum', 'purplish' ) ],
] );
export const COLORS = choices( [
	[ 'last', __( 'Last piece worn', 'purplish' ) ],
	[ 'top', __( 'Top piece', 'purplish' ) ],
	[ 'bottom', __( 'Bottom piece', 'purplish' ) ],
	[ 'off', __( 'Off', 'purplish' ) ],
] );
export const AREAS = choices( [
	[ 'accents', __( 'Buttons and notice', 'purplish' ) ],
	[ 'stage', __( 'Whole block', 'purplish' ) ],
	[ 'page', __( 'Whole page', 'purplish' ) ],
] );
export const SWAPS = choices( [
	[ 'fade', __( 'Fade', 'purplish' ) ],
	[ 'reveal', __( 'Reveal from the top', 'purplish' ) ],
	[ 'none', __( 'Instant', 'purplish' ) ],
] );
export const SLOTS = choices( [
	[ 'top', __( 'Top ring', 'purplish' ) ],
	[ 'bottom', __( 'Bottom ring', 'purplish' ) ],
] );
