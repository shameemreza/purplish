<?php
/**
 * Tests for photo swatches on the Variation Selector.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttributeName;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Purplish\Frontend\PhotoSwatches;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * Builds variable products with photos per color and renders the
 * Variation Selector attribute block on them.
 */
class PhotoSwatchesTest extends WP_UnitTestCase {

	/**
	 * Registers the attribute blocks, which WooCommerce registers only
	 * under a block theme.
	 */
	public function set_up() {
		parent::set_up();
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( PhotoSwatches::BLOCK ) ) {
			$container = Package::container();
			new VariationSelectorAttribute( $container->get( Api::class ), $container->get( AssetDataRegistry::class ), new IntegrationRegistry() );
			new VariationSelectorAttributeName( $container->get( Api::class ), $container->get( AssetDataRegistry::class ), new IntegrationRegistry() );
		}
	}

	/**
	 * Clears the global product.
	 */
	public function tear_down() {
		unset( $GLOBALS['product'] );
		parent::tear_down();
	}

	/**
	 * A square photo attachment with a thumbnail size. No file is written.
	 *
	 * @param string $name Photo name.
	 * @return int Attachment ID.
	 */
	private function photo( $name ) {
		$file = "2026/11/{$name}.jpg";
		$id   = self::factory()->attachment->create_object(
			array(
				'file'           => $file,
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $name,
			)
		);
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 1200,
				'height' => 1200,
				'file'   => $file,
				'sizes'  => array(
					'thumbnail' => array(
						'file'      => "{$name}-150x150.jpg",
						'width'     => 150,
						'height'    => 150,
						'mime-type' => 'image/jpeg',
					),
				),
			)
		);
		return $id;
	}

	/**
	 * The sweater with an Orange photo on both Orange sizes and no photo on Blue.
	 *
	 * @return \WC_Product_Variable
	 */
	private function sweater() {
		$variations = Products::sweater();
		$orange     = $this->photo( 'orange' );
		foreach ( array( 'Orange/S', 'Orange/M' ) as $key ) {
			$variations[ $key ]->set_image_id( $orange );
			$variations[ $key ]->save();
		}
		return wc_get_product( reset( $variations )->get_parent_id() );
	}

	/**
	 * Renders the attribute block the way the product page does: the name
	 * and chips inside a group, for the global product.
	 *
	 * @param \WC_Product $product  Product.
	 * @param array       $purplish The block's Purplish options.
	 * @param string      $style    Display style.
	 * @return string
	 */
	private function render( $product, $purplish, $style = 'woocommerce/product-filter-chips' ) {
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.
		$attrs              = array( 'displayStyle' => $style );
		if ( $purplish ) {
			$attrs['purplish'] = $purplish;
		}
		$inner = 'woocommerce/dropdown' === $style ? '<!-- wp:woocommerce/dropdown /-->' : '<!-- wp:woocommerce/product-filter-chips /-->';
		return do_blocks(
			'<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute ' . wp_json_encode( $attrs ) . ' -->'
			. '<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} --><div class="wp-block-group">'
			. '<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-name /-->'
			. $inner
			. '</div><!-- /wp:group -->'
			. '<!-- /wp:woocommerce/add-to-cart-with-options-variation-selector-attribute -->'
		);
	}

	/**
	 * The row contexts in rendered HTML, by attribute label.
	 *
	 * @param string $html Rendered rows.
	 * @return array<string, array>
	 */
	private function contexts( $html ) {
		$found = array();
		$tags  = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( array( 'class_name' => 'wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute' ) ) ) {
			$context = json_decode( (string) $tags->get_attribute( 'data-wp-context' ), true );
			if ( is_array( $context ) && isset( $context['name'] ) ) {
				$found[ $context['name'] ] = $context;
			}
		}
		return $found;
	}

	/**
	 * Hooks into the blocks.
	 */
	public function test_registers() {
		$this->assertNotFalse( has_filter( 'render_block_data', array( PhotoSwatches::class, 'push' ) ) );
		$this->assertNotFalse( has_filter( 'render_block_woocommerce/product-filter-chips', array( PhotoSwatches::class, 'chips' ) ) );
		$this->assertSame( 15, has_filter( 'render_block_' . PhotoSwatches::BLOCK, array( PhotoSwatches::class, 'rows' ) ) );
	}

	/**
	 * The look and its shape and size become row classes; shape and size
	 * need the photos.
	 */
	public function test_option_classes() {
		$this->assertSame(
			array( 'purplish-chips', 'purplish-chips-swatch-photos', 'purplish-chips-swatchshape-round', 'purplish-chips-swatchsize-large' ),
			\Purplish\Frontend\BlockEffects::classes_for(
				PhotoSwatches::BLOCK,
				array(
					'swatch'      => 'photos',
					'swatchshape' => 'round',
					'swatchsize'  => 'large',
				)
			)
		);
		$this->assertSame(
			array(),
			\Purplish\Frontend\BlockEffects::classes_for(
				PhotoSwatches::BLOCK,
				array(
					'swatchshape' => 'round',
					'swatchsize'  => 'large',
				)
			)
		);
	}

	/**
	 * Each value gets its first variation photo, and a value with neither a
	 * photo nor a color gets nothing.
	 */
	public function test_visuals_use_each_colors_photo() {
		$product = $this->sweater();
		$visuals = PhotoSwatches::visuals( $product, 'Color' );

		$this->assertSame( array( 'Orange' ), array_keys( $visuals ) );
		$this->assertSame( 'image', $visuals['Orange']['type'] );
		$this->assertStringEndsWith( '/orange-150x150.jpg', $visuals['Orange']['value'] );
	}

	/**
	 * The main photo counts when a variation sets it as its own.
	 */
	public function test_main_photo_counts_when_the_variation_sets_it() {
		$variations = Products::sweater();
		$main       = $this->photo( 'main' );
		$product    = wc_get_product( reset( $variations )->get_parent_id() );
		$product->set_image_id( $main );
		$product->save();
		$variations['Blue/S']->set_image_id( $main );
		$variations['Blue/S']->save();

		$this->assertSame( array( 'Blue' ), array_keys( PhotoSwatches::visuals( wc_get_product( $product->get_id() ), 'Color' ) ) );
	}

	/**
	 * A global color with no photo falls back to its swatch color.
	 */
	public function test_falls_back_to_the_term_color() {
		$variations = Products::with_global_color(
			array(
				'Sand'  => '#d8c3a5',
				'Ink'   => '',
				'Ember' => '#c0392b',
			)
		);
		$variations['ember']->set_image_id( $this->photo( 'ember' ) );
		$variations['ember']->save();
		$product = wc_get_product( reset( $variations )->get_parent_id() );
		$visuals = PhotoSwatches::visuals( $product, 'pa_color' );

		$this->assertSame(
			array(
				'type'  => 'color',
				'value' => '#d8c3a5',
			),
			$visuals['sand']
		);
		$this->assertSame( 'image', $visuals['ember']['type'] );
		$this->assertArrayNotHasKey( 'ink', $visuals );
	}

	/**
	 * Auto finds the color attribute; a picked attribute wins when the
	 * product has it.
	 */
	public function test_attribute_auto_and_picked() {
		$product = $this->sweater();

		$this->assertSame( 'Color', PhotoSwatches::attribute( $product, '' ) );
		$this->assertSame( 'Size', PhotoSwatches::attribute( $product, 'size' ) );
		$this->assertSame( 'Color', PhotoSwatches::attribute( $product, 'pa_pattern' ) );
	}

	/**
	 * The filter can add photos.
	 */
	public function test_filter() {
		$product = $this->sweater();
		$filter  = static function ( $visuals ) {
			$visuals['Blue'] = array(
				'type'  => 'image',
				'value' => 'https://example.org/blue.jpg',
			);
			$visuals['Bad']  = array(
				'type'  => 'color',
				'value' => 'nope',
			);
			return $visuals;
		};
		add_filter( 'purplish_photo_swatch_visuals', $filter );
		$visuals = PhotoSwatches::visuals( $product, 'Color' );
		remove_filter( 'purplish_photo_swatch_visuals', $filter );

		$this->assertSame( 'https://example.org/blue.jpg', $visuals['Blue']['value'] );
		$this->assertArrayNotHasKey( 'Bad', $visuals );
	}

	/**
	 * The color row gets swatches with the photos and a text chip for the
	 * color without one, and the browser's list carries the photos too.
	 */
	public function test_color_row_shows_photos() {
		$product = $this->sweater();
		$html    = $this->render( $product, array( 'swatch' => 'photos' ) );

		$this->assertStringContainsString( 'purplish-photo-swatches', $html );
		$this->assertStringContainsString( 'is-style-swatch', $html );
		$this->assertMatchesRegularExpression( '#wc-block-product-filter-chips__swatch"\\s+style="background-image:url\\(&\\#039;[^"]*orange-150x150\\.jpg#', $html );
		$this->assertStringContainsString( 'wc-block-product-filter-chips__swatch--no-color', $html );

		$contexts = $this->contexts( $html );
		$this->assertSame( 'image', $contexts['Color']['variationAttributeOptions'][0]['visual']['type'] );
		$this->assertArrayNotHasKey( 'visual', $contexts['Color']['variationAttributeOptions'][1] );
		$this->assertArrayNotHasKey( 'visual', $contexts['Size']['variationAttributeOptions'][0] );
	}

	/**
	 * Only the color row changes.
	 */
	public function test_size_row_keeps_text_chips() {
		$html = $this->render( $this->sweater(), array( 'swatch' => 'photos' ) );
		$rows = explode( 'name="attribute_color"', $html );

		$this->assertStringNotContainsString( 'is-style-swatch', $rows[1] );
	}

	/**
	 * The picked name sits inside the attribute name, for the script to fill.
	 */
	public function test_picked_name() {
		$html = $this->render( $this->sweater(), array( 'swatch' => 'photos' ) );

		$this->assertMatchesRegularExpression( '#<span[^>]*attribute-name[^>]*>Color<span class="purplish-photo-swatches__picked" aria-hidden="true"></span></span>#', $html );
		$this->assertTrue( wp_script_is( PhotoSwatches::HANDLE, 'enqueued' ) );
	}

	/**
	 * The picked name can be turned off.
	 */
	public function test_picked_name_off() {
		wp_dequeue_script( PhotoSwatches::HANDLE );
		$html = $this->render(
			$this->sweater(),
			array(
				'swatch'     => 'photos',
				'swatchname' => false,
			)
		);

		$this->assertStringContainsString( 'purplish-photo-swatches', $html );
		$this->assertStringNotContainsString( 'purplish-photo-swatches__picked', $html );
		$this->assertFalse( wp_script_is( PhotoSwatches::HANDLE, 'enqueued' ) );
	}

	/**
	 * Nothing changes without the option, with the drop-down, or when no
	 * color has a photo or a swatch color.
	 */
	public function test_nothing_without_the_option_or_photos() {
		$product = $this->sweater();
		$this->assertStringNotContainsString( 'purplish-photo-swatches', $this->render( $product, array() ) );
		$this->assertStringNotContainsString( 'purplish-photo-swatches', $this->render( $product, array( 'swatch' => 'photos' ), 'woocommerce/dropdown' ) );

		$variations = Products::sweater( array( 'Red', 'Teal' ) );
		$plain      = wc_get_product( reset( $variations )->get_parent_id() );
		$html       = $this->render( $plain, array( 'swatch' => 'photos' ) );
		$this->assertStringNotContainsString( 'purplish-photo-swatches', $html );
		$this->assertStringNotContainsString( 'is-style-swatch', $html );
	}
}
