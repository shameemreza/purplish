<?php
/**
 * Tests for the Size guide link, the fit note under the size options and
 * the size guide dialog.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\SizeGuide;
use Purplish\SizeCharts;
use Purplish\Tests\SizeChartsTest;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * Renders the Variation Selector attribute block on a variable product
 * with Color and Size.
 */
class SizeGuideTest extends WP_UnitTestCase {

	/**
	 * The variable product.
	 *
	 * @var \WC_Product_Variable
	 */
	private $product;

	/**
	 * The chart pattern.
	 *
	 * @var int
	 */
	private $chart;

	/**
	 * A product with a chart and a fit note, as the global product.
	 */
	public function set_up() {
		parent::set_up();
		SizeGuide::reset();
		$variations    = Products::sweater();
		$this->product = wc_get_product( reset( $variations )->get_parent_id() );
		$this->chart   = SizeChartsTest::pattern( 'Tops' );
		update_post_meta( $this->product->get_id(), SizeCharts::META, (string) $this->chart );
		update_post_meta( $this->product->get_id(), SizeCharts::FIT_META, 'Runs small, size up.' );
		$GLOBALS['product'] = $this->product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.
	}

	/**
	 * Clears the global product and the dialogs.
	 */
	public function tear_down() {
		unset( $GLOBALS['product'] );
		SizeGuide::reset();
		parent::tear_down();
	}

	/**
	 * One row as WooCommerce 11.3 prints it: the attribute name, the chips
	 * and the hidden input, inside the row's wrapper.
	 *
	 * @param string $label Attribute label.
	 * @param string $field Field name, like attribute_size.
	 * @return string
	 */
	private function row( $label, $field ) {
		return '<div data-block-name="' . SizeGuide::BLOCK . '" class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute" data-wp-interactive="woocommerce/add-to-cart-with-options">'
			. '<div class="wp-block-group is-vertical is-layout-flex">'
			. '<span data-block-name="woocommerce/add-to-cart-with-options-variation-selector-attribute-name" class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute-name" id="wc_product_attribute_1_label">' . $label . '</span>'
			. '<div class="wp-block-woocommerce-product-filter-chips" role="radiogroup"><fieldset><button type="button" role="radio" value="S">S</button></fieldset></div>'
			. '</div>'
			. "\n\t\t" . '<input type="hidden" name="' . $field . '" value="" data-wp-bind--value="context.selectedValue" /></div>';
	}

	/**
	 * Runs the attribute block's rows through the filter.
	 *
	 * @param array $purplish The block's Purplish options.
	 * @return string
	 */
	private function render( $purplish = array() ) {
		$block = array(
			'blockName' => SizeGuide::BLOCK,
			'attrs'     => $purplish ? array( 'purplish' => $purplish ) : array(),
		);
		return ( new SizeGuide() )->add_link( $this->row( 'Color', 'attribute_color' ) . $this->row( 'Size', 'attribute_size' ), $block );
	}

	/**
	 * The footer output.
	 *
	 * @return string
	 */
	private function footer() {
		ob_start();
		( new SizeGuide() )->print_dialogs();
		return ob_get_clean();
	}

	/**
	 * Hooks into the attribute block after BlockEffects, and the footer.
	 */
	public function test_registers() {
		$guide = new SizeGuide();
		$guide->register();
		$this->assertSame( 20, has_filter( 'render_block_' . SizeGuide::BLOCK, array( $guide, 'add_link' ) ) );
		$this->assertSame( 10, has_action( 'wp_footer', array( $guide, 'print_dialogs' ) ) );
	}

	/**
	 * The link sits beside the Size name; the Color row is untouched.
	 */
	public function test_adds_the_link_beside_the_size_name() {
		$html = $this->render();
		$id   = 'purplish-size-guide-' . $this->product->get_id();

		$this->assertMatchesRegularExpression( '#<div class="purplish-size-guide__row"><span[^>]+attribute-name[^>]*>Size</span><button type="button" class="purplish-size-guide__link" command="show-modal" commandfor="' . $id . '" aria-haspopup="dialog">#', $html );
		$this->assertStringContainsString( '<span class="purplish-size-guide__text">Size guide</span>', $html );
		$this->assertSame( 1, substr_count( $html, 'purplish-size-guide__link' ) );
		$this->assertMatchesRegularExpression( '#<span[^>]+attribute-name[^>]*>Color</span>(?!</div>)#', $html );
	}

	/**
	 * The link text and the attribute can be changed.
	 */
	public function test_custom_text_and_attribute() {
		$html = $this->render(
			array(
				'sizelabel' => 'Fit guide',
				'sizeattr'  => 'color',
			)
		);
		$this->assertStringContainsString( '<span class="purplish-size-guide__text">Fit guide</span>', $html );
		$this->assertMatchesRegularExpression( '#attribute-name[^>]*>Color</span><button#', $html );
		$this->assertDoesNotMatchRegularExpression( '#attribute-name[^>]*>Size</span><button#', $html );
		$this->assertMatchesRegularExpression( '#<p class="purplish-size-guide__fit">.*?</p><input type="hidden" name="attribute_color"#', $html );
	}

	/**
	 * The fit note sits under the Size options, before the hidden input.
	 */
	public function test_fit_note_before_the_hidden_input() {
		$html = $this->render();
		$this->assertMatchesRegularExpression( '#<p class="purplish-size-guide__fit"><span class="purplish-size-guide__fit-label">Fit</span> Runs small, size up\.</p><input type="hidden" name="attribute_size"#', $html );
		$this->assertSame( 1, substr_count( $html, 'purplish-size-guide__fit"' ) );
	}

	/**
	 * One dialog in the footer, even with two attribute blocks, holding the
	 * chart and a close button.
	 */
	public function test_prints_one_dialog() {
		$this->render();
		$this->render();
		$html = $this->footer();
		$id   = 'purplish-size-guide-' . $this->product->get_id();

		$this->assertSame( 1, substr_count( $html, '<dialog' ) );
		$this->assertStringContainsString( '<dialog id="' . $id . '" class="purplish-size-guide" closedby="any" aria-labelledby="' . $id . '-title">', $html );
		$this->assertStringContainsString( '<h2 class="purplish-size-guide__title" id="' . $id . '-title">Size guide</h2>', $html );
		$this->assertStringContainsString( '<button type="button" class="purplish-size-guide__close" command="close" commandfor="' . $id . '">', $html );
		$this->assertStringContainsString( '<table', $html );
		$this->assertStringContainsString( 'data-chips="attribute_size"', $html );
		$this->assertStringContainsString( 'class="purplish-size-chart__fit"', $html );
		$this->assertSame( '', $this->footer(), 'printed once' );
	}

	/**
	 * The phone card look adds a class.
	 */
	public function test_card_on_phones() {
		$this->render( array( 'sizesheet' => 'card' ) );
		$this->assertStringContainsString( 'class="purplish-size-guide purplish-size-guide--card"', $this->footer() );
	}

	/**
	 * No chart, the link off or a simple product: nothing added.
	 */
	public function test_nothing_without_a_chart_or_with_the_link_off() {
		$plain = $this->render( array( 'sizeguide' => false ) );
		$this->assertStringNotContainsString( 'purplish-size-guide', $plain );
		$this->assertSame( '', $this->footer() );

		update_post_meta( $this->product->get_id(), SizeCharts::META, 'none' );
		$html = $this->render();
		$this->assertStringNotContainsString( 'purplish-size-guide', $html );
		$this->assertSame( '', $this->footer() );

		$this->assertSame( '', ( new SizeGuide() )->add_link( '', array( 'blockName' => SizeGuide::BLOCK ) ) );
		$GLOBALS['product'] = wc_get_product( self::factory()->post->create( array( 'post_type' => 'product' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.
		$this->assertSame( '<div>x</div>', ( new SizeGuide() )->add_link( '<div>x</div>', array( 'blockName' => SizeGuide::BLOCK ) ) );
	}
}
