<?php
/**
 * Tests for the Changing Room block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Blocks\ChangingRoom;
use Purplish\Blocks\ChangingRoomPiece;
use Purplish\Blocks\Registry;
use WC_Product_Attribute;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * The block and its Piece register from the build, the rings print one
 * piece per Piece block in its place, the first top and bottom are worn
 * with their pair photo, sizes come from the variations, the data for the
 * script carries every pair, and the options turn parts off.
 */
class ChangingRoomTest extends WP_UnitTestCase {

	const NAME = 'purplish/changing-room';

	const PIECE = 'purplish/changing-room-piece';

	/**
	 * Both blocks registered on init from the real build, in the Purplish
	 * category. The Piece only goes inside Changing Room and prints nothing.
	 */
	public function test_registers() {
		$registry = WP_Block_Type_Registry::get_instance();
		$type     = $registry->get_registered( self::NAME );
		$piece    = $registry->get_registered( self::PIECE );

		$this->assertNotNull( $type );
		$this->assertSame( 'Changing Room', $type->title );
		$this->assertSame( Registry::CATEGORY, $type->category );
		$this->assertTrue( $type->is_dynamic(), 'server render' );
		$this->assertSame( array( self::PIECE ), $type->allowed_blocks );
		$this->assertSame( 'full', $type->attributes['align']['default'] );
		foreach ( ChangingRoom::OPTIONS as $option => $values ) {
			$this->assertSame( $values, $type->attributes[ $option ]['enum'], $option );
			$this->assertSame( $values[0], $type->attributes[ $option ]['default'], $option );
		}
		$this->assertNotEmpty( $type->view_script_handles );
		$this->assertNotEmpty( $type->style_handles );
		$this->assertNotEmpty( $type->editor_script_handles );
		$this->assertNotEmpty( $type->editor_style_handles );

		$this->assertNotNull( $piece );
		$this->assertSame( array( self::NAME ), $piece->parent );
		$this->assertSame( array( 'top', 'bottom' ), $piece->attributes['slot']['enum'] );
		$this->assertSame( '', render_block( $this->block( self::PIECE, array( 'product' => 1 ) ) ) );
	}

	/**
	 * Two tops and two bottoms print two rings in order, the first of each
	 * worn and hidden on him, the next standing one step out. The first
	 * pair's photo prints eager, what he wears and the outfit total print
	 * under him, and the data holds every pair's photo and total.
	 */
	public function test_renders_rings_and_outfit() {
		$jacket   = $this->product( 'Linen Jacket', '120' );
		$overcoat = $this->product( 'Overcoat', '200' );
		$chinos   = $this->product( 'Chinos', '60' );
		$jeans    = $this->product( 'Jeans', '80' );
		$photo    = $this->photo( 'jacket-chinos' );
		$html     = do_blocks(
			$this->content(
				array( $jacket, $overcoat ),
				array( $chinos, $jeans ),
				array(
					'photos' => array(
						$jacket . '|' . $chinos  => array(
							'id'  => $photo,
							'url' => '',
						),
						$overcoat . '|' . $jeans => array(
							'id'  => 0,
							'url' => 'https://example.com/overcoat-jeans.jpg',
						),
					),
				)
			)
		);

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( 'section' ) );
		foreach ( array( 'wp-block-purplish-changing-room', 'alignfull', 'purplish-changing-room-heading-off', 'purplish-changing-room-drawer-on', 'purplish-changing-room-color-last', 'purplish-changing-room-swap-fade' ) as $class_name ) {
			$this->assertTrue( $tags->has_class( $class_name ), $class_name );
		}
		$this->assertFalse( $tags->has_class( 'purplish-button' ), 'theme buttons by default' );

		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'purplish-changing-room-photo' ) ) );
		$this->assertSame( 'eager', $tags->get_attribute( 'loading' ) );
		$this->assertSame( 'high', $tags->get_attribute( 'fetchpriority' ) );
		$this->assertSame( 'Wearing the Linen Jacket and Chinos.', $tags->get_attribute( 'alt' ), 'the photo names the outfit it shows' );

		$rings = array();
		while ( $tags->next_tag( array( 'class_name' => 'purplish-changing-room-ring' ) ) ) {
			$slot = $tags->has_class( 'purplish-changing-room-ring-top' ) ? 'top' : 'bottom';
			for ( $n = 0; $n < 2; $n++ ) {
				$tags->next_tag( 'li' );
				$rings[ $slot ][] = array(
					'style' => $tags->get_attribute( 'style' ),
					'inert' => null !== $tags->get_attribute( 'inert' ),
				);
				$tags->next_tag( 'button' );
				$rings[ $slot ][ $n ]['label'] = $tags->get_attribute( 'aria-label' );
			}
		}
		$this->assertCount( 2, $rings['top'] );
		$this->assertCount( 2, $rings['bottom'] );
		$this->assertStringContainsString( 'opacity:0.000', $rings['top'][0]['style'], 'the worn piece hides on him' );
		$this->assertTrue( $rings['top'][0]['inert'] );
		$this->assertStringContainsString( '--x:1.0000;', $rings['top'][1]['style'], 'one step out' );
		$this->assertStringContainsString( 'opacity:1.000', $rings['top'][1]['style'] );
		$this->assertFalse( $rings['top'][1]['inert'] );
		$this->assertStringStartsWith( 'Overcoat, ', $rings['top'][1]['label'] );
		$this->assertStringStartsWith( 'Jeans, ', $rings['bottom'][1]['label'] );

		$this->assertStringContainsString( '<span class="purplish-changing-room-tag-name">Linen Jacket</span>', $html );
		$this->assertStringContainsString( '<span class="purplish-changing-room-tag-name">Chinos</span>', $html );
		$this->assertStringContainsString( '<span class="purplish-changing-room-outfit-t">Add both</span> <span class="purplish-changing-room-total">', $html );
		$this->assertStringContainsString( 'Wearing the Linen Jacket and Chinos.', $html );
		$this->assertStringContainsString( '<dialog class="purplish-changing-room-drawer"', $html );
		$this->assertStringContainsString( 'popover="manual"', $html );

		$data = $this->data( $html );
		$this->assertSame( array( $jacket, $overcoat ), wp_list_pluck( $data['top'], 'id' ) );
		$this->assertSame( array( $chinos, $jeans ), wp_list_pluck( $data['bottom'], 'id' ) );
		$this->assertCount( 4, $data['looks'] );
		$this->assertStringContainsString( 'jacket-chinos.png', $data['looks'][ $jacket . '|' . $chinos ][0] );
		$this->assertSame( 'https://example.com/overcoat-jeans.jpg', $data['looks'][ $overcoat . '|' . $jeans ][0] );
		$this->assertSame( '', $data['looks'][ $jacket . '|' . $jeans ][0], 'a pair without a photo' );
		$this->assertSame( rest_url( 'wc/store/v1/' ), $data['api'] );
		$this->assertSame( ChangingRoom::strings(), $data['s'] );
		$this->assertTrue( $data['top'][0]['a'] );
	}

	/**
	 * A variable product lists its sizes in order, with a crossed-out chip
	 * for a sold out size, a stock line per size and a size label for the
	 * notice. The panel template carries the chips.
	 */
	public function test_sizes_from_variations() {
		$top    = $this->variable(
			'Tee',
			array(
				'S' => 5,
				'M' => 0,
				'L' => 2,
			)
		);
		$bottom = $this->product( 'Shorts', '40' );
		$html   = do_blocks( $this->content( array( $top ), array( $bottom ) ) );
		$data   = $this->data( $html );
		$sizes  = $data['top'][0]['sizes'];

		$this->assertSame( array( 'S', 'M', 'L' ), wp_list_pluck( $sizes, 'l' ) );
		$this->assertSame( array( '5 in stock', 'Sold out', 'Only 2 left in L' ), wp_list_pluck( $sizes, 't' ) );
		$this->assertSame( 'Tee, S', $sizes[0]['n'] );
		$this->assertTrue( $data['top'][0]['a'] );
		$this->assertSame( array(), $data['bottom'][0]['sizes'] );
		$this->assertStringContainsString( 'data-size="1" aria-pressed="false" aria-label="M, sold out" disabled>M</button>', $html );
		$this->assertStringContainsString( 'aria-label="S, 5 in stock">S</button>', $html );
	}

	/**
	 * With no bottom piece only the empty wrapper prints. A Piece without
	 * a product, or a second Piece with the same product in a ring, is
	 * skipped.
	 */
	public function test_empty_wrapper_and_skipped_pieces() {
		$jacket = $this->product( 'Jacket', '100' );
		$html   = do_blocks( $this->content( array( $jacket ), array() ) );
		$this->assertStringNotContainsString( 'purplish-changing-room-stage', $html );
		$this->assertStringContainsString( 'wp-block-purplish-changing-room', $html );

		$chinos = $this->product( 'Chinos', '50' );
		$html   = do_blocks( $this->content( array( $jacket, $jacket, 0 ), array( $chinos ) ) );
		$this->assertCount( 1, $this->data( $html )['top'] );
	}

	/**
	 * Unknown values fall back to the defaults, and each option turns its
	 * part off: no panel, no tags, no Add both, no notice. A Purplish look
	 * adds the button classes, and the hint names only the moves left on.
	 */
	public function test_options_fall_back_and_turn_parts_off() {
		$this->assertSame( 'fade', ChangingRoom::options( array( 'swap' => 'spin' ) )['swap'] );
		$this->assertSame( 'on', ChangingRoom::options( array( 'drawer' => 'maybe' ) )['drawer'] );

		$html = do_blocks(
			$this->content(
				array( $this->product( 'Jacket', '100' ) ),
				array( $this->product( 'Chinos', '50' ) ),
				array(
					'drawer'  => 'off',
					'tags'    => 'off',
					'outfit'  => 'off',
					'notice'  => 'off',
					'wheel'   => 'off',
					'look'    => 'glow',
					'heading' => 'on',
				)
			)
		);
		foreach ( array( 'purplish-changing-room-drawer', 'purplish-changing-room-tag-name', 'purplish-changing-room-outfit-add', 'popover=', 'purplish-changing-room-sheet' ) as $part ) {
			$this->assertStringNotContainsString( $part . '"', $html, $part );
		}
		$this->assertStringNotContainsString( '<dialog', $html );
		$this->assertStringContainsString( 'purplish-button purplish-button-glow', $html );
		$this->assertStringContainsString( '<h2 class="wp-block-heading purplish-changing-room-heading">Put a look together</h2>', $html );
		$this->assertStringContainsString( 'Swipe a row, drag a piece onto the model or tap one to try it on', $html );
	}

	/**
	 * The ring math matches ring.js: the front piece sits on him, hidden,
	 * its neighbours one step out, and far pieces fade only when far
	 * pieces are on.
	 */
	public function test_ring_places() {
		$front = ChangingRoom::spot( 0, 6, 'top', true );
		$this->assertEquals( 0, $front['o'] );
		$this->assertEqualsWithDelta( 0.35 + 0.09 * ( cos( M_PI / 6 ) - 1 ), $front['y'], 0.0001 );
		$this->assertSame( 20, $front['z'] );

		$next = ChangingRoom::spot( 1, 6, 'top', true );
		$this->assertEqualsWithDelta( 1.0, $next['x'], 0.0001 );
		$this->assertEqualsWithDelta( 1.0, $next['s'], 0.0001 );
		$this->assertEqualsWithDelta( 1.0, $next['o'], 0.0001 );
		$this->assertEqualsWithDelta( 0.35, $next['y'], 0.0001 );
		$this->assertEqualsWithDelta( 3.0, $next['r'], 0.0001 );

		$this->assertEqualsWithDelta( 0.55, ChangingRoom::spot( 2, 6, 'top', true )['o'], 0.0001 );
		$this->assertEquals( 0, ChangingRoom::spot( 2, 6, 'top', false )['o'] );
		$this->assertSame( -1, ChangingRoom::offset( 5, 0, 6 ) );
		$this->assertSame( 2, ChangingRoom::offset( 2, 0, 6 ) );
	}

	/**
	 * Stock lines read the way a shopper says them.
	 */
	public function test_stock_lines() {
		$this->assertSame( 'Sold out', ChangingRoomPiece::stock( 4, false ) );
		$this->assertSame( 'In stock', ChangingRoomPiece::stock( null, true ) );
		$this->assertSame( 'Only 3 left', ChangingRoomPiece::stock( 3, true ) );
		$this->assertSame( '12 in stock', ChangingRoomPiece::stock( 12, true ) );
	}

	/**
	 * The script's data from the block's markup.
	 *
	 * @param string $html Rendered block.
	 * @return array
	 */
	private function data( $html ) {
		$this->assertSame( 1, preg_match( '#<script type="application/json" class="purplish-changing-room-data">(.*?)</script>#s', $html, $match ) );
		return json_decode( $match[1], true );
	}

	/**
	 * A parsed block.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Attributes.
	 * @return array
	 */
	private function block( $name, $attrs ) {
		return array(
			'blockName'    => $name,
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}

	/**
	 * Block markup with a Piece for each product.
	 *
	 * @param int[] $tops    Top product IDs.
	 * @param int[] $bottoms Bottom product IDs.
	 * @param array $options Block attributes.
	 * @return string
	 */
	private function content( $tops, $bottoms, $options = array() ) {
		$inner = '';
		foreach ( array(
			'top'    => $tops,
			'bottom' => $bottoms,
		) as $slot => $ids ) {
			foreach ( $ids as $id ) {
				$inner .= '<!-- wp:purplish/changing-room-piece ' . wp_json_encode(
					array(
						'slot'    => $slot,
						'product' => $id,
					)
				) . ' /-->';
			}
		}
		$attrs = $options ? ' ' . wp_json_encode( $options ) : '';
		return '<!-- wp:purplish/changing-room' . $attrs . ' -->' . $inner . '<!-- /wp:purplish/changing-room -->';
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
	 * A variable product with one variation per size, each with its stock.
	 *
	 * @param string             $name  Product name.
	 * @param array<string, int> $sizes Size and stock.
	 * @return int Product ID.
	 */
	private function variable( $name, $sizes ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array_keys( $sizes ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( $name );
		$product->set_attributes( array( $attribute ) );
		$id = $product->save();

		foreach ( $sizes as $size => $stock ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $id );
			$variation->set_attributes( array( 'size' => $size ) );
			$variation->set_regular_price( '20' );
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( $stock );
			$variation->save();
		}
		\WC_Product_Variable::sync( $id );
		return $id;
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
				'width'  => 1200,
				'height' => 1800,
				'file'   => $file,
				'sizes'  => array(),
			)
		);
		return $id;
	}
}
