<?php
/**
 * Tests for the Escalator block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\Escalator;
use Purplish\Blocks\Registry;
use Purplish\Patterns;
use WC_Product_Simple;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block registers from the build, prints photo or product tiles dealt
 * into columns with a hidden copy that closes each loop, keeps its options
 * in range, carries its tile look, prints nothing without tiles, and its
 * patterns hold it.
 */
class EscalatorTest extends WP_UnitTestCase {

	const NAME = 'purplish/escalator';

	/**
	 * The block registered on init from the real build, as a server
	 * render with its scripts and defaults.
	 */
	public function test_registers() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );

		$this->assertNotNull( $type );
		$this->assertSame( 'Escalator', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( 'products', $type->attributes['source']['default'] );
		$this->assertSame( 'newest', $type->attributes['query']['default'] );
		$this->assertSame( 3, $type->attributes['columns']['default'] );
		$this->assertSame( 560, $type->attributes['height']['default'] );
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
	}

	/**
	 * Unknown values fall back to the default, and numbers stay in range.
	 */
	public function test_options() {
		$options = Escalator::options(
			array(
				'source'  => 'video',
				'columns' => 5,
				'speed'   => 'Fast',
				'count'   => 99,
				'height'  => 10,
				'images'  => array( 3, '3', 0, 'x', 7 ),
				'hover'   => 'zoom',
				'color'   => 'red',
				'radius'  => 99,
				'cart'    => 'neon',
			)
		);

		$this->assertSame( 'products', $options['source'] );
		$this->assertSame( 3, $options['columns'] );
		$this->assertSame( 'medium', $options['speed'] );
		$this->assertSame( 18, $options['count'] );
		$this->assertSame( 320, $options['height'] );
		$this->assertSame( array( 3, 7 ), $options['images'] );
		$this->assertSame( 'none', $options['hover'] );
		$this->assertSame( '', $options['color'] );
		$this->assertSame( 48, $options['radius'] );
		$this->assertSame( 'off', $options['cart'] );
		$this->assertNull( Escalator::options( array() )['radius'] );
		$this->assertSame( 2, Escalator::options( array( 'columns' => 2 ) )['columns'] );
	}

	/**
	 * Tiles are dealt one at a time, and a short column is topped up with
	 * copies from the start of the list.
	 */
	public function test_columns() {
		$tiles   = array_map(
			static function ( $n ) {
				return array( 'html' => "t$n" );
			},
			range( 1, 5 )
		);
		$columns = Escalator::columns( $tiles, 2 );

		$this->assertSame( array( 't1', 't3', 't5', 't1' ), wp_list_pluck( $columns[0], 'html' ) );
		$this->assertSame( array( 't2', 't4', 't2', 't3' ), wp_list_pluck( $columns[1], 'html' ) );
		$this->assertArrayNotHasKey( 'copy', $columns[0][2] );
		$this->assertTrue( $columns[0][3]['copy'] );
		$this->assertTrue( $columns[1][2]['copy'] );
	}

	/**
	 * Picked photos print unlinked with srcset in columns that take turns
	 * going up and down. Each column's second set and its top-up copies are
	 * hidden from screen readers but not inert, so they still take clicks. The pause button waits
	 * hidden for the view script.
	 */
	public function test_renders_photos() {
		$ids  = array_map( array( $this, 'photo' ), array( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i' ) );
		$html = render_block(
			array(
				'blockName' => self::NAME,
				'attrs'     => array(
					'source' => 'photos',
					'images' => $ids,
					'height' => 600,
				),
			)
		);

		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		$this->assertTrue( $tags->has_class( 'wp-block-purplish-escalator' ) );
		$this->assertTrue( $tags->has_class( 'purplish-escalator-columns-3' ) );
		$this->assertStringContainsString( '--purplish-escalator-height:600px', $tags->get_attribute( 'style' ) );

		$this->assertSame( 3, substr_count( $html, 'class="purplish-escalator-column ' ) );
		$this->assertSame( 2, substr_count( $html, 'purplish-escalator-up' ) );
		$this->assertSame( 1, substr_count( $html, 'purplish-escalator-down' ) );
		// Three a column, each topped up with one copy, then the whole set again.
		$this->assertSame( 24, substr_count( $html, '<img ' ) );
		$this->assertSame( 24, substr_count( $html, 'srcset=' ) );
		// One top-up copy a column, printed in both sets.
		// One top-up copy a column in the first set, then the whole second set.
		$this->assertSame( 3 + 12, substr_count( $html, '<li class="purplish-escalator-tile" aria-hidden="true">' ) );
		$this->assertSame( 3, substr_count( $html, '<ul class="purplish-escalator-set" aria-hidden="true">' ) );
		$this->assertStringNotContainsString( 'inert', $html );
		$this->assertSame( 24, substr_count( $html, '<span class="purplish-escalator-frame"><img ' ) );
		$this->assertStringNotContainsString( '<a ', $html );
		$this->assertStringContainsString( 'class="purplish-escalator-pause" aria-pressed="false" hidden', $html );
	}

	/**
	 * Products print linked, with the name and price, and only the ones
	 * with a photo. With Details off, the name stays for screen readers.
	 */
	public function test_renders_products() {
		$coat = $this->product( 'Wool Coat', 'coat' );
		$this->product( 'No Photo', '' );

		$html = render_block(
			array(
				'blockName' => self::NAME,
				'attrs'     => array( 'count' => 4 ),
			)
		);
		$this->assertStringContainsString( 'href="' . esc_url( get_permalink( $coat ) ) . '"', $html );
		$this->assertStringContainsString( '<span class="purplish-escalator-name">Wool Coat</span>', $html );
		$this->assertStringContainsString( 'purplish-escalator-price', $html );
		$this->assertStringNotContainsString( 'No Photo', $html );
		// Real tiles keep their link in the Tab order; every copy's is out.
		$this->assertSame( 1, substr_count( $html, '<li class="purplish-escalator-tile"><a class="purplish-escalator-link" href=' ) );
		$this->assertSame( substr_count( $html, '<li class="purplish-escalator-tile" aria-hidden="true">' ), substr_count( $html, 'tabindex="-1"' ) );

		$plain = render_block(
			array(
				'blockName' => self::NAME,
				'attrs'     => array( 'details' => 'off' ),
			)
		);
		$this->assertStringContainsString( '<span class="screen-reader-text">Wool Coat</span>', $plain );
		$this->assertStringNotContainsString( 'purplish-escalator-price', $plain );
	}

	/**
	 * The tile look reaches the wrapper: a hover class, the effect color
	 * and the corner radius. Without them, none are printed.
	 */
	public function test_tile_look() {
		$ids  = array( $this->photo( 'a' ) );
		$html = render_block(
			array(
				'blockName' => self::NAME,
				'attrs'     => array(
					'source' => 'photos',
					'images' => $ids,
					'hover'  => 'ring',
					'color'  => '#720EEC',
					'radius' => 0,
				),
			)
		);
		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		$this->assertTrue( $tags->has_class( 'purplish-escalator-hover-ring' ) );
		$this->assertStringContainsString( '--purplish-effect:#720EEC', $tags->get_attribute( 'style' ) );
		$this->assertStringContainsString( '--purplish-escalator-radius:0px', $tags->get_attribute( 'style' ) );

		$plain = render_block(
			array(
				'blockName' => self::NAME,
				'attrs'     => array(
					'source' => 'photos',
					'images' => $ids,
				),
			)
		);
		$this->assertStringNotContainsString( 'purplish-escalator-hover-', $plain );
		$this->assertStringNotContainsString( '--purplish-effect', $plain );
		$this->assertStringNotContainsString( '--purplish-escalator-radius', $plain );
	}

	/**
	 * With a cart look, each product gets an add to cart button on its
	 * photo and the wrapper carries the look, the Store API address and the
	 * button's words. A product that needs a choice first links to its
	 * page. Copies keep their button out of the Tab order. Photos and the
	 * default print no button.
	 */
	public function test_cart() {
		$coat     = $this->product( 'Wool Coat', 'coat' );
		$variable = new \WC_Product_Variable();
		$variable->set_name( 'Puffer' );
		$variable->set_image_id( $this->photo( 'puffer' ) );
		$variable = $variable->save();

		$html = render_block(
			array(
				'blockName' => self::NAME,
				'attrs'     => array(
					'count' => 4,
					'cart'  => 'filled',
				),
			)
		);
		$tags = new WP_HTML_Tag_Processor( $html );
		$tags->next_tag();
		$this->assertTrue( $tags->has_class( 'purplish-escalator-cart-filled' ) );
		$this->assertStringContainsString( 'wc/store/v1/', $tags->get_attribute( 'data-api' ) );
		$this->assertSame( Escalator::strings(), json_decode( $tags->get_attribute( 'data-strings' ), true ) );

		$this->assertSame( 1, substr_count( $html, '<button type="button" class="purplish-escalator-cart" data-product="' . $coat . '">' ) );
		$this->assertStringContainsString( '<span class="screen-reader-text">Wool Coat</span></button>', $html );
		$this->assertSame( 1, substr_count( $html, '<a class="purplish-escalator-cart is-link" href="' . esc_url( get_permalink( $variable ) ) . '">' ) );
		$tabbed = 0;
		$copies = 0;
		$tags   = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( array( 'class_name' => 'purplish-escalator-cart' ) ) ) {
			if ( '-1' === $tags->get_attribute( 'tabindex' ) ) {
				++$copies;
			} else {
				++$tabbed;
			}
		}
		$this->assertSame( 2, $tabbed, 'one button a product in the Tab order' );
		$this->assertGreaterThan( 0, $copies );

		foreach ( array(
			array( 'count' => 4 ),
			array(
				'source' => 'photos',
				'images' => array( $this->photo( 'a' ) ),
				'cart'   => 'filled',
			),
		) as $attrs ) {
			$plain = render_block(
				array(
					'blockName' => self::NAME,
					'attrs'     => $attrs,
				)
			);
			$this->assertStringNotContainsString( 'purplish-escalator-cart', $plain );
			$this->assertStringNotContainsString( 'data-api', $plain );
		}
	}

	/**
	 * One category without a category picked, or no photos, prints nothing.
	 */
	public function test_nothing_to_show() {
		$this->product( 'Wool Coat', 'coat' );

		$this->assertSame(
			'',
			render_block(
				array(
					'blockName' => self::NAME,
					'attrs'     => array( 'query' => 'category' ),
				)
			)
		);
		$this->assertSame(
			'',
			render_block(
				array(
					'blockName' => self::NAME,
					'attrs'     => array( 'source' => 'photos' ),
				)
			)
		);
	}

	/**
	 * Both hero patterns hold an Escalator, one with products and one with
	 * photos, beside a heading and two buttons with the light sweep. The
	 * products one has add to cart buttons.
	 */
	public function test_patterns() {
		$patterns = Patterns::patterns();
		foreach ( array(
			'escalator-hero-products' => 'products',
			'escalator-hero-photos'   => 'photos',
		) as $slug => $source ) {
			$this->assertSame( array( self::NAME ), $patterns[ $slug ]['blockTypes'] );
			$blocks = parse_blocks( Patterns::content( $patterns[ $slug ]['file'] ) );
			$found  = $this->find( $blocks, self::NAME );
			$this->assertNotNull( $found, $slug );
			$this->assertSame( $source, isset( $found['attrs']['source'] ) ? $found['attrs']['source'] : 'products' );
			$this->assertNotNull( $this->find( $blocks, 'core/buttons' ), $slug );
			$this->assertNotSame( 'none', $found['attrs']['hover'], $slug );
			foreach ( $this->find_all( $blocks, 'core/button' ) as $button ) {
				$this->assertSame( array( 'sweep' => true ), $button['attrs']['purplish'], $slug );
			}
			$this->assertSame( 'products' === $source ? 'filled' : null, isset( $found['attrs']['cart'] ) ? $found['attrs']['cart'] : null, $slug );
		}
	}

	/**
	 * The first block with a name, inner blocks included.
	 *
	 * @param array[] $blocks Parsed blocks.
	 * @param string  $name   Block name.
	 * @return array|null
	 */
	private function find( $blocks, $name ) {
		foreach ( $blocks as $block ) {
			if ( $name === $block['blockName'] ) {
				return $block;
			}
			$inner = $this->find( $block['innerBlocks'], $name );
			if ( $inner ) {
				return $inner;
			}
		}
		return null;
	}

	/**
	 * Every block with a name, inner blocks included.
	 *
	 * @param array  $blocks Parsed blocks.
	 * @param string $name   Block name.
	 * @return array[]
	 */
	private function find_all( $blocks, $name ) {
		$found = array();
		foreach ( $blocks as $block ) {
			if ( $name === $block['blockName'] ) {
				$found[] = $block;
			}
			$found = array_merge( $found, $this->find_all( $block['innerBlocks'], $name ) );
		}
		return $found;
	}

	/**
	 * A simple product, with a photo when one is named.
	 *
	 * @param string $name  Product name.
	 * @param string $photo Photo name, or empty for none.
	 * @return int Product ID.
	 */
	private function product( $name, $photo ) {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( '20' );
		if ( $photo ) {
			$product->set_image_id( $this->photo( $photo ) );
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
					'medium'       => array(
						'file'      => "{$name}-300x400.jpg",
						'width'     => 300,
						'height'    => 400,
						'mime-type' => 'image/jpeg',
					),
					'medium_large' => array(
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
}
