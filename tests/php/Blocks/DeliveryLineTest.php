<?php
/**
 * Tests for the Delivery Line block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Blocks;

use DateTimeImmutable;
use DateTimeZone;
use Purplish\Blocks\DeliveryLine;
use Purplish\Delivery;
use WC_Product_Simple;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * The block registers from the build, words the date line from the
 * settings and the clock, carries the next days' states for cached pages,
 * links the returns policy, adds a pickup line with Local pickup, and
 * leaves the dated lines out for products it can't promise.
 */
class DeliveryLineTest extends WP_UnitTestCase {

	const NAME = 'purplish/delivery-line';

	/**
	 * Fixes the clock at Friday, November 20, 2026, 10:00 in New York.
	 */
	public function set_up() {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
		add_filter( 'purplish_delivery_now', array( $this, 'now' ) );
	}

	/**
	 * Puts options, filters and the global product back after each test.
	 */
	public function tear_down() {
		remove_all_filters( 'purplish_delivery_now' );
		update_option( 'timezone_string', '' );
		delete_option( Delivery::OPTION );
		delete_option( 'woocommerce_pickup_location_settings' );
		delete_option( 'pickup_location_pickup_locations' );
		delete_option( 'woocommerce_refund_returns_page_id' );
		unset( $GLOBALS['product'] );
		parent::tear_down();
	}

	/**
	 * The fixed clock.
	 *
	 * @return int
	 */
	public function now() {
		return ( new DateTimeImmutable( '2026-11-20 10:00', new DateTimeZone( 'America/New_York' ) ) )->getTimestamp();
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
	 * The block's words, without tags or extra spaces.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private function words( $html ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( '</div>', ' </div>', $html ) ) ) );
	}

	/**
	 * Registered from the build with the product context and its front script.
	 */
	public function test_registers_from_the_build() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::NAME );
		$this->assertNotNull( $block );
		$this->assertSame( array( 'postId', 'postType' ), $block->uses_context );
		$this->assertNotEmpty( $block->view_script_handles );
	}

	/**
	 * By default: the date line with the time left, then the returns line.
	 */
	public function test_date_line_with_the_time_left_and_the_returns_line() {
		$html = $this->render();
		$this->assertSame( 'Order within 4 hours, arrives by Fri, Nov 27 Free returns within 30 days', $this->words( $html ) );
		$this->assertStringContainsString( '<strong>4 hours</strong>', $html );
		$this->assertStringContainsString( 'purplish-delivery-line__icon', $html );
	}

	/**
	 * A range of dates, without the time left.
	 */
	public function test_date_range_and_no_countdown() {
		$html = $this->render(
			array(
				'range'     => 'range',
				'countdown' => false,
				'returns'   => false,
			)
		);
		$this->assertSame( 'Arrives Wed, Nov 25 to Fri, Nov 27', $this->words( $html ) );
	}

	/**
	 * The block carries the next days' states and sentences for cached pages.
	 */
	public function test_carries_states_for_cached_pages() {
		$html = $this->render();
		$this->assertMatchesRegularExpression( '/data-purplish-delivery="([^"]+)"/', $html );
		preg_match( '/data-purplish-delivery="([^"]+)"/', $html, $match );
		$data = json_decode( html_entity_decode( $match[1] ), true );

		$this->assertSame( 'Mon, Nov 30', $data['states'][1]['b'] );
		$this->assertSame( 'Arrives by %1$s', $data['texts']['by'] );
		$this->assertArrayNotHasKey( 'range', $data['texts'] );
		$this->assertSame( 'en-US', $data['locale'] );
	}

	/**
	 * The returns line follows the settings, links the policy, and goes with zero days.
	 */
	public function test_returns_line_follows_the_settings_and_links_the_policy() {
		update_option(
			Delivery::OPTION,
			array(
				'returns'      => 14,
				'free_returns' => 'no',
			)
		);
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_refund_returns_page_id', $page );

		$html = $this->render( array( 'date' => false ) );
		$this->assertSame( 'Returns within 14 days', $this->words( $html ) );
		$this->assertStringContainsString( 'href="' . esc_url( get_permalink( $page ) ) . '"', $html );
		$this->assertStringNotContainsString( 'data-purplish-delivery=', $html );

		$this->assertStringNotContainsString( '<a ', $this->render( array( 'link' => false ) ) );

		update_option( Delivery::OPTION, array( 'returns' => 0 ) );
		$this->assertSame( '', $this->render( array( 'date' => false ) ) );
	}

	/**
	 * With Local pickup on, a pickup line names the place and the first day.
	 */
	public function test_pickup_line_with_local_pickup_on() {
		update_option(
			'woocommerce_pickup_location_settings',
			array(
				'enabled' => 'yes',
				'cost'    => '0',
			)
		);
		update_option(
			'pickup_location_pickup_locations',
			array(
				array(
					'name'    => 'Downtown',
					'enabled' => true,
				),
			)
		);

		$html = $this->render( array( 'returns' => false ) );
		$this->assertStringContainsString( 'Free pickup at Downtown from Mon, Nov 23', $this->words( $html ) );
		$this->assertStringContainsString( 'Downtown from <strong>Mon, Nov 23</strong>', $html );

		$this->assertStringNotContainsString( 'pickup', $this->words( $this->render( array( 'pickup' => false ) ) ) );
	}

	/**
	 * A product on backorder keeps only the returns line.
	 */
	public function test_products_that_cannot_be_promised_keep_only_the_returns_line() {
		$product = new WC_Product_Simple();
		$product->set_stock_status( 'onbackorder' );
		$product->save();
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.

		$this->assertSame( 'Free returns within 30 days', $this->words( $this->render() ) );
	}

	/**
	 * A product's extra processing days move the date.
	 */
	public function test_a_product_adds_its_extra_days() {
		$product = new WC_Product_Simple();
		$product->save();
		update_post_meta( $product->get_id(), Delivery::META, 2 );
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global.

		$this->assertStringContainsString( 'arrives by Tue, Dec 1', $this->words( $this->render( array( 'returns' => false ) ) ) );
	}

	/**
	 * Icons can be turned off.
	 */
	public function test_icons_can_be_turned_off() {
		$html = $this->render( array( 'icons' => false ) );
		$this->assertStringNotContainsString( '<svg', $html );
		$this->assertStringContainsString( 'purplish-delivery-line--no-icons', $html );
	}

	/**
	 * Unknown or missing options fall back to their defaults.
	 */
	public function test_options_fall_back_to_their_defaults() {
		$this->assertSame(
			array(
				'date'      => true,
				'range'     => 'latest',
				'countdown' => true,
				'returns'   => true,
				'link'      => true,
				'pickup'    => true,
				'icons'     => true,
				'format'    => 'short',
			),
			DeliveryLine::options( array( 'range' => 'other' ) )
		);
	}

	/**
	 * Sentences and values are escaped, values in strong.
	 */
	public function test_fill_escapes_the_sentence_and_the_values() {
		$this->assertSame(
			'At &lt;b&gt; <strong>&lt;i&gt;</strong>',
			DeliveryLine::fill( 'At <b> %1$s', array( '<i>' ) )
		);
	}
}
