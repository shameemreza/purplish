<?php
/**
 * Tests for the Color Dots block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\ColorDots;
use Purplish\Blocks\Registry;
use Purplish\Meta;
use WC_Product_Attribute;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Block;
use WP_Block_Type_Registry;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * The block registers from the build inside product cards, finds one
 * color per color term or per page color, prints dots with names a screen
 * reader can hear, and gives the editor the same list.
 */
class ColorDotsTest extends WP_UnitTestCase {

	const NAME = 'purplish/color-dots';

	/**
	 * Starts each test with no kept colors.
	 */
	public function set_up() {
		parent::set_up();
		ColorDots::forget();
	}

	/**
	 * Puts the filters and the user back after each test.
	 */
	public function tear_down() {
		remove_all_filters( 'purplish_color_dots_colors' );
		ColorDots::forget();
		wp_dequeue_script( ColorDots::SWAP_HANDLE );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * A global attribute with the given terms, as slug => swatch color.
	 *
	 * @param string $name  Attribute name.
	 * @param array  $terms Term names to swatch colors ('' for none).
	 * @return string The taxonomy.
	 */
	private function attribute( $name, $terms ) {
		$taxonomy = wc_attribute_taxonomy_name( $name );
		if ( ! wc_attribute_taxonomy_id_by_name( $taxonomy ) ) {
			wc_create_attribute(
				array(
					'name' => ucfirst( $name ),
					'slug' => $name,
				)
			);
		}
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, 'product' );
		}
		// WooCommerce fills this list on init, before the test made the attribute.
		$GLOBALS['wc_product_attributes'][ $taxonomy ] = wc_get_attribute( wc_attribute_taxonomy_id_by_name( $taxonomy ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		foreach ( $terms as $term => $color ) {
			$made = wp_insert_term( $term, $taxonomy );
			$id   = is_wp_error( $made ) ? $made->get_error_data() : $made['term_id'];
			if ( $color ) {
				update_term_meta( $id, 'color', $color );
			}
		}
		return $taxonomy;
	}

	/**
	 * A variable product, one variation per row of attribute values, each
	 * with an optional photo color.
	 *
	 * @param array $attributes Taxonomy => term slugs used for variations.
	 * @param array $rows       Each: taxonomy => slug, plus `auto` for a photo color
	 *                          and `image` for a variation photo.
	 * @param int   $main       The product's own photo.
	 * @return WC_Product_Variable
	 */
	private function variable( $attributes, $rows, $main = 0 ) {
		$product = new WC_Product_Variable();
		$product->set_name( 'Trousers' );
		$product->set_image_id( $main );
		$product->set_status( 'publish' );
		$list = array();
		foreach ( $attributes as $taxonomy => $slugs ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
			$attribute->set_name( $taxonomy );
			$attribute->set_options(
				array_map(
					static function ( $slug ) use ( $taxonomy ) {
						return get_term_by( 'slug', $slug, $taxonomy )->term_id;
					},
					$slugs
				)
			);
			$attribute->set_variation( true );
			$list[] = $attribute;
		}
		$product->set_attributes( $list );
		$id = $product->save();

		foreach ( $rows as $row ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $id );
			$auto  = isset( $row['auto'] ) ? $row['auto'] : '';
			$image = isset( $row['image'] ) ? $row['image'] : 0;
			unset( $row['auto'], $row['image'] );
			$variation->set_image_id( $image );
			$variation->set_attributes( $row );
			$variation->set_regular_price( '50' );
			if ( $auto ) {
				$variation->update_meta_data( Meta::AUTO_COLOR, $auto );
			}
			$variation->save();
		}

		WC_Product_Variable::sync( $id );
		return wc_get_product( $id );
	}

	/**
	 * Renders the block on a product card.
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $product_id The card's product.
	 * @return string
	 */
	private function render( $attributes, $product_id ) {
		$parsed = parse_blocks( '<!-- wp:purplish/color-dots ' . wp_json_encode( (object) $attributes ) . ' /-->' );
		return ( new WP_Block(
			$parsed[0],
			array(
				'postId'   => $product_id,
				'postType' => 'product',
			)
		) )->render();
	}

	/**
	 * The block registered on init from the real build, only inside product
	 * cards, with its defaults, style and editor script and no front script.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Color Dots', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'woocommerce/product-template', 'core/post-template', 'woocommerce/single-product' ), $type->ancestor );
		$this->assertSame( array( 'postId', 'postType' ), $type->uses_context );
		$this->assertSame( 'dots', $type->attributes['look']['default'] );
		$this->assertSame( 4, $type->attributes['limit']['default'] );
		$this->assertFalse( $type->attributes['single']['default'] );
		$this->assertSame( 'off', $type->attributes['swap']['default'] );
		$this->assertSame( 'fade', $type->attributes['motion']['default'] );
		$this->assertEmpty( $type->view_script_handles, 'no front script' );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * With a color attribute, one color per term, sizes don't add dots, and
	 * the term's swatch color wins over the photo color.
	 */
	public function test_colors_by_term() {
		$this->attribute(
			'color',
			array(
				'Navy'  => '#2f3647',
				'Olive' => '',
			)
		);
		$this->attribute(
			'size',
			array(
				'Small' => '',
				'Large' => '',
			)
		);
		$product = $this->variable(
			array(
				'pa_color' => array( 'navy', 'olive' ),
				'pa_size'  => array( 'small', 'large' ),
			),
			array(
				array(
					'pa_color' => 'navy',
					'pa_size'  => 'small',
					'auto'     => '#111111',
				),
				array(
					'pa_color' => 'navy',
					'pa_size'  => 'large',
					'auto'     => '#111112',
				),
				array(
					'pa_color' => 'olive',
					'pa_size'  => 'small',
					'auto'     => '#6d6b4a',
				),
				array(
					'pa_color' => 'olive',
					'pa_size'  => 'large',
					'auto'     => '#6d6b4b',
				),
			)
		);

		$this->assertSame( 'pa_color', ColorDots::color_attribute( $product ) );
		$this->assertSame(
			array(
				array(
					'name'  => 'Navy',
					'color' => '#2f3647',
					'image' => 0,
				),
				array(
					'name'  => 'Olive',
					'color' => '#6d6b4a',
					'image' => 0,
				),
			),
			ColorDots::colors( $product )
		);
	}

	/**
	 * Without a color attribute, each distinct page color gets one dot,
	 * with no name.
	 */
	public function test_colors_by_page_color() {
		$this->attribute(
			'size',
			array(
				'Small' => '',
				'Large' => '',
				'Huge'  => '',
			)
		);
		$product = $this->variable(
			array( 'pa_size' => array( 'small', 'large', 'huge' ) ),
			array(
				array(
					'pa_size' => 'small',
					'auto'    => '#375242',
				),
				array(
					'pa_size' => 'large',
					'auto'    => '#375242',
				),
				array(
					'pa_size' => 'huge',
					'auto'    => '#804d3d',
				),
			)
		);

		$this->assertSame( '', ColorDots::color_attribute( $product ) );
		$this->assertSame( array( '#375242', '#804d3d' ), wp_list_pluck( ColorDots::colors( $product ), 'color' ) );
		$this->assertSame( array( '', '' ), wp_list_pluck( ColorDots::colors( $product ), 'name' ) );
	}

	/**
	 * A simple product has its own page color, and none when it's off.
	 */
	public function test_simple_product() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Coat' );
		$product->update_meta_data( Meta::AUTO_COLOR, '#d90324' );
		$product->save();
		$this->assertSame( array( '#d90324' ), wp_list_pluck( ColorDots::colors( $product ), 'color' ) );

		ColorDots::forget();
		$product->update_meta_data( Meta::MODE, Meta::MODE_OFF );
		$product->save();
		$this->assertSame( array(), ColorDots::colors( $product ) );
	}

	/**
	 * The filter can add or change colors; anything that isn't a hex color
	 * is dropped and names are stripped.
	 */
	public function test_filter() {
		$product = new WC_Product_Simple();
		$product->save();
		add_filter(
			'purplish_color_dots_colors',
			static function () {
				return array(
					array(
						'name'  => '<b>Ink</b>',
						'color' => '#222222',
					),
					array(
						'name'  => 'Broken',
						'color' => 'red; background:url(x)',
					),
				);
			}
		);

		$this->assertSame(
			array(
				array(
					'name'  => 'Ink',
					'color' => '#222222',
					'image' => 0,
				),
			),
			ColorDots::colors( $product )
		);
	}

	/**
	 * A card gets its dots up to the limit, a count for the rest, a label
	 * with the names, and a tooltip on each dot.
	 */
	public function test_render() {
		$product = new WC_Product_Simple();
		$product->save();
		add_filter(
			'purplish_color_dots_colors',
			static function () {
				return array(
					array(
						'name'  => 'Ink',
						'color' => '#222222',
					),
					array(
						'name'  => 'Sage',
						'color' => '#8a9a7b',
					),
					array(
						'name'  => 'Sand "light"',
						'color' => '#d8c3a5',
					),
				);
			}
		);

		$out = $this->render(
			array(
				'look'  => 'swatches',
				'limit' => 2,
			),
			$product->get_id()
		);

		$this->assertStringContainsString( 'wp-block-purplish-color-dots', $out );
		$this->assertStringContainsString( 'purplish-color-dots-look-swatches', $out );
		$this->assertStringContainsString( 'role="img"', $out );
		$this->assertStringContainsString( 'aria-label="Colors: Ink, Sage, Sand &quot;light&quot;"', $out );
		$this->assertSame( 2, substr_count( $out, 'purplish-color-dots-dot' ) );
		$this->assertStringContainsString( 'style="--purplish-dot:#222222" title="Ink"', $out );
		$this->assertStringContainsString( '<span class="purplish-color-dots-more">+1</span>', $out );
	}

	/**
	 * An unknown look falls back to dots, and colors with no names are
	 * counted for screen readers.
	 */
	public function test_render_without_names() {
		$product = new WC_Product_Simple();
		$product->save();
		add_filter(
			'purplish_color_dots_colors',
			static function () {
				return array(
					array( 'color' => '#222222' ),
					array( 'color' => '#8a9a7b' ),
				);
			}
		);

		$out = $this->render( array( 'look' => 'stripes' ), $product->get_id() );

		$this->assertStringContainsString( 'purplish-color-dots-look-dots', $out );
		$this->assertStringContainsString( 'aria-label="2 colors"', $out );
		$this->assertStringNotContainsString( 'title=', $out );
		$this->assertStringNotContainsString( 'purplish-color-dots-more', $out );
	}

	/**
	 * One color prints nothing unless Show a single color is on, and no
	 * color or no product prints nothing.
	 */
	public function test_render_nothing() {
		$product = new WC_Product_Simple();
		$product->update_meta_data( Meta::AUTO_COLOR, '#d90324' );
		$product->save();

		$this->assertSame( '', $this->render( array(), $product->get_id() ) );
		$this->assertStringContainsString( '#d90324', $this->render( array( 'single' => true ), $product->get_id() ) );

		$plain = new WC_Product_Simple();
		$plain->update_meta_data( Meta::MODE, Meta::MODE_OFF );
		$plain->save();
		$this->assertSame( '', $this->render( array( 'single' => true ), $plain->get_id() ) );
		$this->assertSame( '', $this->render( array(), 0 ) );
	}

	/**
	 * The limit stays between 1 and 12.
	 */
	public function test_limit() {
		$this->assertSame( 4, ColorDots::limit( array() ) );
		$this->assertSame( 1, ColorDots::limit( array( 'limit' => 0 ) ) );
		$this->assertSame( 12, ColorDots::limit( array( 'limit' => 40 ) ) );
	}

	/**
	 * The REST field gives editors the colors, and nobody else anything.
	 */
	public function test_rest_field() {
		$product = new WC_Product_Simple();
		$product->set_status( 'publish' );
		$product->update_meta_data( Meta::AUTO_COLOR, '#d90324' );
		$product->save();

		$this->assertNull( ColorDots::rest_value( array( 'id' => $product->get_id() ) ), 'logged out' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/product/' . $product->get_id() );
		$request->set_param( 'context', 'edit' );
		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame(
			array(
				array(
					'name'  => '',
					'color' => '#d90324',
					'image' => 0,
				),
			),
			$data['purplish_colors']
		);
	}

	/**
	 * A photo for a test.
	 *
	 * @return int Attachment ID.
	 */
	private function photo() {
		return self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
	}

	/**
	 * Two colors, Navy with a photo on its second variation and Olive with
	 * the product's own photo, so only Navy has a photo of its own.
	 *
	 * @return array{0: WC_Product_Variable, 1: int} The product and Navy's photo.
	 */
	private function two_colors_with_photo() {
		$this->attribute(
			'color',
			array(
				'Navy'  => '#2f3647',
				'Olive' => '#6d6b4a',
			)
		);
		$this->attribute(
			'size',
			array(
				'Small' => '',
				'Large' => '',
			)
		);
		$main    = $this->photo();
		$navy    = $this->photo();
		$product = $this->variable(
			array(
				'pa_color' => array( 'navy', 'olive' ),
				'pa_size'  => array( 'small', 'large' ),
			),
			array(
				array(
					'pa_color' => 'navy',
					'pa_size'  => 'small',
				),
				array(
					'pa_color' => 'navy',
					'pa_size'  => 'large',
					'image'    => $navy,
				),
				array(
					'pa_color' => 'olive',
					'pa_size'  => 'small',
					'image'    => $main,
				),
			),
			$main
		);
		return array( $product, $navy );
	}

	/**
	 * Each color keeps the first variation photo that isn't the main one.
	 */
	public function test_colors_keep_variation_photo() {
		list( $product, $navy ) = $this->two_colors_with_photo();

		$this->assertSame( array( $navy, 0 ), wp_list_pluck( ColorDots::colors( $product ), 'image' ) );
	}

	/**
	 * With Swap the photo on, the dots are buttons in a group: each says
	 * its color, starts unpressed and carries its photo when it has one,
	 * and the swap script loads.
	 */
	public function test_render_swap() {
		list( $product, $navy ) = $this->two_colors_with_photo();

		$out = $this->render(
			array(
				'swap'   => 'hover',
				'motion' => 'wipe',
			),
			$product->get_id()
		);

		$this->assertStringContainsString( 'purplish-color-dots-swap purplish-color-dots-swap-hover', $out );
		$this->assertStringContainsString( 'role="group"', $out );
		$this->assertStringContainsString( 'data-motion="wipe"', $out );
		// The swap names the color in the photo's alt text with this pattern.
		$this->assertStringContainsString( 'data-alt="%1$s, %2$s"', $out );
		$this->assertSame( 2, substr_count( $out, '<button type="button" class="purplish-color-dots-dot"' ) );
		$this->assertSame( 2, substr_count( $out, 'aria-pressed="false"' ) );
		$this->assertStringContainsString( 'aria-label="Navy" title="Navy" data-src="' . esc_url( wp_get_attachment_image_url( $navy, 'large' ) ) . '"', $out );
		$this->assertStringContainsString( 'aria-label="Olive" title="Olive"></button>', $out );
		$this->assertSame( 1, substr_count( $out, 'data-src=' ) );
		$this->assertTrue( wp_script_is( ColorDots::SWAP_HANDLE, 'enqueued' ) );
	}

	/**
	 * Dots without names are numbered, and an unknown motion falls back
	 * to fade.
	 */
	public function test_render_swap_without_names() {
		$product = new WC_Product_Simple();
		$product->save();
		$photo = $this->photo();
		add_filter(
			'purplish_color_dots_colors',
			static function () use ( $photo ) {
				return array(
					array(
						'color' => '#222222',
						'image' => $photo,
					),
					array( 'color' => '#8a9a7b' ),
				);
			}
		);

		$out = $this->render(
			array(
				'swap'   => 'tap',
				'motion' => 'spin',
			),
			$product->get_id()
		);

		$this->assertStringContainsString( 'purplish-color-dots-swap-tap', $out );
		$this->assertStringContainsString( 'data-motion="fade"', $out );
		$this->assertStringContainsString( 'aria-label="Color 1"', $out );
		$this->assertStringContainsString( 'aria-label="Color 2"', $out );
	}

	/**
	 * Without a photo to swap to, or with the option off or unknown, the
	 * dots stay plain and no script loads.
	 */
	public function test_render_swap_needs_photo() {
		$this->attribute(
			'color',
			array(
				'Navy'  => '#2f3647',
				'Olive' => '#6d6b4a',
			)
		);
		$plain = $this->variable(
			array( 'pa_color' => array( 'navy', 'olive' ) ),
			array(
				array( 'pa_color' => 'navy' ),
				array( 'pa_color' => 'olive' ),
			)
		);

		$out = $this->render( array( 'swap' => 'tap' ), $plain->get_id() );
		$this->assertStringContainsString( 'role="img"', $out );
		$this->assertStringNotContainsString( '<button', $out );
		$this->assertStringNotContainsString( 'purplish-color-dots-swap', $out );
		$this->assertStringNotContainsString( 'data-alt', $out );
		$this->assertFalse( wp_script_is( ColorDots::SWAP_HANDLE, 'enqueued' ) );

		ColorDots::forget();
		list( $product ) = $this->two_colors_with_photo();
		$this->assertStringNotContainsString( '<button', $this->render( array(), $product->get_id() ) );
		$this->assertStringNotContainsString( '<button', $this->render( array( 'swap' => 'always' ), $product->get_id() ) );
		$this->assertFalse( wp_script_is( ColorDots::SWAP_HANDLE, 'enqueued' ) );
	}
}
