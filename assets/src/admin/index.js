/**
 * The custom color field in each variation: a hex text field with a
 * swatch, and WordPress's Iris picker below it, the way WooCommerce's own
 * color settings work.
 *
 * WooCommerce loads variations over AJAX, so the field starts when they
 * load or get added, not on page load.
 */
const $ = window.jQuery;
const HEX = /^#([a-f0-9]{6}|[a-f0-9]{3})$/i;

/**
 * Shows a color in the field's swatch. An empty or broken value shows the
 * empty swatch.
 *
 * @param {Object} $input The color input, as a jQuery set.
 */
function paintSwatch( $input ) {
	const value = $input.val().trim();
	$input
		.siblings( '.purplish-color-swatch' )
		.toggleClass( 'is-empty', ! HEX.test( value ) )
		.css( 'background-color', HEX.test( value ) ? value : '' );
}

/**
 * Tells WooCommerce the variation changed, so Save changes turns on.
 *
 * @param {Object} $input The color input, as a jQuery set.
 */
function markChanged( $input ) {
	$input.trigger( 'change' );
}

function start() {
	$( '#variable_product_options input[data-purplish-color-picker]' )
		.not( '.purplish-color-ready' )
		.each( function () {
			const $input = $( this ).addClass( 'purplish-color-ready' );

			$input.wrap( '<span class="purplish-color-field"></span>' );
			$(
				'<span class="purplish-color-swatch" aria-hidden="true"></span>'
			)
				.insertBefore( $input )
				.on( 'click', ( event ) => {
					event.stopPropagation();
					$input.trigger( 'focus' );
				} );

			$input
				.iris( {
					hide: true,
					border: true,
					width: 255,
					change( event, ui ) {
						$input.val( ui.color.toString() );
						paintSwatch( $input );
						// Iris sets the value before it fires change, so wait a tick.
						setTimeout( () => markChanged( $input ) );
					},
				} )
				.on( 'click focus', ( event ) => {
					event.stopPropagation();
					$( '.purplish-color-field .iris-picker' ).hide();
					$input.iris( 'show' );
					$input.data( 'purplishLastGood', $input.val() );
				} )
				.on( 'input', () => {
					const value = $input.val().trim();
					if ( HEX.test( value ) ) {
						$input.iris( 'color', value );
					}
					paintSwatch( $input );
				} )
				.on( 'blur', () => {
					const value = $input.val().trim();
					// A typo goes back to the last good color; empty clears it.
					if ( '' !== value && ! HEX.test( value ) ) {
						$input.val( $input.data( 'purplishLastGood' ) || '' );
						paintSwatch( $input );
						markChanged( $input );
					}
				} );

			paintSwatch( $input );
		} );
}

// WooCommerce triggers both events inside the panel; they bubble up here.
$( document.body ).on(
	'woocommerce_variations_loaded woocommerce_variations_added',
	start
);
// A click anywhere outside the field closes its picker.
$( document.body ).on( 'click', ( event ) => {
	if ( ! $( event.target ).closest( '.purplish-color-field' ).length ) {
		$( '.purplish-color-field .iris-picker' ).hide();
	}
} );
$( start );
