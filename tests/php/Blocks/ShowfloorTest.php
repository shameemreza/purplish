<?php
/**
 * Tests for the Showfloor block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Registry;
use Purplish\Blocks\Showfloor;
use WC_Product_Simple;
use WC_Product_Variable;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block and its Look register from the build, the floor prints one
 * look per Look block with an image, the front look's copy and products
 * print in place, markers and the face crop come from the Look's spots,
 * products that can't be added here link to their page, and unknown
 * values fall back to the defaults.
 */
class ShowfloorTest extends WP_UnitTestCase {

	const NAME = 'purplish/showfloor';

	const LOOK = 'purplish/showfloor-look';

	/**
	 * Both blocks registered on init from the real build, in the Purplish
	 * category. The Look only goes inside Showfloor and prints nothing.
	 */
	public function test_registers() {
		$registry = WP_Block_Type_Registry::get_instance();
		$type     = $registry->get_registered( self::NAME );
		$look     = $registry->get_registered( self::LOOK );

		$this->assertNotNull( $type );
		$this->assertSame( 'Showfloor', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( self::LOOK ), $type->allowed_blocks );
		$this->assertSame( 'full', $type->attributes['align']['default'] );
		foreach ( Showfloor::OPTIONS as $option => $values ) {
			$this->assertSame( $values, $type->attributes[ $option ]['enum'], $option );
			$this->assertSame( $values[0], $type->attributes[ $option ]['default'], $option );
		}
		$this->assertSame( 4, $type->attributes['speed']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
		$this->assertNotEmpty( $type->editor_style_handles );

		$this->assertNotNull( $look );
		$this->assertSame( array( self::NAME ), $look->parent );
		$this->assertTrue( $look->is_dynamic() );
		$this->assertSame(
			'',
			render_block(
				array(
					'blockName'    => self::LOOK,
					'attrs'        => array( 'id' => 1 ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				)
			)
		);
	}

	/**
	 * Two looks print two figures in order, the first loading first. The
	 * front look's name, piece line, short line and products print in
	 * place, every look's products and markers wait in templates, and the
	 * wrapper carries the option classes, the Store API root, the speed
	 * and the strings for the script.
	 */
	public function test_renders_looks() {
		$coat  = $this->product( 'Wool Coat', '120' );
		$boots = $this->product( 'Ankle Boots', '80' );
		$scarf = $this->product( 'Silk Scarf', '30' );
		$html  = do_blocks(
			$this->content(
				array(
					array(
						'name'     => 'Harbor',
						'line'     => 'Wool for the walk home.',
						'products' => array( $coat, $boots ),
						'marks'    => array(
							$coat => array(
								'x' => 0.45,
								'y' => 0.3,
							),
						),
						'face'     => array(
							'x' => 0.6,
							'y' => 0.05,
						),
					),
					array(
						'name'     => 'Gallery',
						'products' => array( $scarf ),
					),
				)
			)
		);

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( 'section' ) );
		foreach ( array( 'wp-block-purplish-showfloor', 'alignfull', 'purplish-showfloor-shape-oval', 'purplish-showfloor-autoplay-off', 'purplish-showfloor-list-both', 'purplish-showfloor-marks-highlight' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertSame( rest_url( 'wc/store/v1/' ), $tags->get_attribute( 'data-api' ) );
		$this->assertSame( '4000', $tags->get_attribute( 'data-speed' ) );
		$this->assertSame( Showfloor::strings(), json_decode( $tags->get_attribute( 'data-strings' ), true ) );

		$looks = array();
		while ( $tags->next_tag( array( 'class_name' => 'purplish-showfloor-look' ) ) ) {
			$looks[] = array(
				'name'  => $tags->get_attribute( 'data-name' ),
				'meta'  => $tags->get_attribute( 'data-meta' ),
				'whole' => $tags->get_attribute( 'data-whole' ),
			);
			$tags->next_tag( 'img' );
			$looks[ count( $looks ) - 1 ]['loading'] = $tags->get_attribute( 'loading' );
		}
		$this->assertSame( array( 'Harbor', 'Gallery' ), wp_list_pluck( $looks, 'name' ) );
		$this->assertSame( array( 'eager', 'lazy' ), wp_list_pluck( $looks, 'loading' ) );
		$this->assertStringStartsWith( '2 pieces, ', $looks[0]['meta'] );
		$this->assertSame( 'Add all 2 pieces', $looks[0]['whole'] );
		$this->assertSame( '', $looks[1]['whole'], 'no whole-look button for one piece' );

		$this->assertStringContainsString( '<h2 class="wp-block-heading purplish-showfloor-title">Harbor</h2>', $html );
		$this->assertStringContainsString( '<p class="purplish-showfloor-line">Wool for the walk home.</p>', $html );
		$this->assertStringContainsString( 'wp-element-button purplish-showfloor-open">Shop this look<', $html );
		$this->assertStringContainsString( 'style="--x:0.45;--y:0.3" data-piece="1" data-name="Wool Coat"', $html );
		$this->assertStringContainsString( 'style="--x:0.5;--y:0.667" data-piece="2" data-name="Ankle Boots"', $html, 'a piece without a spot stands down the middle' );
		$this->assertStringContainsString( 'style="--fx:0.6;--fy:0.05"', $html );

		$panel = substr( $html, strpos( $html, '<aside' ) );
		$this->assertStringContainsString( 'data-product="' . $coat . '"', $panel );
		$this->assertStringContainsString( 'data-product="' . $boots . '"', $panel );
		$this->assertStringNotContainsString( 'data-product="' . $scarf . '"', $panel, 'only the front look in the panel' );
	}

	/**
	 * A variable product links to its page with WooCommerce's own text,
	 * and its look gets no whole-look button.
	 */
	public function test_variable_product_links() {
		$coat    = $this->product( 'Wool Coat', '120' );
		$variant = new WC_Product_Variable();
		$variant->set_name( 'Knit Hat' );
		$variant->save();

		$html = do_blocks(
			$this->content(
				array(
					array(
						'name'     => 'Harbor',
						'products' => array( $coat, $variant->get_id() ),
					),
				)
			)
		);

		$this->assertStringContainsString( 'class="purplish-showfloor-add is-link" href="' . esc_url( $variant->get_permalink() ) . '"', $html );
		$this->assertStringContainsString( 'data-whole=""', $html );
		$this->assertStringContainsString( '<button type="button" class="purplish-showfloor-whole" hidden>', $html );
	}

	/**
	 * A Look without an image prints nothing; with no look left, only an
	 * empty wrapper prints.
	 */
	public function test_empty_wrapper_without_images() {
		$html = do_blocks( '<!-- wp:purplish/showfloor --><!-- wp:purplish/showfloor-look {"name":"Harbor"} /--><!-- /wp:purplish/showfloor -->' );

		$this->assertStringContainsString( 'wp-block-purplish-showfloor', $html );
		$this->assertStringNotContainsString( 'purplish-showfloor-stage', $html );
	}

	/**
	 * Unknown values fall back to the defaults, the speed stays between
	 * three and eight seconds, and off settings leave their parts out.
	 */
	public function test_options_fall_back_and_turn_parts_off() {
		$this->assertSame( 'oval', Showfloor::options( array( 'shape' => 'square' ) )['shape'] );
		$this->assertSame( 'strong', Showfloor::options( array( 'depth' => 'strong' ) )['depth'] );
		$this->assertSame( 3, Showfloor::speed( array( 'speed' => 1 ) ) );
		$this->assertSame( 8, Showfloor::speed( array( 'speed' => 20 ) ) );
		$this->assertSame( 4, Showfloor::speed( array( 'speed' => 'fast' ) ) );

		$coat = $this->product( 'Wool Coat', '120' );
		$html = do_blocks(
			$this->content(
				array(
					array(
						'name'     => 'Harbor',
						'products' => array( $coat ),
					),
				),
				array(
					'list'     => 'off',
					'toggle'   => 'off',
					'shop'     => 'off',
					'autoplay' => 'on',
					'total'    => 'off',
				)
			)
		);

		$this->assertStringNotContainsString( 'purplish-showfloor-names', $html );
		$this->assertStringNotContainsString( 'purplish-showfloor-view', $html );
		$this->assertStringNotContainsString( 'purplish-showfloor-open', $html );
		$this->assertStringContainsString( 'purplish-showfloor-play', $html );
		$this->assertStringContainsString( 'data-meta="1 piece"', $html );
	}

	/**
	 * Saved block markup for a Showfloor holding these looks.
	 *
	 * @param array[] $looks   Look attributes, without the image.
	 * @param array   $options Showfloor attributes.
	 * @return string
	 */
	private function content( $looks, $options = array() ) {
		$inner = '';
		foreach ( $looks as $index => $look ) {
			$look['id'] = $this->photo( 'look-' . $index );
			$inner     .= '<!-- wp:purplish/showfloor-look ' . wp_json_encode( $look ) . ' /-->';
		}
		$attrs = $options ? ' ' . wp_json_encode( $options ) : '';
		return '<!-- wp:purplish/showfloor' . $attrs . ' -->' . $inner . '<!-- /wp:purplish/showfloor -->';
	}

	/**
	 * A simple product with a price.
	 *
	 * @param string $name  Product name.
	 * @param string $price Regular price.
	 * @return int Product ID.
	 */
	private function product( $name, $price ) {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( $price );
		return $product->save();
	}

	/**
	 * A portrait image attachment. No file is written: the block only
	 * reads the metadata.
	 *
	 * @param string $name Image name.
	 * @return int Attachment ID.
	 */
	private function photo( $name ) {
		$file = "2026/11/{$name}.png";
		$id   = self::factory()->attachment->create_object(
			array(
				'file'           => $file,
				'post_mime_type' => 'image/png',
				'post_title'     => $name,
			)
		);
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 800,
				'height' => 2000,
				'file'   => $file,
				'sizes'  => array(),
			)
		);
		return $id;
	}
}
