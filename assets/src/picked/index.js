/**
 * Fills the picked color's name beside each photo swatch row.
 */
import { watchPicked } from './picked';

document
	.querySelectorAll( '.purplish-photo-swatches__picked' )
	.forEach( watchPicked );
