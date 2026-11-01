<?php
/**
 * Tests for reading the main color of a photo.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Color;

use Purplish\Color\Contrast;
use Purplish\Color\Detector;
use WP_UnitTestCase;

/**
 * The detector finds the product's color, not the background's.
 */
class DetectorTest extends WP_UnitTestCase {

	/**
	 * Files the tests wrote, removed after each test.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Removes the files the test wrote.
	 */
	public function tear_down() {
		foreach ( $this->temp_files as $file ) {
			wp_delete_file( $file );
		}
		$this->temp_files = array();
		parent::tear_down();
	}

	/**
	 * RGB distance between two hex colors.
	 *
	 * @param string $a Color.
	 * @param string $b Color.
	 * @return float
	 */
	private function distance( $a, $b ) {
		$a = Contrast::to_rgb( $a );
		$b = Contrast::to_rgb( $b );
		return sqrt( ( $a[0] - $b[0] ) ** 2 + ( $a[1] - $b[1] ) ** 2 + ( $a[2] - $b[2] ) ** 2 );
	}

	/**
	 * Asserts a color is near the product color and away from the background.
	 *
	 * @param string $expected   Product color.
	 * @param string $background Background color.
	 * @param string $actual     Detected color.
	 */
	private function assert_product_color( $expected, $background, $actual ) {
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', $actual );
		$this->assertLessThan( 30, $this->distance( $expected, $actual ), "$actual is not close to $expected" );
		$this->assertLessThan(
			$this->distance( $background, $actual ),
			$this->distance( $expected, $actual ),
			"$actual is closer to the background $background than to $expected"
		);
	}

	/**
	 * The orange sweater, not the grey wall behind it.
	 */
	public function test_orange_sweater_photo() {
		$color = Detector::from_file( dirname( __DIR__, 2 ) . '/fixtures/orange-sweater.webp' );

		$this->assert_product_color( '#dc4a24', '#e8e6e3', $color );
	}

	/**
	 * The blue sweater, not the navy backdrop, even though the sweater
	 * runs off the bottom edge of the photo.
	 */
	public function test_blue_sweater_photo() {
		$color = Detector::from_file( dirname( __DIR__, 2 ) . '/fixtures/blue-sweater.webp' );

		$this->assert_product_color( '#0e3c91', '#0b2b55', $color );
	}

	/**
	 * A tonal shot: the green sweater on a green wall. Background removal
	 * can't separate them, so the color comes from the center of the photo,
	 * not from the face, the only other colorful area.
	 */
	public function test_green_sweater_on_green_wall() {
		$color = Detector::from_file( dirname( __DIR__, 2 ) . '/fixtures/green-sweater.webp' );

		$this->assert_product_color( '#1e4a3c', '#b07f62', $color );
	}

	/**
	 * A pastel product on a pale wall reads as the pastel, not the wall.
	 */
	public function test_pastel_pink_on_pale_wall() {
		$color = Detector::from_file( dirname( __DIR__, 2 ) . '/fixtures/pink-sweater.webp' );

		$this->assert_product_color( '#de9eb6', '#efece8', $color );
	}

	/**
	 * A grey product on white has no colorful pixels, so it falls back to
	 * the most common color that isn't the background.
	 */
	public function test_grey_product_on_white() {
		$path = $this->write_png(
			120,
			80,
			array( 255, 255, 255 ),
			array( 30, 15, 90, 70 ),
			array( 128, 128, 128 )
		);

		$this->assert_product_color( '#808080', '#ffffff', Detector::from_file( $path ) );
	}

	/**
	 * A small product on a big, colorful background still wins over the
	 * background.
	 */
	public function test_small_product_on_colored_background() {
		$path = $this->write_png(
			200,
			200,
			array( 46, 139, 87 ),
			array( 80, 80, 120, 120 ),
			array( 200, 30, 60 )
		);

		$this->assert_product_color( '#c81e3c', '#2e8b57', Detector::from_file( $path ) );
	}

	/**
	 * Transparent pixels around a cut-out product are ignored.
	 */
	public function test_transparent_background() {
		$path = $this->write_png( 100, 100, null, array( 20, 20, 80, 80 ), array( 255, 165, 0 ) );

		$this->assert_product_color( '#ffa500', '#ffffff', Detector::from_file( $path ) );
	}

	/**
	 * A file that isn't an image gives no color.
	 */
	public function test_unreadable_file() {
		$path               = wp_tempnam( 'purplish-not-an-image.png' );
		$this->temp_files[] = $path;
		file_put_contents( $path, 'not an image' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->assertSame( '', Detector::from_file( $path ) );
	}

	/**
	 * A missing file gives no color.
	 */
	public function test_missing_file() {
		$this->assertSame( '', Detector::from_file( '/nowhere/purplish.png' ) );
	}

	/**
	 * An empty picture gives no color.
	 */
	public function test_no_pixels() {
		$this->assertSame( '', Detector::from_pixels( array(), 0, 0 ) );
	}

	/**
	 * Reading an attachment uses the medium size when it exists.
	 */
	public function test_attachment_uses_medium_size() {
		$upload = wp_upload_bits( 'purplish-orange.webp', null, file_get_contents( dirname( __DIR__, 2 ) . '/fixtures/orange-sweater.webp' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertEmpty( $upload['error'] );
		$this->temp_files[] = $upload['file'];

		$attachment_id      = self::factory()->attachment->create_upload_object( $upload['file'] );
		$file               = Detector::file_for( $attachment_id );
		$this->temp_files[] = get_attached_file( $attachment_id );

		$this->assertFileExists( $file );
		$this->assert_product_color( '#dc4a24', '#e8e6e3', Detector::from_attachment( $attachment_id ) );
	}

	/**
	 * Writes a PNG with a filled rectangle on a background.
	 *
	 * @param int        $width      Width.
	 * @param int        $height     Height.
	 * @param int[]|null $background Background RGB, or null for transparent.
	 * @param int[]      $rect       Rectangle as x1, y1, x2, y2.
	 * @param int[]      $fill       Rectangle RGB.
	 * @return string Path.
	 */
	private function write_png( $width, $height, $background, $rect, $fill ) {
		$image = imagecreatetruecolor( $width, $height );
		imagealphablending( $image, false );
		imagesavealpha( $image, true );

		$back = null === $background
			? imagecolorallocatealpha( $image, 0, 0, 0, 127 )
			: imagecolorallocate( $image, $background[0], $background[1], $background[2] );
		imagefilledrectangle( $image, 0, 0, $width - 1, $height - 1, $back );
		imagefilledrectangle( $image, $rect[0], $rect[1], $rect[2], $rect[3], imagecolorallocate( $image, $fill[0], $fill[1], $fill[2] ) );

		$path               = wp_tempnam( 'purplish-test.png' );
		$this->temp_files[] = $path;
		imagepng( $image, $path );

		return $path;
	}
}
