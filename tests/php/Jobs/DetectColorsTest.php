<?php
/**
 * Tests for the background detection jobs.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Jobs;

use Purplish\Jobs\DetectColors;
use Purplish\Meta;
use Purplish\Tests\Support\Products;
use WP_UnitTestCase;

/**
 * A photo change queues one job; the job saves the color; activation walks
 * existing variations in batches.
 */
class DetectColorsTest extends WP_UnitTestCase {

	/**
	 * Files the tests wrote, removed after each test.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Clears queued actions and written files.
	 */
	public function tear_down() {
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );
		as_unschedule_all_actions( DetectColors::HOOK_BATCH, null, DetectColors::GROUP );
		foreach ( $this->temp_files as $file ) {
			wp_delete_file( $file );
		}
		$this->temp_files = array();
		delete_option( DetectColors::QUEUE_OPTION );
		parent::tear_down();
	}

	/**
	 * Uploads a fixture photo as an attachment.
	 *
	 * @param string $name Fixture name without extension.
	 * @return int Attachment ID.
	 */
	private function upload( $name ) {
		$upload             = wp_upload_bits( "purplish-{$name}.webp", null, file_get_contents( dirname( __DIR__, 2 ) . "/fixtures/{$name}.webp" ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->temp_files[] = $upload['file'];

		$id                 = self::factory()->attachment->create_upload_object( $upload['file'] );
		$this->temp_files[] = get_attached_file( $id );

		return $id;
	}

	/**
	 * Whether a detection job is queued for a variation.
	 *
	 * @param int $variation_id Variation ID.
	 * @return bool
	 */
	private function is_queued( $variation_id ) {
		return as_has_scheduled_action( DetectColors::HOOK_ONE, array( 'variation_id' => $variation_id ), DetectColors::GROUP );
	}

	/**
	 * Setting a photo queues a job, and the job saves the color and source.
	 */
	public function test_photo_change_queues_and_job_saves_color() {
		$variation = Products::sweater()['Orange/S'];
		$this->assertFalse( $this->is_queued( $variation->get_id() ), 'no photo, no job' );

		$variation->set_image_id( $this->upload( 'orange-sweater' ) );
		$variation->save();
		$this->assertTrue( $this->is_queued( $variation->get_id() ) );

		( new DetectColors() )->detect( $variation->get_id() );
		$saved = wc_get_product( $variation->get_id() );

		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', $saved->get_meta( Meta::AUTO_COLOR ) );
		$this->assertSame( DetectColors::source_of( $saved ), $saved->get_meta( Meta::AUTO_SOURCE ) );
	}

	/**
	 * Saving again with the same photo queues nothing.
	 */
	public function test_same_photo_does_not_queue_again() {
		$variation = Products::sweater()['Orange/S'];
		$variation->set_image_id( $this->upload( 'orange-sweater' ) );
		$variation->save();
		( new DetectColors() )->detect( $variation->get_id() );
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );

		$saved = wc_get_product( $variation->get_id() );
		$saved->set_regular_price( '60' );
		$saved->save();

		$this->assertFalse( $this->is_queued( $variation->get_id() ) );
	}

	/**
	 * A new photo queues a new job and gives a new color.
	 */
	public function test_new_photo_queues_again() {
		$variation = Products::sweater()['Orange/S'];
		$variation->set_image_id( $this->upload( 'orange-sweater' ) );
		$variation->save();
		( new DetectColors() )->detect( $variation->get_id() );
		$orange = wc_get_product( $variation->get_id() )->get_meta( Meta::AUTO_COLOR );
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );

		$saved = wc_get_product( $variation->get_id() );
		$saved->set_image_id( $this->upload( 'blue-sweater' ) );
		$saved->save();
		$this->assertTrue( $this->is_queued( $variation->get_id() ) );

		( new DetectColors() )->detect( $variation->get_id() );
		$this->assertNotSame( $orange, wc_get_product( $variation->get_id() )->get_meta( Meta::AUTO_COLOR ) );
	}

	/**
	 * Custom and Off variations never queue a job.
	 *
	 * @dataProvider provide_manual_modes
	 *
	 * @param string $mode Mode.
	 */
	public function test_manual_modes_do_not_queue( $mode ) {
		$variation = Products::sweater()['Orange/S'];
		$variation->update_meta_data( Meta::MODE, $mode );
		$variation->set_image_id( $this->upload( 'orange-sweater' ) );
		$variation->save();

		$this->assertFalse( $this->is_queued( $variation->get_id() ) );
	}

	/**
	 * Modes that don't read the photo.
	 *
	 * @return array<string, string[]>
	 */
	public function provide_manual_modes() {
		return array(
			'custom' => array( Meta::MODE_CUSTOM ),
			'off'    => array( Meta::MODE_OFF ),
		);
	}

	/**
	 * Removing the photo removes the old color.
	 */
	public function test_removed_photo_clears_color() {
		$variation = Products::sweater()['Orange/S'];
		$variation->set_image_id( $this->upload( 'orange-sweater' ) );
		$variation->save();
		( new DetectColors() )->detect( $variation->get_id() );

		$saved = wc_get_product( $variation->get_id() );
		$saved->set_image_id( 0 );
		$saved->save();
		( new DetectColors() )->detect( $variation->get_id() );

		$cleared = wc_get_product( $variation->get_id() );
		$this->assertSame( '', $cleared->get_meta( Meta::AUTO_COLOR ) );
		$this->assertSame( '', $cleared->get_meta( Meta::AUTO_SOURCE ) );
	}

	/**
	 * Activation leaves a note, and the next init queues the first batch once.
	 */
	public function test_activation_queues_the_first_batch_on_init() {
		DetectColors::activate();
		( new DetectColors() )->queue_first_batch();

		$this->assertTrue( as_has_scheduled_action( DetectColors::HOOK_BATCH, array( 'offset' => 0 ), DetectColors::GROUP ) );
		$this->assertFalse( get_option( DetectColors::QUEUE_OPTION ) );
	}

	/**
	 * A batch queues a job for each variation with a new photo, then the
	 * next batch; an empty page ends it.
	 */
	public function test_batch_walks_variations_with_photos() {
		$photo      = $this->upload( 'orange-sweater' );
		$variations = Products::sweater( array( 'Orange', 'Blue' ), array( 'S', 'M', 'L' ) );
		foreach ( $variations as $variation ) {
			$variation->set_image_id( $photo );
			$variation->save();
		}
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );

		( new DetectColors() )->batch( 0 );

		foreach ( $variations as $variation ) {
			$this->assertTrue( $this->is_queued( $variation->get_id() ) );
		}
		$this->assertTrue( as_has_scheduled_action( DetectColors::HOOK_BATCH, array( 'offset' => 6 ), DetectColors::GROUP ) );

		as_unschedule_all_actions( DetectColors::HOOK_BATCH, null, DetectColors::GROUP );
		( new DetectColors() )->batch( 6 );
		$this->assertFalse( as_has_scheduled_action( DetectColors::HOOK_BATCH, null, DetectColors::GROUP ), 'empty page ends the walk' );
	}
}
