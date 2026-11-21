<?php
/**
 * Tests for the Account style on the Group block.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\AccountStyle;
use Purplish\Frontend\BlockEffects;
use WP_Block_Styles_Registry;
use WP_UnitTestCase;

/**
 * The style is a Group block style, and a Group that carries it loads the
 * account stylesheet. Nothing else does.
 */
class AccountStyleTest extends WP_UnitTestCase {

	/**
	 * Drops the stylesheets and the style between tests.
	 */
	public function tear_down() {
		wp_dequeue_style( BlockEffects::HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE . '-account' );
		if ( WP_Block_Styles_Registry::get_instance()->is_registered( 'core/group', AccountStyle::NAME ) ) {
			unregister_block_style( 'core/group', AccountStyle::NAME );
		}
		parent::tear_down();
	}

	/**
	 * The hooks, and the style on the Group block.
	 */
	public function test_registers_the_style() {
		$style = new AccountStyle();
		$style->register();

		$this->assertSame( 10, has_action( 'init', array( $style, 'register_style' ) ) );
		$this->assertSame( 10, has_filter( 'render_block_core/group', array( AccountStyle::class, 'render' ) ) );
		$this->assertContains( 'account', BlockEffects::PARTS );

		$style->register_style();
		$registered = WP_Block_Styles_Registry::get_instance()->get_registered( 'core/group', AccountStyle::NAME );
		$this->assertSame( 'Account', $registered['label'] );
	}

	/**
	 * The class among others counts; a longer class or another block's
	 * attributes don't.
	 */
	public function test_uses() {
		$this->assertTrue( AccountStyle::uses( array( 'attrs' => array( 'className' => 'is-style-purplish-account' ) ) ) );
		$this->assertTrue( AccountStyle::uses( array( 'attrs' => array( 'className' => 'alignwide  is-style-purplish-account extra' ) ) ) );
		$this->assertFalse( AccountStyle::uses( array( 'attrs' => array( 'className' => 'is-style-purplish-account-wide' ) ) ) );
		$this->assertFalse( AccountStyle::uses( array( 'attrs' => array() ) ) );
		$this->assertFalse( AccountStyle::uses( array() ) );
	}

	/**
	 * A rendered Group with the style loads the stylesheet and keeps its
	 * markup; one without it, or one that rendered nothing, loads nothing.
	 */
	public function test_render_loads_the_stylesheet() {
		$plain = array( 'attrs' => array( 'className' => 'alignwide' ) );
		$this->assertSame( '<div>a</div>', AccountStyle::render( '<div>a</div>', $plain ) );
		$this->assertFalse( wp_style_is( BlockEffects::HANDLE . '-account', 'enqueued' ) );

		$styled = array( 'attrs' => array( 'className' => 'is-style-purplish-account' ) );
		$this->assertSame( '', AccountStyle::render( '', $styled ) );
		$this->assertFalse( wp_style_is( BlockEffects::HANDLE . '-account', 'enqueued' ) );

		$this->assertSame( '<div>a</div>', AccountStyle::render( '<div>a</div>', $styled ) );
		$this->assertTrue( wp_style_is( BlockEffects::HANDLE . '-account', 'enqueued' ) );
		$this->assertTrue( wp_style_is( BlockEffects::HANDLE, 'enqueued' ) );
	}

	/**
	 * The stylesheet is built, scoped to the style's class, and keeps
	 * WooCommerce's order number on phones.
	 */
	public function test_stylesheet() {
		$css = file_get_contents( PURPLISH_DIR . 'build/style-effects-account.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build file.
		$this->assertNotEmpty( $css );
		$this->assertFileExists( PURPLISH_DIR . 'build/style-effects-account-rtl.css' );
		$rules = preg_replace( array( '#/\*.*?\*/#s', '#@media[^{]*\{#' ), '', $css );
		$count = 0;
		foreach ( explode( '}', $rules ) as $rule ) {
			if ( false === strpos( $rule, '{' ) ) {
				continue;
			}
			foreach ( preg_split( '/,(?![^(]*\))/', strtok( $rule, '{' ) ) as $selector ) {
				$this->assertStringStartsWith( '.is-style-purplish-account', trim( $selector ), $selector );
				++$count;
			}
		}
		$this->assertGreaterThan( 20, $count );
		$this->assertStringContainsString( 'attr(data-title)', $css );
	}
}
