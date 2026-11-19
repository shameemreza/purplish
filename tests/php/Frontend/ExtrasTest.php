<?php
/**
 * Tests for matching extras under Add to Cart + Options.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\Extras;
use WP_UnitTestCase;

/**
 * The switch is a class on the block. With it, the block's form gets a
 * list of other products to tick, picked by hand, from the product's
 * cross-sells or by category, and a posted form adds the ticked ones.
 */
class ExtrasTest extends WP_UnitTestCase {

	const BLOCK = '<div class="wc-block-components-notices"><form class="wp-block-woocommerce-add-to-cart-with-options" data-wp-on--submit="actions.addToCart"><button type="submit" class="single_add_to_cart_button">Add to cart</button><input type="hidden" name="add-to-cart" value="1" /></form></div>';

	/**
	 * Drops the script, the post and the cart between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( Extras::HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE . '-extras' );
		unset( $GLOBALS['post'], $_REQUEST[ Extras::FIELD ], $_REQUEST['add-to-cart'] );
		$added = new \ReflectionProperty( Extras::class, 'added' );
		$added->setAccessible( true );
		$added->setValue( null, false );
		parent::tear_down();
	}

	/**
	 * A saved simple product.
	 *
	 * @param string $name    Name.
	 * @param array  $changes Setter name => value.
	 * @return \WC_Product_Simple
	 */
	private function product( $name, $changes = array() ) {
		$product = new \WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( '20' );
		foreach ( $changes as $setter => $value ) {
			$product->{'set_' . $setter}( $value );
		}
		$product->save();
		return $product;
	}

	/**
	 * Makes a product the current post.
	 *
	 * @param \WC_Product $product The product.
	 * @return void
	 */
	private function on( $product ) {
		$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Renders the block with the given Purplish options.
	 *
	 * @param array $saved Purplish options.
	 * @return string
	 */
	private function render( $saved ) {
		return ( new BlockEffects() )->add_classes(
			self::BLOCK,
			array(
				'blockName' => 'woocommerce/add-to-cart-with-options',
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * The names in a rendered list, in order.
	 *
	 * @param string $html Rendered block.
	 * @return string[]
	 */
	private function names( $html ) {
		preg_match_all( '/<label class="purplish-extra-name"[^>]*>([^<]*)<\/label>/', $html, $found );
		return $found[1];
	}

	/**
	 * The switch is a class on the block, next to the other buy row looks.
	 */
	public function test_option() {
		$this->assertSame( array( 'purplish-cart', 'purplish-cart-extras' ), BlockEffects::classes_for( 'woocommerce/add-to-cart-with-options', array( 'extras' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/add-to-cart-with-options', array( 'extras' => 'yes' ) ) );
		$this->assertContains( 'extras', BlockEffects::PARTS );
	}

	/**
	 * Unsaved and unknown settings fall back to the defaults, and the count
	 * stays between 1 and 6.
	 */
	public function test_settings() {
		$this->assertSame(
			array(
				'from'     => 'crosssells',
				'pick'     => array(),
				'fallback' => true,
				'title'    => 'Add matching extras?',
				'notitle'  => false,
				'style'    => 'cards',
				'photo'    => true,
				'link'     => true,
				'desc'     => false,
				'count'    => 3,
			),
			Extras::settings( array( 'extras' => true ) )
		);

		$settings = Extras::settings(
			array(
				'extrasfrom'     => 'random',
				'extraspick'     => array( '7', 7, 0, 'x', 9 ),
				'extrasfallback' => false,
				'extrastitle'    => '  Add stickers?  ',
				'extrasnotitle'  => true,
				'extrasstyle'    => 'tiles',
				'extrasphoto'    => false,
				'extraslink'     => false,
				'extrasdesc'     => true,
				'extrascount'    => 40,
			)
		);
		$this->assertSame( 'crosssells', $settings['from'] );
		$this->assertSame( array( 7, 9 ), $settings['pick'] );
		$this->assertFalse( $settings['fallback'] );
		$this->assertSame( 'Add stickers?', $settings['title'] );
		$this->assertTrue( $settings['notitle'] );
		$this->assertSame( 'tiles', $settings['style'] );
		$this->assertFalse( $settings['photo'] );
		$this->assertFalse( $settings['link'] );
		$this->assertTrue( $settings['desc'] );
		$this->assertSame( 6, $settings['count'] );
		$this->assertSame( 1, Extras::settings( array( 'extrascount' => 0 ) )['count'] );
	}

	/**
	 * Only simple products that can go in the cart are kept, in the given
	 * order, never the product on the page, up to the count.
	 */
	public function test_usable() {
		$page     = $this->product( 'Trousers' );
		$first    = $this->product( 'Scarf' );
		$second   = $this->product( 'Belt' );
		$out      = $this->product( 'Gone', array( 'stock_status' => 'outofstock' ) );
		$draft    = $this->product( 'Draft', array( 'status' => 'draft' ) );
		$hidden   = $this->product( 'Hidden', array( 'catalog_visibility' => 'hidden' ) );
		$no_price = $this->product( 'Free text', array( 'regular_price' => '' ) );
		$variable = new \WC_Product_Variable();
		$variable->set_name( 'Sweater' );
		$variable->save();

		$ids  = array( $second->get_id(), $page->get_id(), $out->get_id(), $draft->get_id(), $hidden->get_id(), $no_price->get_id(), $variable->get_id(), $first->get_id(), 999999 );
		$kept = array_map(
			static function ( $extra ) {
				return $extra->get_name();
			},
			Extras::usable( $ids, $page->get_id(), 6 )
		);
		$this->assertSame( array( 'Belt', 'Scarf' ), $kept );
		$this->assertCount( 1, Extras::usable( $ids, $page->get_id(), 1 ) );
	}

	/**
	 * Pick them keeps the picked order, Cross-sells reads the product's own,
	 * and both fall back to the same category unless that's off.
	 */
	public function test_sources() {
		$category = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$page     = $this->product( 'Trousers', array( 'category_ids' => array( $category ) ) );
		$scarf    = $this->product( 'Scarf' );
		$belt     = $this->product( 'Belt' );
		$this->product( 'Socks', array( 'category_ids' => array( $category ) ) );

		$pick = Extras::settings(
			array(
				'extrasfrom' => 'pick',
				'extraspick' => array( $belt->get_id(), $scarf->get_id() ),
			)
		);
		$this->assertSame( array( $belt->get_id(), $scarf->get_id() ), array_map( array( $this, 'id' ), Extras::products( $page, $pick ) ) );

		$cross = Extras::settings( array() );
		$this->assertSame( array( 'Socks' ), array_map( array( $this, 'name' ), Extras::products( $page, $cross ) ), 'No cross-sells falls back to the category.' );

		$page->set_cross_sell_ids( array( $scarf->get_id() ) );
		$page->save();
		$this->assertSame( array( 'Scarf' ), array_map( array( $this, 'name' ), Extras::products( $page, $cross ) ) );

		$auto = Extras::settings( array( 'extrasfrom' => 'auto' ) );
		$this->assertSame( array( 'Socks' ), array_map( array( $this, 'name' ), Extras::products( $page, $auto ) ) );

		$bare = $this->product( 'Hat' );
		$this->assertSame( array(), Extras::products( $bare, Extras::settings( array( 'extrasfallback' => false ) ) ) );
	}

	/**
	 * A product's ID.
	 *
	 * @param \WC_Product $product The product.
	 * @return int
	 */
	public function id( $product ) {
		return $product->get_id();
	}

	/**
	 * A product's name.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	public function name( $product ) {
		return $product->get_name();
	}

	/**
	 * The list goes inside the form, after the button, with the picked
	 * style, and brings its stylesheet and script.
	 */
	public function test_render() {
		$scarf = $this->product( 'Scarf', array( 'short_description' => 'Soft <strong>wool</strong> scarf for cold days.' ) );
		$page  = $this->product( 'Trousers', array( 'cross_sell_ids' => array( $scarf->get_id() ) ) );
		$this->on( $page );

		$html = $this->render(
			array(
				'extras'      => true,
				'extrasstyle' => 'compact',
				'extrasdesc'  => true,
			)
		);

		$this->assertStringContainsString( 'purplish-cart-extras', $html );
		$this->assertMatchesRegularExpression( '#Add to cart</button><input type="hidden" name="add-to-cart" value="1" /><fieldset class="purplish-extras purplish-extras-compact"[^>]*><legend class="purplish-extras-title">Add matching extras\?</legend>.*</fieldset></form></div>$#s', $html );
		$this->assertSame( array( 'Scarf' ), $this->names( $html ) );
		$this->assertStringContainsString( 'name="purplish_extras[]" value="' . $scarf->get_id() . '"', $html );
		$this->assertStringContainsString( '<p class="purplish-extra-desc">Soft wool scarf for cold days.</p>', $html );
		$this->assertStringContainsString( 'See more details<span class="screen-reader-text"> about Scarf (opens in a new tab)</span>', $html );
		$this->assertStringContainsString( 'data-api="' . esc_url( rest_url( 'wc/store/v1/' ) ) . '"', $html );
		$this->assertMatchesRegularExpression( '#<span class="purplish-extra-price">\+ <span class="woocommerce-Price-amount amount">#', $html );
		$this->assertTrue( wp_script_is( Extras::HANDLE, 'enqueued' ) );
		$this->assertSame( 'defer', wp_scripts()->get_data( Extras::HANDLE, 'strategy' ) );
		$this->assertTrue( wp_style_is( BlockEffects::HANDLE . '-extras', 'enqueued' ) );
	}

	/**
	 * A hidden title stays for screen readers, and the photo and link can
	 * be left out.
	 */
	public function test_render_options() {
		$scarf = $this->product( 'Scarf' );
		$this->on( $this->product( 'Trousers', array( 'cross_sell_ids' => array( $scarf->get_id() ) ) ) );

		$html = $this->render(
			array(
				'extras'        => true,
				'extrastitle'   => 'Add stickers?',
				'extrasnotitle' => true,
				'extrasphoto'   => false,
				'extraslink'    => false,
			)
		);

		$this->assertStringContainsString( '<legend class="purplish-extras-title screen-reader-text">Add stickers?</legend>', $html );
		$this->assertStringNotContainsString( 'purplish-extra-photo', $html );
		$this->assertStringNotContainsString( 'purplish-extra-link', $html );
		$this->assertStringNotContainsString( 'purplish-extra-desc', $html );
	}

	/**
	 * With nothing to show, or off a product page, the block stays as it is
	 * and nothing loads.
	 */
	public function test_nothing_to_show() {
		$this->on( $this->product( 'Trousers' ) );
		$html = $this->render(
			array(
				'extras'         => true,
				'extrasfallback' => false,
			)
		);
		$this->assertStringNotContainsString( 'purplish-extras', str_replace( 'purplish-cart-extras', '', $html ) );
		$this->assertFalse( wp_script_is( Extras::HANDLE, 'enqueued' ) );

		unset( $GLOBALS['post'] );
		$this->assertSame( self::BLOCK, Extras::render( self::BLOCK, array( 'extras' => true ) ) );
	}

	/**
	 * A posted form adds the ticked extras once, after the main product,
	 * and skips ones that can't go in.
	 */
	public function test_posted_form_adds_extras() {
		$page  = $this->product( 'Trousers' );
		$scarf = $this->product( 'Scarf' );
		$gone  = $this->product( 'Gone', array( 'stock_status' => 'outofstock' ) );
		WC()->initialize_cart();
		WC()->cart->empty_cart();

		$_REQUEST['add-to-cart']   = (string) $page->get_id();
		$_REQUEST[ Extras::FIELD ] = array( (string) $scarf->get_id(), (string) $gone->get_id() );
		WC()->cart->add_to_cart( $page->get_id(), 1 );

		$in = array_values( wp_list_pluck( WC()->cart->get_cart(), 'product_id' ) );
		$this->assertSame( array( $page->get_id(), $scarf->get_id() ), $in );

		WC()->cart->add_to_cart( $page->get_id(), 1 );
		$this->assertCount( 2, WC()->cart->get_cart(), 'The extras go in once per request.' );
		WC()->cart->empty_cart();
	}

	/**
	 * Adding another product, or adding without ticked boxes, adds nothing
	 * more.
	 */
	public function test_other_adds_add_no_extras() {
		$page  = $this->product( 'Trousers' );
		$other = $this->product( 'Belt' );
		$scarf = $this->product( 'Scarf' );
		WC()->initialize_cart();
		WC()->cart->empty_cart();

		$_REQUEST['add-to-cart']   = (string) $page->get_id();
		$_REQUEST[ Extras::FIELD ] = array( (string) $scarf->get_id() );
		WC()->cart->add_to_cart( $other->get_id(), 1 );
		$this->assertCount( 1, WC()->cart->get_cart() );

		unset( $_REQUEST[ Extras::FIELD ] );
		WC()->cart->add_to_cart( $page->get_id(), 1 );
		$this->assertCount( 2, WC()->cart->get_cart() );
		WC()->cart->empty_cart();
	}
}
