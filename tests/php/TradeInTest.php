<?php
/**
 * Tests for the trade-in settings, device table and checks.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\TradeIn;
use Purplish\TradeInCsv;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * Rows are cleaned, deduped and sorted, amounts take the store's format,
 * a product follows its own setting, then its category, then the store,
 * and Yes needs a device from the table and, when asked, the terms box.
 */
class TradeInTest extends WP_UnitTestCase {

	/**
	 * Puts options back after each test.
	 */
	public function tear_down() {
		delete_option( TradeIn::OPTION );
		delete_option( TradeIn::DEVICES );
		update_option( 'woocommerce_price_num_decimals', 2 );
		update_option( 'woocommerce_price_decimal_sep', '.' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		parent::tear_down();
	}

	/**
	 * A saved simple product.
	 *
	 * @return WC_Product_Simple
	 */
	private function product() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( '4599' );
		$product->save();
		return $product;
	}

	/**
	 * A row needs a brand, a model and a high value; low swaps or fills in.
	 */
	public function test_clean_device() {
		$this->assertSame(
			array(
				'brand'   => 'Apple',
				'model'   => 'iPhone 13',
				'storage' => '128GB',
				'low'     => 600.0,
				'high'    => 680.0,
			),
			TradeIn::clean_device( array( 'Apple', 'iPhone 13', '128GB', '680', '600' ) ),
			'column order, low above high swaps'
		);
		$this->assertSame( 680.0, TradeIn::clean_device( array( 'Apple', 'iPhone 13', '', '', 'RM680' ) )['low'], 'an empty low takes the high' );
		$this->assertSame( 680.0, TradeIn::clean_device( array( 'Apple', 'iPhone 13', '', '680' ) )['high'], 'only one value is the high' );
		$this->assertSame(
			'',
			TradeIn::clean_device(
				array(
					'brand' => 'Apple',
					'model' => 'X',
					'high'  => 5,
				)
			)['storage']
		);
		$this->assertNull( TradeIn::clean_device( array( '', 'iPhone 13', '', '1', '2' ) ), 'no brand' );
		$this->assertNull( TradeIn::clean_device( array( 'Apple', '', '', '1', '2' ) ), 'no model' );
		$this->assertNull( TradeIn::clean_device( array( 'Apple', 'iPhone 13', '', '', '' ) ), 'no value' );
		$this->assertNull( TradeIn::clean_device( array( 'Apple', 'iPhone 13', '', '0', 'abc' ) ), 'value not a number' );
		$this->assertNull( TradeIn::clean_device( 'Apple' ) );
		$this->assertSame( 'iPhone', TradeIn::clean_device( array( 'Apple', '<b>iPhone</b>', '', '1', '2' ) )['model'], 'tags go' );
	}

	/**
	 * Amounts read the store's separators and round to its decimals.
	 */
	public function test_amount() {
		$this->assertSame( 1234.5, TradeIn::amount( 'RM1,234.50' ) );
		$this->assertSame( 680.0, TradeIn::amount( 680 ) );
		$this->assertNull( TradeIn::amount( '' ) );
		$this->assertNull( TradeIn::amount( array() ) );
		$this->assertSame( 0.0, TradeIn::amount( '-5' ), 'never below 0' );

		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );
		$this->assertSame( 1234.5, TradeIn::amount( '1.234,50' ) );

		update_option( 'woocommerce_price_num_decimals', 0 );
		$this->assertSame( 1235.0, TradeIn::amount( '1.234,50' ) );
	}

	/**
	 * The same device keeps its last row, and storage sorts naturally.
	 */
	public function test_clean_devices_dedupes_and_sorts() {
		$devices = TradeIn::clean_devices(
			array(
				array( 'Samsung', 'Galaxy S23', '256GB', 900, 1000 ),
				array( 'Apple', 'iPhone 13', '128GB', 600, 680 ),
				array( 'Apple', 'iPhone 13', '64GB', 500, 560 ),
				array( 'apple', 'iphone 13', '128gb', 610, 690 ),
				array( '', 'broken', '', 1, 1 ),
				'not a row',
			)
		);
		$this->assertSame(
			array( 'Apple iPhone 13, 64GB', 'apple iphone 13, 128gb', 'Samsung Galaxy S23, 256GB' ),
			array_map( array( TradeIn::class, 'name' ), $devices )
		);
		$this->assertSame( 690.0, $devices[1]['high'], 'the last row for a device wins' );
	}

	/**
	 * Keys are stable, short and found again in the saved table.
	 */
	public function test_save_and_find() {
		$saved = TradeIn::save_devices( array( array( 'Apple', 'iPhone 13', '128GB', 600, 680 ) ) );
		$key   = TradeIn::key( $saved[0] );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{12}$/', $key );
		$this->assertSame( $saved[0], TradeIn::find( $key ) );
		$this->assertSame(
			$key,
			TradeIn::key(
				array(
					'brand'   => 'APPLE',
					'model'   => 'iphone 13',
					'storage' => '128gb',
				)
			),
			'case doesn’t change the key'
		);
		$this->assertNull( TradeIn::find( 'abc' ) );
		$this->assertNull( TradeIn::find( array( $key ) ) );
		$this->assertNull( TradeIn::find( str_repeat( '0', 12 ) ) );
		$this->assertSame( 'no', wp_load_alloptions()[ TradeIn::DEVICES ] ?? 'no', 'the table isn’t autoloaded' );
	}

	/**
	 * Merge adds and updates; replace starts over.
	 */
	public function test_merge() {
		$current  = TradeIn::clean_devices( array( array( 'Apple', 'iPhone 13', '128GB', 600, 680 ), array( 'Apple', 'iPhone 12', '', 400, 450 ) ) );
		$imported = TradeIn::clean_devices( array( array( 'Apple', 'iPhone 13', '128GB', 620, 700 ) ) );
		$merged   = TradeIn::merge( $current, $imported, false );
		$this->assertCount( 2, $merged );
		$this->assertSame( 700.0, $merged[1]['high'] );
		$this->assertCount( 1, TradeIn::merge( $current, $imported, true ) );
	}

	/**
	 * Settings fall back to the defaults and links must be URLs.
	 */
	public function test_clean_settings() {
		$clean = TradeIn::clean(
			array(
				'scope'     => 'nope',
				'terms'     => 'yes',
				'link_url'  => 'javascript:alert(1)',
				'condition' => '<script>x</script>Good',
			)
		);
		$this->assertSame( 'all', $clean['scope'] );
		$this->assertTrue( $clean['terms'] );
		$this->assertSame( '', $clean['link_url'] );
		$this->assertSame( 'Good', $clean['condition'] );
		$this->assertSame( '/trade-in/', TradeIn::clean( array( 'terms_url' => '/trade-in/' ) )['terms_url'] );
		$this->assertSame( 'https://example.org/terms', TradeIn::clean( array( 'terms_url' => ' https://example.org/terms ' ) )['terms_url'] );
		$this->assertFalse( TradeIn::settings()['terms'], 'terms are off until turned on' );
	}

	/**
	 * Product, then category, then store.
	 */
	public function test_accepts() {
		$product = $this->product();
		$this->assertTrue( TradeIn::accepts( $product ), 'all products by default' );

		update_option( TradeIn::OPTION, array( 'scope' => 'chosen' ) );
		$this->assertFalse( TradeIn::accepts( $product ) );

		$term = wp_insert_term( 'Phones', 'product_cat' );
		$product->set_category_ids( array( $term['term_id'] ) );
		$product->save();
		update_term_meta( $term['term_id'], TradeIn::META, 'on' );
		$this->assertTrue( TradeIn::accepts( $product ), 'category on' );

		update_post_meta( $product->get_id(), TradeIn::META, 'off' );
		$this->assertFalse( TradeIn::accepts( $product ), 'product off wins over category' );

		update_post_meta( $product->get_id(), TradeIn::META, 'junk' );
		$this->assertTrue( TradeIn::accepts( $product ), 'a bad value follows the category' );
	}

	/**
	 * Yes needs a device from the table, and the terms box when asked.
	 */
	public function test_choice() {
		$product = $this->product();
		$saved   = TradeIn::save_devices( array( array( 'Apple', 'iPhone 13', '128GB', 600, 680 ) ) );
		$key     = TradeIn::key( $saved[0] );

		$this->assertNull( TradeIn::choice( array(), $product ), 'no fields' );
		$this->assertNull(
			TradeIn::choice(
				array(
					TradeIn::FIELD        => 'no',
					TradeIn::DEVICE_FIELD => $key,
				),
				$product
			),
			'No'
		);
		$this->assertSame( 'purplish_trade_in_device', TradeIn::choice( array( TradeIn::FIELD => 'yes' ), $product )->get_error_code() );
		$this->assertSame(
			'purplish_trade_in_device',
			TradeIn::choice(
				array(
					TradeIn::FIELD        => 'yes',
					TradeIn::DEVICE_FIELD => 'ffffffffffff',
				),
				$product
			)->get_error_code(),
			'a key not in the table'
		);
		$this->assertSame(
			$saved[0],
			TradeIn::choice(
				array(
					TradeIn::FIELD        => 'yes',
					TradeIn::DEVICE_FIELD => $key,
				),
				$product
			)
		);

		update_option( TradeIn::OPTION, array( 'terms' => true ) );
		$this->assertSame(
			'purplish_trade_in_terms',
			TradeIn::choice(
				array(
					TradeIn::FIELD        => 'yes',
					TradeIn::DEVICE_FIELD => $key,
				),
				$product
			)->get_error_code()
		);
		$this->assertSame(
			$saved[0],
			TradeIn::choice(
				array(
					TradeIn::FIELD        => 'yes',
					TradeIn::DEVICE_FIELD => $key,
					TradeIn::TERMS_FIELD  => '1',
				),
				$product
			)
		);

		update_post_meta( $product->get_id(), TradeIn::META, 'off' );
		$this->assertNull( TradeIn::choice( array( TradeIn::FIELD => 'yes' ), $product ), 'a product without trade-in ignores the fields' );
	}

	/**
	 * The cart lines: device, then a range or one figure.
	 */
	public function test_lines() {
		$this->assertSame(
			array(
				array( 'Trade-in', 'Apple iPhone 13, 128GB' ),
				array( 'Estimated trade-in value', '$600.00 to $680.00' ),
			),
			TradeIn::lines( array( 'Apple', 'iPhone 13', '128GB', 600, 680 ), 1 )
		);
		$lines = TradeIn::lines( array( 'Apple', 'iPhone 12', '', 450, 450 ), 2 );
		$this->assertSame( 'Apple iPhone 12', $lines[0][1] );
		$this->assertSame( '$450.00 (one device per unit)', $lines[1][1] );
		$this->assertSame( array(), TradeIn::lines( 'broken', 1 ) );
	}

	/**
	 * CSV: header and blank lines pass, bad rows are reported by line.
	 */
	public function test_csv_parse() {
		$csv    = "\xEF\xBB\xBFBrand,Model,Storage,Low,High\r\nApple,iPhone 13,128GB,600,680\r\n\r\nApple,,64GB,1,2\r\n\"Samsung\",\"Galaxy S23, Ultra\",256GB,\"1,000\",\"1,200\"\r\n'=cmd,X,,1,2\n";
		$parsed = TradeInCsv::parse( $csv );
		$this->assertSame( array( 4 ), $parsed['skipped'] );
		$this->assertSame( array( '=cmd X', 'Apple iPhone 13, 128GB', 'Samsung Galaxy S23, Ultra, 256GB' ), array_map( array( TradeIn::class, 'name' ), $parsed['devices'] ) );
		$this->assertSame( 1200.0, $parsed['devices'][2]['high'] );
		$this->assertSame( array(), TradeInCsv::parse( '' )['devices'] );
	}

	/**
	 * Export guards formula signs and reads back the same table.
	 */
	public function test_csv_round_trip() {
		$devices = TradeIn::clean_devices(
			array(
				array( 'Apple', 'iPhone "13"', '128GB', 600, 680.5 ),
				array( '=HYPERLINK("x")', '+Model', '-', 1, 2 ),
			)
		);
		$csv     = TradeInCsv::build( $devices );
		$this->assertStringStartsWith( "brand,model,storage,low,high\r\n", $csv );
		$this->assertStringContainsString( "\"'=HYPERLINK(\"\"x\"\")\",\"'+Model\",\"'-\"", $csv );
		$this->assertSame( $devices, TradeInCsv::parse( $csv )['devices'] );
	}
}
