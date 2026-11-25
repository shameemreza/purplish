/**
 * Instant results on every Search block with the option on.
 */
// Not named style.css, so the styles stay in this entry and the bundle needs no chunk loader.
import './panel.css';
import { setup } from './combobox';

document
	.querySelectorAll( 'form[data-purplish-search]' )
	.forEach( ( form ) => setup( form ) );
