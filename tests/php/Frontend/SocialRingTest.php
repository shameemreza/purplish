<?php
/**
 * Tests for the Social Icons Ring look.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * Ring is a wrapper class on the Social Icons list, shown in the editor
 * too. The icons inside stay as WordPress prints them.
 */
class SocialRingTest extends WP_UnitTestCase {

	const LINKS = '<ul class="wp-block-social-links has-icon-color is-layout-flex wp-block-social-links-is-layout-flex"><li style="color:#ffffff;" class="wp-social-link wp-social-link-x wp-block-social-link"><a href="#" class="wp-block-social-link-anchor"><svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M1 1h2"></path></svg><span class="wp-block-social-link-label screen-reader-text">X</span></a></li></ul>';

	/**
	 * The server accepts the attribute on Social Icons.
	 */
	public function test_attribute_registered() {
		$args = ( new BlockEffects() )->add_attribute( array(), 'core/social-links' );

		$this->assertSame( 'object', $args['attributes']['purplish']['type'] );
	}

	/**
	 * Ring is the one look, and anything else gives no class.
	 */
	public function test_classes() {
		$this->assertSame( array( 'purplish-social', 'purplish-social-look-ring' ), BlockEffects::classes_for( 'core/social-links', array( 'look' => 'ring' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/social-links', array( 'look' => 'square' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'core/social-links', array() ) );
	}

	/**
	 * The classes go on the list tag and the icons stay as printed.
	 */
	public function test_markup() {
		$out = ( new BlockEffects() )->add_classes(
			self::LINKS,
			array(
				'blockName' => 'core/social-links',
				'attrs'     => array( 'purplish' => array( 'look' => 'ring' ) ),
			)
		);

		$this->assertSame( str_replace( 'wp-block-social-links-is-layout-flex"', 'wp-block-social-links-is-layout-flex purplish-social purplish-social-look-ring"', self::LINKS ), $out );
	}

	/**
	 * The look shows in the editor, so the preview map carries it.
	 */
	public function test_previews_in_the_editor() {
		$map = BlockEffects::preview_map();

		$this->assertArrayHasKey( 'core/social-links', $map );
		$this->assertSame( 'social', $map['core/social-links']['prefix'] );
		$this->assertSame( array( 'look' => array( 'ring' ) ), $map['core/social-links']['options'] );
	}
}
