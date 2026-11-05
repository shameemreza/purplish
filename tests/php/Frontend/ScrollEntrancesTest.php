<?php
/**
 * Tests for the 1.1 scroll entrances: headings, groups, columns and covers.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Each option becomes a wrapper class on its block. The script and the
 * stylesheet do the rest, so the block's markup is otherwise untouched.
 */
class ScrollEntrancesTest extends WP_UnitTestCase {

	const COVER = '<div class="wp-block-cover alignfull is-light"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-9" alt="" src="x.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container is-layout-flow wp-block-cover-is-layout-flow"><h2 class="wp-block-heading">New in</h2></div></div>';

	/**
	 * Renders a block through the class filter.
	 *
	 * @param string $name  Block name.
	 * @param string $html  Rendered block.
	 * @param array  $saved Saved options.
	 * @return string
	 */
	private function render( $name, $html, $saved ) {
		return ( new BlockEffects() )->add_classes(
			$html,
			array(
				'blockName' => $name,
				'attrs'     => array( 'purplish' => $saved ),
			)
		);
	}

	/**
	 * The server accepts the attribute on the three new blocks.
	 */
	public function test_attribute_registered() {
		foreach ( array( 'core/heading', 'core/columns', 'core/cover' ) as $name ) {
			$args = ( new BlockEffects() )->add_attribute( array(), $name );

			$this->assertSame( 'object', $args['attributes']['purplish']['type'], $name );
		}
	}

	/**
	 * A heading rises or comes into focus, and nothing else.
	 */
	public function test_heading_reveal() {
		$this->assertSame( array( 'purplish-heading', 'purplish-heading-reveal-rise' ), BlockEffects::classes_for( 'core/heading', array( 'reveal' => 'rise' ) ) );
		$this->assertSame( array( 'purplish-heading', 'purplish-heading-reveal-focus' ), BlockEffects::classes_for( 'core/heading', array( 'reveal' => 'focus' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/heading', array( 'reveal' => 'spin' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/heading', array( 'reveal' => true ) ) );
	}

	/**
	 * The heading's classes go on the heading tag, and its text and links
	 * stay as printed.
	 */
	public function test_heading_markup() {
		$html = '<h2 class="wp-block-heading has-text-align-center" id="new">New <a href="/shop/">in store</a></h2>';
		$out  = $this->render( 'core/heading', $html, array( 'reveal' => 'focus' ) );

		$this->assertSame( str_replace( 'has-text-align-center"', 'has-text-align-center purplish-heading purplish-heading-reveal-focus"', $html ), $out );
	}

	/**
	 * The entrance sits next to 1.0's page color switch on the group, and
	 * the two combine.
	 */
	public function test_group_entrance() {
		$this->assertSame( array( 'purplish-group', 'purplish-group-entrance-fadeup' ), BlockEffects::classes_for( 'core/group', array( 'entrance' => 'fadeup' ) ) );
		$both = array(
			'tint'     => true,
			'entrance' => 'zoomin',
		);
		$this->assertSame( array( 'purplish-group', 'purplish-group-tint', 'purplish-group-entrance-zoomin' ), BlockEffects::classes_for( 'core/group', $both ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/group', array( 'entrance' => 'slide' ) ) );
	}

	/**
	 * Columns take the same entrances. The class goes on the Columns
	 * wrapper only; the stylesheet reaches each column from there.
	 */
	public function test_columns_entrance() {
		foreach ( BlockEffects::ENTRANCES as $entrance ) {
			$this->assertSame( array( 'purplish-columns', 'purplish-columns-entrance-' . $entrance ), BlockEffects::classes_for( 'core/columns', array( 'entrance' => $entrance ) ), $entrance );
		}

		$html = '<div class="wp-block-columns is-layout-flex"><div class="wp-block-column">A</div><div class="wp-block-column">B</div></div>';
		$out  = $this->render( 'core/columns', $html, array( 'entrance' => 'fadeup' ) );

		$this->assertSame( '<div class="wp-block-columns is-layout-flex purplish-columns purplish-columns-entrance-fadeup"><div class="wp-block-column">A</div><div class="wp-block-column">B</div></div>', $out );
	}

	/**
	 * A cover has one scroll effect at a time.
	 */
	public function test_cover_scroll() {
		foreach ( array( 'slowzoom', 'parallax', 'pill' ) as $scroll ) {
			$this->assertSame( array( 'purplish-cover', 'purplish-cover-scroll-' . $scroll ), BlockEffects::classes_for( 'core/cover', array( 'scroll' => $scroll ) ), $scroll );
		}
		$this->assertSame( array(), BlockEffects::classes_for( 'core/cover', array( 'scroll' => 'zoom' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/cover', array( 'hover' => 'tilt' ) ), 'no image hover on covers' );
	}

	/**
	 * The cover's classes go on its wrapper; the image, overlay and inner
	 * blocks stay as printed.
	 */
	public function test_cover_markup() {
		$out = $this->render( 'core/cover', self::COVER, array( 'scroll' => 'pill' ) );

		$this->assertSame( str_replace( 'alignfull is-light"', 'alignfull is-light purplish-cover purplish-cover-scroll-pill"', self::COVER ), $out );
	}

	/**
	 * Without a saved option the blocks print exactly what they did.
	 */
	public function test_no_option_no_change() {
		$this->assertSame( self::COVER, $this->render( 'core/cover', self::COVER, array() ) );
		$this->assertSame( self::COVER, $this->render( 'core/cover', self::COVER, array( 'scroll' => '' ) ) );
	}
}
