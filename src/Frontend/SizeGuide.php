<?php
/**
 * The Size guide link beside the size options of the Variation Selector,
 * the fit note under them, and the size guide dialog.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\Blocks\SizeChart;
use Purplish\SizeCharts;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the link and fit note to the size attribute's row and prints the
 * product's chart in a dialog in the footer, outside the add to cart form.
 */
final class SizeGuide {

	const BLOCK = 'woocommerce/add-to-cart-with-options-variation-selector-attribute';

	const NAME_CLASS = 'wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name';

	/**
	 * Dialogs to print in the footer, by ID.
	 *
	 * @var array<string, string>
	 */
	private static $dialogs = array();

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		// After BlockEffects adds its classes to each row.
		add_filter( 'render_block_' . self::BLOCK, array( $this, 'add_link' ), 20, 2 );
		add_action( 'wp_footer', array( $this, 'print_dialogs' ) );
	}

	/**
	 * Forgets the dialogs, for tests.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$dialogs = array();
	}

	/**
	 * Adds the link beside the size attribute's name and the fit note under
	 * its options. The block prints one row per attribute, each ending with
	 * WooCommerce's hidden `attribute_*` input.
	 *
	 * @param string $html  Rendered rows.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public function add_link( $html, $block ) {
		global $product;
		$saved = isset( $block['attrs']['purplish'] ) && is_array( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array();
		if ( '' === $html || ( isset( $saved['sizeguide'] ) && false === $saved['sizeguide'] ) || ! $product instanceof \WC_Product_Variable ) {
			return $html;
		}

		$picked    = isset( $saved['sizeattr'] ) && is_string( $saved['sizeattr'] ) ? sanitize_title( $saved['sizeattr'] ) : '';
		$attribute = SizeCharts::size_attribute( $product, $picked );
		$field     = '' === $attribute ? '' : wc_variation_attribute_name( $attribute );
		$input     = '<input type="hidden" name="' . esc_attr( $field ) . '"';
		$at        = '' === $field ? false : strpos( $html, $input );
		if ( false === $at ) {
			return $html;
		}

		$id = 'purplish-size-guide-' . $product->get_id();
		if ( ! isset( self::$dialogs[ $id ] ) ) {
			$dialog = self::dialog( $id, $product, $attribute, isset( $saved['sizesheet'] ) && 'card' === $saved['sizesheet'] );
			if ( '' === $dialog ) {
				return $html;
			}
			self::$dialogs[ $id ] = $dialog;
		}

		// The row runs from the end of the row before it to this input.
		$start = strrpos( substr( $html, 0, $at ), '/></div>' );
		$start = false === $start ? 0 : $start + 8;
		$row   = substr( $html, $start, $at - $start );
		$note  = SizeCharts::fit_note( $product );
		$link  = self::link( $id, isset( $saved['sizelabel'] ) && is_string( $saved['sizelabel'] ) ? trim( $saved['sizelabel'] ) : '' );
		$name  = '#<(span|div|label|p|h[1-6])\b[^>]*class="[^"]*\b' . preg_quote( self::NAME_CLASS, '#' ) . '\b[^"]*"[^>]*>.*?</\1>#s';

		$count = 0;
		$row   = preg_replace_callback(
			$name,
			static function ( $found ) use ( $link ) {
				return '<div class="purplish-size-guide__row">' . $found[0] . $link . '</div>';
			},
			$row,
			1,
			$count
		);
		if ( ! $count ) {
			$row .= '<div class="purplish-size-guide__row">' . $link . '</div>';
		}
		if ( '' !== $note ) {
			$row .= sprintf(
				'<p class="purplish-size-guide__fit"><span class="purplish-size-guide__fit-label">%1$s</span> %2$s</p>',
				esc_html__( 'Fit', 'purplish' ),
				esc_html( $note )
			);
		}

		return substr( $html, 0, $start ) . $row . substr( $html, $at );
	}

	/**
	 * The Size guide link: a button that opens the dialog with no script in
	 * browsers that know `command`, and through the chart script elsewhere.
	 *
	 * @param string $id   Dialog ID.
	 * @param string $text Link text, or '' for "Size guide".
	 * @return string
	 */
	private static function link( $id, $text ) {
		return sprintf(
			'<button type="button" class="purplish-size-guide__link" command="show-modal" commandfor="%1$s" aria-haspopup="dialog"><svg class="purplish-size-guide__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3.5 15.5 15.5 3.5l5 5-12 12z"/><path d="M7 12l1.6 1.6M9.5 9.5l2.2 2.2M12 7l1.6 1.6M14.5 4.5l2.2 2.2"/></svg><span class="purplish-size-guide__text">%2$s</span></button>',
			esc_attr( $id ),
			esc_html( '' === $text ? __( 'Size guide', 'purplish' ) : $text )
		);
	}

	/**
	 * The dialog with the product's fit note and chart. The chart renders
	 * now, inside the page, so its styles and script load as usual.
	 *
	 * @param string      $id        Dialog ID.
	 * @param \WC_Product $product   Product.
	 * @param string      $attribute Size attribute slug.
	 * @param bool        $card      Whether phones get a centered card instead of a bottom sheet.
	 * @return string '' when the product has no chart.
	 */
	private static function dialog( $id, $product, $attribute, $card ) {
		$chart = SizeCharts::chart_id( $product );
		if ( ! $chart ) {
			return '';
		}

		SizeCharts::push(
			array(
				'product'   => $product->get_id(),
				'attribute' => $attribute,
			)
		);
		$table = do_blocks( '<!-- wp:block {"ref":' . (int) $chart . '} /-->' );
		SizeCharts::pop();
		if ( '' === trim( $table ) ) {
			return '';
		}

		$note = SizeCharts::fit_note( $product );
		return sprintf(
			'<dialog id="%1$s" class="%2$s" closedby="any" aria-labelledby="%1$s-title"><div class="purplish-size-guide__head"><div class="purplish-size-guide__heading"><h2 class="purplish-size-guide__title" id="%1$s-title">%3$s</h2><p class="purplish-size-guide__product">%4$s</p></div><button type="button" class="purplish-size-guide__close" command="close" commandfor="%1$s"><span class="screen-reader-text">%5$s</span><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div><div class="purplish-size-guide__body">%6$s%7$s</div></dialog>',
			esc_attr( $id ),
			esc_attr( 'purplish-size-guide' . ( $card ? ' purplish-size-guide--card' : '' ) ),
			esc_html__( 'Size guide', 'purplish' ),
			esc_html( $product->get_name() ),
			esc_html__( 'Close size guide', 'purplish' ),
			'' === $note ? '' : SizeChart::fit( $note ),
			$table
		);
	}

	/**
	 * Prints the dialogs, once each.
	 *
	 * @return void
	 */
	public function print_dialogs() {
		foreach ( self::$dialogs as $id => $dialog ) {
			// Built in dialog() from escaped parts and rendered blocks.
			echo $dialog; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			self::$dialogs[ $id ] = '';
		}
		self::$dialogs = array_filter( self::$dialogs );
	}
}
