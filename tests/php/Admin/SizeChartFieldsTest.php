<?php
/**
 * Tests for the size chart fields in the product editor and on product
 * categories.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Admin;

use Purplish\Admin\SizeChartFields;
use Purplish\SizeCharts;
use Purplish\Tests\SizeChartsTest;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * The product box picks a chart and a fit note; the category field picks a
 * chart; "New chart" opens the pattern editor with the starter chart.
 */
class SizeChartFieldsTest extends WP_UnitTestCase {

	/**
	 * The fields.
	 *
	 * @var SizeChartFields
	 */
	private $fields;

	/**
	 * Signs in a shop manager.
	 */
	public function set_up() {
		parent::set_up();
		$this->fields = new SizeChartFields();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Clears the form and the query.
	 */
	public function tear_down() {
		$_POST = array();
		$_GET  = array();
		parent::tear_down();
	}

	/**
	 * A variable product's ID.
	 *
	 * @return int
	 */
	private function product_id() {
		$variations = Products::sweater();
		return reset( $variations )->get_parent_id();
	}

	/**
	 * Posts the product box.
	 *
	 * @param array $fields Posted fields.
	 * @return void
	 */
	private function post( $fields ) {
		$_POST = array_merge( array( 'purplish_size_chart_nonce' => wp_create_nonce( SizeChartFields::NONCE ) ), $fields );
	}

	/**
	 * Prints the box output.
	 *
	 * @param int $id Product ID.
	 * @return string
	 */
	private function box( $id ) {
		ob_start();
		$this->fields->render_box( get_post( $id ) );
		return ob_get_clean();
	}

	/**
	 * Adds the box to the product sidebar and the fields to categories.
	 */
	public function test_registers() {
		$this->fields->register();

		$this->assertSame( 10, has_action( 'add_meta_boxes_product', array( $this->fields, 'add_box' ) ) );
		$this->assertSame( 10, has_action( 'save_post_product', array( $this->fields, 'save_product' ) ) );
		$this->assertSame( 10, has_action( 'product_cat_add_form_fields', array( $this->fields, 'category_add_field' ) ) );
		$this->assertSame( 10, has_action( 'product_cat_edit_form_fields', array( $this->fields, 'category_edit_field' ) ) );
		$this->assertSame( 10, has_action( 'created_product_cat', array( $this->fields, 'save_term' ) ) );
		$this->assertSame( 10, has_action( 'edited_product_cat', array( $this->fields, 'save_term' ) ) );
		$this->assertSame( 10, has_filter( 'default_content', array( $this->fields, 'default_content' ) ) );
		$this->assertSame( 10, has_filter( 'default_title', array( $this->fields, 'default_title' ) ) );
	}

	/**
	 * The box: a nonce, the chart select with the category's chart named,
	 * every chart and None, links, and the fit note.
	 */
	public function test_box_prints_the_fields() {
		$tops = SizeChartsTest::pattern( 'Tops' );
		$coat = SizeChartsTest::pattern( 'Coats' );
		$term = wp_insert_term( 'Coats', 'product_cat' );
		update_term_meta( $term['term_id'], SizeCharts::TERM_META, $coat );
		$id = $this->product_id();
		wp_set_object_terms( $id, array( $term['term_id'] ), 'product_cat' );
		update_post_meta( $id, SizeCharts::FIT_META, 'Runs small.' );

		$html = $this->box( $id );
		$this->assertStringContainsString( 'name="purplish_size_chart_nonce"', $html );
		$this->assertStringContainsString( '<select name="purplish_size_chart" id="purplish_size_chart"', $html );
		$this->assertStringContainsString( '<option value="" selected=\'selected\'>Category&#039;s chart (Coats)</option>', $html );
		$this->assertStringContainsString( '<option value="' . $tops . '">Tops</option>', $html );
		$this->assertStringContainsString( '<option value="none">None</option>', $html );
		$this->assertStringContainsString( 'post-new.php?post_type=wp_block&#038;purplish_size_chart=new', $html );
		$this->assertMatchesRegularExpression( '#<textarea[^>]+name="purplish_fit_note"[^>]*>Runs small\.</textarea>#', $html );
		$this->assertStringContainsString( 'post.php?post=' . $coat . '&#038;action=edit', $html, 'edit link for the chart that shows' );

		update_post_meta( $id, SizeCharts::META, 'none' );
		$html = $this->box( $id );
		$this->assertStringContainsString( '<option value="none" selected=\'selected\'>None</option>', $html );
		$this->assertStringNotContainsString( 'action=edit', $html );
	}

	/**
	 * Without a category chart the first option says so.
	 */
	public function test_box_without_a_category_chart() {
		$this->assertStringContainsString( 'Category&#039;s chart (none set)', $this->box( $this->product_id() ) );
	}

	/**
	 * Saves a chart ID, None or empty, and the fit note.
	 */
	public function test_saves_the_product() {
		$tops = SizeChartsTest::pattern( 'Tops' );
		$id   = $this->product_id();

		$this->post(
			array(
				'purplish_size_chart' => (string) $tops,
				'purplish_fit_note'   => "Runs small.\n<b>Size up</b>",
			)
		);
		$this->fields->save_product( $id );
		$this->assertSame( (string) $tops, get_post_meta( $id, SizeCharts::META, true ) );
		$this->assertSame( "Runs small.\nSize up", get_post_meta( $id, SizeCharts::FIT_META, true ) );

		$this->post( array( 'purplish_size_chart' => 'none' ) );
		$this->fields->save_product( $id );
		$this->assertSame( 'none', get_post_meta( $id, SizeCharts::META, true ) );
		$this->assertSame( '', get_post_meta( $id, SizeCharts::FIT_META, true ), 'an empty note is removed' );

		$this->post( array( 'purplish_size_chart' => '' ) );
		$this->fields->save_product( $id );
		$this->assertFalse( metadata_exists( 'post', $id, SizeCharts::META ) );
	}

	/**
	 * An unknown ID, a bad nonce or a user who can't edit saves nothing.
	 */
	public function test_save_checks() {
		$id   = $this->product_id();
		$post = self::factory()->post->create();

		$this->post( array( 'purplish_size_chart' => (string) $post ) );
		$this->fields->save_product( $id );
		$this->assertFalse( metadata_exists( 'post', $id, SizeCharts::META ), 'a post is not a chart' );

		$tops  = SizeChartsTest::pattern( 'Tops' );
		$_POST = array(
			'purplish_size_chart_nonce' => 'nope',
			'purplish_size_chart'       => (string) $tops,
		);
		$this->fields->save_product( $id );
		$this->assertFalse( metadata_exists( 'post', $id, SizeCharts::META ), 'bad nonce' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->post( array( 'purplish_size_chart' => (string) $tops ) );
		$this->fields->save_product( $id );
		$this->assertFalse( metadata_exists( 'post', $id, SizeCharts::META ), 'no edit_product' );
	}

	/**
	 * The category add and edit screens print the select.
	 */
	public function test_category_fields() {
		$tops = SizeChartsTest::pattern( 'Tops' );
		ob_start();
		$this->fields->category_add_field();
		$add = ob_get_clean();
		$this->assertStringContainsString( 'name="purplish_size_chart_nonce"', $add );
		$this->assertStringContainsString( '<option value="' . $tops . '">Tops</option>', $add );
		$this->assertStringContainsString( 'purplish_size_chart=new', $add );

		$term = wp_insert_term( 'Tees', 'product_cat' );
		update_term_meta( $term['term_id'], SizeCharts::TERM_META, $tops );
		ob_start();
		$this->fields->category_edit_field( get_term( $term['term_id'] ) );
		$edit = ob_get_clean();
		$this->assertStringContainsString( '<tr class="form-field term-purplish-size-chart-wrap">', $edit );
		$this->assertStringContainsString( '<option value="' . $tops . '" selected=\'selected\'>Tops</option>', $edit );
	}

	/**
	 * Saving a category needs the nonce and manage_product_terms.
	 */
	public function test_saves_the_term() {
		$tops = SizeChartsTest::pattern( 'Tops' );
		$term = wp_insert_term( 'Tees', 'product_cat' );
		$id   = $term['term_id'];

		$this->post( array( 'purplish_size_chart' => (string) $tops ) );
		$this->fields->save_term( $id );
		$this->assertSame( (string) $tops, get_term_meta( $id, SizeCharts::TERM_META, true ) );

		$this->post( array( 'purplish_size_chart' => '' ) );
		$this->fields->save_term( $id );
		$this->assertFalse( metadata_exists( 'term', $id, SizeCharts::TERM_META ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->post( array( 'purplish_size_chart' => (string) $tops ) );
		$this->fields->save_term( $id );
		$this->assertFalse( metadata_exists( 'term', $id, SizeCharts::TERM_META ) );
	}

	/**
	 * "New chart" fills a new pattern with the starter chart.
	 */
	public function test_new_chart_prefill() {
		$block = get_default_post_to_edit( 'wp_block' );
		$post  = get_default_post_to_edit( 'post' );

		$this->assertSame( '', $this->fields->default_content( '', $block ) );

		$_GET['purplish_size_chart'] = 'new';
		$content                     = $this->fields->default_content( '', $block );
		$this->assertStringContainsString( '<!-- wp:purplish/size-chart', $content );
		$this->assertSame( SizeCharts::starter(), $content );
		$this->assertSame( 'Size chart', $this->fields->default_title( '', $block ) );
		$this->assertSame( '', $this->fields->default_content( '', $post ) );
		$this->assertSame( '', $this->fields->default_title( '', $post ) );
	}
}
