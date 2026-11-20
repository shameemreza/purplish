<?php
/**
 * Tests for size charts: the table math, the chart patterns and which
 * chart a product shows.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\SizeCharts;
use Purplish\Tests\Support\Products;
use WC_Product_Attribute;
use WC_Product_Variable;
use WP_UnitTestCase;

/**
 * The math matches the editor's (tests/js/size-chart-table.test.js). Charts
 * are synced patterns tagged "Size charts"; a product shows its own pick,
 * then its deepest category's chart.
 */
class SizeChartsTest extends WP_UnitTestCase {

	/**
	 * Drops the filters a test added.
	 */
	public function tear_down() {
		remove_all_filters( 'purplish_size_chart' );
		remove_all_filters( 'purplish_size_attribute' );
		parent::tear_down();
	}

	/**
	 * A published synced pattern.
	 *
	 * @param string $title   Title.
	 * @param bool   $chart   Whether it holds a Size Chart block.
	 * @param string $status  Post status.
	 * @return int Pattern ID.
	 */
	public static function pattern( $title, $chart = true, $status = 'publish' ) {
		$content = $chart ? '<!-- wp:purplish/size-chart {"columns":[{"name":"Chest","kind":"measure"}],"rows":[["S","86-91"]]} /-->' : '<!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph -->';
		return self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_title'   => $title,
				'post_status'  => $status,
				'post_content' => $content,
			)
		);
	}

	/**
	 * A product category, optionally with a chart and a parent.
	 *
	 * @param string $name   Name.
	 * @param int    $chart  Chart pattern ID, or 0.
	 * @param int    $under  Parent term ID.
	 * @return int Term ID.
	 */
	private function category( $name, $chart = 0, $under = 0 ) {
		$term = wp_insert_term( $name, 'product_cat', array( 'parent' => $under ) );
		if ( $chart ) {
			update_term_meta( $term['term_id'], SizeCharts::TERM_META, $chart );
		}
		return $term['term_id'];
	}

	/**
	 * A variable product with a Size attribute in the given categories.
	 *
	 * @param int[] $categories Term IDs.
	 * @return \WC_Product_Variable
	 */
	private function product( $categories = array() ) {
		$variations = Products::sweater();
		$product    = wc_get_product( reset( $variations )->get_parent_id() );
		$product->set_category_ids( $categories );
		$product->save();
		return $product;
	}

	/**
	 * Numbers and ranges, with commas or dots.
	 */
	public function test_parse_value_reads_numbers_and_ranges() {
		$this->assertSame(
			array(
				'min' => 86.0,
				'max' => 91.0,
			),
			SizeCharts::parse_value( '86-91' )
		);
		$this->assertSame(
			array(
				'min' => 64.5,
				'max' => 64.5,
			),
			SizeCharts::parse_value( '64,5' )
		);
		$this->assertSame(
			array(
				'min' => 86.0,
				'max' => 91.0,
			),
			SizeCharts::parse_value( '86 – 91' )
		);
		$this->assertNull( SizeCharts::parse_value( 'XL' ) );
		$this->assertNull( SizeCharts::parse_value( '' ) );
	}

	/**
	 * Inches round to the nearest half.
	 */
	public function test_inches_round_to_the_nearest_half() {
		$this->assertSame( 34.0, SizeCharts::inches( 86 ) );
		$this->assertSame( 36.0, SizeCharts::inches( 91 ) );
		$this->assertSame( 25.5, SizeCharts::inches( 64.5 ) );
	}

	/**
	 * A cell gives both units, or null for text.
	 */
	public function test_cell_gives_both_units() {
		$this->assertSame(
			array(
				'cm' => '86-91',
				'in' => '34-36',
			),
			SizeCharts::cell( '86-91' )
		);
		$this->assertSame(
			array(
				'cm' => '64.5',
				'in' => '25.5',
			),
			SizeCharts::cell( '64,5' )
		);
		$this->assertNull( SizeCharts::cell( 'n/a' ) );
	}

	/**
	 * The tape line comes from the pick, or from the column's name.
	 */
	public function test_line_for_guesses_from_the_name() {
		$this->assertSame(
			'chest',
			SizeCharts::line_for(
				array(
					'name' => 'Bust',
					'kind' => 'measure',
					'line' => 'auto',
				)
			)
		);
		$this->assertSame(
			'inseam',
			SizeCharts::line_for(
				array(
					'name' => 'Inside leg',
					'kind' => 'measure',
					'line' => 'auto',
				)
			)
		);
		$this->assertSame( 'hips', SizeCharts::line_for( array( 'name' => 'Hips' ) ) );
		$this->assertSame(
			'sleeve',
			SizeCharts::line_for(
				array(
					'name' => 'Length',
					'kind' => 'measure',
					'line' => 'sleeve',
				)
			)
		);
		$this->assertSame(
			'none',
			SizeCharts::line_for(
				array(
					'name' => 'EU',
					'kind' => 'label',
					'line' => 'auto',
				)
			)
		);
		$this->assertSame(
			'none',
			SizeCharts::line_for(
				array(
					'name' => 'Chest',
					'kind' => 'measure',
					'line' => 'elbow',
				)
			)
		);
	}

	/**
	 * Auto shows the bottom for an inseam, the top for other lines.
	 */
	public function test_figure_for_picks_from_the_lines() {
		$tops    = array(
			array(
				'name' => 'Chest',
				'kind' => 'measure',
				'line' => 'auto',
			),
		);
		$bottoms = array(
			array(
				'name' => 'Waist',
				'kind' => 'measure',
				'line' => 'auto',
			),
			array(
				'name' => 'Inseam',
				'kind' => 'measure',
				'line' => 'auto',
			),
		);
		$this->assertSame( 'bottom', SizeCharts::figure_for( 'auto', $bottoms ) );
		$this->assertSame( 'top', SizeCharts::figure_for( 'auto', $tops ) );
		$this->assertSame(
			'none',
			SizeCharts::figure_for(
				'auto',
				array(
					array(
						'name' => 'EU',
						'kind' => 'label',
					),
				)
			)
		);
		$this->assertSame( 'bottom', SizeCharts::figure_for( 'bottom', $tops ) );
		$this->assertSame( 'none', SizeCharts::figure_for( 'none', $tops ) );
		$this->assertSame( 'none', SizeCharts::figure_for( 'sideways', $tops ) );
	}

	/**
	 * Saving a pattern with a Size Chart block tags it "Size charts".
	 */
	public function test_tags_chart_patterns() {
		$chart = self::pattern( 'Tops' );
		$other = self::pattern( 'Banner', false );

		$terms = wp_get_object_terms( $chart, 'wp_pattern_category' );
		$this->assertSame( array( SizeCharts::CATEGORY ), wp_list_pluck( $terms, 'slug' ) );
		$this->assertSame( 'Size charts', $terms[0]->name );
		$this->assertSame( array(), wp_get_object_terms( $other, 'wp_pattern_category' ) );
	}

	/**
	 * The charts list holds published tagged patterns by title.
	 */
	public function test_charts_lists_published_charts_by_title() {
		$tops    = self::pattern( 'Tops' );
		$bottoms = self::pattern( 'Bottoms' );
		self::pattern( 'Draft', true, 'draft' );
		self::pattern( 'Banner', false );

		$this->assertSame(
			array(
				$bottoms => 'Bottoms',
				$tops    => 'Tops',
			),
			SizeCharts::charts()
		);
	}

	/**
	 * The product's pick wins; None hides the category's chart.
	 */
	public function test_product_pick_wins() {
		$tops    = self::pattern( 'Tops' );
		$coats   = self::pattern( 'Coats' );
		$product = $this->product( array( $this->category( 'Coats', $coats ) ) );

		$this->assertSame( $coats, SizeCharts::chart_id( $product ) );

		update_post_meta( $product->get_id(), SizeCharts::META, (string) $tops );
		$this->assertSame( $tops, SizeCharts::chart_id( $product ) );

		update_post_meta( $product->get_id(), SizeCharts::META, 'none' );
		$this->assertSame( 0, SizeCharts::chart_id( $product ) );
	}

	/**
	 * The deepest category with a chart wins, then the lower term order.
	 */
	public function test_deepest_category_wins() {
		$parent_chart = self::pattern( 'Outerwear' );
		$child_chart  = self::pattern( 'Puffers' );
		$parent       = $this->category( 'Outerwear', $parent_chart );
		$child        = $this->category( 'Puffers', $child_chart, $parent );
		$product      = $this->product( array( $parent, $child ) );

		$this->assertSame( $child_chart, SizeCharts::chart_id( $product ) );

		$first  = self::pattern( 'First' );
		$second = self::pattern( 'Second' );
		$a      = $this->category( 'Knits', $second );
		$b      = $this->category( 'Tees', $first );
		update_term_meta( $a, 'order', 2 );
		update_term_meta( $b, 'order', 1 );
		$this->assertSame( $first, SizeCharts::chart_id( $this->product( array( $a, $b ) ) ) );
	}

	/**
	 * An ID that isn't a published chart pattern is skipped.
	 */
	public function test_skips_missing_charts() {
		$draft   = self::pattern( 'Draft', true, 'draft' );
		$coats   = self::pattern( 'Coats' );
		$post    = self::factory()->post->create();
		$product = $this->product( array( $this->category( 'Coats', $coats ) ) );

		update_post_meta( $product->get_id(), SizeCharts::META, (string) $draft );
		$this->assertSame( $coats, SizeCharts::chart_id( $product ), 'a draft falls back to the category' );

		update_post_meta( $product->get_id(), SizeCharts::META, (string) $post );
		$this->assertSame( $coats, SizeCharts::chart_id( $product ), 'a post is not a pattern' );

		$this->assertSame( 0, SizeCharts::chart_id( $this->product() ) );
	}

	/**
	 * A variation reads its parent, and the filter has the last word.
	 */
	public function test_variation_reads_parent_and_filter() {
		$coats      = self::pattern( 'Coats' );
		$variations = Products::sweater();
		$variation  = reset( $variations );
		update_post_meta( $variation->get_parent_id(), SizeCharts::META, (string) $coats );

		$this->assertSame( $coats, SizeCharts::chart_id( $variation ) );

		add_filter( 'purplish_size_chart', '__return_zero' );
		$this->assertSame( 0, SizeCharts::chart_id( $variation ) );
	}

	/**
	 * The fit note comes from the product, or its parent for a variation.
	 */
	public function test_fit_note() {
		$variations = Products::sweater();
		$variation  = reset( $variations );
		update_post_meta( $variation->get_parent_id(), SizeCharts::FIT_META, 'Runs small, size up.' );

		$this->assertSame( 'Runs small, size up.', SizeCharts::fit_note( $variation ) );
		$this->assertSame( 'Runs small, size up.', SizeCharts::fit_note( wc_get_product( $variation->get_parent_id() ) ) );
	}

	/**
	 * Auto finds the attribute with "size" in its name; a pick wins when
	 * the product has it; the filter can point elsewhere.
	 */
	public function test_size_attribute() {
		$product = $this->product();
		$this->assertSame( 'size', SizeCharts::size_attribute( $product ) );
		$this->assertSame( 'color', SizeCharts::size_attribute( $product, 'color' ) );
		$this->assertSame( 'size', SizeCharts::size_attribute( $product, 'pa_missing' ) );

		$shoe = new WC_Product_Attribute();
		$shoe->set_name( 'Shoe size' );
		$shoe->set_options( array( '38', '39' ) );
		$shoe->set_variation( true );
		$boots = new WC_Product_Variable();
		$boots->set_attributes( array( $shoe ) );
		$boots->save();
		$this->assertSame( 'shoe-size', SizeCharts::size_attribute( $boots ) );

		add_filter(
			'purplish_size_attribute',
			static function () {
				return 'color';
			}
		);
		$this->assertSame( 'color', SizeCharts::size_attribute( $product ) );
	}

	/**
	 * The editor's attribute choices list the store's global attributes.
	 */
	public function test_attribute_choices() {
		wc_create_attribute(
			array(
				'name' => 'Shoe size',
				'slug' => 'shoe-size',
			)
		);
		$this->assertContains(
			array(
				'value' => 'pa_shoe-size',
				'label' => 'Shoe size',
			),
			SizeCharts::attribute_choices()
		);
	}
}
