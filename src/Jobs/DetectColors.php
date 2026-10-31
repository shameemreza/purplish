<?php
/**
 * Reads variation photo colors in the background with Action Scheduler.
 *
 * @package Purplish
 */

namespace Purplish\Jobs;

use Purplish\Color\Detector;
use Purplish\Meta;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Queues one job per variation whose photo changed, and walks existing
 * variations in batches after activation. Saving a product never waits
 * on reading a photo.
 */
final class DetectColors {

	const GROUP        = 'purplish';
	const HOOK_ONE     = 'purplish_detect_variation_color';
	const HOOK_BATCH   = 'purplish_detect_batch';
	const BATCH_SIZE   = 50;
	const QUEUE_OPTION = 'purplish_queue_batch';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_new_product_variation', array( $this, 'maybe_queue' ), 10, 2 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'maybe_queue' ), 10, 2 );
		add_action( self::HOOK_ONE, array( $this, 'detect' ) );
		add_action( self::HOOK_BATCH, array( $this, 'batch' ) );
		add_action( 'init', array( $this, 'queue_first_batch' ) );
	}

	/**
	 * Runs on activation. Action Scheduler may not be ready yet, so this
	 * only leaves a note for the next init.
	 *
	 * @return void
	 */
	public static function activate() {
		update_option( self::QUEUE_OPTION, 1, false );
	}

	/**
	 * Queues the first batch after activation, once.
	 *
	 * @return void
	 */
	public function queue_first_batch() {
		if ( ! get_option( self::QUEUE_OPTION ) || ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		if ( as_enqueue_async_action( self::HOOK_BATCH, array( 'offset' => 0 ), self::GROUP, true ) ) {
			delete_option( self::QUEUE_OPTION );
		}
	}

	/**
	 * Queues a detection job when a variation's photo changed.
	 *
	 * @param int                       $variation_id Variation ID.
	 * @param WC_Product_Variation|null $variation    Variation, when WooCommerce passes it.
	 * @return void
	 */
	public function maybe_queue( $variation_id, $variation = null ) {
		if ( ! $variation instanceof WC_Product_Variation ) {
			$variation = wc_get_product( $variation_id );
		}
		if ( ! $variation instanceof WC_Product_Variation || ! self::needs_detection( $variation ) ) {
			return;
		}

		as_enqueue_async_action( self::HOOK_ONE, array( 'variation_id' => (int) $variation->get_id() ), self::GROUP, true );
	}

	/**
	 * Whether a variation's saved auto color is out of date: it's in
	 * Automatic mode and its photo, or the photo file, isn't the one the
	 * color came from.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @return bool
	 */
	public static function needs_detection( $variation ) {
		if ( Meta::MODE_AUTO !== Meta::mode( $variation ) ) {
			return false;
		}
		return self::source_of( $variation ) !== (string) $variation->get_meta( Meta::AUTO_SOURCE );
	}

	/**
	 * Where a variation's auto color comes from: the photo's attachment ID
	 * and its modified time, so a replaced file counts as a new photo.
	 *
	 * @param WC_Product_Variation $variation Variation.
	 * @return string Empty when the variation has no photo.
	 */
	public static function source_of( $variation ) {
		$image_id = (int) $variation->get_image_id( 'edit' );
		if ( ! $image_id ) {
			return '';
		}
		return $image_id . ':' . (string) get_post_modified_time( 'U', true, $image_id );
	}

	/**
	 * Reads one variation's photo color and saves it with the variation.
	 *
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public function detect( $variation_id ) {
		$variation = wc_get_product( absint( $variation_id ) );
		if ( ! $variation instanceof WC_Product_Variation || ! self::needs_detection( $variation ) ) {
			return;
		}

		$source = self::source_of( $variation );
		if ( '' === $source ) {
			// The photo was removed, so the old color no longer applies.
			$variation->delete_meta_data( Meta::AUTO_COLOR );
			$variation->delete_meta_data( Meta::AUTO_SOURCE );
			$variation->save();
			return;
		}

		$image_id = (int) $variation->get_image_id( 'edit' );
		if ( '' === Detector::file_for( $image_id ) ) {
			// Offloaded to a CDN or missing on disk. Purplish never fetches URLs.
			wc_get_logger()->info(
				sprintf( 'Variation %1$d: photo %2$d is not on disk, so its color was not read.', $variation->get_id(), $image_id ),
				array( 'source' => 'purplish' )
			);
		}

		$color = Detector::from_attachment( $image_id );
		if ( '' === $color ) {
			$variation->delete_meta_data( Meta::AUTO_COLOR );
		} else {
			$variation->update_meta_data( Meta::AUTO_COLOR, $color );
		}
		// Saved even without a color, so the same photo isn't read again.
		$variation->update_meta_data( Meta::AUTO_SOURCE, $source );
		$variation->save();
	}

	/**
	 * Queues jobs for one page of variations that have a photo, then the
	 * next page. Stops on an empty page.
	 *
	 * @param int $offset Variations already walked.
	 * @return void
	 */
	public function batch( $offset = 0 ) {
		$offset = absint( $offset );

		$ids = get_posts(
			array(
				'post_type'        => 'product_variation',
				'post_status'      => array( 'publish', 'private' ),
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'posts_per_page'   => self::BATCH_SIZE,
				'offset'           => $offset,
				'no_found_rows'    => true,
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One indexed key, 50 rows per run, in the background.
				'meta_query'       => array(
					array(
						'key'     => '_thumbnail_id',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( empty( $ids ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			$this->maybe_queue( $id );
		}

		as_enqueue_async_action( self::HOOK_BATCH, array( 'offset' => $offset + count( $ids ) ), self::GROUP, true );
	}
}
