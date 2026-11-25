<?php
/**
 * Tests for the instalment line on the Product Price block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\InstalmentLine;
use Purplish\Instalments;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * The line goes inside the price block's wrapper only when the option is
 * on and the product shows one, and loads its styles then.
 */
class InstalmentLineTest extends WP_UnitTestCase {

	/**
	 * Puts options and assets back after each test.
	 */
	public function tear_down() {
		delete_option( Instalments::OPTION );
		wp_dequeue_style( InstalmentLine::HANDLE );
		wp_dequeue_script( InstalmentLine::HANDLE );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * A simple product.
	 *
	 * @param string $price Regular price.
	 * @return int Product ID.
	 */
	private function product( $price ) {
		$product = new WC_Product_Simple();
		$product->set_regular_price( $price );
		return $product->save();
	}

	/**
	 * The block's markup, as WooCommerce prints it on a card.
	 *
	 * @return string
	 */
	private function html() {
		return '<div class="wp-block-woocommerce-product-price"><div class="wc-block-components-product-price">$120.00</div></div>';
	}

	/**
	 * Renders the block for a product.
	 *
	 * @param array $saved Purplish options.
	 * @param int   $id    Product ID.
	 * @return string
	 */
	private function render( $saved, $id ) {
		return InstalmentLine::render(
			$this->html(),
			array(
				'blockName' => InstalmentLine::BLOCK,
				'attrs'     => array( 'purplish' => $saved ),
			),
			new \WP_Block( array( 'blockName' => InstalmentLine::BLOCK ), array( 'postId' => $id ) )
		);
	}

	/**
	 * The hooks are on, and the block keeps its attribute.
	 */
	public function test_registers() {
		( new InstalmentLine() )->register();
		$this->assertSame( 10, has_filter( 'render_block_' . InstalmentLine::BLOCK, array( InstalmentLine::class, 'render' ) ) );
		$args = InstalmentLine::add_attribute( array(), InstalmentLine::BLOCK );
		$this->assertSame( 'object', $args['attributes']['purplish']['type'] );
		$this->assertSame( array(), InstalmentLine::add_attribute( array(), 'core/paragraph' ) );
	}

	/**
	 * Off by default: the block stays as it was.
	 */
	public function test_off_leaves_the_block() {
		$id = $this->product( '120' );
		$this->assertSame( $this->html(), $this->render( array(), $id ) );
		$this->assertFalse( wp_style_is( InstalmentLine::HANDLE ) );
	}

	/**
	 * On, the line goes inside the wrapper after the price, the styles
	 * load, and a card needs no script.
	 */
	public function test_on_adds_the_line_inside_the_wrapper() {
		$html = $this->render( array( 'instalments' => true ), $this->product( '120' ) );
		$this->assertMatchesRegularExpression( '#</div><div class="purplish-instalments"><span class="purplish-instalments__text">.*</span></div></div>$#s', $html );
		$this->assertStringNotContainsString( 'data-purplish-instalments', $html );
		$this->assertTrue( wp_style_is( InstalmentLine::HANDLE ) );
		$this->assertFalse( wp_script_is( InstalmentLine::HANDLE ) );
	}

	/**
	 * A product hidden by its setting gets no line.
	 */
	public function test_hidden_product() {
		$id = $this->product( '120' );
		update_post_meta( $id, Instalments::META, array( 'mode' => 'hide' ) );
		$this->assertSame( $this->html(), $this->render( array( 'instalments' => true ), $id ) );
	}

	/**
	 * The logo and the info link come with the line.
	 */
	public function test_logo_and_link() {
		$logo = self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/fixtures/blue-sweater.webp' );
		update_option(
			Instalments::OPTION,
			array(
				'link_url' => 'https://example.com/plans',
				'logo'     => $logo,
				'logo_alt' => 'PayLater',
			)
		);
		$html = $this->render( array( 'instalments' => true ), $this->product( '120' ) );
		$this->assertStringContainsString( 'class="purplish-instalments__logo"', $html );
		$this->assertStringContainsString( 'alt="PayLater"', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringContainsString( '<a class="purplish-instalments__link" href="https://example.com/plans">Learn more</a>', $html );
	}

	/**
	 * The editor field gives the line to people who can edit the product.
	 */
	public function test_rest_value() {
		$id = $this->product( '120' );
		$this->assertNull( InstalmentLine::rest_value( array( 'id' => $id ) ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertStringContainsString( 'purplish-instalments__text', InstalmentLine::rest_value( array( 'id' => $id ) ) );
	}
}
