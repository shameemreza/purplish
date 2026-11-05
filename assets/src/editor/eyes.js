/**
 * The googly eyes choice, shared by the Button and Add to Cart + Options
 * panels.
 */
import { __ } from '@wordpress/i18n';

export const EYES = [
	{ value: '', label: __( 'None', 'purplish' ) },
	{ value: 'one', label: __( 'One eye', 'purplish' ) },
	{ value: 'two', label: __( 'Two eyes', 'purplish' ) },
];

export const EYES_HELP = __(
	'Eyes inside the button that follow the mouse and blink when it moves again. They stay still on touch screens and for visitors who ask for reduced motion.',
	'purplish'
);
