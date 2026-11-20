/**
 * Stock Indicator dots on variable products follow the picked variation.
 */
import { watchStock } from './stock';

const data = document.getElementById(
	'wp-script-module-data-@wordpress/interactivity'
);
let store = null;
try {
	store = JSON.parse( data ? data.textContent : '{}' ).state[
		'woocommerce/products'
	];
} catch {
	store = null;
}

if ( store ) {
	document
		.querySelectorAll( '.purplish-stock-dot[data-wp-text]' )
		.forEach( ( indicator ) => watchStock( indicator, store ) );
}
