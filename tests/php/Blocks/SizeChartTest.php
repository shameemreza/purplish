<?php
/**
 * Tests for the Size Chart block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Registry;
use Purplish\SizeCharts;
use Purplish\Tests\Support\Products;
use WP_Block;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * The block prints its own table with both units, the unit switch, How to
 * measure notes and a figure with tape lines.
 */
class SizeChartTest extends WP_UnitTestCase {

	const NAME = 'purplish/size-chart';

	/**
	 * Clears any chart context a test left.
	 */
	public function tear_down() {
		while ( SizeCharts::current() ) {
			SizeCharts::pop();
		}
		parent::tear_down();
	}

	/**
	 * A tops chart: two measurements, one label.
	 *
	 * @param array $extra Attributes to add or change.
	 * @return array
	 */
	private function chart( $extra = array() ) {
		return array_merge(
			array(
				'columns' => array(
					array(
						'name' => 'Chest',
						'kind' => 'measure',
						'line' => 'auto',
						'note' => 'Around the fullest part of your chest.',
					),
					array(
						'name' => 'Waist',
						'kind' => 'measure',
						'line' => 'auto',
						'note' => '',
					),
					array(
						'name' => 'EU',
						'kind' => 'label',
						'line' => 'auto',
						'note' => '',
					),
				),
				'rows'    => array(
					array( 'S', '86-91', '70', '46' ),
					array( 'M', '92-97', '76,5', '48' ),
				),
			),
			$extra
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $context    Block context.
	 * @return string
	 */
	private function render( $attributes, $context = array() ) {
		$parsed = parse_blocks( '<!-- wp:purplish/size-chart ' . wp_json_encode( (object) $attributes ) . ' /-->' );
		return ( new WP_Block( $parsed[0], $context ) )->render();
	}

	/**
	 * A variable product with a Size attribute and a picked chart.
	 *
	 * @param string $content Chart pattern content, or '' for no chart.
	 * @param string $note    Fit note.
	 * @return array Block context for the product.
	 */
	private function product_with_chart( $content, $note = 'Runs small, size up.' ) {
		$variations = Products::sweater();
		$product_id = reset( $variations )->get_parent_id();
		if ( '' !== $content ) {
			$chart = self::factory()->post->create(
				array(
					'post_type'    => 'wp_block',
					'post_title'   => 'Tops',
					'post_status'  => 'publish',
					'post_content' => $content,
				)
			);
			update_post_meta( $product_id, SizeCharts::META, (string) $chart );
		}
		update_post_meta( $product_id, SizeCharts::FIT_META, $note );
		return array(
			'postId'   => $product_id,
			'postType' => 'product',
		);
	}

	/**
	 * A chart pattern's content.
	 *
	 * @return string
	 */
	private function chart_content() {
		return '<!-- wp:purplish/size-chart ' . wp_json_encode( $this->chart() ) . ' /-->';
	}

	/**
	 * Registered from the build, server rendered, with its front script,
	 * styles and the Striped style.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Size Chart', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic() );
		$this->assertSame( 'own', $type->attributes['source']['default'] );
		$this->assertSame( 'cm', $type->attributes['unit']['default'] );
		$this->assertTrue( $type->attributes['switch']['default'] );
		$this->assertTrue( $type->attributes['pick']['default'] );
		$this->assertTrue( $type->attributes['fitnote']['default'] );
		$this->assertSame( 'auto', $type->attributes['figure']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertContains( 'striped', wp_list_pluck( $type->styles, 'name' ) );
	}

	/**
	 * The table: a caption, column and row headers, both units in each
	 * measurement cell, labels as typed.
	 */
	public function test_prints_the_table_in_both_units() {
		$html = $this->render( $this->chart() );

		$this->assertStringContainsString( 'wp-block-purplish-size-chart', $html );
		$this->assertMatchesRegularExpression( '#<caption class="screen-reader-text">[^<]+</caption>#', $html );
		$this->assertStringContainsString( '<th scope="col">Size</th>', $html );
		$this->assertMatchesRegularExpression( '#<th scope="col" data-m="c1">Chest<span class="purplish-size-chart__u">#', $html );
		$this->assertStringContainsString( '<th scope="row">S</th>', $html );
		$this->assertStringContainsString( 'data-cm="86-91" data-in="34-36"', $html );
		$this->assertStringContainsString( 'data-cm="76.5" data-in="30"', $html );
		$this->assertStringContainsString( '<td data-m="c3">46</td>', $html );
	}

	/**
	 * The cm/in switch: radios in a fieldset with a hidden legend, cm first
	 * by default, in first when picked, and none when it's off.
	 */
	public function test_prints_the_unit_switch() {
		$html = $this->render( $this->chart() );
		$this->assertStringContainsString( '<fieldset class="purplish-size-chart__units">', $html );
		$this->assertMatchesRegularExpression( '#<legend class="screen-reader-text">#', $html );
		$this->assertMatchesRegularExpression( '#value="cm" checked#', $html );
		$this->assertStringContainsString( 'data-unit="cm"', $html );

		$html = $this->render( $this->chart( array( 'unit' => 'in' ) ) );
		$this->assertMatchesRegularExpression( '#value="in" checked#', $html );
		$this->assertStringContainsString( 'data-unit="in"', $html );

		$html = $this->render( $this->chart( array( 'switch' => false ) ) );
		$this->assertStringNotContainsString( '<fieldset', $html );
		$this->assertStringContainsString( 'data-cm="86-91"', $html );
	}

	/**
	 * Two charts on a page don't share their radios.
	 */
	public function test_each_chart_gets_its_own_radio_names() {
		preg_match( '#name="([^"]+)" value="cm"#', $this->render( $this->chart() ), $one );
		preg_match( '#name="([^"]+)" value="cm"#', $this->render( $this->chart() ), $two );

		$this->assertNotEmpty( $one[1] );
		$this->assertNotSame( $one[1], $two[1] );
	}

	/**
	 * Nothing prints without rows.
	 */
	public function test_prints_nothing_without_rows() {
		$this->assertSame( '', $this->render( $this->chart( array( 'rows' => array() ) ) ) );
		$this->assertSame( '', $this->render( array() ) );
	}

	/**
	 * Sizes are pick buttons only on a product's chart, with the option on.
	 */
	public function test_pick_buttons_need_a_product() {
		$this->assertStringNotContainsString( 'purplish-size-chart__pick', $this->render( $this->chart() ) );

		SizeCharts::push(
			array(
				'product'   => 12,
				'attribute' => 'pa_size',
			)
		);
		$html = $this->render( $this->chart() );
		$this->assertStringContainsString( '<button type="button" class="purplish-size-chart__pick" data-size="S">', $html );
		$this->assertStringContainsString( 'data-chips="attribute_pa_size"', $html );

		$html = $this->render( $this->chart( array( 'pick' => false ) ) );
		$this->assertStringNotContainsString( 'purplish-size-chart__pick', $html );
	}

	/**
	 * A product's settings win over the chart's own.
	 */
	public function test_product_overrides_win() {
		SizeCharts::push(
			array(
				'product'   => 12,
				'attribute' => 'pa_size',
				'overrides' => array(
					'unit'   => 'in',
					'pick'   => false,
					'switch' => false,
				),
			)
		);
		$html = $this->render( $this->chart() );

		$this->assertStringContainsString( 'data-unit="in"', $html );
		$this->assertStringNotContainsString( 'purplish-size-chart__pick', $html );
		$this->assertStringNotContainsString( '<fieldset', $html );
	}

	/**
	 * How to measure: a note per column, and the figure with a tape line
	 * per column that draws one.
	 */
	public function test_prints_how_to_measure() {
		$html = $this->render( $this->chart() );

		$this->assertStringContainsString( 'purplish-size-chart__measure', $html );
		$this->assertMatchesRegularExpression( '#<div class="purplish-size-chart__note" data-m="c1"><dt>Chest#', $html );
		$this->assertStringContainsString( '<dd>Around the fullest part of your chest.</dd>', $html );
		$this->assertStringNotContainsString( 'data-m="c3"><dt>', $html, 'labels get no note' );
		$this->assertStringContainsString( '<svg class="purplish-size-chart__figure-svg"', $html );
		$this->assertStringContainsString( '<g class="purplish-size-chart__tape" data-m="c1">', $html );
		$this->assertStringContainsString( '<g class="purplish-size-chart__tape" data-m="c2">', $html );
		$this->assertStringContainsString( 'purplish-size-chart--figure-top', $html );
	}

	/**
	 * The bottom figure for an inseam, and none when it's off.
	 */
	public function test_figure_options() {
		$bottoms = $this->chart(
			array(
				'columns' => array(
					array(
						'name' => 'Inseam',
						'kind' => 'measure',
					),
				),
				'rows'    => array( array( '30', '76' ) ),
			)
		);
		$this->assertStringContainsString( 'purplish-size-chart--figure-bottom', $this->render( $bottoms ) );

		$html = $this->render( $this->chart( array( 'figure' => 'none' ) ) );
		$this->assertStringNotContainsString( '<svg', $html );
		$this->assertStringContainsString( '<dd>Around the fullest part of your chest.</dd>', $html, 'notes stay' );
	}

	/**
	 * No notes and no figure means no How to measure part.
	 */
	public function test_no_measure_part_without_notes_or_figure() {
		$columns = array(
			array(
				'name' => 'EU',
				'kind' => 'label',
			),
		);
		$html    = $this->render(
			$this->chart(
				array(
					'columns' => $columns,
					'rows'    => array( array( 'S', '46' ) ),
				)
			)
		);
		$this->assertStringNotContainsString( 'purplish-size-chart__measure', $html );
	}

	/**
	 * Typed text loses its tags and is escaped.
	 */
	public function test_escapes_typed_text() {
		$html = $this->render(
			$this->chart(
				array(
					'rows' => array( array( 'S <img src=x onerror=alert(1)>', '86', '70', 'A & "B"' ) ),
				)
			)
		);
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( '<th scope="row">S</th>', $html );
		$this->assertStringContainsString( 'A &amp; &quot;B&quot;', $html );
	}

	/**
	 * Product mode prints the fit note and the product's chart, with pick
	 * buttons for the product's size attribute.
	 */
	public function test_product_source_prints_the_chart_and_fit_note() {
		$context = $this->product_with_chart( $this->chart_content() );
		$html    = $this->render( array( 'source' => 'product' ), $context );

		$this->assertStringContainsString( 'purplish-size-chart--product', $html );
		$this->assertMatchesRegularExpression( '#<p class="purplish-size-chart__fit"><span class="purplish-size-chart__fit-label">Fit</span> Runs small, size up\.</p>#', $html );
		$this->assertStringContainsString( 'data-cm="86-91" data-in="34-36"', $html );
		$this->assertStringContainsString( 'data-chips="attribute_size"', $html );
		$this->assertStringContainsString( 'class="purplish-size-chart__pick" data-size="S"', $html );
		$this->assertEmpty( SizeCharts::current(), 'the context is popped' );

		$html = $this->render(
			array(
				'source'  => 'product',
				'fitnote' => false,
			),
			$context
		);
		$this->assertStringNotContainsString( 'purplish-size-chart__fit', $html );
		$this->assertStringContainsString( 'data-cm="86-91"', $html );
	}

	/**
	 * Settings saved on the product-mode block win over the chart's own;
	 * ones left alone keep the chart's.
	 */
	public function test_product_source_settings_reach_the_chart() {
		$context = $this->product_with_chart( '<!-- wp:purplish/size-chart ' . wp_json_encode( $this->chart( array( 'switch' => false ) ) ) . ' /-->' );

		$html = $this->render(
			array(
				'source' => 'product',
				'unit'   => 'in',
				'pick'   => false,
			),
			$context
		);
		$this->assertStringContainsString( 'data-unit="in"', $html );
		$this->assertStringNotContainsString( 'purplish-size-chart__pick', $html );
		$this->assertStringNotContainsString( '<fieldset', $html, 'the chart turned its switch off' );
	}

	/**
	 * No chart, no product or no fit note prints only what there is.
	 */
	public function test_product_source_without_a_chart() {
		$this->assertSame( '', $this->render( array( 'source' => 'product' ), $this->product_with_chart( '' ) ) );
		$this->assertSame( '', $this->render( array( 'source' => 'product' ) ) );

		$html = $this->render( array( 'source' => 'product' ), $this->product_with_chart( $this->chart_content(), '' ) );
		$this->assertStringNotContainsString( 'purplish-size-chart__fit', $html );
		$this->assertStringContainsString( 'data-cm="86-91"', $html );
	}

	/**
	 * A chart that holds a product-mode block doesn't loop.
	 */
	public function test_product_source_does_not_loop() {
		$context = $this->product_with_chart( '<!-- wp:purplish/size-chart {"source":"product"} /-->' . $this->chart_content() );
		$html    = $this->render( array( 'source' => 'product' ), $context );

		$this->assertSame( 1, substr_count( $html, 'purplish-size-chart--product' ) );
		$this->assertSame( 1, substr_count( $html, '<table' ) );
		$this->assertStringNotContainsString( 'halted', $html );
	}
}
