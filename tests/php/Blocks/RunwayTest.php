<?php
/**
 * Tests for the Runway block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Registry;
use Purplish\Blocks\Runway;
use Purplish\Patterns;
use WC_Product_Simple;
use WP_Block_Patterns_Registry;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block registers from the build with its render, prints one look per
 * product photo with srcset from the collection it holds, prints only the
 * inner blocks when there's nothing to walk, and its pattern holds only
 * blocks that exist.
 */
class RunwayTest extends WP_UnitTestCase {

	const NAME = 'purplish/runway';

	const INNER = '<!-- wp:heading --><h2 class="wp-block-heading">The new season</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Walk the line.</p><!-- /wp:paragraph -->';

	/**
	 * The block registered on init from the real build, in the Purplish
	 * category, with its render and scripts.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Runway', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( 'woocommerce/product-collection', 'core/heading', 'core/paragraph' ), $type->allowed_blocks );
		$this->assertSame( 'all', $type->attributes['looks']['default'] );
		$this->assertSame( 'framed', $type->attributes['shape']['default'] );
		$this->assertSame( 'on', $type->attributes['number']['default'] );
		$this->assertSame( 'on', $type->attributes['fly']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * A collection of two products prints one look per photo, featured
	 * first, then the gallery, in card order, each with srcset and an empty
	 * alt. The first loads eagerly, the rest lazily, and the stage waits
	 * hidden for the script, with the inner blocks after it.
	 */
	public function test_renders_one_look_per_photo() {
		$coat  = $this->product( 'Wool Coat', '2026-02-01', array( 'coat-front', 'coat-back', 'coat-side' ) );
		$shirt = $this->product( 'Linen Shirt', '2026-01-01', array( 'shirt' ) );

		$html = do_blocks( $this->pattern_content() );

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		foreach ( array( 'wp-block-purplish-runway', 'alignfull', 'purplish-runway-looks-all', 'purplish-runway-shape-framed', 'purplish-runway-number-on', 'purplish-runway-fly-on' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertSame( 'Runway, look %1$s of %2$s', $tags->get_attribute( 'data-look-label' ) );
		$this->assertSame( 'Shop the look: %s', $tags->get_attribute( 'data-shop-label' ) );

		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-runway-stage' ) ) );
		$this->assertTrue( $tags->get_attribute( 'hidden' ), 'stage hidden without the script' );
		$this->assertSame( '0', $tags->get_attribute( 'tabindex' ) );
		$this->assertSame( 'Runway', $tags->get_attribute( 'aria-label' ) );

		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-runway-line' ) ) );
		$this->assertSame( 'true', $tags->get_attribute( 'aria-hidden' ) );

		$products = array();
		$photos   = array();
		while ( $tags->next_tag( array( 'class_name' => 'purplish-runway-look' ) ) ) {
			$products[] = (int) $tags->get_attribute( 'data-product' );
			$this->assertNotEmpty( $tags->get_attribute( 'data-url' ) );
			$this->assertTrue( $tags->next_tag( 'img' ) );
			$photos[] = array(
				'src'     => $tags->get_attribute( 'src' ),
				'srcset'  => $tags->get_attribute( 'srcset' ),
				'alt'     => $tags->get_attribute( 'alt' ),
				'loading' => $tags->get_attribute( 'loading' ),
			);
		}

		$this->assertSame( array( $coat, $coat, $coat, $shirt ), $products );
		$this->assertStringContainsString( 'coat-front', $photos[0]['src'] );
		$this->assertStringContainsString( 'coat-back', $photos[1]['src'] );
		$this->assertStringContainsString( 'coat-side', $photos[2]['src'] );
		$this->assertStringContainsString( 'shirt', $photos[3]['src'] );
		foreach ( $photos as $photo ) {
			$this->assertStringContainsString( '300w', (string) $photo['srcset'] );
			$this->assertStringContainsString( '768w', (string) $photo['srcset'] );
			$this->assertSame( '', $photo['alt'] );
		}
		$this->assertSame( array( 'eager', 'lazy', 'lazy', 'lazy' ), array_column( $photos, 'loading' ) );

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-runway-prev' ) ) );
		$this->assertSame( 'Previous look', $tags->get_attribute( 'aria-label' ) );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-runway-next' ) ) );
		$this->assertSame( 'Next look', $tags->get_attribute( 'aria-label' ) );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-runway-shop' ) ) );
		$this->assertSame( get_permalink( $coat ), $tags->get_attribute( 'href' ) );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-runway-status' ) ) );
		$this->assertSame( 'polite', $tags->get_attribute( 'aria-live' ) );

		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-runway-content' ) ) );
		$this->assertMatchesRegularExpression( '/purplish-runway-content.*The new season.*wc-block-product-template.*Wool Coat.*Linen Shirt/s', $html );
	}

	/**
	 * "Featured photo only" prints one look per product.
	 */
	public function test_featured_photo_only() {
		$coat  = $this->product( 'Wool Coat', '2026-02-01', array( 'coat-front', 'coat-back' ) );
		$shirt = $this->product( 'Linen Shirt', '2026-01-01', array( 'shirt', 'shirt-back' ) );

		$html = do_blocks( str_replace( '<!-- wp:purplish/runway {"align":"full"} -->', '<!-- wp:purplish/runway {"align":"full","looks":"featured","shape":"cutout","number":"off","fly":"off"} -->', $this->pattern_content() ) );

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		foreach ( array( 'purplish-runway-looks-featured', 'purplish-runway-shape-cutout', 'purplish-runway-number-off', 'purplish-runway-fly-off' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}

		$products = array();
		while ( $tags->next_tag( array( 'class_name' => 'purplish-runway-look' ) ) ) {
			$products[] = (int) $tags->get_attribute( 'data-product' );
		}
		$this->assertSame( array( $coat, $shirt ), $products );
		$this->assertStringNotContainsString( 'coat-back', $html );
	}

	/**
	 * With no collection, or one with nothing to walk, only the inner
	 * blocks print inside the wrapper.
	 */
	public function test_prints_inner_blocks_only_without_looks() {
		$html = do_blocks( '<!-- wp:purplish/runway -->' . self::INNER . '<!-- /wp:purplish/runway -->' );
		$this->assertSame(
			'<div class="purplish-runway-looks-all purplish-runway-shape-framed purplish-runway-number-on purplish-runway-fly-on wp-block-purplish-runway"><div class="purplish-runway-content">' . do_blocks( self::INNER ) . '</div></div>',
			$html
		);

		$this->product( 'Plain Tee', '2026-01-01', array() );
		$this->product( 'Wool Coat', '2026-02-01', array( 'coat-front' ) );
		$html = do_blocks( $this->pattern_content() );
		$this->assertStringNotContainsString( 'purplish-runway-stage', $html, 'one photo is no line' );
		$this->assertStringContainsString( 'Plain Tee', $html );
	}

	/**
	 * Products come from the first collection only, in card order, read
	 * from the post-<ID> class on each card.
	 */
	public function test_reads_products_from_the_first_collection() {
		$html = '<h2>Intro</h2>'
			. '<div class="wp-block-woocommerce-product-collection"><ul class="wc-block-product-template"><li class="wc-block-product post-12 product type-product">A</li><li class="wc-block-product post-7 product">B</li><li class="wc-block-product post-12">A again</li></ul></div>'
			. '<div class="wp-block-woocommerce-product-collection"><ul><li class="wc-block-product post-99">C</li></ul></div>';

		$this->assertSame( array( 12, 7 ), Runway::product_ids( $html ) );
		$this->assertSame( array(), Runway::product_ids( '<ul><li class="wc-block-product post-5">Not in a collection</li></ul>' ) );
	}

	/**
	 * Unknown or missing values fall back to each option's default.
	 */
	public function test_unknown_values_fall_back() {
		$this->assertSame(
			array( 'purplish-runway-looks-all', 'purplish-runway-shape-cutout', 'purplish-runway-number-on', 'purplish-runway-fly-off' ),
			Runway::classes(
				array(
					'looks'  => 'gallery',
					'shape'  => 'cutout',
					'number' => true,
					'fly'    => 'off',
				)
			)
		);
		$this->assertSame(
			array( 'purplish-runway-looks-all', 'purplish-runway-shape-framed', 'purplish-runway-number-on', 'purplish-runway-fly-on' ),
			Runway::classes( array() )
		);
	}

	/**
	 * The pattern holds only registered blocks: the runway with a heading,
	 * a line and the 6 newest products.
	 */
	public function test_pattern() {
		( new Patterns() )->register_patterns();
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'purplish/runway' );

		$this->assertNotNull( $pattern );
		$this->assertSame( 'Runway lookbook', $pattern['title'] );
		$this->assertSame( array( self::NAME ), $pattern['blockTypes'] );

		$blocks = parse_blocks( $pattern['content'] );
		$blocks = array_values( array_filter( $blocks, static fn( $block ) => null !== $block['blockName'] ) );
		$this->assertCount( 1, $blocks );
		$this->assertSame( self::NAME, $blocks[0]['blockName'] );
		$this->assertSame(
			array( 'core/heading', 'core/paragraph', 'woocommerce/product-collection' ),
			array_column( $blocks[0]['innerBlocks'], 'blockName' )
		);
		$this->assertSame( 6, $blocks[0]['innerBlocks'][2]['attrs']['query']['perPage'] );
		$this->assertSame( 'date', $blocks[0]['innerBlocks'][2]['attrs']['query']['orderBy'] );

		foreach ( $this->block_names( $blocks ) as $name ) {
			$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( $name ), $name . ' is registered' );
		}
		$this->assertStringNotContainsString( '<img', $pattern['content'], 'no media' );
	}

	/**
	 * The pattern's markup.
	 *
	 * @return string
	 */
	private function pattern_content() {
		return Patterns::content( PURPLISH_DIR . 'patterns/runway.php' );
	}

	/**
	 * A simple product with a featured photo and the rest as its gallery.
	 *
	 * @param string   $name   Product name.
	 * @param string   $date   Date created.
	 * @param string[] $photos Photo names, featured first.
	 * @return int Product ID.
	 */
	private function product( $name, $date, $photos ) {
		$ids     = array_map( array( $this, 'photo' ), $photos );
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( '20' );
		$product->set_date_created( $date );
		if ( $ids ) {
			$product->set_image_id( array_shift( $ids ) );
			$product->set_gallery_image_ids( $ids );
		}
		return $product->save();
	}

	/**
	 * A portrait photo attachment with two smaller sizes, so it gets a
	 * srcset. No file is written: srcset only reads the metadata.
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
				'height' => 1600,
				'file'   => $file,
				'sizes'  => array(
					'medium' => array(
						'file'      => "{$name}-300x400.jpg",
						'width'     => 300,
						'height'    => 400,
						'mime-type' => 'image/jpeg',
					),
					'large'  => array(
						'file'      => "{$name}-768x1024.jpg",
						'width'     => 768,
						'height'    => 1024,
						'mime-type' => 'image/jpeg',
					),
				),
			)
		);
		return $id;
	}

	/**
	 * The names of every block in a parsed tree, inner blocks included.
	 *
	 * @param array[] $blocks Parsed blocks.
	 * @return string[]
	 */
	private function block_names( $blocks ) {
		$names = array();
		foreach ( $blocks as $block ) {
			if ( null !== $block['blockName'] ) {
				$names[] = $block['blockName'];
			}
			$names = array_merge( $names, $this->block_names( $block['innerBlocks'] ) );
		}
		return $names;
	}
}
