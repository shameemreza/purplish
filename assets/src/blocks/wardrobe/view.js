/**
 * Turns every Wardrobe menu item on. Without this script the item is a
 * plain link to its page.
 */
import { wardrobe } from './menu';

document.querySelectorAll( '.wp-block-purplish-wardrobe' ).forEach( wardrobe );
