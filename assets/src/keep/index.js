/**
 * Column, keep in view while the photos scroll (see ./keep.js). Columns
 * sit side by side from 782px.
 */
import { watchKeep } from './keep';

watchKeep( document, window, window.matchMedia( '(min-width: 782px)' ) );
