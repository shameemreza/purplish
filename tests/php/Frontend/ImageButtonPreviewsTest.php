<?php
/**
 * Tests for the editor previews of the image and button options.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Image and Button show their looks in the editor, at rest and on hover.
 */
class ImageButtonPreviewsTest extends WP_UnitTestCase {

	/**
	 * Image and Button are in the editor map, with their options.
	 */
	public function test_preview_map() {
		$map    = BlockEffects::preview_map();
		$blocks = BlockEffects::blocks();

		foreach ( array( 'core/image', 'core/button' ) as $name ) {
			$this->assertArrayHasKey( $name, $map, $name );
			$this->assertSame( $blocks[ $name ]['prefix'], $map[ $name ]['prefix'], $name );
			$this->assertSame( $blocks[ $name ]['options'], $map[ $name ]['options'], $name );
		}
	}
}
