<?php
/**
 * Tests for the Matching Extras block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use Purplish\Admin\Settings;
use Purplish\Blocks\MatchingExtras;
use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\Extras;
use Purplish\Frontend\PurpleParts;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * The block prints the same list as the Matching extras switch for the
 * product on the page. A hooked copy leaves the list to Add to Cart +
 * Options when that block's own switch is on.
 */
class MatchingExtrasTest extends WP_UnitTestCase {

	const NAME = 'purplish/matching-extras';

	/**
	 * Puts the product, the switch note, the option and the assets back.
	 */
	public function tear_down() {
		unset( $GLOBALS['product'] );
		MatchingExtras::note_switch( array( 'blockName' => 'woocommerce/add-to-cart-with-options' ) );
		remove_filter( 'template', array( $this, 'purple' ) );
		delete_option( Settings::OPTION );
		wp_dequeue_script( Extras::HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE . '-extras' );
		parent::tear_down();
	}

	/**
	 * Purple as the active template.
	 *
	 * @return string
	 */
	public function purple() {
		return 'purple';
	}

	/**
	 * A product with one cross-sell, set as the current product.
	 *
	 * @param string $type Product class suffix.
	 * @return \WC_Product
	 */
	private function page( $type = 'Simple' ) {
		$scarf = new \WC_Product_Simple();
		$scarf->set_name( 'Scarf' );
		$scarf->set_regular_price( '20' );
		$scarf->save();

		$class = '\WC_Product_' . $type;
		$page  = new $class();
		$page->set_name( 'Trousers' );
		$page->set_regular_price( '50' );
		$page->set_cross_sell_ids( array( $scarf->get_id() ) );
		$page->save();

		$GLOBALS['product'] = $page; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.
		return $page;
	}

	/**
	 * The block's markup.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private function render( $attributes = array() ) {
		return render_block(
			array(
				'blockName'    => self::NAME,
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * Registered from the build with the product context, and the switch
	 * note is hooked.
	 */
	public function test_registers_from_the_build() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );
		$this->assertNotNull( $block );
		$this->assertSame( array( 'postId', 'postType' ), $block->uses_context );
		$this->assertArrayHasKey( PurpleParts::MARK, $block->attributes );
		$this->assertSame( 10, has_filter( 'render_block_data', array( MatchingExtras::class, 'note_switch' ) ) );
	}

	/**
	 * The list for the product, with the block's own options, inside the
	 * block's wrapper, with its styles and script.
	 */
	public function test_prints_the_list() {
		$this->page();

		$html = $this->render( array( 'options' => array( 'extrastitle' => 'Add stickers?' ) ) );
		$this->assertMatchesRegularExpression( '#^<div class="wp-block-purplish-matching-extras"><fieldset class="purplish-extras #', $html );
		$this->assertStringContainsString( '>Add stickers?</legend>', $html );
		$this->assertStringContainsString( '>Scarf</label>', $html );
		$this->assertTrue( wp_script_is( Extras::HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( BlockEffects::HANDLE . '-extras', 'enqueued' ) );
	}

	/**
	 * Nothing with no product, or for an external product.
	 */
	public function test_nothing_without_a_product_to_add() {
		$this->assertSame( '', $this->render() );

		$this->page( 'External' );
		$this->assertSame( '', $this->render() );
	}

	/**
	 * With the switch on Add to Cart + Options, a hooked copy prints
	 * nothing and a copy the store placed still prints.
	 */
	public function test_hooked_copy_leaves_the_list_to_the_switch() {
		add_filter( 'template', array( $this, 'purple' ) );
		update_option( Settings::OPTION, array( 'purple_extras' => 'yes' ) );
		$this->page();

		$hooked = array( PurpleParts::MARK => true );
		$this->assertStringContainsString( 'purplish-extras', $this->render( $hooked ) );

		MatchingExtras::note_switch(
			array(
				'blockName' => 'woocommerce/add-to-cart-with-options',
				'attrs'     => array( 'purplish' => array( 'extras' => true ) ),
			)
		);
		$this->assertSame( '', $this->render( $hooked ) );
		$this->assertStringContainsString( 'purplish-extras', $this->render() );
	}

	/**
	 * A hooked copy prints nothing once its switch is off.
	 */
	public function test_hooked_copy_with_the_switch_off() {
		$this->page();
		$this->assertSame( '', $this->render( array( PurpleParts::MARK => true ) ) );
	}

	/**
	 * When the block printed a list inside the form, the switch adds no
	 * second one.
	 */
	public function test_switch_skips_a_form_with_a_list() {
		$this->page();
		$form = '<form class="wp-block-woocommerce-add-to-cart-with-options">' . $this->render() . '</form>';

		$this->assertSame( $form, Extras::render( $form, array( 'extras' => true ) ) );
	}
}
