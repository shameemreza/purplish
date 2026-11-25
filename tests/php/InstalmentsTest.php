<?php
/**
 * Tests for the instalment settings and maths.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\Instalments;
use Purplish\Tests\Support\Products;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * Bands are cleaned and sorted, a price takes the highest band it reaches,
 * amounts round up, a rate always shows the total, and a product follows
 * its own setting, then its category, then the store.
 */
class InstalmentsTest extends WP_UnitTestCase {

	/**
	 * Puts options back after each test.
	 */
	public function tear_down() {
		delete_option( Instalments::OPTION );
		update_option( 'woocommerce_price_num_decimals', 2 );
		parent::tear_down();
	}

	/**
	 * A simple product.
	 *
	 * @param string $price Regular price.
	 * @param string $sale  Sale price, or ''.
	 * @return WC_Product_Simple
	 */
	private function product( $price, $sale = '' ) {
		$product = new WC_Product_Simple();
		$product->set_regular_price( $price );
		if ( '' !== $sale ) {
			$product->set_sale_price( $sale );
		}
		$product->save();
		return wc_get_product( $product->get_id() );
	}

	/**
	 * The line text without tags, with entities read.
	 *
	 * @param string $html Text HTML.
	 * @return string
	 */
	private function plain( $html ) {
		return html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES );
	}

	/**
	 * Nothing saved gives one 12 month band at 0%.
	 */
	public function test_defaults() {
		$settings = Instalments::settings();
		$this->assertSame(
			array(
				array(
					'from'   => 0.0,
					'months' => 12,
					'rate'   => 0.0,
				),
			),
			$settings['bands']
		);
		$this->assertSame( 0.0, $settings['minimum'] );
		$this->assertStringContainsString( '{amount}', $settings['wording'] );
	}

	/**
	 * Bands drop rows without months, keep rates in range, keep the last
	 * row for a repeated "From price", and sort by it.
	 */
	public function test_clean_bands() {
		$bands = Instalments::clean_bands(
			array(
				array(
					'from'   => '500',
					'months' => '24',
					'rate'   => '150',
				),
				array(
					'from'   => '0',
					'months' => '6',
					'rate'   => '-3',
				),
				array(
					'from'   => '200',
					'months' => '0',
					'rate'   => '0',
				),
				array(
					'from'   => '500',
					'months' => '18',
					'rate'   => '7.5',
				),
				'not a row',
			)
		);
		$this->assertSame(
			array(
				array(
					'from'   => 0.0,
					'months' => 6,
					'rate'   => 0.0,
				),
				array(
					'from'   => 500.0,
					'months' => 18,
					'rate'   => 7.5,
				),
			),
			$bands
		);
	}

	/**
	 * Text is stripped, an empty wording goes back to the default, the
	 * link takes only http(s) and the logo only images.
	 */
	public function test_clean_text_link_and_logo() {
		$clean = Instalments::clean(
			array(
				'wording'  => '',
				'zero'     => '<b>, interest free</b>',
				'link_url' => 'javascript:alert(1)',
				'logo'     => 999999,
				'minimum'  => '-5',
			)
		);
		$this->assertSame( Instalments::defaults()['wording'], $clean['wording'] );
		$this->assertSame( ', interest free', $clean['zero'] );
		$this->assertSame( '', $clean['link_url'] );
		$this->assertSame( 0, $clean['logo'] );
		$this->assertSame( 0.0, $clean['minimum'] );
	}

	/**
	 * A price takes the highest band it reaches; under the first band or
	 * the minimum it takes none.
	 */
	public function test_band_for() {
		$bands = Instalments::clean_bands(
			array(
				array(
					'from'   => 100,
					'months' => 3,
					'rate'   => 0,
				),
				array(
					'from'   => 500,
					'months' => 12,
					'rate'   => 0,
				),
				array(
					'from'   => 1000,
					'months' => 24,
					'rate'   => 8,
				),
			)
		);
		$this->assertNull( Instalments::band_for( 99.99, $bands ) );
		$this->assertSame( 3, Instalments::band_for( 100, $bands )['months'] );
		$this->assertSame( 3, Instalments::band_for( 499, $bands )['months'] );
		$this->assertSame( 12, Instalments::band_for( 500, $bands )['months'] );
		$this->assertSame( 24, Instalments::band_for( 5000, $bands )['months'] );
		$this->assertNull( Instalments::band_for( 400, $bands, 450 ), 'under the minimum' );
		$this->assertNull( Instalments::band_for( 0, Instalments::defaults()['bands'] ), 'no price' );
	}

	/**
	 * 0% splits the price and rounds up, so the months never add up to
	 * less than the price; an exact split stays exact.
	 */
	public function test_zero_rate_rounds_up() {
		$this->assertSame(
			array(
				'monthly' => 10.0,
				'total'   => 120.0,
			),
			Instalments::payment( 120, 12, 0, 2 )
		);
		$pay = Instalments::payment( 100, 3, 0, 2 );
		$this->assertSame( 33.34, $pay['monthly'] );
		$this->assertGreaterThanOrEqual( 100, $pay['monthly'] * 3 );
		$this->assertSame( 34.0, Instalments::payment( 100, 3, 0, 0 )['monthly'], 'no decimals' );
	}

	/**
	 * A yearly rate uses the loan payment formula: 1,000 over 12 months at
	 * 12% a year is 88.85 a month (88.8488 rounded up).
	 */
	public function test_rate_uses_loan_formula() {
		$pay = Instalments::payment( 1000, 12, 12, 2 );
		$this->assertSame( 88.85, $pay['monthly'] );
		$this->assertSame( 1066.2, $pay['total'] );
	}

	/**
	 * The default wording at 0%.
	 */
	public function test_text_at_zero() {
		$settings = Instalments::settings();
		$text     = Instalments::text( 549, $settings['bands'][0], $settings );
		$this->assertSame( 'or $45.75/month for 12 months, 0% interest', $this->plain( $text ) );
		$this->assertStringContainsString( 'purplish-instalments__amount', $text );
	}

	/**
	 * A rate shows the rate and the total, and adds the total when the
	 * merchant's wording leaves it out.
	 */
	public function test_rate_always_shows_the_total() {
		$band     = array(
			'from'   => 0.0,
			'months' => 12,
			'rate'   => 12.0,
		);
		$settings = Instalments::settings();
		$this->assertSame( 'or $88.85/month for 12 months, 12% a year, $1,066.20 in total', $this->plain( Instalments::text( 1000, $band, $settings ) ) );

		$settings['interest'] = ', {rate}% APR';
		$this->assertSame( 'or $88.85/month for 12 months, 12% APR, $1,066.20 in total', $this->plain( Instalments::text( 1000, $band, $settings ) ) );
	}

	/**
	 * The merchant's wording is escaped; only the placeholders carry HTML.
	 */
	public function test_wording_is_escaped() {
		$settings            = Instalments::settings();
		$settings['wording'] = '<script>x</script> {amount}';
		$text                = Instalments::text( 120, $settings['bands'][0], $settings );
		$this->assertStringNotContainsString( '<script>', $text );
	}

	/**
	 * The sale price counts.
	 */
	public function test_sale_price() {
		$text = Instalments::text_for( $this->product( '240', '120' ), Instalments::settings() );
		$this->assertStringStartsWith( 'or $10.00/month', $this->plain( $text ) );
	}

	/**
	 * Sold out and under the minimum show nothing.
	 */
	public function test_sold_out_and_minimum() {
		$product = $this->product( '120' );
		$product->set_stock_status( 'outofstock' );
		$product->save();
		$this->assertSame( '', Instalments::text_for( $product, Instalments::settings() ) );

		update_option( Instalments::OPTION, array( 'minimum' => '200' ) );
		$this->assertSame( '', Instalments::text_for( $this->product( '120' ), Instalments::settings() ) );
	}

	/**
	 * A product's own setting wins over its category, and the category
	 * over the store.
	 */
	public function test_override_order() {
		$cat     = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$product = $this->product( '120' );
		wp_set_object_terms( $product->get_id(), array( $cat ), 'product_cat' );

		$this->assertSame( 'store', Instalments::source_for( $product )['mode'] );

		update_term_meta(
			$cat,
			Instalments::META,
			array(
				'mode'  => 'own',
				'bands' => array(
					array(
						'from'   => 0,
						'months' => 6,
						'rate'   => 0,
					),
				),
			)
		);
		$this->assertSame( 6, Instalments::source_for( $product )['bands'][0]['months'] );

		update_post_meta( $product->get_id(), Instalments::META, array( 'mode' => 'hide' ) );
		$this->assertSame( 'hide', Instalments::source_for( $product )['mode'] );
		$this->assertSame( '', Instalments::text_for( $product, Instalments::settings() ) );
	}

	/**
	 * Own bands with no valid row count as the store's.
	 */
	public function test_empty_own_bands_follow_the_store() {
		$this->assertSame(
			'store',
			Instalments::clean_override(
				array(
					'mode'  => 'own',
					'bands' => array(),
				)
			)['mode']
		);
	}

	/**
	 * A variable product with different prices starts at "from" its
	 * cheapest variation, and each variation's text is sent once per
	 * price. A sold-out variation maps to no text.
	 */
	public function test_variable_from_and_variations() {
		$variations = Products::sweater( array( 'Orange', 'Blue' ), array( 'S' ) );
		$variations['Blue/S']->set_regular_price( '120' );
		$variations['Blue/S']->save();
		$product = wc_get_product( $variations['Orange/S']->get_parent_id() );
		\WC_Product_Variable::sync( $product );
		$product = wc_get_product( $product->get_id() );

		$settings = Instalments::settings();
		$this->assertSame( 'or from $4.17/month for 12 months, 0% interest', $this->plain( Instalments::text_for( $product, $settings ) ) );

		$data = Instalments::variations( $product, $settings );
		$this->assertCount( 2, $data['v'] );
		$this->assertCount( 2, $data['l'] );
		$this->assertSame( array( 'attribute_color', 'attribute_size' ), array_keys( $data['v'][0][0] ) );

		$variations['Blue/S']->set_stock_status( 'outofstock' );
		$variations['Blue/S']->save();
		$data = Instalments::variations( wc_get_product( $product->get_id() ), $settings );
		$keys = wp_list_pluck( $data['v'], 1 );
		$this->assertContains( '', $keys, 'sold out maps to no text' );
	}
}
