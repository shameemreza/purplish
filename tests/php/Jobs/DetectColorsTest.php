<?php
/**
 * Tests for the background detection jobs.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Jobs;

use Purplish\Color\Detector;
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
		update_option( DetectColors::CONTAINER_WALK_OPTION, Detector::VERSION );
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
		return as_has_scheduled_action( DetectColors::HOOK_ONE, array( 'product_id' => $variation_id ), DetectColors::GROUP );
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

		$this->assertTrue( as_has_scheduled_action( DetectColors::HOOK_BATCH, DetectColors::batch_args( 0, DetectColors::KIND_VARIATION ), DetectColors::GROUP ) );
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
		$this->assertTrue( as_has_scheduled_action( DetectColors::HOOK_BATCH, DetectColors::batch_args( 6, DetectColors::KIND_VARIATION ), DetectColors::GROUP ) );

		as_unschedule_all_actions( DetectColors::HOOK_BATCH, null, DetectColors::GROUP );
		( new DetectColors() )->batch( 6 );
		$this->assertTrue(
			as_has_scheduled_action( DetectColors::HOOK_BATCH, DetectColors::batch_args( 0, DetectColors::KIND_SIMPLE ), DetectColors::GROUP ),
			'an empty page of variations starts the simple products'
		);

		as_unschedule_all_actions( DetectColors::HOOK_BATCH, null, DetectColors::GROUP );
		( new DetectColors() )->batch( 0, DetectColors::KIND_SIMPLE );
		$this->assertFalse( as_has_scheduled_action( DetectColors::HOOK_BATCH, null, DetectColors::GROUP ), 'an empty page of simple products ends the walk' );
	}

	/**
	 * A simple product's main photo queues a job, and the job saves its color.
	 */
	public function test_simple_product_photo() {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Orange scarf' );
		$product->set_image_id( $this->upload( 'orange-sweater' ) );
		$product->save();

		$this->assertTrue( $this->is_queued( $product->get_id() ) );

		( new DetectColors() )->detect( $product->get_id() );
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', wc_get_product( $product->get_id() )->get_meta( Meta::AUTO_COLOR ) );
	}

	/**
	 * A simple product in the activation walk gets a job.
	 */
	public function test_batch_walks_simple_products() {
		$product = new \WC_Product_Simple();
		$product->set_image_id( $this->upload( 'blue-sweater' ) );
		$product->save();
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );

		( new DetectColors() )->batch( 0, DetectColors::KIND_SIMPLE );

		$this->assertTrue( $this->is_queued( $product->get_id() ) );
	}

	/**
	 * A grouped product's photo is read, like a simple product's.
	 */
	public function test_grouped_product_photo() {
		$product = new \WC_Product_Grouped();
		$product->set_image_id( $this->upload( 'orange-sweater' ) );
		$product->save();

		$this->assertTrue( $this->is_queued( $product->get_id() ) );
	}

	/**
	 * The simple walk covers grouped products too.
	 */
	public function test_batch_walks_grouped_products() {
		$product = new \WC_Product_Grouped();
		$product->set_image_id( $this->upload( 'blue-sweater' ) );
		$product->save();
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );

		( new DetectColors() )->batch( 0, DetectColors::KIND_SIMPLE );

		$this->assertTrue( $this->is_queued( $product->get_id() ) );
	}

	/**
	 * A site that ran the first walk before 1.4 walks everything once
	 * more, so existing bundles get their photo color and saved colors
	 * are read with the current detector.
	 */
	public function test_update_walks_products_once_more() {
		delete_option( DetectColors::CONTAINER_WALK_OPTION );

		( new DetectColors() )->queue_first_batch();
		$this->assertTrue( as_has_scheduled_action( DetectColors::HOOK_BATCH, DetectColors::batch_args( 0, DetectColors::KIND_VARIATION ), DetectColors::GROUP ) );
		$this->assertSame( (string) Detector::VERSION, (string) get_option( DetectColors::CONTAINER_WALK_OPTION ) );

		as_unschedule_all_actions( DetectColors::HOOK_BATCH, null, DetectColors::GROUP );
		( new DetectColors() )->queue_first_batch();
		$this->assertFalse( as_has_scheduled_action( DetectColors::HOOK_BATCH, null, DetectColors::GROUP ) );
	}

	/**
	 * A site whose last walk ran with an older detector walks again.
	 */
	public function test_older_detector_walks_again() {
		update_option( DetectColors::CONTAINER_WALK_OPTION, Detector::VERSION - 1 );

		( new DetectColors() )->queue_first_batch();

		$this->assertTrue( as_has_scheduled_action( DetectColors::HOOK_BATCH, DetectColors::batch_args( 0, DetectColors::KIND_VARIATION ), DetectColors::GROUP ) );
		$this->assertSame( (string) Detector::VERSION, (string) get_option( DetectColors::CONTAINER_WALK_OPTION ) );
	}

	/**
	 * A color an older detector read is out of date, so the walk queues it
	 * and the job reads the same photo again. Custom colors are left alone.
	 */
	public function test_color_from_older_detector_is_read_again() {
		$variations = Products::sweater( array( 'Orange', 'Blue' ), array( 'S' ) );
		$photo      = $this->upload( 'orange-sweater' );
		foreach ( $variations as $variation ) {
			$variation->set_image_id( $photo );
			// Saved by 1.3: photo ID and modified time, no detector version.
			$variation->update_meta_data( Meta::AUTO_COLOR, '#9d7b63' );
			$variation->update_meta_data( Meta::AUTO_SOURCE, $photo . ':' . get_post_modified_time( 'U', true, $photo ) );
			$variation->save();
		}
		$custom = $variations['Blue/S'];
		$custom->update_meta_data( Meta::MODE, Meta::MODE_CUSTOM );
		$custom->save();
		as_unschedule_all_actions( DetectColors::HOOK_ONE, null, DetectColors::GROUP );

		( new DetectColors() )->batch( 0 );

		$auto = $variations['Orange/S'];
		$this->assertTrue( $this->is_queued( $auto->get_id() ) );
		$this->assertFalse( $this->is_queued( $custom->get_id() ) );

		( new DetectColors() )->detect( $auto->get_id() );
		$saved = wc_get_product( $auto->get_id() );
		$this->assertNotSame( '#9d7b63', $saved->get_meta( Meta::AUTO_COLOR ) );
		$this->assertFalse( DetectColors::needs_detection( $saved ) );
	}

	/**
	 * A variable product's own photo is not read; its variations are.
	 */
	public function test_variable_parent_is_not_queued() {
		$variations = Products::sweater();
		$parent     = wc_get_product( reset( $variations )->get_parent_id() );
		$parent->set_image_id( $this->upload( 'orange-sweater' ) );
		$parent->save();

		$this->assertFalse( $this->is_queued( $parent->get_id() ) );
	}
}
