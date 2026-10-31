<?php
/**
 * Tests for the Page color field.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Admin;

use Purplish\Admin\VariationField;
use Purplish\Meta;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * The field prints the saved values and saves through the variation object.
 */
class VariationFieldTest extends WP_UnitTestCase {

	/**
	 * Loads WooCommerce's admin field functions, which only load in wp-admin.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once WC()->plugin_path() . '/includes/admin/wc-meta-box-functions.php';
	}

	/**
	 * Clears the posted form after each test.
	 */
	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Saves the posted mode and color for the variation at a loop index.
	 *
	 * @param \WC_Product_Variation $variation Variation.
	 * @param string                $mode      Posted mode.
	 * @param string                $color     Posted color.
	 * @return \WC_Product_Variation The variation, read back from the database.
	 */
	private function post_and_save( $variation, $mode, $color ) {
		$_POST['purplish_mode']         = array( 3 => $mode );
		$_POST['purplish_custom_color'] = array( 3 => $color );

		( new VariationField() )->save( $variation, 3 );
		$variation->save();

		return wc_get_product( $variation->get_id() );
	}

	/**
	 * Custom saves the mode and the color.
	 */
	public function test_saves_custom() {
		$saved = $this->post_and_save( Products::sweater()['Orange/S'], 'custom', '#7B2CBF' );

		$this->assertSame( 'custom', $saved->get_meta( Meta::MODE ) );
		$this->assertSame( '#7B2CBF', $saved->get_meta( Meta::CUSTOM_COLOR ) );
	}

	/**
	 * Off saves the mode and keeps the custom color for later.
	 */
	public function test_saves_off_and_keeps_custom_color() {
		$variation = $this->post_and_save( Products::sweater()['Orange/S'], 'custom', '#7b2cbf' );
		$saved     = $this->post_and_save( $variation, 'off', '#000000' );

		$this->assertSame( 'off', $saved->get_meta( Meta::MODE ) );
		$this->assertSame( '#7b2cbf', $saved->get_meta( Meta::CUSTOM_COLOR ) );
	}

	/**
	 * Automatic removes the mode meta, since missing means automatic.
	 */
	public function test_auto_deletes_the_mode() {
		$variation = $this->post_and_save( Products::sweater()['Orange/S'], 'off', '#000000' );
		$saved     = $this->post_and_save( $variation, 'auto', '#000000' );

		$this->assertSame( '', $saved->get_meta( Meta::MODE ) );
		$this->assertSame( Meta::MODE_AUTO, Meta::mode( $saved ) );
	}

	/**
	 * An unknown mode counts as Automatic.
	 *
	 * @dataProvider provide_bad_modes
	 *
	 * @param string $mode Posted mode.
	 */
	public function test_rejects_unknown_modes( $mode ) {
		$saved = $this->post_and_save( Products::sweater()['Orange/S'], $mode, '#7b2cbf' );

		$this->assertSame( '', $saved->get_meta( Meta::MODE ) );
		$this->assertSame( '', $saved->get_meta( Meta::CUSTOM_COLOR ) );
	}

	/**
	 * Modes the field must refuse.
	 *
	 * @return array<string, string[]>
	 */
	public function provide_bad_modes() {
		return array(
			'unknown mode'       => array( 'rainbow' ),
			'script in the mode' => array( '<script>' ),
			'empty'              => array( '' ),
		);
	}

	/**
	 * A custom color that isn't a hex color is not saved.
	 *
	 * @dataProvider provide_bad_colors
	 *
	 * @param string $color Posted color.
	 */
	public function test_rejects_bad_colors( $color ) {
		$saved = $this->post_and_save( Products::sweater()['Orange/S'], 'custom', $color );

		$this->assertSame( '', $saved->get_meta( Meta::CUSTOM_COLOR ) );
	}

	/**
	 * Colors the field must refuse.
	 *
	 * @return array<string, string[]>
	 */
	public function provide_bad_colors() {
		return array(
			'a color name'  => array( 'red' ),
			'css injection' => array( '#fff;}body{display:none' ),
			'missing the #' => array( '7b2cbf' ),
		);
	}

	/**
	 * Clearing the picker in Custom mode removes the saved color.
	 */
	public function test_clearing_the_color_removes_it() {
		$variation = $this->post_and_save( Products::sweater()['Orange/S'], 'custom', '#7b2cbf' );
		$saved     = $this->post_and_save( $variation, 'custom', '' );

		$this->assertSame( 'custom', $saved->get_meta( Meta::MODE ) );
		$this->assertSame( '', $saved->get_meta( Meta::CUSTOM_COLOR ) );
	}

	/**
	 * A bad color in Custom mode keeps the color saved before.
	 */
	public function test_bad_color_keeps_the_saved_one() {
		$variation = $this->post_and_save( Products::sweater()['Orange/S'], 'custom', '#7b2cbf' );
		$saved     = $this->post_and_save( $variation, 'custom', 'red' );

		$this->assertSame( '#7b2cbf', $saved->get_meta( Meta::CUSTOM_COLOR ) );
	}

	/**
	 * The picker's Default button goes back to the photo color.
	 */
	public function test_picker_default_is_the_photo_color() {
		$variation = Products::sweater()['Blue/M'];
		$variation->update_meta_data( Meta::AUTO_COLOR, '#3357b7' );
		$variation->save();

		ob_start();
		( new VariationField() )->render( 0, array(), get_post( $variation->get_id() ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'data-default-color="#3357b7"', $html );
	}

	/**
	 * Automatic doesn't save the posted color.
	 */
	public function test_auto_ignores_the_color() {
		$saved = $this->post_and_save( Products::sweater()['Orange/S'], 'auto', '#7b2cbf' );

		$this->assertSame( '', $saved->get_meta( Meta::CUSTOM_COLOR ) );
	}

	/**
	 * Nothing posted for this variation leaves it alone.
	 */
	public function test_nothing_posted_changes_nothing() {
		$variation = $this->post_and_save( Products::sweater()['Orange/S'], 'off', '' );
		$_POST     = array();

		( new VariationField() )->save( $variation, 3 );
		$variation->save();

		$this->assertSame( 'off', wc_get_product( $variation->get_id() )->get_meta( Meta::MODE ) );
	}

	/**
	 * The field prints the saved mode, the custom color and the photo color.
	 */
	public function test_render_shows_saved_values() {
		$variation = Products::sweater()['Blue/M'];
		$variation->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$variation->update_meta_data( Meta::CUSTOM_COLOR, '#7b2cbf' );
		$variation->update_meta_data( Meta::AUTO_COLOR, '#3357b7' );
		$variation->save();

		ob_start();
		( new VariationField() )->render( 5, array(), get_post( $variation->get_id() ) );
		$html = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<option value="custom"\s+selected/', $html );
		$this->assertStringContainsString( 'name="purplish_mode[5]"', $html );
		$this->assertStringContainsString( 'name="purplish_custom_color[5]"', $html );
		$this->assertStringContainsString( 'value="#7b2cbf"', $html );
		$this->assertStringContainsString( '#3357b7', $html );
	}
}
