<?php
/**
 * Tests for the login and register options on My Account.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Admin\Settings;
use Purplish\Frontend\AccountForms;
use Purplish\Frontend\BlockEffects;
use WP_UnitTestCase;

/**
 * The options come from the Account Group, the Shortcode block that holds
 * My Account, or the settings page, and turn into classes around the forms.
 */
class AccountFormsTest extends WP_UnitTestCase {

	/**
	 * Starts each test logged out with nothing placed.
	 */
	public function set_up() {
		parent::set_up();
		AccountForms::reset();
		wp_set_current_user( 0 );
	}

	/**
	 * Drops the assets, the setting and the placed flag.
	 */
	public function tear_down() {
		AccountForms::reset();
		delete_option( Settings::OPTION );
		wp_dequeue_style( BlockEffects::HANDLE );
		wp_dequeue_style( BlockEffects::HANDLE . '-login' );
		wp_dequeue_script( AccountForms::HANDLE );
		parent::tear_down();
	}

	/**
	 * A Shortcode block holding My Account.
	 *
	 * @param array $account Saved options.
	 * @return array Parsed block.
	 */
	private function shortcode_block( $account ) {
		return array(
			'blockName' => 'core/shortcode',
			'attrs'     => array( 'purplish' => array( 'account' => $account ) ),
		);
	}

	/**
	 * The hooks, and the login part among the effect styles.
	 */
	public function test_registers_hooks() {
		$forms = new AccountForms();
		$forms->register();

		$this->assertSame( 10, has_filter( 'register_block_type_args', array( $forms, 'add_attribute' ) ) );
		$this->assertSame( 10, has_filter( 'render_block_core/shortcode', array( AccountForms::class, 'render_shortcode_block' ) ) );
		$this->assertSame( 10, has_filter( 'do_shortcode_tag', array( AccountForms::class, 'wrap_classic' ) ) );
		$this->assertContains( 'login', BlockEffects::PARTS );
	}

	/**
	 * Known values turn into classes, unknown ones and a switch saved as
	 * anything but true don't.
	 */
	public function test_classes() {
		$this->assertSame( array(), AccountForms::classes( array() ) );
		$this->assertSame( array(), AccountForms::classes( 'soft' ) );
		$this->assertSame(
			array( 'purplish-account', 'purplish-account-fields-soft', 'purplish-account-focus', 'purplish-account-labels-wave' ),
			AccountForms::classes(
				array(
					'fields' => 'soft',
					'focus'  => true,
					'labels' => 'wave',
				)
			)
		);
		$this->assertSame(
			array(),
			AccountForms::classes(
				array(
					'fields' => 'glow',
					'focus'  => 'yes',
				)
			)
		);
	}

	/**
	 * Only the Shortcode block gets the attribute.
	 */
	public function test_add_attribute() {
		$forms = new AccountForms();
		$args  = $forms->add_attribute( array(), 'core/shortcode' );
		$this->assertSame( 'object', $args['attributes']['purplish']['type'] );
		$this->assertSame( array(), $forms->add_attribute( array(), 'core/paragraph' ) );
	}

	/**
	 * A Shortcode block with options wraps My Account and loads the assets
	 * for a logged-out visitor; another shortcode stays as it is.
	 */
	public function test_shortcode_block() {
		$block = $this->shortcode_block( array( 'fields' => 'underline' ) );

		$html = AccountForms::render_shortcode_block( '[woocommerce_my_account]', $block );
		$this->assertSame( '<div class="purplish-account purplish-account-fields-underline purplish-account-forms">[woocommerce_my_account]</div>', $html );
		$this->assertTrue( wp_style_is( BlockEffects::HANDLE . '-login', 'enqueued' ) );
		$this->assertTrue( wp_script_is( AccountForms::HANDLE, 'enqueued' ) );

		AccountForms::reset();
		$this->assertSame( '[woocommerce_cart]', AccountForms::render_shortcode_block( '[woocommerce_cart]', $block ) );
		$this->assertSame( '[woocommerce_my_account]', AccountForms::render_shortcode_block( '[woocommerce_my_account]', $this->shortcode_block( array() ) ) );
	}

	/**
	 * A signed-in customer never sees the forms, so nothing loads.
	 */
	public function test_no_assets_when_logged_in() {
		wp_set_current_user( self::factory()->user->create() );
		AccountForms::render_shortcode_block( '[woocommerce_my_account]', $this->shortcode_block( array( 'fields' => 'soft' ) ) );

		$this->assertFalse( wp_script_is( AccountForms::HANDLE, 'enqueued' ) );
		$this->assertFalse( wp_style_is( BlockEffects::HANDLE . '-login', 'enqueued' ) );
	}

	/**
	 * The Account Group puts its classes on its own element, and leaves it
	 * to a Shortcode block inside that has options of its own.
	 */
	public function test_group() {
		$block = array(
			'blockName' => 'core/group',
			'attrs'     => array(
				'className' => 'is-style-purplish-account',
				'purplish'  => array( 'account' => array( 'labels' => 'floating' ) ),
			),
		);
		$html  = '<div class="wp-block-group is-style-purplish-account">[woocommerce_my_account]</div>';

		$this->assertStringContainsString( 'class="wp-block-group is-style-purplish-account purplish-account purplish-account-labels-floating"', AccountForms::render_group( $html, $block ) );

		$inner = '<div class="wp-block-group"><div class="purplish-account purplish-account-forms">x</div></div>';
		$this->assertSame( $inner, AccountForms::render_group( $inner, $block ) );
	}

	/**
	 * The settings wrap My Account on a page no block set options for, and
	 * stay out once a block has.
	 */
	public function test_classic() {
		update_option(
			Settings::OPTION,
			array(
				'account_fields' => 'filled',
				'account_focus'  => 'yes',
				'account_labels' => 'bogus',
			)
		);

		$this->assertSame(
			array(
				'fields' => 'filled',
				'focus'  => true,
			),
			AccountForms::saved_in_settings()
		);
		$this->assertSame( 'forms', AccountForms::wrap_classic( 'forms', 'woocommerce_cart' ) );
		$this->assertSame(
			'<div class="purplish-account purplish-account-fields-filled purplish-account-focus purplish-account-forms">forms</div>',
			AccountForms::wrap_classic( 'forms', 'woocommerce_my_account' )
		);

		AccountForms::reset();
		AccountForms::render_shortcode_block( '[woocommerce_my_account]', $this->shortcode_block( array( 'fields' => 'soft' ) ) );
		$this->assertSame( 'forms', AccountForms::wrap_classic( 'forms', 'woocommerce_my_account' ) );
	}

	/**
	 * The settings page carries the options on block and classic themes.
	 */
	public function test_settings_fields() {
		foreach ( array( Settings::fields( false ), Settings::fields( true ) ) as $fields ) {
			$ids = wp_list_pluck( $fields, 'id' );
			$this->assertContains( Settings::OPTION . '[account_fields]', $ids );
			$this->assertContains( Settings::OPTION . '[account_focus]', $ids );
			$this->assertContains( Settings::OPTION . '[account_labels]', $ids );
		}
	}
}
