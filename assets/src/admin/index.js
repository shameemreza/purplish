/**
 * Starts WordPress's color picker on the Page color field of each variation.
 *
 * WooCommerce loads variations over AJAX, so the picker starts when they
 * load or get added, not on page load.
 */
const $ = window.jQuery;
const panel = '#variable_product_options';

/**
 * Tells WooCommerce the variation changed, so Save changes turns on.
 *
 * @param {HTMLElement|Object} input The color input, or a jQuery set holding it.
 */
function markChanged( input ) {
	$( input ).trigger( 'change' );
}

function start() {
	$( panel )
		.find( 'input[data-purplish-color-picker]' )
		.not( '.wp-color-picker' )
		.wpColorPicker( {
			change( event ) {
				markChanged( event.target );
			},
			clear( event ) {
				const input = $( event.target )
					.closest( '.wp-picker-container' )
					.find( 'input[data-purplish-color-picker]' );
				markChanged( input );
			},
		} );
}

// WooCommerce triggers both events inside the panel; they bubble up here.
$( document.body ).on(
	'woocommerce_variations_loaded woocommerce_variations_added',
	start
);
$( start );
