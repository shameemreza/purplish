<?php
/**
 * Tests for the Instant results option on the Search block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\InstantSearch;
use WP_UnitTestCase;

/**
 * With the option on, the form gets its settings and a hidden results
 * list, and the assets load; with it off nothing changes.
 */
class InstantSearchTest extends WP_UnitTestCase {

	/**
	 * Clears the queues between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( InstantSearch::HANDLE );
		wp_dequeue_style( InstantSearch::HANDLE );
		parent::tear_down();
	}

	/**
	 * Renders a product Search block with these options.
	 *
	 * @param array<string, mixed>|null $purplish The block's `purplish` attribute, or null for none.
	 * @return string
	 */
	private function render( $purplish ) {
		$attrs = array(
			'showLabel' => false,
			'query'     => array( 'post_type' => 'product' ),
		);
		if ( null !== $purplish ) {
			$attrs['purplish'] = $purplish;
		}
		return do_blocks( '<!-- wp:search ' . wp_json_encode( $attrs ) . ' /-->' );
	}

	/**
	 * Off by default: the block and the page stay as they were.
	 */
	public function test_off_changes_nothing() {
		$html = $this->render( null );

		$this->assertStringNotContainsString( 'data-purplish-search', $html );
		$this->assertStringNotContainsString( 'purplish-search', $html );
		$this->assertFalse( wp_script_is( InstantSearch::HANDLE, 'enqueued' ) );
		$this->assertFalse( wp_style_is( InstantSearch::HANDLE, 'enqueued' ) );
	}

	/**
	 * On: the form keeps submitting to the products results page, gains
	 * its settings and a hidden listbox, and the assets load.
	 */
	public function test_on_adds_settings_list_and_assets() {
		$html = $this->render(
			array(
				'instant' => true,
				'count'   => 4,
				'cats'    => false,
			)
		);

		$tags = new \WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( 'FORM' ) );
		$this->assertSame( esc_url( home_url( '/' ) ), $tags->get_attribute( 'action' ) );
		$settings = json_decode( (string) $tags->get_attribute( 'data-purplish-search' ), true );
		$this->assertSame( rest_url( 'purplish/v1/search' ), $settings['url'] );
		$this->assertSame( 4, $settings['n'] );
		$this->assertSame( 0, $settings['c'] );
		$this->assertSame( 1, $settings['p'] );
		$this->assertCount( 5, $settings['counts'] );
		$this->assertSame( '1 product found', $settings['counts'][1] );

		$this->assertMatchesRegularExpression( '#<input type="hidden" name="post_type" value="product"\s*/?>#', $html );
		$this->assertMatchesRegularExpression( '#<div class="purplish-search" hidden><div class="purplish-search__list" role="listbox" id="purplish-search-\d+"#', $html );
		$this->assertStringContainsString( '<span class="purplish-search__live" role="status"></span></form>', $html );
		$this->assertTrue( wp_script_is( InstantSearch::HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( InstantSearch::HANDLE, 'enqueued' ) );
	}

	/**
	 * Two blocks on one page get different list IDs.
	 */
	public function test_ids_are_unique() {
		preg_match( '/id="(purplish-search-\d+)"/', $this->render( array( 'instant' => true ) ), $one );
		preg_match( '/id="(purplish-search-\d+)"/', $this->render( array( 'instant' => true ) ), $two );
		$this->assertNotSame( $one[1], $two[1] );
	}

	/**
	 * Saved values are cleaned: count kept to 3 to 10, categories and price
	 * on unless saved off.
	 */
	public function test_options_defaults_and_clamp() {
		$this->assertSame(
			array(
				'instant' => false,
				'count'   => 6,
				'cats'    => true,
				'price'   => true,
			),
			InstantSearch::options( 'junk' )
		);
		$options = InstantSearch::options(
			array(
				'instant' => true,
				'count'   => 40,
				'price'   => false,
			)
		);
		$this->assertSame( 10, $options['count'] );
		$this->assertFalse( $options['price'] );
		$this->assertTrue( $options['cats'] );
	}

	/**
	 * The server keeps the block's `purplish` attribute.
	 */
	public function test_block_keeps_attribute() {
		$type = \WP_Block_Type_Registry::get_instance()->get_registered( 'core/search' );
		$this->assertArrayHasKey( 'purplish', $type->attributes );
	}
}
