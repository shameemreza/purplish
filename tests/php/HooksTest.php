<?php
/**
 * Tests for the 1.1 developer hooks that don't need a block.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\PageData;
use Purplish\Jobs\DetectColors;
use Purplish\Meta;
use Purplish\Options;
use WP_UnitTestCase;

/**
 * Each hook changes what it promises and nothing else; bad values from a
 * filter fall back safely.
 */
class HooksTest extends WP_UnitTestCase {

	/**
	 * Files the tests wrote, removed after each test.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Clears the filters, queued actions and written files.
	 */
	public function tear_down() {
		remove_all_filters( 'purplish_color_set' );
		remove_all_filters( 'purplish_detected_color' );
		remove_all_filters( 'purplish_block_classes' );
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );
		foreach ( $this->temp_files as $file ) {
			wp_delete_file( $file );
		}
		$this->temp_files = array();
		parent::tear_down();
	}

	/**
	 * A simple product with a custom color.
	 *
	 * @param string $color Hex color.
	 * @return \WC_Product
	 */
	private function product( $color = '#7b2cbf' ) {
		$product = new \WC_Product_Simple();
		$product->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$product->update_meta_data( Meta::CUSTOM_COLOR, $color );
		$product->save();
		return wc_get_product( $product->get_id() );
	}

	/**
	 * `purplish_loaded` fired when Purplish started with WooCommerce.
	 */
	public function test_loaded_fires() {
		$this->assertGreaterThan( 0, did_action( 'purplish_loaded' ) );
	}

	/**
	 * A store can force its own color; the other parts are worked out
	 * from it unless the filter gives valid ones.
	 */
	public function test_color_set_forces_a_color() {
		$product = $this->product();
		add_filter(
			'purplish_color_set',
			static function ( $set, $filtered_product ) use ( $product ) {
				return $filtered_product->get_id() === $product->get_id() ? array(
					'color' => '#ffd60a',
					'on'    => 'not a color',
					'tint'  => '#fefefe',
				) : $set;
			},
			10,
			2
		);

		$default = PageData::for_product( $product, Options::defaults() )['default'];
		$expect  = PageData::color_set( '#ffd60a' );

		$this->assertSame( '#ffd60a', $default['color'] );
		$this->assertSame( $expect['on'], $default['on'], 'a broken part is worked out from the color' );
		$this->assertSame( $expect['ink'], $default['ink'], 'a missing part is worked out from the color' );
		$this->assertSame( '#fefefe', $default['tint'] );
	}

	/**
	 * Null from the filter, or a set without a valid color, means no color.
	 */
	public function test_color_set_can_turn_the_color_off() {
		$product = $this->product();

		add_filter( 'purplish_color_set', '__return_null' );
		$this->assertNull( PageData::for_product( $product, Options::defaults() ) );

		remove_all_filters( 'purplish_color_set' );
		add_filter(
			'purplish_color_set',
			static function () {
				return array( 'color' => 'purple' );
			}
		);
		$this->assertNull( PageData::for_product( $product, Options::defaults() ) );
	}

	/**
	 * The filter can give a color to a product that has none.
	 */
	public function test_color_set_for_a_product_without_color() {
		$product = new \WC_Product_Simple();
		$product->save();
		add_filter(
			'purplish_color_set',
			static function () {
				return array( 'color' => '#2a9d8f' );
			}
		);

		$this->assertSame( PageData::color_set( '#2a9d8f' ), PageData::for_product( wc_get_product( $product->get_id() ), Options::defaults() )['default'] );
	}

	/**
	 * Variations go through the filter one by one, with their own object.
	 */
	public function test_color_set_per_variation() {
		$variations = Support\Products::sweater();
		$product    = wc_get_product( reset( $variations )->get_parent_id() );
		$seen       = array();
		add_filter(
			'purplish_color_set',
			static function ( $set, $variation ) use ( &$seen ) {
				$seen[] = $variation->get_id();
				return $set;
			},
			10,
			2
		);

		PageData::for_product( $product, Options::defaults() );

		$this->assertEqualsCanonicalizing( $product->get_children(), $seen );
		$this->assertCount( 4, $seen );
	}

	/**
	 * The detected color can be swapped before it's saved, or dropped.
	 */
	public function test_detected_color() {
		$product = new \WC_Product_Simple();
		$product->set_image_id( $this->upload( 'orange-sweater' ) );
		$product->save();

		$seen = array();
		add_filter(
			'purplish_detected_color',
			static function ( $color, $image_id ) use ( &$seen ) {
				$seen = array( $color, $image_id );
				return '#123456';
			},
			10,
			2
		);
		( new DetectColors() )->detect( $product->get_id() );

		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', $seen[0], 'the filter gets the detector\'s color' );
		$this->assertSame( $product->get_image_id(), $seen[1] );
		$this->assertSame( '#123456', wc_get_product( $product->get_id() )->get_meta( Meta::AUTO_COLOR ) );
	}

	/**
	 * A value that isn't a hex color saves no color, and the photo still
	 * counts as read.
	 */
	public function test_detected_color_not_a_color() {
		$product = new \WC_Product_Simple();
		$product->set_image_id( $this->upload( 'blue-sweater' ) );
		$product->save();

		add_filter(
			'purplish_detected_color',
			static function () {
				return 'blue';
			}
		);
		( new DetectColors() )->detect( $product->get_id() );

		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( '', $saved->get_meta( Meta::AUTO_COLOR ) );
		$this->assertSame( DetectColors::source_of( $saved ), $saved->get_meta( Meta::AUTO_SOURCE ) );
	}

	/**
	 * A filter can add a class, gets the block name and saved options, and
	 * class names are cleaned.
	 */
	public function test_block_classes() {
		$seen = array();
		add_filter(
			'purplish_block_classes',
			static function ( $classes, $name, $saved ) use ( &$seen ) {
				$seen      = array( $name, $saved );
				$classes[] = 'my-brand-gallery';
				$classes[] = 'bad"class';
				$classes[] = array( 'not', 'a', 'string' );
				return $classes;
			},
			10,
			3
		);

		$out = ( new BlockEffects() )->add_classes(
			'<div class="wp-block-woocommerce-product-gallery"></div>',
			array(
				'blockName' => 'woocommerce/product-gallery',
				'attrs'     => array( 'purplish' => array( 'rounded' => true ) ),
			)
		);

		$this->assertSame( array( 'woocommerce/product-gallery', array( 'rounded' => true ) ), $seen );
		$this->assertStringContainsString( 'class="wp-block-woocommerce-product-gallery purplish-gallery purplish-gallery-rounded my-brand-gallery badclass"', $out );
	}

	/**
	 * Removing every class leaves the block as it was.
	 */
	public function test_block_classes_removed() {
		add_filter( 'purplish_block_classes', '__return_empty_array' );
		$html = '<div class="wp-block-woocommerce-product-gallery"></div>';

		$out = ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => 'woocommerce/product-gallery',
				'attrs'     => array( 'purplish' => array( 'rounded' => true ) ),
			)
		);

		$this->assertSame( $html, $out );
	}

	/**
	 * Uploads a fixture photo as an attachment.
	 *
	 * @param string $name Fixture name without extension.
	 * @return int Attachment ID.
	 */
	private function upload( $name ) {
		$upload             = wp_upload_bits( "purplish-hooks-{$name}.webp", null, file_get_contents( dirname( __DIR__ ) . "/fixtures/{$name}.webp" ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->temp_files[] = $upload['file'];

		$id                 = self::factory()->attachment->create_upload_object( $upload['file'] );
		$this->temp_files[] = get_attached_file( $id );

		return $id;
	}
}
